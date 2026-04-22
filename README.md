# Виджет ЦБ-курсов (CBR Currency Widget)

Современный веб-виджет для отображения официальных курсов валют Центрального Банка Российской Федерации с красивым интерфейсом, гибкими настройками и автоматическим обновлением данных.

### **Возможности**
- 🎨 **Современный дизайн**: Красивый интерфейс с градиентами и анимациями
- ⚡ **Быстрое обновление**: Автоматическое обновление курсов с настраиваемым интервалом
- 📊 **Визуальные индикаторы**: Цветовые индикаторы изменения курсов (рост/падение)
- 🔍 **Поиск валют**: Удобный поиск в настройках по названию или коду
- 🎛️ **Гибкие настройки**: Выбор валют для загрузки и отображения
- 📱 **Адаптивный дизайн**: Работает на всех устройствах
- 🔄 **Оптимизированная загрузка**: Job запрашивает только настроенные валюты

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

## **API**

Приложение предоставляет REST API для получения данных о курсах валют.

### Получение курсов для виджета
```
GET /api/v1/widget/rates
```

**Ответ:**
```json
{
  "rates": {
    "USD": {
      "currency": {
        "char_code": "USD",
        "name": "Доллар США"
      },
      "today": {
        "value": 74.5897,
        "vunit_rate": 74.5897
      },
      "yesterday": {
        "value": 74.5123,
        "vunit_rate": 74.5123
      }
    }
  }
}
```

### Получение настроек
```
GET /api/v1/settings
```

**Ответ:**
```json
{
  "cbr_fetch_currencies": ["USD", "EUR", "CNY"],
  "widget_currencies": ["USD", "EUR"],
  "widget_update_interval": 60
}
```

### Обновление настроек
```
POST /api/v1/settings
Content-Type: application/json

{
  "cbr_fetch_currencies": ["USD", "EUR"],
  "widget_currencies": ["USD", "EUR"],
  "widget_update_interval": 30
}
```

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
- Ежедневно в 09:00 и 15:00 по расписанию

## **Архитектура**

- **Backend:** Laravel 11, PHP 8.3+
- **База данных:** MySQL 8.0
- **Кэш:** Redis 8.6
- **Frontend:** HTML, CSS (TailwindCSS), Vanilla JavaScript
- **API:** RESTful API с версионированием (v1)
- **Оптимизация:** Job запрашивает только настроенные валюты после первоначальной синхронизации

### Основные компоненты
- **CurrencySyncService:** Сервис синхронизации курсов (оптимизирован для запроса только настроенных валют)
- **CbrClient:** HTTP-клиент для API ЦБ РФ (поддерживает запросы по конкретным валютам)
- **CbrXmlParser:** Парсер XML-ответов ЦБ
- **SettingsService:** Управление настройками приложения
- **FetchCbrRatesCommand:** Команда для синхронизации настроенных валют
- **FetchAllCbrRatesCommand:** Команда для первоначальной синхронизации всех валют

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

## **Структура проекта**

```
├── app/
│   ├── Console/Commands/FetchCbrRatesCommand.php
│   ├── Http/Controllers/Api/V1/
│   ├── Models/
│   ├── Services/
│   └── Exceptions/
├── database/migrations/
├── docker/
├── resources/views/
├── routes/
└── docker-compose.yml
```

