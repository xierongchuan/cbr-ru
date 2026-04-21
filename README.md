# CBR Parser

## **Установка**

- **Требования:** `podman` или `docker`.
- Создание конфигурационного файла на основе щаблона:

```bash
cp .env .env.local
```

- Сборка и запуск контейнеров (можно использовать и `docker`):

```bash
podman compose up -d --build
```

- Установка зависимостей (вообще оно устанавливается автоматически в Dockerfile):

```bash
podman compose exec app composer install
```

- Генерация ключа:

```bash
podman compose exec app php artisan key:generate
```

- Миграция (оно тоже мигрирует автоматически при первом старте в Dockerfile):

```bash
podman compose exec app php artisan migrate --seed
```

- Доступен по адресу: `http://localhost:8080`.

