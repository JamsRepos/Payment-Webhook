FROM php:7.4-apache
WORKDIR /var/www/html
COPY . .
RUN apt-get update \
    && apt-get install -y cron \
    && pecl channel-update pecl.php.net \
    && pecl install mongodb \
	&& docker-php-ext-enable mongodb \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install
COPY crontab /etc/cron.d/crontab
RUN chmod 0644 /etc/cron.d/crontab && crontab /etc/cron.d/crontab
EXPOSE 80/tcp
EXPOSE 443/tcp