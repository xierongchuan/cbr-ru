# Виджет ЦБ-курсов (CBR Currency Widget)

## **Установка и запуск**

### Требования
- `podman` или `docker`
- `docker-compose` или `podman-compose`

### Шаги установки

1. **Создайте конфигурационный файл:**
   ```bash
   cp .env.example .env
   ```
   Отредактируйте `.env` файл, установив необходимые переменные окружения (БД, Redis и т.д.).

2. **Запустите контейнеры:**
   ```bash
   podman compose up -d --build
   # или
   docker compose up -d --build
   ```

3. **Контейнеры автоматически выполнят:**
   - Установку зависимостей Composer
   - Генерацию ключа Laravel
   - Миграции базы данных с сидами
   - Первоначальную синхронизацию курсов валют

### Доступ к приложению

- **Веб-интерфейс:** `http://localhost:8080`
- **Страница виджета:** `http://localhost:8080/widget`
- **Страница настроек:** `http://localhost:8080/settings`

## API

Базовый путь: `/api/v1`

### Курсы валют на дату

```
GET /api/v1/rates
```

**Параметры:**
- `date` (опционально) — дата в формате `YYYY-MM-DD` (по умолчанию — сегодня)
- `compare_date` (опционально) — дата для сравнения в формате `YYYY-MM-DD`
- `currencies` (опционально) — список кодов валют через запятую (например, `USD,EUR`)

**Примеры:**
```bash
# Курсы на сегодня
curl http://localhost:8080/api/v1/rates

# Курсы на конкретную дату
curl "http://localhost:8080/api/v1/rates?date=2026-04-22"

# Курсы с сравнением (сегодня vs вчера)
TODAY=$(date +%Y-%m-%d)
YESTERDAY=$(date -d "yesterday" +%Y-%m-%d)
curl "http://localhost:8080/api/v1/rates?date=${TODAY}&compare_date=${YESTERDAY}&currencies=USD,EUR"
```

**Ответ:**
```json
{
  "date": "2026-04-22",
  "compare_date": "2026-04-21",
  "rates": [
    {
      "char_code": "USD",
      "name": "Доллар США",
      "nominal": 1,
      "cbr_id": "R01235",
      "value": "74.5897",
      "vunit_rate": "74.5897",
      "compare": {
        "date": "2026-04-21",
        "value": "74.0000",
        "vunit_rate": "74.0000"
      }
    }
  ]
}
```

### Справочник валют

```
GET /api/v1/currencies
```

**Пример:**
```bash
curl http://localhost:8080/api/v1/currencies
```

**Ответ:**
```json
{
  "currencies": [
    {
      "id": 1,
      "cbr_id": "R01235",
      "char_code": "USD",
      "num_code": "840",
      "name": "Доллар США",
      "nominal": 1
    }
  ]
}
```

### Динамика курсов валюты

```
GET /api/v1/dynamics
```

**Параметры (обязательные):**
- `char_code` — буквенный код валюты (например, `USD`)
- `from` — дата начала периода (`YYYY-MM-DD`)
- `to` — дата окончания периода (`YYYY-MM-DD`)

**Пример:**
```bash
curl "http://localhost:8080/api/v1/dynamics?char_code=USD&from=2026-04-01&to=2026-04-22"
```

**Ответ:**
```json
{
  "char_code": "USD",
  "cbr_id": "R01235",
  "from": "2026-04-01",
  "to": "2026-04-22",
  "dynamics": [
    {"date": "2026-04-01", "value": "73.5000", "vunit_rate": "73.5000"},
    {"date": "2026-04-02", "value": "73.8000", "vunit_rate": "73.8000"}
  ]
}
```

### Настройки

```
GET /api/v1/settings   # Получить настройки
PUT /api/v1/settings   # Обновить настройки
```

## Консольные команды

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

## Планировщик задач

Приложение автоматически синхронизирует курсы валют:
- При каждом запуске/перезапуске контейнера
- Ежедневно в 08:00, 13:00, 18:00 и 22:00 по расписанию

## Разработка

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

## Логи

Логи синхронизации сохраняются в `storage/logs/cbr.log` в контейнере.