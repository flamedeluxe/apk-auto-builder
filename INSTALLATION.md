# 🚀 Инструкция по установке универсальной системы CI/CD

## ✅ Что уже готово

Система полностью настроена и готова к использованию! Все файлы созданы, база данных настроена, пользователь создан.

## 🎯 Быстрый старт

### 1. Запуск Laravel приложения

```bash
cd laravel-app
php artisan serve
```

Приложение будет доступно по адресу: `http://localhost:8000`

### 2. Вход в админ панель

- URL: `http://localhost:8000/admin`
- Email: `admin@example.com`
- Пароль: `password`

### 3. Настройка переменных окружения

Отредактируйте файл `laravel-app/.env`:

```env
# Codemagic API
CODEMAGIC_API_TOKEN=your_codemagic_token_here
CODEMAGIC_APP_ID=your_app_id_here

# Telegram Bot
TELEGRAM_BOT_TOKEN=your_telegram_bot_token_here
TELEGRAM_CHAT_ID=your_telegram_chat_id_here
TELEGRAM_ADMIN_CHAT_ID=admin_chat_id_here

# Google Play Console
GOOGLE_PLAY_SERVICE_ACCOUNT_JSON=path_to_service_account.json

# Webhook Security
WEBHOOK_SECRET_KEY=your_secret_key_here
```

## 📱 Настройка для нового Android проекта

### 1. Скопируйте codemagic.yaml

Скопируйте файл `codemagic.yaml` в корень вашего Android проекта.

### 2. Создайте проект в Codemagic

1. Зайдите в [Codemagic](https://codemagic.io)
2. Создайте новое приложение
3. Подключите ваш Gitverse репозиторий
4. Настройте переменные окружения:

```
APPLICATION_NAME=My Awesome App
PACKAGE_NAME=com.example.myapp
BUILD_TYPE=release
GRADLE_TASK=bundleRelease
LARAVEL_WEBHOOK_URL=https://your-domain.com
PROJECT_ID=1
EMAIL_RECIPIENTS=developer@example.com
GOOGLE_PLAY_TRACK=beta
```

### 3. Добавьте проект в Laravel

1. Откройте админ панель Laravel
2. Перейдите в "Проекты" → "Создать"
3. Заполните информацию:
   - **Название проекта**: My Awesome App
   - **Package Name**: com.example.myapp
   - **Gitverse Repository URL**: git@gitverse.ru:user/myapp.git
   - **Codemagic App ID**: ваш ID из Codemagic
   - **Build Type**: Release
   - **Gradle Task**: bundleRelease
   - **Google Play Track**: Beta
   - **Telegram Chat ID**: ваш Telegram chat ID

### 4. Настройте Telegram Bot

1. Создайте бота через [@BotFather](https://t.me/BotFather)
2. Получите токен бота
3. Настройте webhook:
```bash
curl -X POST "https://api.telegram.org/bot<BOT_TOKEN>/setWebhook" \
     -H "Content-Type: application/json" \
     -d '{"url": "https://your-domain.com/api/telegram/webhook"}'
```

## 🔧 Использование

### Запуск сборки через админ панель

1. Откройте проект в админ панели
2. Нажмите кнопку "Собрать Release" или "Собрать Debug"

### Запуск сборки через Telegram

Отправьте боту команды:
- `/start` - показать команды
- `/projects` - список проектов
- `/build_1` - запустить сборку проекта с ID 1

### Workflow'ы

- **build-release-and-publish-beta** - Сборка релиза и публикация в Beta
- **build-debug** - Сборка Debug APK
- **beta-to-release** - Продвижение из Beta в Production

## 📊 Мониторинг

- **Админ панель**: `http://localhost:8000/admin`
- **Telegram уведомления**: Статус сборок в реальном времени
- **Логи**: `laravel-app/storage/logs/laravel.log`

## 🚀 Деплой в продакшн

### 1. Настройте сервер

```bash
# Установите PHP 8.1+, Composer, MySQL
# Склонируйте репозиторий
git clone your-repo-url
cd laravel-app
composer install --no-dev --optimize-autoloader
```

### 2. Настройте веб-сервер

Создайте виртуальный хост для Laravel приложения.

### 3. Настройте SSL

Для webhook'ов нужен HTTPS.

### 4. Настройте cron

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🔐 Безопасность

- Измените пароль администратора
- Настройте сильные секретные ключи
- Используйте HTTPS в продакшне
- Ограничьте доступ к админ панели

## 🆘 Поддержка

При возникновении проблем:

1. Проверьте логи: `laravel-app/storage/logs/laravel.log`
2. Убедитесь, что все переменные окружения настроены
3. Проверьте подключение к базе данных
4. Убедитесь, что webhook'и доступны по HTTPS

## 📝 Примеры

### Создание проекта "My App"

1. **В Codemagic**:
   - App ID: `abc123def456`
   - Repository: `git@gitverse.ru:user/myapp.git`

2. **В Laravel**:
   - Name: "My App"
   - Package: "com.example.myapp"
   - Codemagic App ID: "abc123def456"

3. **В Telegram**:
   - `/build_1` - запуск сборки

4. **Результат**:
   - APK/AAB файл в Google Play Beta
   - Уведомления в Telegram
   - История сборок в админ панели

Система готова к использованию! 🎉
