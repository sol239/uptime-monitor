# Website monitor 

## Overview

This is a straightforward website and server monitoring application designed to help you track uptime and performance with ease. Backend of the application was built with Laravel. There are 2 monitoring services from which you can choose: one built with Python and another with PHP. The Python service is designed to be more efficient and scalable, while the PHP service is simpler and easier to set up.

## Installation 

### With Docker
```shell
git clone https://github.com/sol239/uptime-monitor
cd uptime-monitor
docker-compose up -d
```

### Without Docker

```shell
git clone https://github.com/sol239/uptime-monitor
cd uptime-monitor

# ===================================
# Laravel Backend + Front End
# ===================================

composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate

# Optionally: seeding the database with testing data --> for tests.
php artisan db:seed

# Run the Laravel development server
php artisan serve

# Run the front end
npm run dev

# ===================================
# PHP Monitor Service
# ===================================
php services/php-checker/MonitorChecker.php

# ===================================
# Python Monitor Service
# ===================================

# Create virtual environment
python -m venv services/python-checker/venv

# Activate venv
services/python-checker/venv/Scripts/activate  # Windows
source services/python-checker/venv/bin/activate  # MacOS/Linux

# Install required packages
pip install -r services/python-checker/requirements.txt

# Run the Python monitor service
python services/python-checker/Main.py

```

## Testing

### Laravel Testing

```shell
# Pest backend tests (eg. includes unit tests for backend)
php artisan test
```

### E2E Testing

```shell
# END TO END UI Testing
npx cypress run
npx cypress open   # with UI
```

---

## API

### GraphQL

```graphql
# Example 1
query {
  projects {
    identifier
    label
    description
    monitors {
      identifier
      label
      type
      host
      url
      badgeUrl
      periodicity
    }
  }
}

# Example 2
status(monitorIdentifier: "monitor-123") {
    date
    ok
    responseTime
  }
```

---

### REST API