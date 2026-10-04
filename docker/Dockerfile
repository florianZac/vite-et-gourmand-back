FROM php:8.4-cli

# Dépendances système + extensions PHP nécessaires (MySQL, intl pour Symfony, zip, mongodb pour Atlas)
RUN apt-get update && apt-get install -y \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
        default-mysql-client \
        libssl-dev \
    && docker-php-ext-install pdo pdo_mysql intl \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["entrypoint.sh"]
