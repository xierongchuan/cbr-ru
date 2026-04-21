# ---------------- STAGE: build ----------------
FROM php:8.5-fpm AS build

# 1) Системные библиотеки для сборки
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    zlib1g-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip \
    && pecl install redis && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# 2) Установка Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Настройка Git для безопасной работы
RUN git config --global --add safe.directory /var/www/html

# 3) Копирование файлов composer и установка зависимостей
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --no-scripts

# 4) Копирование файлов проекта
COPY . .

# 5) Выставляем правильные права
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 6) Кеширование конфигураций
RUN rm -f bootstrap/cache/services.php bootstrap/cache/packages.php \
    && php artisan package:discover --ansi


# ---------------- STAGE: runner ----------------
FROM php:8.5-fpm AS runner

WORKDIR /var/www/html

# 1) Устанавливаем минимальные runtime-зависимости (без dev-пакетов)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip5 \
    libicu76 \
    libpng16-16t64 \
    libonig5 \
    libxml2 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 2) Копируем скомпилированные PHP-расширения из build
COPY --from=build /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=build /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# 3) Копируем артефакты и код из build
COPY --from=build /var/www/html /var/www/html
COPY --from=build /usr/bin/composer /usr/bin/composer

# 4) Настраиваем права в runner
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# 5) Копируем и настраиваем Entrypoint
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
