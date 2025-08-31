# Neo Dashboard WordPress Project

Современный dashboard для WordPress с использованием плагина Neo Dashboard Core.

## 🚀 Быстрый старт

### Требования
- **PHP:** 8.1 или выше
- **WordPress:** 6.0 или выше
- **XAMPP/WAMP/MAMP** или аналогичный локальный сервер
- **Git**

### Установка за 5 минут

1. **Клонируйте репозиторий:**
```bash
git clone https://github.com/your-username/neo-dashboard-wordpress.git
cd neo-dashboard-wordpress
```

2. **Запустите XAMPP:**
   - Запустите Apache и MySQL
   - Убедитесь, что порты 80 и 3306 свободны

3. **Импортируйте базу данных:**
   - Откройте phpMyAdmin: `http://localhost/phpmyadmin`
   - Создайте новую базу данных: `wordpress_neo`
   - Импортируйте файл `database/wordpress_neo.sql`

4. **Настройте WordPress:**
   - Скопируйте папку `wordpress` в `htdocs`
   - Откройте `http://localhost/wordpress`
   - Следуйте инструкциям установки WordPress
   - Используйте данные базы данных: `wordpress_neo`

5. **Активируйте плагины:**
   - Войдите в админ-панель: `http://localhost/wordpress/wp-admin`
   - Перейдите в **Плагины**
   - Активируйте **Neo Dashboard Core**
   - Активируйте **Neo Dashboard Examples**

6. **Откройте dashboard:**
   - Перейдите по ссылке: `http://localhost/wordpress/neo-dashboard`

## 📁 Структура проекта

```
neo-dashboard-wordpress/
├── wordpress/                          # WordPress файлы
│   ├── wp-content/
│   │   ├── plugins/
│   │   │   ├── neo-dashboard/         # Основной плагин
│   │   │   └── neo-dashboard-examples/ # Примеры
│   │   └── themes/
│   ├── wp-config.php
│   └── index.php
├── database/                           # База данных
│   └── wordpress_neo.sql
├── docs/                              # Документация
├── scripts/                           # Скрипты установки
└── README.md
```

## 🔧 Настройка

### Изменение портов (если нужно)
Если порт 80 занят, измените в `apache/conf/httpd.conf`:
```apache
Listen 8080
ServerName localhost:8080
```

### Изменение URL базы данных
В `wp-config.php` измените:
```php
define('WP_HOME','http://localhost:8080/wordpress');
define('WP_SITEURL','http://localhost:8080/wordpress');
```

## 🌟 Возможности

- **Современный UI** на Bootstrap 5.3
- **Адаптивный дизайн** для всех устройств
- **Модульная архитектура** для расширения
- **REST API** для интеграции
- **Система уведомлений**
- **Виджеты и секции**
- **Группировка в sidebar**

## 🛠️ Разработка

### Добавление новых секций
```php
add_action('neo_dashboard_init', function() {
    do_action('neo_dashboard_register_section', [
        'slug'          => 'my-section',
        'label'         => 'Моя секция',
        'icon'          => 'bi-star',
        'template_path' => plugin_dir_path(__FILE__) . 'templates/my-section.php',
    ]);
});
```

### Добавление виджетов
```php
add_action('neo_dashboard_init', function() {
    do_action('neo_dashboard_register_widget', [
        'id'       => 'my-widget',
        'label'    => 'Мой виджет',
        'icon'     => 'bi-graph-up',
        'priority' => 10,
        'callback' => function() {
            echo '<p>Содержимое виджета</p>';
        },
    ]);
});
```

## 📚 Документация

- [Neo Dashboard Core](docs/neo-dashboard-core.md)
- [API Reference](docs/api-reference.md)
- [Примеры использования](docs/examples.md)
- [Troubleshooting](docs/troubleshooting.md)

## 🐛 Устранение неполадок

### Плагин не активируется
- Проверьте версию PHP (должна быть 8.1+)
- Проверьте права доступа к папкам
- Очистите кэш WordPress

### Страница dashboard не найдена
- Создайте страницу с slug "neo-dashboard"
- Добавьте shortcode `[neo-dashboard]`
- Обновите permalinks

### Стили не загружаются
- Проверьте, что CSS файлы существуют
- Очистите кэш браузера
- Проверьте консоль браузера на ошибки

## 🤝 Вклад в проект

1. Fork репозитория
2. Создайте feature branch: `git checkout -b feature/amazing-feature`
3. Commit изменения: `git commit -m 'Add amazing feature'`
4. Push в branch: `git push origin feature/amazing-feature`
5. Откройте Pull Request

## 📄 Лицензия

Этот проект распространяется под лицензией GPL-2.0-or-later.

## 🆘 Поддержка

- **Issues:** [GitHub Issues](https://github.com/your-username/neo-dashboard-wordpress/issues)
- **Discussions:** [GitHub Discussions](https://github.com/your-username/neo-dashboard-wordpress/discussions)
- **Wiki:** [GitHub Wiki](https://github.com/your-username/neo-dashboard-wordpress/wiki)

## 🙏 Благодарности

- WordPress Community
- Bootstrap Team
- Все участники проекта

---

**Сделано с ❤️ для WordPress сообщества**
