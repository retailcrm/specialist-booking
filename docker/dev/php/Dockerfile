ARG PHP_VERSION
FROM hub.retailcrm.pro/box/php:${PHP_VERSION}

USER root
COPY zz_dev.ini /usr/local/etc/php/conf.d/

WORKDIR /var/www/booking
USER www-data
