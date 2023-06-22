#!/usr/bin/env sh

export WORKERS="${WORKERS:=2}"
export WORKER_CONNECTIONS="${WORKER_CONNECTIONS:=1024}"

sed -e "s~WORKERS~$WORKERS~g" \
    -e "s~WORKER_CONNECTIONS~$(($WORKER_CONNECTIONS * 2))~g" \
    -i /etc/nginx/nginx.conf

nginx

touch .env
echo "APP_ENV=$APP_ENV" >> .env
echo "APP_DEBUG=$APP_DEBUG" >> .env
echo "APP_KEY=$APP_KEY" >> .env

if [ "$APP_KEY" = "" ]; then
  echo "Generating encryption keys..."
  php artisan key:generate
fi

php artisan passport:install

#php artisan down
#php artisan migrate
#php artisan up

php artisan cache:clear
php artisan config:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

php artisan serve --host=0.0.0.0