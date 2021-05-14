FROM php:7.2-fpm

RUN apt-get update && apt-get -y install libpq-dev
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
  && docker-php-ext-install pdo pdo_pgsql pgsql
RUN docker-php-ext-install pdo_mysql pdo_pgsql mysqli
