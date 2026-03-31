FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends brotli \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install mysqli \
    && a2enmod rewrite filter deflate headers brotli

WORKDIR /var/www/html
