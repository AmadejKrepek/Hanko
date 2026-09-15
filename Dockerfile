FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && a2enmod rewrite headers \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/hanko-entrypoint.sh
RUN chmod +x /usr/local/bin/hanko-entrypoint.sh

COPY --chown=www-data:www-data . /var/www/html
RUN rm -rf /var/www/html/docker \
    && mkdir -p /var/www/html/data/mail \
    && chown -R www-data:www-data /var/www/html/data

ENV PORT=8080
ENV HANKO_DATA_PATH=/var/www/html/data
ENV HANKO_SHOW_ADMIN_HINT=0

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/hanko-entrypoint.sh"]
