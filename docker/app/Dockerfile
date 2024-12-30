# Based on https://github.com/TrafeX/docker-php-nginx
FROM alpine:3.21

# Setup document root
WORKDIR /var/www

# Install packages and remove default server definition
RUN apk add --no-cache \
  bash \
  curl \
  nginx \
  php84 \
  php84-bcmath \
  php84-ctype \
  php84-curl \
  php84-dom \
  php84-fileinfo \
  php84-fpm \
  php84-gd \
  php84-intl \
  php84-mbstring \
  php84-opcache \
  php84-openssl \
  php84-pdo \
  php84-pdo_sqlite \
  php84-phar \
  php84-session \
  php84-simplexml \
  php84-tokenizer \
  php84-xml \
  php84-xmlreader \
  php84-xmlwriter \
  php84-zip \
  supervisor

# Configure nginx - http
COPY docker/app/config/nginx.conf /etc/nginx/nginx.conf
# Configure nginx - default server
COPY docker/app/config/conf.d /etc/nginx/conf.d/

# Configure PHP-FPM
ENV PHP_INI_DIR="/etc/php84"
RUN ln -s /usr/bin/php84 /usr/bin/php
COPY docker/app/config/fpm-pool.conf ${PHP_INI_DIR}/php-fpm.d/www.conf
COPY docker/app/config/php.ini ${PHP_INI_DIR}/conf.d/custom.ini

# Configure supervisord
COPY docker/app/config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Make sure files/folders needed by the processes are accessable when they run under the nobody user
RUN chown -R nobody:nobody /var/www /run /var/lib/nginx /var/log/nginx

# Switch to use a non-root user from here on
USER nobody

# Add application
COPY --chown=nobody . /var/www/
RUN touch /var/www/.env
RUN rm -Rf docker

# Expose the port nginx is reachable on
EXPOSE 8080

# Let supervisord start services
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Configure a healthcheck to validate that everything is up&running
HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping || exit 1