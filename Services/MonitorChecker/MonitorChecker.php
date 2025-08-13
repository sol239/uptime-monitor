<?php

namespace App\Services;

use PDO;
use PDOException;

class MonitorChecker
{
    /**
     * Refresh period in seconds (how often to reload monitors from database)
     */
    private const REFRESH_PERIOD = 300; // 5 minutes

    /**
     * A list of ping monitors to check.
     */
    private array $pingMonitors;

    /**
     * A list of website monitors to check.
     */
    private array $websiteMonitors;

    /**
     * The path to the SQLite database file.
     */
    public static string $DB_PATH = __DIR__ . '/../../database/database.sqlite';

    /**
     * The PDO instance for database connections.
     */
    private PDO $pdo;

    public array $monitorNextChecks = [];

    /**
     * Timestamp of last monitor refresh
     */
    private int $lastRefreshTime;

    public function __construct()
    {
        $this->connectToDatabase();
        $this->loadAllMonitors();
        $this->lastRefreshTime = time();
    }

    private function connectToDatabase()
    {
        $dbPath = self::$DB_PATH;
        print_r("Connecting to database at: {$dbPath}\n");

        try {
            $this->pdo = new PDO('sqlite:' . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "Connected successfully.\n";
        } catch (PDOException $e) {
            exit('Could not connect to database: ' . $e->getMessage() . "\n");
        }
    }

    private function loadMonitors(string $type)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM monitors WHERE monitor_type = :type');
        $stmt->execute(['type' => $type]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function hasLog($monitor): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM monitor_logs WHERE monitor_id = :monitor_id');
        $stmt->execute(['monitor_id' => $monitor['id']]);

        return $stmt->fetchColumn() > 0;
    }

    public function printInformation(): void
    {
        echo 'Ping Monitors [' . count($this->pingMonitors) . "]:\n";
        foreach ($this->pingMonitors as $monitor) {
            echo "Monitor ID: {$monitor['id']}, Label: {$monitor['label']}, Hostname: {$monitor['hostname']}, Port: {$monitor['port']}\n";
        }
        echo 'Website Monitors [' . count($this->websiteMonitors) . "]:\n";
        foreach ($this->websiteMonitors as $monitor) {
            echo "Monitor ID: {$monitor['id']}, Label: {$monitor['label']}, URL: {$monitor['url']}\n";
        }
    }

    private function runPingMonitorCheck($pingMonitor): void
    {
        $host = $pingMonitor['hostname'];
        $port = (int) $pingMonitor['port'];

        $start = microtime(true);
        $connection = @fsockopen($host, $port, $errno, $errstr, 5); // 5s timeout
        $responseTime = round((microtime(true) - $start) * 1000); // in ms

        if ($connection) {
            fclose($connection);
            $status = 'succeeded';
            // echo "Connection successful! Response time: {$responseTime} ms\n";
        } else {
            $status = 'failed';
            // echo "Connection failed: {$errstr} ({$errno})\n";
        }

        // INSERTING RESULT INTO DB
        $stmt = $this->pdo->prepare('
            INSERT INTO monitor_logs (monitor_id, started_at, status, response_time_ms, created_at, updated_at)
            VALUES (:monitor_id, :started_at, :status, :response_time_ms, :created_at, :updated_at)
        ');
        $stmt->execute([
            'monitor_id' => $pingMonitor['id'],
            'started_at' => date('Y-m-d H:i:s', $start),
            'status' => $status,
            'response_time_ms' => $responseTime,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        echo "Log saved for monitor ID {$pingMonitor['id']}.\n";
    }

    public function runWebsiteMonitorCheck($websiteMonitor): void
    {
        $url = $websiteMonitor['url'];
        $checkStatus = ! empty($websiteMonitor['check_status']) && $websiteMonitor['check_status'];
        $keywords = [];
        if (! empty($websiteMonitor['keywords'])) {
            // Keywords are stored as JSON array, decode them
            $keywords = json_decode($websiteMonitor['keywords'], true);
            if (! is_array($keywords)) {
                $keywords = [];
            }
        }

        $start = microtime(true);
        $status = 'succeeded';
        $responseTime = null;
        $httpCode = null;
        $missingKeywords = [];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // allow self-signed certs

        $response = curl_exec($ch);
        $responseTime = round((microtime(true) - $start) * 1000); // ms

        // page content
        if ($response === false) {
            $status = 'failed';
            $error = curl_error($ch);
            echo "Website connection failed: {$error}\n";
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            echo "Website connection successful! HTTP status: {$httpCode}, Response time: {$responseTime} ms\n";
            if ($checkStatus && ($httpCode < 200 || $httpCode >= 300)) {
                $status = 'failed';
                echo "Status code check failed.\n";
            }
            if ($status === 'succeeded' && $keywords) {
                foreach ($keywords as $keyword) {
                    $pos = strpos($response, $keyword);
                    // echo "Checking for keyword: {$keyword} - Position: {$pos}\n";
                    if (! empty($keyword) && $pos === false) {
                        $missingKeywords[] = $keyword;
                    }
                }
                if ($missingKeywords) {
                    $status = 'failed';
                    echo 'Missing keywords: ' . implode(', ', $missingKeywords) . "\n";
                }
            }
        }
        curl_close($ch);

        // INSERTING RESULT INTO DB
        $stmt = $this->pdo->prepare('
            INSERT INTO monitor_logs (monitor_id, started_at, status, response_time_ms, created_at, updated_at)
            VALUES (:monitor_id, :started_at, :status, :response_time_ms, :created_at, :updated_at)
        ');
        $stmt->execute([
            'monitor_id' => $websiteMonitor['id'],
            'started_at' => date('Y-m-d H:i:s', $start),
            'status' => $status,
            'response_time_ms' => $responseTime,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        echo "Log saved for website monitor ID {$websiteMonitor['id']}.\n";
    }

    public function initializeMonitorChecks(): void
    {
        $currentTime = date('Y-m-d H:i:s');
        echo "=== Initialization started at: {$currentTime} ===\n";

        foreach ($this->pingMonitors as $pingMonitor) {
            $_hasLog = $this->hasLog($pingMonitor);
            echo 'Monitor [' . $pingMonitor['label'] . '] has log: ' . ($_hasLog ? 'Yes' : 'No') . "\n";

            if (! $_hasLog) {
                $this->runPingMonitorCheck($pingMonitor);
            }

            $nextCheckTime = $this->calculateNextCheckTime($pingMonitor);
            $this->monitorNextChecks[] = [
                'monitor' => $pingMonitor,
                'nextCheckTime' => $nextCheckTime,
            ];

            echo "Monitor [{$pingMonitor['label']}] next check scheduled at: {$nextCheckTime}\n";
        }

        foreach ($this->websiteMonitors as $websiteMonitor) {
            $_hasLog = $this->hasLog($websiteMonitor);
            echo 'Website Monitor [' . $websiteMonitor['label'] . '] has log: ' . ($_hasLog ? 'Yes' : 'No') . "\n";

            if (! $_hasLog) {
                $this->runWebsiteMonitorCheck($websiteMonitor);
            }

            $nextCheckTime = $this->calculateNextCheckTime($websiteMonitor);
            $this->monitorNextChecks[] = [
                'monitor' => $websiteMonitor,
                'nextCheckTime' => $nextCheckTime,
            ];

            echo "Website Monitor [{$websiteMonitor['label']}] next check scheduled at: {$nextCheckTime}\n";
        }

        echo '=== Initialization completed at: ' . date('Y-m-d H:i:s') . " ===\n";
    }

    /**
     * Calculates next check time for a monitor.
     * Uses latest started_at from monitor_logs + monitor['periodicity'] (in seconds).
     */
    private function calculateNextCheckTime($monitor): string
    {
        $stmt = $this->pdo->prepare('SELECT started_at FROM monitor_logs WHERE monitor_id = :monitor_id ORDER BY started_at DESC LIMIT 1');
        $stmt->execute(['monitor_id' => $monitor['id']]);
        $latestStartedAt = $stmt->fetchColumn();
        $periodicity = isset($monitor['periodicity']) ? (int) $monitor['periodicity'] : 300; // default 5 minutes (300 seconds)

        if ($latestStartedAt) {
            $nextCheckTimestamp = strtotime($latestStartedAt) + $periodicity; // periodicity in seconds

            return date('Y-m-d H:i:s', $nextCheckTimestamp);
        } else {
            // If no log, schedule immediately
            return date('Y-m-d H:i:s');
        }
    }

    /**
     * Load all monitors from database
     */
    private function loadAllMonitors(): void
    {
        $this->pingMonitors = $this->loadMonitors('ping');
        $this->websiteMonitors = $this->loadMonitors('website');
    }

    /**
     * Refresh monitors from database and update next check times
     */
    private function refreshMonitors(): void
    {
        echo "=== Refreshing monitors from database at " . date('Y-m-d H:i:s') . " ===\n";
        
        $this->loadAllMonitors();
        
        // Clear existing monitor checks
        $this->monitorNextChecks = [];
        
        // Rebuild monitor checks for all monitors
        foreach ($this->pingMonitors as $pingMonitor) {
            $nextCheckTime = $this->calculateNextCheckTime($pingMonitor);
            $this->monitorNextChecks[] = [
                'monitor' => $pingMonitor,
                'nextCheckTime' => $nextCheckTime,
            ];
        }

        foreach ($this->websiteMonitors as $websiteMonitor) {
            $nextCheckTime = $this->calculateNextCheckTime($websiteMonitor);
            $this->monitorNextChecks[] = [
                'monitor' => $websiteMonitor,
                'nextCheckTime' => $nextCheckTime,
            ];
        }
        
        $this->lastRefreshTime = time();
        echo "Monitors refreshed. Total monitors: " . count($this->monitorNextChecks) . "\n";
    }

    public function runChecks(): void
    {
        while (true) {
            $currentTime = time();
            
            // Check if we need to refresh monitors
            if ($currentTime - $this->lastRefreshTime >= self::REFRESH_PERIOD) {
                $this->refreshMonitors();
            }
            
            foreach ($this->monitorNextChecks as $index => $monitorCheck) {
                $nextCheckTimestamp = strtotime($monitorCheck['nextCheckTime']);
                if ($currentTime >= $nextCheckTimestamp) {
                    $monitor = $monitorCheck['monitor'];
                    echo "Running check for monitor ID {$monitor['id']} at " . date('Y-m-d H:i:s') . "\n";

                    if ($monitor['monitor_type'] === 'ping') {
                        $this->runPingMonitorCheck($monitor);
                    } elseif ($monitor['monitor_type'] === 'website') {
                        $this->runWebsiteMonitorCheck($monitor);
                    }

                    // Update next check time for this specific monitor
                    $this->monitorNextChecks[$index]['nextCheckTime'] = $this->calculateNextCheckTime($monitor);
                    echo "Next check for monitor ID {$monitor['id']} scheduled at: " . $this->monitorNextChecks[$index]['nextCheckTime'] . "\n";
                }
            }
        }
    }
}

$checker = new MonitorChecker;
$checker->initializeMonitorChecks();
$checker->runChecks();
