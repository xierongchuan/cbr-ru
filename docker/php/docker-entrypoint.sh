#!/bin/bash
set -e

# Убеждаемся, что системные директории существуют
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# ПРОВЕРКА ОКРУЖЕНИЯ dev или production
STORAGE_OWNER=$(stat -c '%u' /var/www/html/storage)

if [ "$APP_ENV" = "local" ] || [ "$STORAGE_OWNER" = "0" ]; then
    echo "🔧 Режим локальной разработки обнаружен. Выставляем права 777..."
    chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache
else
    echo "🚀 Продакшен режим обнаружен. Кеширование конфигурации Laravel..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
    php artisan event:cache || true
fi

# Ожидание установки зависимостей Composer (для локальной разработки)
if [ ! -f "vendor/autoload.php" ]; then
    echo "⏳ Ожидание завершения работы Composer (vendor/autoload.php не найден)..."
    for i in {1..30}; do
        if [ -f "vendor/autoload.php" ]; then
            echo "✅ Зависимости Composer найдены."
            break
        fi
        sleep 2
    done
fi

# Запуск миграций базы данных с ожиданием
echo "⏳ Запуск миграций базы данных..."
for i in {1..15}; do
    if php artisan migrate --force; then
        echo "✅ Миграции успешно выполнены."
        break
    fi
    echo "⚠️ Ожидание базы данных или других зависимостей... ($i/15)"
    sleep 2
done

# Синхронизация всех курсов валют при запуске контейнера (сегодня + вчера)
echo "🔄 Загрузка всех курсов валют (сегодня + вчера)..."
php artisan cbr:fetch-rates --date=both --all || echo "⚠️ Загрузка курсов не удалась."

# Запуск основного процесса php-fpm
exec "$@"
