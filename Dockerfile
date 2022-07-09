FROM php:7.4-apache
WORKDIR /var/www/html
COPY . .
RUN apt-get update \
    && pecl channel-update pecl.php.net \
    && pecl install mongodb \
	&& docker-php-ext-enable mongodb \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install
EXPOSE 80/tcp
EXPOSE 443/tcp