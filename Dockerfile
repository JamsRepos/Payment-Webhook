FROM php:7.4-apache
WORKDIR /var/www/html
COPY . .

RUN apt-get update \
    && apt-get install nano wget -y \
    && pecl channel-update pecl.php.net \
    && pecl install mongodb \
	&& docker-php-ext-enable mongodb \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install

RUN chmod +x ./refresh.sh
ENTRYPOINT ["/bin/bash", "./refresh.sh"]
CMD ""

EXPOSE 80/tcp
EXPOSE 443/tcp