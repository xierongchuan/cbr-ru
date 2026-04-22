# Виджет ЦБ-курсов (CBR Currency Widget)

## **Установка и запуск**

### Требования
- `podman` или `docker`
- `docker-compose` или `podman-compose`

### Шаги установки

1. **Клонируйте репозиторий и перейдите в директорию:**
   ```bash
   git clone <repository-url>
   cd cbr-currency-widget
   ```

2. **Создайте конфигурационный файл:**
   ```bash
   cp .env.example .env
   ```
   Отредактируйте `.env` файл, установив необходимые переменные окружения (БД, Redis и т.д.).

3. **Запустите контейнеры:**
   ```bash
   podman compose up -d --build
   # или
   docker compose up -d --build
   ```

4. **Контейнеры автоматически выполнят:**
   - Установку зависимостей Composer
   - Генерацию ключа Laravel
   - Миграции базы данных с сидами
   - Первоначальную синхронизацию курсов валют

### Доступ к приложению

- **Веб-интерфейс:** `http://localhost:8080`
- **Страница виджета:** `http://localhost:8080/widget`
- **Страница настроек:** `http://localhost:8080/settings`

## **Консольные команды**

### Ручная синхронизация курсов
```bash
# Синхронизация настроенных валют
podman compose exec app php artisan cbr:fetch-rates

# Синхронизация всех валют (для заполнения списка)
podman compose exec app php artisan cbr:fetch-all-rates
```

### Просмотр списка команд
```bash
podman compose exec app php artisan list
```

## **Планировщик задач**

Приложение автоматически синхронизирует курсы валют:
- При каждом запуске/перезапуске контейнера
- Ежедневно в 08:00, 13:00, 18:00 и 22:00 по расписанию

## **Разработка**

### Запуск в режиме разработки
```bash
podman compose exec app composer install
podman compose exec app npm install
podman compose exec app npm run dev
```

### Запуск тестов
```bash
podman compose exec app composer test
```

### Линтинг кода
```bash
podman compose exec app ./vendor/bin/pint
```

## **Логи**

Логи синхронизации сохраняются в `storage/logs/cbr.log` в контейнере.
