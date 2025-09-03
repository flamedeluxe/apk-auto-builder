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

#### 🔑 Получение токенов и ключей:

**Codemagic API Token:**
1. Зайдите в [Codemagic](https://codemagic.io)
2. User settings → API tokens → Create token
3. Скопируйте токен

**Telegram Bot Token:**
1. Напишите [@BotFather](https://t.me/BotFather)
2. `/newbot` → следуйте инструкциям
3. Введите имя бота (например: "My CI/CD Bot")
4. Введите username бота (например: "my_cicd_bot")
5. Получите токен вида: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`
6. Запомните username бота - он понадобится для работы в группах

**Telegram Chat ID:**
1. Напишите вашему боту
2. Откройте: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
3. Найдите `"chat":{"id":123456789}` в ответе

**Google Play Service Account:**
1. [Google Cloud Console](https://console.cloud.google.com/)
2. APIs & Services → Credentials → Create Credentials → Service Account
3. Создайте ключ в формате JSON
4. В [Google Play Console](https://play.google.com/console/) → Setup → API access
5. Свяжите проект и дайте права Release Manager

**Webhook Secret Key:**
```bash
# Генерируем случайный ключ
openssl rand -hex 32
```

#### 📝 Файл .env:

```env
# Codemagic API
CODEMAGIC_API_TOKEN=cm_1234567890abcdef
CODEMAGIC_APP_ID=your_app_id_here

# Telegram Bot
TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_BOT_USERNAME=my_cicd_bot
TELEGRAM_CHAT_ID=123456789
TELEGRAM_ADMIN_CHAT_ID=987654321

# Google Play Console
GOOGLE_PLAY_SERVICE_ACCOUNT_JSON=/path/to/service-account.json

# Webhook Security
WEBHOOK_SECRET_KEY=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6

# Email уведомления
EMAIL_RECIPIENTS=developer@example.com,admin@example.com
```

⚠️ **Важно:** Никогда не коммитьте .env файл в Git!

### 3. Настройка Codemagic

#### 📱 Создание приложения в Codemagic:

1. Зайдите в [Codemagic](https://codemagic.io)
2. Нажмите **Add application**
3. Выберите **GitHub** и подключите ваш репозиторий
4. Выберите ветку (обычно `main`)

#### ⚙️ Настройка переменных окружения в Codemagic:

В настройках приложения → **Environment variables** добавьте:

| Переменная | Описание | Пример |
|------------|----------|---------|
| `APPLICATION_NAME` | Название приложения | My Awesome App |
| `PACKAGE_NAME` | Package name | com.example.myapp |
| `BUILD_TYPE` | Тип сборки | release |
| `GRADLE_TASK` | Gradle задача | bundleRelease |
| `LARAVEL_WEBHOOK_URL` | URL Laravel приложения | https://your-domain.com |
| `PROJECT_ID` | ID проекта в Laravel | 1 |
| `EMAIL_RECIPIENTS` | Email для уведомлений | developer@example.com |
| `GOOGLE_PLAY_TRACK` | Трек Google Play | beta |

#### 📄 Копирование codemagic.yaml:

Скопируйте файл `codemagic.yaml` из этого репозитория в корень вашего Android проекта.

### 4. Настройка Telegram Bot

#### 🤖 Создание бота:

1. Напишите [@BotFather](https://t.me/BotFather) в Telegram
2. Отправьте `/newbot`
3. Введите имя бота (например: "My CI/CD Bot")
4. Введите username бота (например: "my_cicd_bot")
5. Получите токен бота

#### 🔗 Настройка webhook:

```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
     -H "Content-Type: application/json" \
     -d '{"url": "https://your-domain.com/api/telegram/webhook"}'
```

#### 📱 Получение Chat ID:

**Для личных сообщений:**
1. Напишите вашему боту любое сообщение
2. Откройте в браузере: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
3. Найдите в ответе: `"chat":{"id":123456789}`
4. Скопируйте этот ID

**Для групповых чатов:**
1. Добавьте бота в группу
2. Дайте боту права администратора (рекомендуется)
3. Напишите в группе любое сообщение с упоминанием бота: `@your_bot_name test`
4. Откройте: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
5. Найдите в ответе: `"chat":{"id":-123456789}` (отрицательный ID для групп)
6. Скопируйте этот ID

**Настройка команд для групп:**
```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setMyCommands" \
     -H "Content-Type: application/json" \
     -d '{
       "commands": [
         {"command": "start", "description": "Показать команды"},
         {"command": "projects", "description": "Список проектов"},
         {"command": "build_", "description": "Запустить сборку проекта"}
       ],
       "scope": {
         "type": "all_group_chats"
       }
     }'
```

#### ⚙️ Настройка команд бота для личных сообщений:

```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setMyCommands" \
     -H "Content-Type: application/json" \
     -d '{
       "commands": [
         {"command": "start", "description": "Показать команды"},
         {"command": "projects", "description": "Список проектов"},
         {"command": "build_", "description": "Запустить сборку проекта"}
       ],
       "scope": {
         "type": "all_private_chats"
       }
     }'
```

#### 👥 Настройка бота для работы в группах:

1. **Добавьте бота в группу:**
   - Создайте группу или используйте существующую
   - Добавьте бота в группу через поиск по username
   - Дайте боту права администратора (рекомендуется)

2. **Получите Chat ID группы:**
   - Напишите в группе: `@your_bot_name /start`
   - Откройте: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
   - Найдите: `"chat":{"id":-123456789}` (отрицательный ID для групп)

3. **Настройте переменные окружения:**
   ```env
   TELEGRAM_BOT_USERNAME=my_cicd_bot
   TELEGRAM_CHAT_ID=-123456789  # ID группы (отрицательный)
   ```

4. **Использование в группе:**
   - Команды: `@my_cicd_bot /start`
   - Список проектов: `@my_cicd_bot /projects`
   - Запуск сборки: `@my_cicd_bot /build_1`

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

## 🚀 Быстрый старт

### Запуск системы за 5 минут:

1. **Клонируйте репозиторий:**
   ```bash
   git clone https://github.com/flamedeluxe/apk-auto-builder.git
   cd apk-auto-builder
   ```

2. **Запустите Laravel:**
   ```bash
   cd laravel-app
   composer install
   cp env.example .env
   php artisan key:generate
   php artisan migrate
   php artisan serve
   ```

3. **Откройте админ панель:**
   - URL: http://localhost:8000/admin
   - Email: admin@example.com
   - Пароль: password

4. **Настройте переменные окружения** в `.env` файле

5. **Создайте проект** в админ панели

## 🔧 Troubleshooting

### Частые проблемы:

**❌ Ошибка "Could not open input file: artisan"**
```bash
# Решение: установите зависимости
cd laravel-app
composer install
```

**❌ Ошибка "Database connection failed"**
```bash
# Решение: настройте базу данных в .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=android_cicd
DB_USERNAME=root
DB_PASSWORD=
```

**❌ Telegram Bot не отвечает**
- Проверьте токен бота в `.env`
- Убедитесь, что webhook настроен правильно
- Проверьте Chat ID

**❌ Codemagic не запускает сборку**
- Проверьте API токен Codemagic
- Убедитесь, что переменные окружения настроены
- Проверьте права доступа к репозиторию

**❌ Google Play публикация не работает**
- Проверьте Service Account JSON файл
- Убедитесь, что права Release Manager предоставлены
- Проверьте настройки в Google Play Console

### 📋 Чек-лист для отладки:

- [ ] Laravel приложение запускается без ошибок
- [ ] База данных подключена и миграции выполнены
- [ ] Все переменные окружения настроены
- [ ] Telegram Bot отвечает на команды
- [ ] Codemagic может получить доступ к репозиторию
- [ ] Google Play Console API настроен
- [ ] Webhook'и доступны по HTTPS

## 🆘 Поддержка

При возникновении проблем:

1. **Проверьте логи:**
   ```bash
   tail -f laravel-app/storage/logs/laravel.log
   ```

2. **Проверьте статус сервисов:**
   - Laravel: http://localhost:8000
   - Админ панель: http://localhost:8000/admin
   - API: http://localhost:8000/api/build/start

3. **Проверьте настройки:**
   - Переменные окружения в `.env`
   - Статус webhook'ов в Codemagic
   - Настройки Telegram Bot
   - Права доступа к Google Play Console

4. **Создайте Issue** в [GitHub репозитории](https://github.com/flamedeluxe/apk-auto-builder/issues)

## 📚 Дополнительные ресурсы

- [Codemagic Documentation](https://docs.codemagic.io/)
- [Laravel Documentation](https://laravel.com/docs)
- [Filament Documentation](https://filamentphp.com/docs)
- [Telegram Bot API](https://core.telegram.org/bots/api)
- [Google Play Console API](https://developers.google.com/android-publisher)
