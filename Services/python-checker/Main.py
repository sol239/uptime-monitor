#!/usr/bin/env python3
"""
Enhanced Monitor Checker - Python Implementation

Key advantages over PHP version:
- True async/await for concurrent processing
- Connection pooling for database and HTTP requests
- More efficient memory usage
- Better error handling and logging
- Type hints for better code reliability
- Faster network operations with aiohttp and asyncio
"""

import asyncio
import time
import json
import socket
import logging
from datetime import datetime, timedelta, UTC
from typing import List, Dict, Any, Optional, Tuple
from dataclasses import dataclass
from pathlib import Path
import aiohttp
import os
from contextlib import asynccontextmanager
import concurrent.futures
import psutil
import mysql.connector
from mysql.connector import pooling
from dotenv import load_dotenv
import os

load_dotenv()

class ColorFormatter(logging.Formatter):
    COLORS = {
        logging.DEBUG: "\033[94m",   # Blue
        logging.INFO: "\033[92m",    # Green
        logging.WARNING: "\033[93m", # Yellow
        logging.ERROR: "\033[91m",   # Red
        logging.CRITICAL: "\033[95m" # Magenta
    }
    RESET = "\033[0m"

    
    def format(self, record):
        color = self.COLORS.get(record.levelno, self.RESET)
        # Add time to log output (HH:MM:SS)
        log_time = self.formatTime(record, "%Y-%m-%d %H:%M:%S")        
        message = super().format(record)
        return f"{color}[{log_time}] {message}{self.RESET}"

# Setup logger
logger = logging.getLogger(__name__)
handler = logging.StreamHandler()
formatter = ColorFormatter("%(levelname)s - %(message)s")
handler.setFormatter(formatter)
logger.addHandler(handler)
logger.setLevel(logging.DEBUG)


@dataclass
class MonitorResult:
    """Data class for monitor results"""

    success: bool
    status: str
    response_time: int
    error: Optional[str] = None
    http_code: Optional[int] = None
    missing_keywords: Optional[List[str]] = None


@dataclass
class Monitor:
    """Data class for monitor configuration"""

    id: int
    label: str
    monitor_type: str
    periodicity: int
    hostname: Optional[str] = None
    port: Optional[int] = None
    url: Optional[str] = None
    check_status: Optional[bool] = None
    keywords: Optional[List[str]] = None


class MonitorChecker:

    def __init__(self, env_path: str = None):
        """Initialize the monitor checker with enhanced configuration"""
        self.env_path = env_path or os.path.join(os.path.dirname(__file__), ".env")

        # Monitor storage
        self.ping_monitors: List[Monitor] = []
        self.website_monitors: List[Monitor] = []
        self.monitor_next_checks: List[Dict] = []

        # Performance tracking
        self.stats = {
            "total_checks": 0,
            "successful_checks": 0,
            "failed_checks": 0,
            "avg_response_time": 0.0,
        }

        # Connection management
        self.http_session: Optional[aiohttp.ClientSession] = None
        self.db_pool: Optional[pooling.MySQLConnectionPool] = None

        # Thread pool for database operations
        self.executor = concurrent.futures.ThreadPoolExecutor(max_workers=10)

        # Initialize
        # self._load_env()
        self.last_refresh_time = time.time()

        self.PING_TIMEOUT = float(os.getenv("PING_TIMEOUT", 2))
        self.WEBSITE_TIMEOUT = float(os.getenv("WEBSITE_TIMEOUT", 5))
        self.CONNECTION_POOL_SIZE = int(os.getenv("CONNECTION_POOL_SIZE", 100))
        self.PING_CONCURRENCY = int(os.getenv("PING_CONCURRENCY", 200))
        self.WEBSITE_CONCURRENCY = int(os.getenv("WEBSITE_CONCURRENCY", 100))

        print("PING CONCURRENCY:", self.PING_CONCURRENCY)
        print("WEBSITE CONCURRENCY:", self.WEBSITE_CONCURRENCY)
        print("CONNECTION POOL SIZE:", self.CONNECTION_POOL_SIZE)
        print("PING TIMEOUT:", self.PING_TIMEOUT)
        print("WEBSITE TIMEOUT:", self.WEBSITE_TIMEOUT)

    def _load_env(self) -> Dict[str, str]:
        """Load environment variables from .env file"""
        if not os.path.exists(self.env_path):
            raise FileNotFoundError(f"Could not find .env file at: {self.env_path}")

        env = {}
        with open(self.env_path, "r") as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith("#"):
                    try:
                        key, value = line.split("=", 1)
                        env[key.strip()] = value.strip()
                    except ValueError:
                        continue

        self.env = env
        logger.info(f"Loaded environment variables from {self.env_path}")
        return env

    import os


    def _connect_to_database(self) -> pooling.MySQLConnectionPool:
        """Establish MySQL database connection pool using environment variables"""
        required_keys = [
            "DB_CONNECTION",
            "DB_HOST",
            "DB_PORT",
            "DB_DATABASE",
            "DB_USERNAME",
            "DB_PASSWORD",
        ]

        # Check for missing environment variables
        missing_keys = [key for key in required_keys if not os.getenv(key)]
        if missing_keys:
            raise ValueError(f"Missing DB configuration in environment: {missing_keys}")

        if os.getenv("DB_CONNECTION") != "mysql":
            raise ValueError(
                f"Unsupported DB connection type: {os.getenv('DB_CONNECTION')}"
            )

        try:
            config = {
                "user": os.getenv("DB_USERNAME"),
                "password": os.getenv("DB_PASSWORD"),
                "host": os.getenv("DB_HOST"),
                "port": int(os.getenv("DB_PORT")),
                "database": os.getenv("DB_DATABASE"),
                "charset": "utf8mb4",
                "autocommit": True,
                "pool_name": "monitor_pool",
                "pool_size": 10,
                "pool_reset_session": True,
            }

            self.db_pool = pooling.MySQLConnectionPool(**config)
            logger.info(
                f"MySQL connection pool created: {config['host']}:{config['port']}/{config['database']}"
            )
            return self.db_pool
        except Exception as e:
            logger.error(f"Failed to create MySQL connection pool: {e}")
            raise

    async def _get_http_session(self) -> aiohttp.ClientSession:
        """Get or create HTTP session with connection pooling"""
        if self.http_session is None or self.http_session.closed:
            timeout = aiohttp.ClientTimeout(total=self.WEBSITE_TIMEOUT)
            connector = aiohttp.TCPConnector(
                limit=self.CONNECTION_POOL_SIZE,
                limit_per_host=20,
                ttl_dns_cache=300,
                use_dns_cache=True,
            )
            self.http_session = aiohttp.ClientSession(
                timeout=timeout,
                connector=connector,
                headers={"User-Agent": "MonitorChecker/2.0 (Python)"},
            )
        return self.http_session

    async def _load_monitors(self, monitor_type: str) -> List[Monitor]:
        """Load monitors from database with proper type conversion"""

        def _load_monitors_sync():
            if not self.db_pool:
                self._connect_to_database()

            conn = self.db_pool.get_connection()
            try:
                cursor = conn.cursor(dictionary=True)
                cursor.execute(
                    "SELECT * FROM monitors WHERE monitor_type = %s", (monitor_type,)
                )
                rows = cursor.fetchall()
                return rows
            finally:
                cursor.close()
                conn.close()

        rows = await asyncio.get_event_loop().run_in_executor(
            self.executor, _load_monitors_sync
        )

        monitors = []
        for row in rows:
            # Parse keywords if present
            keywords = None
            if row.get("keywords"):
                try:
                    keywords = json.loads(row["keywords"])
                    if not isinstance(keywords, list):
                        keywords = None
                except json.JSONDecodeError:
                    keywords = None

            monitor = Monitor(
                id=row["id"],
                label=row["label"],
                monitor_type=row["monitor_type"],
                periodicity=row.get("periodicity", 300),
                hostname=row.get("hostname"),
                port=row.get("port"),
                url=row.get("url"),
                check_status=bool(row.get("check_status", False)),
                keywords=keywords,
            )
            monitors.append(monitor)

        return monitors

    async def _has_log(self, monitor: Monitor) -> bool:
        """Check if monitor has existing logs"""

        def _has_log_sync():
            if not self.db_pool:
                self._connect_to_database()

            conn = self.db_pool.get_connection()
            try:
                cursor = conn.cursor()
                cursor.execute(
                    "SELECT COUNT(*) FROM monitor_logs WHERE monitor_id = %s",
                    (monitor.id,),
                )
                result = cursor.fetchone()
                return result[0] > 0
            finally:
                cursor.close()
                conn.close()

        return await asyncio.get_event_loop().run_in_executor(
            self.executor, _has_log_sync
        )

    async def _run_single_ping_check(self, monitor: Monitor) -> MonitorResult:
        """Enhanced single ping check with async socket operations"""
        host = monitor.hostname
        port = monitor.port
        timeout = self.PING_TIMEOUT

        start_time = time.time()
        try:
            # Use asyncio for non-blocking socket operations
            future = asyncio.open_connection(host, port)
            reader, writer = await asyncio.wait_for(future, timeout=timeout)
            writer.close()
            await writer.wait_closed()

            response_time = round((time.time() - start_time) * 1000)
            logger.info(f"Ping monitor {monitor.id} - {monitor.hostname}:{monitor.port} - Connection success -> status:succeeded")
            return MonitorResult(
                success=True, status="succeeded", response_time=response_time
            )
        except asyncio.TimeoutError:
            response_time = round((time.time() - start_time) * 1000)
            logger.warning(f"Ping monitor {monitor.id} - {monitor.hostname}:{monitor.port} - Connection timeout -> status:failed")
            return MonitorResult(
                success=False,
                status="failed",
                response_time=response_time,
                error="Connection timeout",
            )
        except Exception as e:
            response_time = round((time.time() - start_time) * 1000)
            logger.warning(f"Ping monitor {monitor.id} - {monitor.hostname}:{monitor.port} - Connection error -> status:failed")
            return MonitorResult(
                success=False,
                status="failed",
                response_time=response_time,
                error=str(e),
            )

    async def _run_single_website_check(self, monitor: Monitor) -> MonitorResult:
        """Enhanced single website check with aiohttp"""
        session = await self._get_http_session()
        url = monitor.url
        check_status = monitor.check_status
        keywords = monitor.keywords or []

        start_time = time.time()
        try:
            async with session.get(url, ssl=False) as response:
                response_time = round((time.time() - start_time) * 1000)
                content = await response.text()
                http_code = response.status

                success = True
                status = "succeeded"
                error_message = None
                missing_keywords = []

                # Check status code
                if check_status and not (200 <= http_code < 300):
                    success = False
                    status = "failed"
                    error_message = (
                        f"HTTP status code {http_code} not in range [200, 300)"
                    )

                # Check keywords
                if success and keywords:
                    for keyword in keywords:
                        if keyword and keyword not in content:
                            missing_keywords.append(keyword)

                    if missing_keywords:
                        success = False
                        status = "failed"
                        error_message = (
                            f'Missing keywords: {", ".join(missing_keywords)}'
                        )
                logger.info(f"Website monitor {monitor.id} - {monitor.url} - Connection success -> status:succeeded")

                return MonitorResult(
                    success=success,
                    status=status,
                    response_time=response_time,
                    http_code=http_code,
                    error=error_message,
                    missing_keywords=missing_keywords,
                )

        except asyncio.TimeoutError:
            response_time = round((time.time() - start_time) * 1000)
            logger.warning(f"Website monitor {monitor.id} - {monitor.url} - Connection timeout -> status:failed")
            return MonitorResult(
                success=False,
                status="failed",
                response_time=response_time,
                error="Request timeout",
            )
        except Exception as e:
            logger.warning(f"Website monitor {monitor.id} - {monitor.url} - Connection error -> status:failed")
            response_time = round((time.time() - start_time) * 1000)
            return MonitorResult(
                success=False,
                status="failed",
                response_time=response_time,
                error=str(e),
            )

    async def run_batch_ping_checks(
        self, monitors: List[Monitor]
    ) -> List[Tuple[Monitor, MonitorResult]]:
        """Run ping checks concurrently with semaphore for connection limiting"""
        semaphore = asyncio.Semaphore(self.PING_CONCURRENCY)

        async def check_with_semaphore(monitor):
            async with semaphore:
                result = await self._run_single_ping_check(monitor)
                return (monitor, result)

        tasks = [check_with_semaphore(monitor) for monitor in monitors]
        results = await asyncio.gather(*tasks, return_exceptions=True)

        # Filter out exceptions and log them
        valid_results = []
        for result in results:
            if isinstance(result, Exception):
                logger.error(f"Ping check failed with exception: {result}")
            else:
                valid_results.append(result)
                # Save to database asynchronously
                monitor, check_result = result
                await self._save_result_async(monitor, check_result)

        return valid_results

    async def run_batch_website_checks(
        self, monitors: List[Monitor]
    ) -> List[Tuple[Monitor, MonitorResult]]:
        """Run website checks concurrently with proper connection pooling"""
        semaphore = asyncio.Semaphore(self.WEBSITE_CONCURRENCY)

        async def check_with_semaphore(monitor):
            async with semaphore:
                result = await self._run_single_website_check(monitor)
                return (monitor, result)

        tasks = [check_with_semaphore(monitor) for monitor in monitors]
        results = await asyncio.gather(*tasks, return_exceptions=True)

        # Filter out exceptions and log them
        valid_results = []
        for result in results:
            if isinstance(result, Exception):
                logger.error(f"Website check failed with exception: {result}")
            else:
                valid_results.append(result)
                # Save to database asynchronously
                monitor, check_result = result
                await self._save_result_async(monitor, check_result)

        return valid_results

    async def _save_result_async(self, monitor: Monitor, result: MonitorResult):
        """Save result to database asynchronously (using UTC)"""

        def _save_result_sync():
            if not self.db_pool:
                self._connect_to_database()

            conn = self.db_pool.get_connection()
            try:
                cursor = conn.cursor()
                utc_now = datetime.now(UTC).strftime("%Y-%m-%d %H:%M:%S")
                cursor.execute(
                    """
                    INSERT INTO monitor_logs 
                    (monitor_id, started_at, status, response_time_ms, created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, %s)
                """,
                    (
                        monitor.id,
                        utc_now,
                        result.status,
                        result.response_time,
                        utc_now,
                        utc_now,
                    ),
                )
            finally:
                cursor.close()
                conn.close()

        await asyncio.get_event_loop().run_in_executor(self.executor, _save_result_sync)

    async def _calculate_next_check_time(self, monitor: Monitor) -> datetime:
        """Calculate next check time based on last check and periodicity (UTC)"""

        def _calculate_next_check_time_sync():
            if not self.db_pool:
                self._connect_to_database()

            conn = self.db_pool.get_connection()
            try:
                cursor = conn.cursor()
                cursor.execute(
                    "SELECT started_at FROM monitor_logs WHERE monitor_id = %s ORDER BY started_at DESC LIMIT 1",
                    (monitor.id,),
                )
                result = cursor.fetchone()
                return result[0] if result else None
            finally:
                cursor.close()
                conn.close()

        last_check = await asyncio.get_event_loop().run_in_executor(
            self.executor, _calculate_next_check_time_sync
        )

        if last_check:
            if isinstance(last_check, datetime):
                return last_check + timedelta(seconds=monitor.periodicity)
            else:
                last_check_dt = datetime.strptime(str(last_check), "%Y-%m-%d %H:%M:%S")
                return last_check_dt + timedelta(seconds=monitor.periodicity)
        else:
            return datetime.now(UTC)

    async def initialize_monitors(self):
        """Initialize all monitors with enhanced batch processing"""
        if not self.db_pool:
            self._connect_to_database()

        start_time = time.time()
        logger.info("=== Monitor initialization started ===")

        # Load monitors from database
        self.ping_monitors = await self._load_monitors("ping")
        self.website_monitors = await self._load_monitors("website")

        logger.info(
            f"Loaded {len(self.ping_monitors)} ping monitors and {len(self.website_monitors)} website monitors"
        )

        # Find monitors that need initial checks
        ping_check_tasks = [self._has_log(m) for m in self.ping_monitors]
        website_check_tasks = [self._has_log(m) for m in self.website_monitors]

        ping_has_logs = (
            await asyncio.gather(*ping_check_tasks) if ping_check_tasks else []
        )
        website_has_logs = (
            await asyncio.gather(*website_check_tasks) if website_check_tasks else []
        )

        ping_monitors_needing_check = [
            m
            for i, m in enumerate(self.ping_monitors)
            if not (ping_has_logs and ping_has_logs[i])
        ]
        website_monitors_needing_check = [
            m
            for i, m in enumerate(self.website_monitors)
            if not (website_has_logs and website_has_logs[i])
        ]

        # Run initial checks concurrently
        initial_tasks = []
        if ping_monitors_needing_check:
            initial_tasks.append(
                self.run_batch_ping_checks(ping_monitors_needing_check)
            )
        if website_monitors_needing_check:
            initial_tasks.append(
                self.run_batch_website_checks(website_monitors_needing_check)
            )

        if initial_tasks:
            await asyncio.gather(*initial_tasks)

        # Schedule all monitors
        self.monitor_next_checks = []
        next_check_tasks = [
            self._calculate_next_check_time(monitor)
            for monitor in self.ping_monitors + self.website_monitors
        ]
        next_check_times = await asyncio.gather(*next_check_tasks)

        for i, monitor in enumerate(self.ping_monitors + self.website_monitors):
            self.monitor_next_checks.append(
                {"monitor": monitor, "next_check_time": next_check_times[i]}
            )

        elapsed = time.time() - start_time
        logger.info(f"=== Initialization completed in {elapsed:.2f}s ===")

    async def _check_monitor_updates(self):
        """Check monitor_updates table for must_update=1, run check, and reset must_update."""
        def _get_updates_sync():
            if not self.db_pool:
                self._connect_to_database()
            conn = self.db_pool.get_connection()
            try:
                cursor = conn.cursor(dictionary=True)
                cursor.execute("SELECT monitor_id FROM monitor_updates WHERE must_update = 1")
                rows = cursor.fetchall()
                return [row["monitor_id"] for row in rows]
            finally:
                cursor.close()
                conn.close()

        monitor_ids = await asyncio.get_event_loop().run_in_executor(self.executor, _get_updates_sync)
        if not monitor_ids:
            return



        # Fetch monitors by id
        def _get_monitor_sync(monitor_id):
            if not self.db_pool:
                self._connect_to_database()
            conn = self.db_pool.get_connection()
            try:
                cursor = conn.cursor(dictionary=True)
                cursor.execute("SELECT * FROM monitors WHERE id = %s", (monitor_id,))
                row = cursor.fetchone()
                return row
            finally:
                cursor.close()
                conn.close()

        monitors = []
        for monitor_id in monitor_ids:
            row = await asyncio.get_event_loop().run_in_executor(self.executor, _get_monitor_sync, monitor_id)
            if row:
                keywords = None
                if row.get("keywords"):
                    try:
                        keywords = json.loads(row["keywords"])
                        if not isinstance(keywords, list):
                            keywords = None
                    except Exception:
                        keywords = None
                monitor = Monitor(
                    id=row["id"],
                    label=row["label"],
                    monitor_type=row["monitor_type"],
                    periodicity=row.get("periodicity", 300),
                    hostname=row.get("hostname"),
                    port=row.get("port"),
                    url=row.get("url"),
                    check_status=bool(row.get("check_status", False)),
                    keywords=keywords,
                )
                monitors.append(monitor)

        # Run checks for each monitor and reset must_update
        for monitor in monitors:
            if monitor.monitor_type == "ping":
                logger.info("Running ping update check.")
                result = await self._run_single_ping_check(monitor)
            elif monitor.monitor_type == "website":
                logger.info("Running website update check.")

                result = await self._run_single_website_check(monitor)
            else:
                continue
            await self._save_result_async(monitor, result)

            # Reset must_update to 0
            def _reset_update_sync(monitor_id):
                if not self.db_pool:
                    self._connect_to_database()
                conn = self.db_pool.get_connection()
                try:
                    cursor = conn.cursor()
                    cursor.execute("UPDATE monitor_updates SET must_update = 0 WHERE monitor_id = %s", (monitor_id,))
                finally:
                    cursor.close()
                    conn.close()
            await asyncio.get_event_loop().run_in_executor(self.executor, _reset_update_sync, monitor.id)

    async def run_monitoring_loop(self):
        """Enhanced monitoring loop with better performance tracking"""
        logger.info("Starting monitoring loop...")


        while True:
            current_time = datetime.now(UTC)
            due_ping_monitors = []
            due_website_monitors = []

            # Collect due monitors
            for i, monitor_check in enumerate(self.monitor_next_checks):
                next_check_time = monitor_check["next_check_time"]
                # Ensure next_check_time is UTC-aware
                if isinstance(next_check_time, datetime):
                    if next_check_time.tzinfo is None:
                        next_check_time = next_check_time.replace(tzinfo=UTC)
                else:
                    # If not a datetime, skip
                    continue
                if current_time >= next_check_time:
                    monitor = monitor_check["monitor"]
                    if monitor.monitor_type == "ping":
                        due_ping_monitors.append((i, monitor))
                    elif monitor.monitor_type == "website":
                        due_website_monitors.append((i, monitor))

            # Process monitors in batches
            batch_tasks = []
            if due_ping_monitors:
                monitors = [item[1] for item in due_ping_monitors]
                batch_tasks.append(
                    ("ping", due_ping_monitors, self.run_batch_ping_checks(monitors))
                )

            if due_website_monitors:
                monitors = [item[1] for item in due_website_monitors]
                batch_tasks.append(
                    (
                        "website",
                        due_website_monitors,
                        self.run_batch_website_checks(monitors),
                    )
                )

            # Execute all batches concurrently
            if batch_tasks:
                for batch_type, due_monitors, task in batch_tasks:
                    try:
                        results = await task

                        # Update next check times
                        next_check_tasks = [
                            self._calculate_next_check_time(monitor)
                            for index, monitor in due_monitors
                        ]
                        next_check_times = await asyncio.gather(*next_check_tasks)

                        for i, (index, monitor) in enumerate(due_monitors):
                            self.monitor_next_checks[index]["next_check_time"] = (
                                next_check_times[i]
                            )

                        # Update statistics
                        self._update_stats(results)

                        # logger.debug(f"Processed {len(results)} {batch_type} monitors")

                    except Exception as e:
                        logger.error(f"Error processing {batch_type} monitors: {e}")

            # Periodically check monitor_updates table (every 5 seconds)
            await self._check_monitor_updates()

            # Memory management - log stats periodically
            if time.time() % 60 < 1:  # Every minute
                self._log_stats()

            # Short sleep to prevent CPU spinning
            await asyncio.sleep(1)

    def _update_stats(self, results: List[Tuple[Monitor, MonitorResult]]):
        """Update performance statistics"""
        for monitor, result in results:
            self.stats["total_checks"] += 1
            if result.success:
                self.stats["successful_checks"] += 1
            else:
                self.stats["failed_checks"] += 1

            # Update rolling average response time
            self.stats["avg_response_time"] = (
                self.stats["avg_response_time"] * (self.stats["total_checks"] - 1)
                + result.response_time
            ) / self.stats["total_checks"]

    def _log_stats(self):
        """Log performance statistics"""
        total = self.stats["total_checks"]
        if total > 0:
            success_rate = (self.stats["successful_checks"] / total) * 100
            logger.info(
                f"Stats - Total: {total}, Success: {success_rate:.1f}%, "
                f"Avg Response Time: {self.stats['avg_response_time']:.1f}ms, "
                f"Memory: {psutil.Process().memory_info().rss / 1024 / 1024:.1f}MB"
            )

    async def run_all_pending_checks(self):
        """Run all monitors immediately for testing purposes"""
        logger.info("=== Running all pending checks ===")

        tasks = []
        if self.ping_monitors:
            tasks.append(self.run_batch_ping_checks(self.ping_monitors))
        if self.website_monitors:
            tasks.append(self.run_batch_website_checks(self.website_monitors))

        if tasks:
            results = await asyncio.gather(*tasks)
            for result_batch in results:
                self._update_stats(result_batch)

        self._log_stats()
        logger.info("=== All pending checks completed ===")

    async def cleanup(self):
        """Cleanup resources"""
        if self.http_session and not self.http_session.closed:
            await self.http_session.close()

        if self.db_pool:
            # MySQL connection pool doesn't need explicit closing
            pass

        self.executor.shutdown(wait=True)
        logger.info("Cleanup completed")


async def main():
    """Main entry point"""
    checker = MonitorChecker()

    try:
        await checker.initialize_monitors()

        # Choose one of the following:

        # For continuous monitoring:
        await checker.run_monitoring_loop()

        # For one-time batch processing:
        # await checker.run_all_pending_checks()

    except KeyboardInterrupt:
        logger.info("Shutdown requested by user")
    except Exception as e:
        logger.error(f"Unexpected error: {e}")
    finally:
        await checker.cleanup()


if __name__ == "__main__":
    # Set up asyncio event loop with optimizations
    if hasattr(asyncio, "WindowsSelectorEventLoopPolicy"):
        asyncio.set_event_loop_policy(asyncio.WindowsSelectorEventLoopPolicy())

    # Run the main function
    asyncio.run(main())
