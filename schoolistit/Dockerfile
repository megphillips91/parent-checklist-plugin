FROM php:7.4-apache

# Copy base files
COPY ./files/ /var/www/html/
# Set permisions
RUN chown -R www-data:www-data /var/www
RUN chmod -R 750 /var/www/

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Execute
WORKDIR /var/www
#CMD [ "php", "./plugin.php" ]
