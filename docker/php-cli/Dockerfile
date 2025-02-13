FROM php:8.4-cli

COPY ./php.ini "${PHP_INI_DIR}/conf.d/docker-php-config.ini"

RUN apt-get update && apt-get install -y \
    gnupg \
    g++ \
    procps \
    openssl \
    curl \
    git \
    unzip \
    zlib1g-dev \
    libzip-dev \
    libfreetype6-dev \
    libpng-dev \
    libjpeg-dev \
    libicu-dev  \
    libonig-dev \
    libxslt1-dev \
    acl

RUN docker-php-ext-configure gd --with-jpeg --with-freetype

RUN docker-php-ext-install pdo pdo_mysql zip xsl gd intl opcache exif mbstring sockets
RUN docker-php-ext-install bcmath

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN pecl install excimer

COPY ./xdebug.ini "${PHP_INI_DIR}/conf.d"

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer