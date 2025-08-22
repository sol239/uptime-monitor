FROM php:8.2-cli

RUN docker-php-ext-install pdo pdo_mysql sockets

RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app

CMD ["php", "/app/monitor-checker.php"]
