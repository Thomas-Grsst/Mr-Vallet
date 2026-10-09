FROM php:8.4-cli

RUN docker-php-ext-install opcache \
    && printf "opcache.enable=1\nopcache.enable_cli=1\nopcache.validate_timestamps=0\nopcache.memory_consumption=128\nopcache.max_accelerated_files=20000\n" > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /app

COPY . .

ENV PHP_CLI_SERVER_WORKERS=4

EXPOSE 8000

CMD ["sh", "-c", "cp .env.example .env && php artisan key:generate --force && touch database/database.sqlite && php artisan migrate:fresh --seed --force && php artisan config:cache && php artisan route:cache && php artisan serve --host=0.0.0.0 --port=8000"]
