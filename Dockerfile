FROM php:8.1-cli
RUN docker-php-source mongodb \
	composer install \
	&& docker-php-source delete
COPY . .
CMD [ "php", "./webhook.php" ]