FROM wordpress:8.0.21

# Copy base files
COPY ./files/ /var/www/html/wp-content/plugins
# Set permisions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 750 /var/www/html

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Execute
WORKDIR /var/www/html
