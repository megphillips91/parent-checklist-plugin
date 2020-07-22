FROM php:7.4.8-fpm

# Copy base files
COPY files/endpoints.php /var/www/html/
COPY files/lesson-plan.php /var/www/html/
COPY files/plugin.php /var/www/html/
COPY files/post-types.php /var/www/html/
COPY files/translate_blocks.php /var/www/html/

# allow port 9000 ( php-fpm )
EXPOSE 9000
