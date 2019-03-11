ARG version

# Install Composer because it's awesome
FROM composer:latest AS composer

FROM php:${version}-fpm-alpine

ARG app_name

# Adds an up to date repository for PHP packages for Alpine
ADD https://dl.bintray.com/php-alpine/key/php-alpine.rsa.pub /etc/apk/keys/php-alpine.rsa.pub
RUN echo "@ext https://dl.bintray.com/php-alpine/v3.9/php-7.3" >> /etc/apk/repositories

RUN apk update && \
  apk upgrade && \
  apk add \
  autoconf \
  ca-certificates \
  g++ \
  libmcrypt-dev \
  libssh2-dev \
  make \
  mlocate \
  vim && \
  mkdir -p /usr/local/log && \
  touch /usr/local/log/$app_name.log.slow && \
  docker-php-ext-install opcache

# Copy the 'composer' phar from the official image to the container
ENV COMPOSER_ALLOW_SUPERUSER 1
COPY --from=composer /usr/bin/composer /usr/bin/composer
