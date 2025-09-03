# 🚀 Универсальная система CI/CD для Android приложений

Система автоматизации сборки и публикации Android приложений с использованием Gitverse, Codemagic, Laravel и Telegram Bot.

## 📋 Архитектура

1. **Gitverse** - Хранение исходного кода
2. **Codemagic** - CI/CD платформа для сборки и публикации
3. **Laravel + Filament** - Управление проектами и мониторинг
4. **Telegram Bot** - Уведомления и управление сборками

## 🛠 Установка

### 1. Laravel приложение

```bash
cd laravel-app
composer install
cp env.example .env
php artisan key:generate
php artisan migrate
php artisan filament:install --panels
```

### 2. Настройка переменных окружения

```env
# Codemagic API
CODEMAGIC_API_TOKEN=your_codemagic_token
CODEMAGIC_APP_ID=your_app_id

# Telegram Bot
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_CHAT_ID=your_chat_id
TELEGRAM_ADMIN_CHAT_ID=admin_chat_id

# Google Play Console
GOOGLE_PLAY_SERVICE_ACCOUNT_JSON=path_to_service_account.json

# Webhook Security
WEBHOOK_SECRET_KEY=your_secret_key
```

### 3. Настройка Codemagic

1. Создайте приложение в Codemagic
2. Скопируйте `codemagic.yaml` в корень вашего Android проекта
3. Настройте переменные окружения в Codemagic:
   - `APPLICATION_NAME` - название приложения
   - `PACKAGE_NAME` - package name
   - `BUILD_TYPE` - тип сборки (debug/release)
   - `GRADLE_TASK` - gradle задача
   - `LARAVEL_WEBHOOK_URL` - URL вашего Laravel приложения
   - `PROJECT_ID` - ID проекта в Laravel
   - `EMAIL_RECIPIENTS` - email для уведомлений
   - `GOOGLE_PLAY_TRACK` - трек Google Play

### 4. Настройка Telegram Bot

1. Создайте бота через @BotFather
2. Получите токен бота
3. Настройте webhook:
```bash
curl -X POST "https://api.telegram.org/bot<BOT_TOKEN>/setWebhook" \
     -H "Content-Type: application/json" \
     -d '{"url": "https://your-domain.com/api/telegram/webhook"}'
```

## 📱 Использование

### 1. Создание проекта

1. Откройте админ панель Laravel (`/admin`)
2. Перейдите в "Проекты" → "Создать"
3. Заполните информацию о проекте:
   - Название проекта
   - Package name
   - Gitverse repository URL
   - Настройки сборки
   - Telegram chat ID для уведомлений

### 2. Запуск сборки

#### Через админ панель:
1. Откройте проект
2. Нажмите кнопку "Собрать Release" или "Собрать Debug"

#### Через Telegram Bot:
```
/start - Показать команды
/projects - Список проектов
/build_[project_id] - Запустить сборку
```

### 3. Workflow'ы

- **build-release-and-publish-beta** - Сборка релиза и публикация в Beta
- **build-debug** - Сборка Debug APK
- **beta-to-release** - Продвижение из Beta в Production

## 🔧 API Endpoints

### Webhook'и для Codemagic:

- `POST /api/build/start` - Начало сборки
- `POST /api/build/finish` - Завершение сборки
- `POST /api/build/publish` - Публикация артефакта
- `POST /api/build/promote` - Продвижение в Production

### Telegram:

- `POST /api/telegram/webhook` - Webhook для Telegram Bot

## 📊 Мониторинг

- **Админ панель** - Полный контроль над проектами и сборками
- **Telegram уведомления** - Статус сборок в реальном времени
- **Email уведомления** - Детальные отчеты о сборках
- **Логи** - Подробные логи всех операций

## 🔐 Безопасность

- Все webhook'и защищены секретными ключами
- Telegram Bot использует callback query для команд
- API endpoints требуют аутентификации
- Переменные окружения для всех секретов

## 🚀 Возможности

- ✅ Универсальная система для любого Android проекта
- ✅ Автоматическая сборка и публикация
- ✅ Telegram Bot для управления
- ✅ Web интерфейс для администрирования
- ✅ Уведомления в реальном времени
- ✅ Поддержка Debug и Release сборок
- ✅ Продвижение из Beta в Production
- ✅ Интеграция с Google Play Console
- ✅ История сборок и артефактов
- ✅ Настраиваемые email уведомления

## 📝 Примеры использования

### Создание нового проекта:

1. В админ панели создайте проект с настройками:
   - Name: "My Awesome App"
   - Package: "com.example.myapp"
   - Gitverse URL: "git@gitverse.ru:user/myapp.git"
   - Build Type: "release"
   - Gradle Task: "bundleRelease"

2. Настройте переменные в Codemagic для этого проекта

3. Запустите сборку через Telegram: `/build_1`

4. Получайте уведомления о статусе сборки

5. Скачивайте готовые APK/AAB файлы

## 🆘 Поддержка

При возникновении проблем проверьте:
1. Логи Laravel приложения
2. Настройки переменных окружения
3. Статус webhook'ов в Codemagic
4. Настройки Telegram Bot
5. Права доступа к Google Play Console
