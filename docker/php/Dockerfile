FROM php:8.2-fpm-alpine

# 安装系统依赖和 PHP 扩展
RUN apk add --no-cache \
        bash \
        git \
        curl \
        unzip \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        libxml2-dev \
        libzip-dev \
        icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# 安装 Redis 扩展
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# 安装 Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 非 root 用户运行
RUN addgroup -g 1000 appgroup && adduser -u 1000 -G appgroup -s /bin/bash -D appuser \
    && chown -R appuser:appgroup /var/www/html

USER appuser

EXPOSE 9000
