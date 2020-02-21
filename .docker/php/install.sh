#!/bin/bash

echo "****************** Iniciando composer install ********************"
composer install

echo "****************** Copia do .env ********************"
cp .env.example .env

echo "****************** CHMOD storage ********************"
chmod -R 755 storage

echo "****************** php artisan key:generate ********************"
php artisan key:generate

echo "****************** php artisan config:cache ********************"
php artisan config:cache

echo "****************** php artisan migrate ********************"
php artisan migrate



php-fpm

rm -rf /install.sh
