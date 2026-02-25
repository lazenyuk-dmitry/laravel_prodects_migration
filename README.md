# Laravel Products Migration

Система синхронизации данных с Wildberries API (Orders, Sales, Stocks, Incomes).

## Требования

- PHP 8.2+
- Расширение pcntl (для RoadRunner)
- MySQL 8.0+ / PostgreSQL
- RoadRunner Binary

## Установка зависимостей

```bash
composer install
```

## Настройка окружения

```bash
cp .env.example .env
php artisan key:generate
```

## Настройка `.env`

```bash
APP_KEY=artisan_key

WB_API_HOST=host_url
WB_API_KEY=api_key

# Можно запустить базу в докере локально
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=root
```

Можно запустить базу локально для демо

```bash
docker-compose up --build -d
```

## Установка roadrunner

```bash
php artisan octane:install --server=roadrunner
```

## Миграции

```bash
php artisan migrate
```

## Запуск в терминале

Это проще и лучше в этом примере так как если запускать в потокох то все равно все упирается в ограничения апи `429 Too Many Requests`

```bash
# например данные с 2000-01-01 по сегодня для всех эндпоинтов
php artisan wb:sync --dateFrom=2000-01-01

# или можно указать тип синхронизации
php artisan wb:sync --dateFrom=2000-01-01 --type=orders
```

## Запуск в octane

Сделать нормально в октане с какими то логами на страницу не так просто и быстро поэтому проверять нужно в логах.

Запуск сервера

```bash
php artisan octane:start --server=roadrunner --workers=4
```

Заходим на урл `http://localhost:8000/api/sync`

Проверяем логи в терминале

```bash
tail -f storage/logs/laravel.log
```

Увидим что то то типа

```bash
[2026-02-25 19:44:29] local.INFO: Общий прогресс orders: 3500/127121
[2026-02-25 19:44:30] local.INFO: Загружаю страницу: 8
[2026-02-25 19:44:30] local.INFO: Сохранено 500 записей
[2026-02-25 19:44:30] local.INFO: Общий прогресс orders: 4000/127121
[2026-02-25 19:44:31] local.INFO: Загружаю страницу: 9
[2026-02-25 19:44:31] local.INFO: Сохранено 500 записей
[2026-02-25 19:44:31] local.INFO: Общий прогресс orders: 4500/127121
[2026-02-25 19:44:32] local.INFO: Загружаю страницу: 10
[2026-02-25 19:44:33] local.INFO: Сохранено 500 записей
[2026-02-25 19:44:33] local.INFO: Общий прогресс orders: 5000/127121
[2026-02-25 19:44:34] local.INFO: Загружаю страницу: 11
[2026-02-25 19:44:34] local.INFO: Сохранено 500 записей
[2026-02-25 19:44:34] local.INFO: Общий прогресс orders: 5500/127121
[2026-02-25 19:44:35] local.INFO: Загружаю страницу: 12
[2026-02-25 19:44:35] local.INFO: Сохранено 500 записей
[2026-02-25 19:44:35] local.INFO: Общий прогресс orders: 6000/127121
[2026-02-25 19:44:36] local.INFO: Загружаю страницу: 13
[2026-02-25 19:44:37] local.INFO: Сохранено 500 записей
[2026-02-25 19:44:37] local.INFO: Общий прогресс orders: 6500/127121
```
