# Website monitor 

## Overview

This is a straightforward website and server monitoring application designed to help you track uptime and performance with ease. Backend of the application was built with Laravel. There are 2 monitoring services from which you can choose: one built with Python and another with PHP. The Python service is designed to be more efficient and scalable, while the PHP service is simpler and easier to set up.

## Monitor Checker Services

### PHP Monitor Service
- Built with PHP
- Simple and easy to set up
- Ideal for smaller projects or quick deployments
- It is much slower than the Python service

### Python Monitor Service
- Built with Python
- More efficient and scalable
- Suitable for larger projects or high-traffic websites

## Installation 

### With Docker Compose 🐳
```shell
git clone https://github.com/sol239/uptime-monitor
cd uptime-monitor

# Python based monitor checking service 
docker compose --profile python build

# Php based monitor checking service 
docker compose --profile php build
```

### Without Docker Compose

```shell
git clone https://github.com/sol239/uptime-monitor
cd uptime-monitor


composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate

# Optionally: seeding the database with testing data --> for tests.
php artisan db:seed

# Create virtual environment
python -m venv services/python-checker/venv

# Activate venv
services/python-checker/venv/Scripts/activate  # Windows
source services/python-checker/venv/bin/activate  # MacOS/Linux

# Install required packages
pip install -r services/python-checker/requirements.txt
```

## Usage
 
### With Docker Compose 🐳

```shell
# Python Monitor Service
docker-compose --profile python up -d

# Php Monitor Service
docker-compose --profile php up -d
```

### Without Docker Compose

```shell
# Run the Laravel development server
php artisan serve

# Run the front end
npm run dev

# PHP Monitor Service
php services/php-checker/Main.php

# Python Monitor service
python services/python-checker/Main.py
```

## Testing

### Laravel Testing

```shell
# Pest backend tests (eg. includes unit tests for backend)
php artisan test
```

### Vitest Testing

```shell
# Vitest unit tests (eg. includes unit tests for frontend)
npm test
```

### E2E Testing

```shell
# Cypress E2E tests (eg. includes API tests, UI tests)
npx cypress run
npx cypress open   # with UI
```

---

## API

> [!CAUTION]
> API endpoints are not protected. In production Laravel Sanctum should be used to protect these endpoints.

### GraphQL

You can use the GraphQL API to interact with the application's data using {APP_URL}/graphql.

```graphql
# Example 1:
query {
  projects {
    identifier
    label
    description
    monitors {
      identifier
      periodicity
      label
      type
      host
      url
      badgeUrl
    }
  }
}

# Example 2:
# monitorIdentifier is monitor's id
query {
  status(monitorIdentifier: "8") {
    date
    ok
    responseTime
  }
}
```

---

### REST API

The application exposes a RESTful API for interacting with its resources. 

It uses OpenAPI/Swagger to document its RESTful API endpoints. You can find the API documentation at `http://localhost:8000/api/documentation/#/api/docs` or `{APP_URL}/api/documentation/#/api/docs`. Also you can find the auto-generated API documentation in the `storage/api-docs` directory.


## Additional Information

You can contact me at [david.valek17@gmail.com](mailto:david.valek17@gmail.com).

You can find the assignment specification at [this link](https://webik.ms.mff.cuni.cz/nswi153/seminar-project/).
