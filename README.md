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

### 📧 Email-рассылки
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
- Страница отписки от рассылок

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

Отредактируйте файл `config/config.php`:

```php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'contact_system');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');

// Application URL
define('APP_URL', 'http://localhost');

// Email settings (для PHPMailer)
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@example.com');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_ENCRYPTION', 'tls'); // tls или ssl
define('MAIL_FROM_EMAIL', 'noreply@example.com');
define('MAIL_FROM_NAME', 'Contact Management System');
```

### 5. Настройка веб-сервера

#### Apache

Убедитесь, что mod_rewrite включен:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Document Root должен указывать на папку `public/`:

```apache
<VirtualHost *:80>
    ServerName contact-system.local
    DocumentRoot /path/to/contacts/public

    <Directory /path/to/contacts/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name contact-system.local;
    root /path/to/contacts/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### 6. Права доступа

Установите правильные права на папки:

```bash
chmod 755 -R .
chmod 777 -R public/uploads
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
│   └── config.php
├── database/            # SQL схемы и миграции
│   └── schema.sql
├── public/              # Публичная папка (Document Root)
│   ├── css/
│   ├── js/
│   ├── uploads/
│   ├── .htaccess
│   └── index.php        # Точка входа
├── src/
│   ├── controllers/     # Контроллеры
│   ├── models/          # Модели
│   ├── views/           # Представления
│   ├── Database.php     # Класс подключения к БД
│   └── helpers.php      # Вспомогательные функции
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

Проверьте настройки SMTP в `config/config.php`:
- Правильность хоста, порта, логина и пароля
- Firewall не блокирует исходящие соединения на порт 587/465

## Лицензия

MIT License

## Поддержка

Для вопросов и предложений создавайте Issue в репозитории.

## Changelog

### v1.0.0 (2025-11-27)
- Первый релиз
- Модуль авторизации
- Управление контактами
- Email-кампании
- Dashboard
- Импорт/Экспорт
- Отслеживание статистики

## TODO

- [ ] Интеграция с внешними сервисами (Mailchimp, SendGrid)
- [ ] Расширенная аналитика
- [ ] Двухфакторная аутентификация
- [ ] REST API
- [ ] WebSocket уведомления
- [ ] Темная тема
- [ ] Мультиязычность
- [ ] Автоматические рассылки по расписанию
- [ ] A/B тестирование кампаний
- [ ] Сегментация аудитории

## Благодарности

- Bootstrap 5
- PHPMailer
- Bootstrap Icons
