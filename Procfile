web: vendor/bin/heroku-php-nginx -C docker/nginx-heroku.conf public/
release: php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache
