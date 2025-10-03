FROM php:8.2-apache

# Install Microsoft ODBC Driver and SQL Server prerequisites
RUN apt-get update && apt-get install -y --no-install-recommends gnupg2 curl \
    && curl -sSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft.gpg \
    && echo "deb [arch=amd64,arm64,armhf signed-by=/usr/share/keyrings/microsoft.gpg] https://packages.microsoft.com/debian/11/prod bullseye main" > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 \
    && apt-get install -y unixodbc-dev libpq-dev libzip-dev unzip openssl libmcrypt-dev libmemcached-dev locales \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql pdo_pgsql zip \
    && pecl install apcu redis memcached mcrypt xdebug pdo_sqlsrv \
    && docker-php-ext-enable redis memcached mcrypt pdo_sqlsrv xdebug apcu \
    && echo "apc.enable_cli = 1" >> /usr/local/etc/php/php.ini

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

RUN sed -i '/de_DE /s/^# //g' /etc/locale.gen \
    && sed -i '/es_ES /s/^# //g' /etc/locale.gen \
    && locale-gen

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/html/app/webroot

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
