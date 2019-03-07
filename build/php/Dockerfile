ARG version

FROM php:$version-fpm-alpine

ARG app_name

RUN apk update && \
  apk upgrade && \
  apk add \
  autoconf \
  g++ \
  libmcrypt-dev \
  libssh2-dev \
  make \
  mlocate \
  vim && \
  mkdir -p /usr/local/log && \
  touch /usr/local/log/$app_name.log.slow && \
  docker-php-ext-install \
  mcrypt \
  opcache && \
  pecl install ssh2 && \
  docker-php-ext-enable ssh2
