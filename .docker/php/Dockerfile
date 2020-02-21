FROM php:7.3.6-fpm-alpine3.9

RUN apk update && apk add bash nano mysql-client
RUN docker-php-ext-install pdo pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

#COPY install.sh /

WORKDIR /var/www

EXPOSE 9000
#ENTRYPOINT ["php-fpm"]
#ENTRYPOINT ["/./install.sh"]
