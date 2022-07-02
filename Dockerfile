FROM php:7.4-cli
RUN pecl install mongodb \
	&& docker-php-ext-enable mongodb \
    && composer install
COPY . .
CMD [ "php", "./webhook.php" ]