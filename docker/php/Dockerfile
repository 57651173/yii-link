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

# 安装 Redis 扩展（PECL）
# 注意：在 Alpine 官方 PHP 镜像里，`$PHPIZE_DEPS` 可能未定义；
# 同时 `pecl install redis` 在未固定版本时偶发解析失败，因此固定版本并显式安装编译依赖。
RUN apk add --no-cache \
        autoconf \
        dpkg-dev \
        dpkg \
        file \
        g++ \
        gcc \
        libc-dev \
        make \
        pkgconf \
        re2c \
    && pecl install redis-6.3.0 \
    && docker-php-ext-enable redis \
    && apk del --no-cache \
        autoconf \
        dpkg-dev \
        dpkg \
        file \
        g++ \
        gcc \
        libc-dev \
        make \
        pkgconf \
        re2c

# 安装 Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 非 root 用户运行
RUN addgroup -g 1000 appgroup && adduser -u 1000 -G appgroup -s /bin/bash -D appuser \
    && chown -R appuser:appgroup /var/www/html

USER appuser

EXPOSE 9000
