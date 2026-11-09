FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql && a2enmod headers \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY . .
COPY deploy/apache.conf /etc/apache2/conf-available/harvee.conf
COPY deploy/php.ini /usr/local/etc/php/conf.d/harvee.ini
RUN a2enconf harvee
EXPOSE 10000
CMD ["/bin/sh", "/var/www/html/deploy/start.sh"]
