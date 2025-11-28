# Contact Management and Mailing System

Полнофункциональная веб-система управления контактами и email-рассылками на PHP 8.x + MySQL 8.x.

## Возможности

### 🔐 Модуль авторизации
- Регистрация пользователей
- Вход/выход из системы
- Восстановление пароля
- Защита от CSRF атак
- Хеширование паролей (bcrypt)
- Роли: Администратор и Пользователь

### 👥 Управление контактами
- CRUD операции (создание, чтение, обновление, удаление)
- Расширенный поиск по всем полям
- Фильтрация по статусу, тегам, дате
- Сортировка по любому столбцу
- Пагинация (50/100/200 записей)
- Массовые операции
- Импорт из CSV
- Экспорт в CSV
- Система тегов
- Статусы: active, inactive, unsubscribed

### 📧 Email-рассылки (PHPMailer + SMTP)
- **Профессиональная отправка через SMTP** (не попадают в спам)
- Поддержка очереди отправки (queue system)
- Rate limiting для защиты от блокировки SMTP
- Создание и управление кампаниями
- WYSIWYG редактор для писем
- Персонализация (подстановка {first_name}, {last_name}, и т.д.)
- Выбор получателей:
  - Все активные контакты
  - Выборочно
  - По тегам
- Отслеживание статистики:
  - Количество отправленных писем
  - Открытия (через pixel tracking)
  - Клики по ссылкам
- Тестовая отправка перед запуском кампании
- Страница отписки от рассылок
- Автоматическая обработка очереди через cron

### 📊 Dashboard
- Статистика контактов
- Статистика кампаний
- Последние контакты
- Последние рассылки
- Быстрые действия

### 🔒 Безопасность
- Prepared statements для SQL запросов
- Валидация и санитизация всех входящих данных
- CSRF защита
- XSS защита
- Хеширование паролей
- Разграничение прав доступа

## Технологический стек

- **Backend:** PHP 8.x
- **База данных:** MySQL 8.x
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5
- **Email:** PHPMailer
- **Архитектура:** MVC

## Требования

- PHP >= 8.0
- MySQL >= 8.0
- Apache/Nginx с mod_rewrite
- Composer

## Установка

### 1. Клонирование репозитория

```bash
git clone <repository-url>
cd contacts
```

### 2. Установка зависимостей

```bash
composer install
```

### 3. Настройка базы данных

Создайте базу данных MySQL:

```sql
CREATE DATABASE contact_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Импортируйте схему базы данных:

```bash
mysql -u root -p contact_system < database/schema.sql
```

### 4. Конфигурация

Файл `config/config.php` уже создан и готов к использованию. Он автоматически загружает переменные окружения из `.env` файла.

Скопируйте `.env.example` в `.env` и отредактируйте:

```bash
cp .env.example .env
nano .env
```

Заполните настройки в `.env`:

```env
# База данных
DB_HOST=localhost
DB_NAME=contacts_db
DB_USER=root
DB_PASSWORD=your_password

# Приложение
APP_URL=https://yourdomain.com
APP_NAME="Contact Management System"
APP_ENV=production

# SMTP настройки
MAIL_DRIVER=smtp
SMTP_HOST=smtp.yandex.ru
SMTP_PORT=465
SMTP_ENCRYPTION=ssl
SMTP_USERNAME=your-email@yourdomain.ru
SMTP_PASSWORD=your-app-password
SMTP_FROM_EMAIL=noreply@yourdomain.ru
SMTP_FROM_NAME="Your Company Name"

# Безопасность
SESSION_LIFETIME=7200
```

#### Настройка SMTP для разных провайдеров:

**Яндекс 360 / Яндекс Почта для бизнеса:**
```env
SMTP_HOST=smtp.yandex.ru
SMTP_PORT=465
SMTP_ENCRYPTION=ssl
SMTP_USERNAME=your-email@yourdomain.ru
SMTP_PASSWORD=app-password  # Используйте пароль приложения!
```

**Mail.ru для бизнеса:**
```env
SMTP_HOST=smtp.mail.ru
SMTP_PORT=465
SMTP_ENCRYPTION=ssl
```

**Gmail (требует App Password):**
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
```

**SendGrid:**
```env
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=apikey
SMTP_PASSWORD=your-sendgrid-api-key
```

### 5. Настройка веб-сервера

**ВАЖНО:** Document Root веб-сервера должен указывать на папку `public/`, а НЕ на корень проекта!

#### Apache

Убедитесь, что mod_rewrite включен:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Конфигурация VirtualHost:

```apache
<VirtualHost *:80>
    ServerName contact-system.local
    DocumentRoot /var/www/contacts/public

    <Directory /var/www/contacts/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/contacts-error.log
    CustomLog ${APACHE_LOG_DIR}/contacts-access.log combined
</VirtualHost>
```

#### Nginx

Базовая конфигурация:

```nginx
server {
    listen 80;
    server_name contact-system.local;
    root /var/www/contacts/public;
    index index.php;

    # Логирование
    access_log /var/log/nginx/contacts-access.log;
    error_log /var/log/nginx/contacts-error.log;

    # Обработка PHP файлов
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Защита от доступа к скрытым файлам
    location ~ /\. {
        deny all;
    }
}
```

#### Nginx + Cloudflare (рекомендуемая конфигурация для продакшена)

Если вы используете Cloudflare, настройте Nginx следующим образом для получения реальных IP адресов:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/contacts/public;
    index index.php;

    # Логирование
    access_log /var/log/nginx/contacts-access.log;
    error_log /var/log/nginx/contacts-error.log;

    # Получение реального IP от Cloudflare
    set_real_ip_from 103.21.244.0/22;
    set_real_ip_from 103.22.200.0/22;
    set_real_ip_from 103.31.4.0/22;
    set_real_ip_from 104.16.0.0/13;
    set_real_ip_from 104.24.0.0/14;
    set_real_ip_from 108.162.192.0/18;
    set_real_ip_from 131.0.72.0/22;
    set_real_ip_from 141.101.64.0/18;
    set_real_ip_from 162.158.0.0/15;
    set_real_ip_from 172.64.0.0/13;
    set_real_ip_from 173.245.48.0/20;
    set_real_ip_from 188.114.96.0/20;
    set_real_ip_from 190.93.240.0/20;
    set_real_ip_from 197.234.240.0/22;
    set_real_ip_from 198.41.128.0/17;
    set_real_ip_from 2400:cb00::/32;
    set_real_ip_from 2606:4700::/32;
    set_real_ip_from 2803:f800::/32;
    set_real_ip_from 2405:b500::/32;
    set_real_ip_from 2405:8100::/32;
    set_real_ip_from 2c0f:f248::/32;
    set_real_ip_from 2a06:98c0::/29;
    real_ip_header CF-Connecting-IP;

    # Обработка PHP файлов
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTP_X_FORWARDED_FOR $http_cf_connecting_ip;
        fastcgi_param REMOTE_ADDR $http_cf_connecting_ip;
    }

    # Защита от доступа к скрытым файлам
    location ~ /\. {
        deny all;
    }

    # Кэширование статических файлов
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

**Примечание:** После настройки Nginx не забудьте:
```bash
sudo nginx -t  # Проверить конфигурацию
sudo systemctl reload nginx  # Перезагрузить Nginx
```

### 6. Настройка Cron Job

Для автоматической обработки очереди email добавьте в crontab:

```bash
crontab -e
```

Добавьте строку (запуск каждые 5 минут):

```bash
*/5 * * * * /usr/bin/php /path/to/contacts/cron/process-email-queue.php >> /var/log/email-queue.log 2>&1
```

Или создайте systemd timer (рекомендуется):

```bash
# /etc/systemd/system/email-queue.service
[Unit]
Description=Process Email Queue

[Service]
Type=oneshot
ExecStart=/usr/bin/php /path/to/contacts/cron/process-email-queue.php
User=www-data
Group=www-data

[Install]
WantedBy=multi-user.target
```

```bash
# /etc/systemd/system/email-queue.timer
[Unit]
Description=Run email queue processor every 5 minutes

[Timer]
OnBootSec=5min
OnUnitActiveSec=5min

[Install]
WantedBy=timers.target
```

Активация:

```bash
sudo systemctl enable email-queue.timer
sudo systemctl start email-queue.timer
sudo systemctl status email-queue.timer
```

### 7. Применение миграций БД

Примените новые миграции для очереди email:

```bash
mysql -u root -p contacts_db < database/migrations/001_add_email_queue_and_logs.sql
```

### 8. Сброс пароля администратора (опционально)

Если вам нужно сбросить пароль администратора после импорта схемы БД:

```bash
mysql -u root -p contacts_db
```

Затем выполните:

```sql
UPDATE users SET password = '$2y$12$ERRXw4KWGqZPnfE1KwqdX..3PwXlhWvmh0iperNu0hccGiTNEz9t2' WHERE email = 'admin@example.com';
```

Этот хеш соответствует паролю: `admin123`

### 9. Права доступа

Установите правильные права на папки:

```bash
chmod 755 -R .
chmod 777 -R public/uploads
chmod 777 -R logs
chmod +x cron/process-email-queue.php
```

## Использование

### Первый вход

После установки используйте учетные данные по умолчанию:

- **Email:** admin@example.com
- **Пароль:** admin123

**ВАЖНО:** Сразу после первого входа смените пароль!

### Создание контактов

1. Перейдите в раздел "Contacts"
2. Нажмите "New Contact"
3. Заполните форму и сохраните

### Импорт контактов

1. Подготовьте CSV файл с колонками: first_name, last_name, email, phone, company, position, tags, status
2. Перейдите в "Contacts" → "Import"
3. Загрузите файл

Пример CSV:

```csv
first_name,last_name,email,phone,company,position,tags,status
John,Doe,john@example.com,+1234567890,Acme Corp,Manager,"client,vip",active
Jane,Smith,jane@example.com,+0987654321,Tech Inc,Developer,partner,active
```

### Создание email-кампании

1. Перейдите в "Campaigns"
2. Нажмите "New Campaign"
3. Заполните название, тему и текст письма
4. Используйте плейсхолдеры для персонализации:
   - `{first_name}` - Имя
   - `{last_name}` - Фамилия
   - `{email}` - Email
   - `{company}` - Компания
5. Добавьте получателей
6. Отправьте кампанию

### Отслеживание статистики

После отправки кампании вы можете отслеживать:

- Количество отправленных писем
- Количество открытых писем
- Количество кликов по ссылкам
- Статус каждого получателя

## Структура проекта

```
/contacts
├── config/              # Конфигурационные файлы
│   ├── config.php       # Основная конфигурация приложения
│   ├── config.example.php  # Пример конфигурации
│   └── mail.php         # Настройки SMTP и очереди
├── cron/                # Cron jobs
│   └── process-email-queue.php
├── database/            # SQL схемы и миграции
│   ├── schema.sql
│   └── migrations/
│       └── 001_add_email_queue_and_logs.sql
├── docs/                # Документация
├── logs/                # Логи приложения
│   └── email.log
├── public/              # Публичная папка (Document Root)
│   ├── css/
│   ├── js/
│   ├── uploads/
│   ├── track.php        # Tracking pixel endpoint
│   ├── click.php        # Link tracking endpoint
│   ├── .htaccess
│   └── index.php        # Точка входа
├── src/
│   ├── controllers/     # Контроллеры
│   ├── models/          # Модели
│   ├── views/           # Представления
│   ├── services/        # Бизнес-логика
│   │   ├── EmailService.php      # Отправка email через PHPMailer
│   │   ├── QueueService.php      # Управление очередью
│   │   └── TrackingService.php   # Отслеживание открытий/кликов
│   ├── Database.php     # Класс подключения к БД
│   └── helpers.php      # Вспомогательные функции
├── vendor/              # Composer зависимости
├── .env                 # Конфигурация (не в git!)
├── .env.example         # Пример конфигурации
├── composer.json
└── README.md
```

## API (опционально)

Система поддерживает REST API для интеграции с внешними системами.

### Аутентификация

Используйте API ключ в заголовке:

```
Authorization: Bearer YOUR_API_KEY
```

### Endpoints

- `GET /api/contacts` - Получить список контактов
- `POST /api/contacts` - Создать контакт
- `GET /api/contacts/{id}` - Получить контакт
- `PUT /api/contacts/{id}` - Обновить контакт
- `DELETE /api/contacts/{id}` - Удалить контакт

## Безопасность

### Рекомендации

1. **Смените пароль администратора** сразу после установки
2. **Используйте HTTPS** в продакшене
3. **Регулярно обновляйте** зависимости: `composer update`
4. **Настройте бэкапы** базы данных
5. **Отключите display_errors** в продакшене
6. **Используйте сильные пароли** для MySQL и email

### Обновление

```bash
git pull origin main
composer update
# Проверьте database/schema.sql на новые миграции
```

## Устранение неполадок

### Ошибка подключения к базе данных

Проверьте настройки в `config/config.php`:
- Правильность имени БД, пользователя и пароля
- Запущен ли MySQL сервер

### Ошибки 404

Убедитесь, что:
- mod_rewrite включен (для Apache)
- .htaccess файл существует в `public/`
- Document Root указывает на `public/`

### Не работает импорт

Проверьте:
- Права на папку `public/uploads` (должна быть 777)
- Размер загружаемого файла (php.ini: `upload_max_filesize`, `post_max_size`)

### Не отправляются письма

Проверьте настройки SMTP в `.env`:
- Правильность хоста, порта, логина и пароля
- Firewall не блокирует исходящие соединения на порт 587/465
- Используете ли вы **пароль приложения** (не основной пароль) для Яндекс/Gmail
- Проверьте логи: `tail -f logs/email.log`

#### Тестирование SMTP подключения:

```bash
# Запустите тестовую отправку
php -r "
require 'vendor/autoload.php';
use App\Services\EmailService;
\$service = new EmailService();
\$result = \$service->testConnection();
echo \$result['message'];
"
```

### Очередь не обрабатывается

Проверьте:
- Запущен ли cron job: `sudo systemctl status email-queue.timer`
- Логи cron: `tail -f /var/log/email-queue.log`
- Статус очереди через БД:
  ```sql
  SELECT status, COUNT(*) FROM email_queue GROUP BY status;
  ```

## Лицензия

MIT License

## Поддержка

Для вопросов и предложений создавайте Issue в репозитории.

## Changelog

### v2.0.0 (2025-11-27)
- 🎉 **Модернизация системы отправки email:**
  - Интеграция PHPMailer 6.8 с SMTP
  - Система очереди отправки (QueueService)
  - Rate limiting для защиты от блокировки
  - TrackingService для отслеживания открытий и кликов
  - Тестовая отправка писем
  - Cron job для автоматической обработки очереди
  - Поддержка популярных SMTP провайдеров (Яндекс, Mail.ru, Gmail, SendGrid)
- 📧 Конфигурация через .env файл
- 📊 Расширенная статистика email-кампаний
- 🔒 Улучшенная безопасность SMTP

### v1.0.0 (2025-11-27)
- Первый релиз
- Модуль авторизации
- Управление контактами
- Email-кампании
- Dashboard
- Импорт/Экспорт
- Отслеживание статистики

## TODO

- [x] ~~Интеграция PHPMailer с SMTP~~
- [x] ~~Система очереди email~~
- [x] ~~Rate limiting~~
- [x] ~~Tracking открытий и кликов~~
- [ ] Admin UI для настройки SMTP
- [ ] Расширенная аналитика (графики, heatmaps)
- [ ] Двухфакторная аутентификация
- [ ] REST API
- [ ] WebSocket уведомления
- [ ] Темная тема
- [ ] Мультиязычность
- [ ] Автоматические рассылки по расписанию
- [ ] A/B тестирование кампаний
- [ ] Сегментация аудитории
- [ ] Интеграция с Mailchimp API

## Благодарности

- Bootstrap 5
- PHPMailer
- Bootstrap Icons
