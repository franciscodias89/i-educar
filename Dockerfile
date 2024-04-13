FROM httpd

MAINTAINER Portabilis

RUN apt-get update -y

RUN echo "Include /usr/local/apache2/conf/default.conf" >> /usr/local/apache2/conf/httpd.conf


FROM php:8.2-fpm-alpine

LABEL maintainer="Portabilis <contato@portabilis.com.br>"

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_PROCESS_TIMEOUT 900
ENV COMPOSER_DISABLE_XDEBUG_WARN=1

ENV XDEBUG_IDEKEY xdebug
ENV XDEBUG_CLIENT_HOST 127.0.0.1
ENV XDEBUG_CLIENT_PORT 9003
ENV XDEBUG_MODE dev,debug,coverage
ENV XDEBUG_START_WITH_REQUEST off

COPY --chown=www-data:www-data ./docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY --chown=www-data:www-data ./docker/nginx/default.conf /etc/nginx/sites-enabled/default.conf


RUN apk add --update \
    libc-dev \
    libpng-dev  \
    libzip-dev  \
    linux-headers \
    openjdk8  \
    postgresql14-client \
    postgresql-dev \
    ttf-dejavu \
    unzip \
    openssh \
    git

# https://github.com/docker-library/php/issues/436#issuecomment-303171390
RUN apk add --no-cache --virtual .phpize_deps $PHPIZE_DEPS

RUN pecl install \
    redis \
    xdebug

RUN docker-php-ext-enable \
    redis \
    xdebug

RUN docker-php-ext-install  \
    bcmath \
    gd \
    pcntl \
    pdo \
    pdo_pgsql \
    pgsql \
    zip

# https://github.com/docker-library/php/issues/436#issuecomment-303171390
RUN apk del .phpize_deps

RUN ln -s /var/www/ieducar/artisan /usr/local/bin/artisan

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
