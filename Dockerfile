FROM wordpress:6.5.5-php8.2-apache

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

RUN a2enmod ssl rewrite headers
COPY docker/apache/neo-ssl.conf /etc/apache2/sites-available/neo-ssl.conf
RUN a2ensite neo-ssl.conf
WORKDIR /var/www/html
CMD ["apache2-foreground"]