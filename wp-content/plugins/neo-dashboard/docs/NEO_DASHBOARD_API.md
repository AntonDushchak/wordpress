# Neo Dashboard API Documentation

## Обзор архитектуры

Neo Dashboard - это мощная система для создания кастомных дашбордов в WordPress. Система построена на модульной архитектуре с центральным Registry и набором Manager'ов для управления различными компонентами.

### Основные компоненты

```
Neo Dashboard Architecture
├── Bootstrap.php           # Инициализация системы
├── Registry.php           # Центральное хранилище компонентов
├── Router.php            # Маршрутизация URL'ов
├── Dashboard.php         # Главный контроллер дашборда
├── Helper.php           # Вспомогательные функции
├── AccessControl.php    # Контроль доступа и безопасность
├── SecurityEnforcer.php # Принудительная безопасность
├── Roles.php           # Управление ролями пользователей
└── Manager/            # Менеджеры компонентов
    ├── SectionManager.php      # Управление страницами/секциями
    ├── SidebarManager.php      # Управление боковым меню
    ├── WidgetManager.php       # Управление виджетами
    ├── NotificationManager.php # Управление уведомлениями
    ├── AssetManager.php        # Управление CSS/JS ресурсами
    ├── ContentManager.php      # Рендеринг контента
    ├── AjaxManager.php         # AJAX обработка
    ├── RestManager.php         # REST API
    └── ThemeSwitcher.php       # Переключение тем
```

## Registry System

Центральное хранилище всех компонентов дашборда. Singleton паттерн.

```php
$registry = \NeoDashboard\Core\Registry::instance();

// Доступные методы:
$registry->addSidebarItem($slug, $args);
$registry->getSidebarItems();
$registry->getSidebarTree();
$registry->addWidget($id, $args);
$registry->getWidgets();
$registry->addNotification($id, $args);
$registry->getNotifications();
$registry->addSection($slug, $args);
$registry->getSections();
$registry->addAjaxRoute($slug, $args);
$registry->getAjaxRoutes();
```

## Plugin Integration API

### 1. Регистрация секций (страниц)

```php
add_action('neo_dashboard_init', function() {
    do_action('neo_dashboard_register_section', [
        'slug' => 'my-section',           // Обязательно
        'label' => 'My Section',          // Заголовок секции
        'callback' => 'my_callback_func', // Функция отображения
        'template_path' => '/path/to/template.php', // Альтернатива callback
        'roles' => ['administrator'],     // Доступные роли (null = все)
    ]);
});
```

**Параметры:**
- `slug` (string) - Уникальный идентификатор секции
- `label` (string) - Отображаемое название
- `callback` (callable) - Функция для рендеринга контента
- `template_path` (string) - Путь к шаблону (альтернатива callback)
- `roles` (array|null) - Массив ролей или null для всех пользователей

### 2. Регистрация элементов меню

```php
add_action('neo_dashboard_init', function() {
    do_action('neo_dashboard_register_sidebar_item', [
        'slug' => 'my-menu-item',         // Обязательно
        'label' => 'My Menu Item',        // Текст в меню
        'icon' => 'bi-calendar',          // Bootstrap Icons класс
        'url' => '/neo-dashboard/my-section', // URL для перехода
        'position' => 10,                 // Порядок сортировки
        'roles' => null,                  // Доступные роли
        'parent' => null,                 // Родительский элемент
        'is_group' => false,             // Группа или обычный элемент
    ]);
});
```

**Параметры:**
- `slug` (string) - Уникальный идентификатор
- `label` (string) - Текст пункта меню
- `icon` (string) - CSS класс иконки (Bootstrap Icons)
- `url` (string) - URL для навигации
- `position` (int) - Порядок сортировки (по умолчанию 10)
- `roles` (array|null) - Доступные роли
- `parent` (string) - Slug родительского элемента для создания подменю
- `is_group` (bool) - Является ли элемент группой

### 3. Регистрация виджетов

```php
add_action('neo_dashboard_init', function() {
    do_action('neo_dashboard_register_widget', [
        'id' => 'my-widget',              // Обязательно
        'title' => 'My Widget',          // Заголовок виджета
        'callback' => 'my_widget_callback', // Функция отображения
        'priority' => 10,                 // Приоритет (сортировка)
        'roles' => null,                  // Доступные роли
    ]);
});
```

**Параметры:**
- `id` (string) - Уникальный идентификатор виджета
- `title` (string) - Заголовок виджета
- `callback` (callable) - Функция для рендеринга содержимого
- `priority` (int) - Приоритет отображения
- `roles` (array|null) - Доступные роли

### 4. Регистрация уведомлений

```php
add_action('neo_dashboard_init', function() {
    do_action('neo_dashboard_register_notification', [
        'id' => 'my-notification',        // Обязательно
        'message' => 'Hello World!',      // Текст уведомления
        'type' => 'info',                // info|success|warning|error
        'dismissible' => true,           // Можно ли закрыть
        'priority' => 10,                // Приоритет отображения
        'roles' => null,                 // Доступные роли
        'expires' => time() + 3600,      // Время истечения (Unix timestamp)
    ]);
});
```

**Параметры:**
- `id` (string) - Уникальный идентификатор
- `message` (string) - Текст уведомления
- `type` (string) - Тип: info, success, warning, error
- `dismissible` (bool) - Возможность закрытия пользователем
- `priority` (int) - Приоритет отображения
- `roles` (array|null) - Доступные роли
- `expires` (int) - Unix timestamp истечения уведомления

## Asset Management API

### Регистрация ресурсов плагина

```php
add_action('neo_dashboard_init', function() {
    do_action('neo_dashboard_register_plugin_assets', 'my-plugin', [
        'css' => [
            'my-plugin-core' => [
                'src' => plugin_dir_url(__FILE__) . 'assets/css/core.css',
                'deps' => ['neo-dashboard-core'],
                'version' => '1.0.0',
                'contexts' => ['my-plugin', 'my-plugin/settings'] // На каких страницах загружать
            ]
        ],
        'js' => [
            'my-plugin-script' => [
                'src' => plugin_dir_url(__FILE__) . 'assets/js/script.js',
                'deps' => ['neo-dashboard-core', 'jquery'],
                'version' => '1.0.0',
                'in_footer' => true,
                'contexts' => ['my-plugin']
            ]
        ]
    ]);
});
```

### Регистрация ресурсов для конкретной страницы

```php
add_action('neo_dashboard_init', function() {
    do_action('neo_dashboard_register_page_assets', 'my-plugin/calendar', 'js', [
        'handle' => 'my-plugin-calendar',
        'src' => plugin_dir_url(__FILE__) . 'assets/js/calendar.js',
        'deps' => ['neo-dashboard-core'],
        'version' => '1.0.0',
        'in_footer' => true
    ]);
});
```

## Новые прямые методы API v4.0.0

### Через AssetManager

```php
add_action('neo_dashboard_init', function() {
    $assetManager = \NeoDashboard\Core\Registry::instance()->getAssetManager();
    
    // Регистрация ресурсов плагина
    $assetManager->registerPluginAssets('my-plugin', [
        'css' => [/* ... */],
        'js' => [/* ... */]
    ]);
    
    // Регистрация ресурсов страницы
    $assetManager->registerPageAssets('my-plugin/settings', 'css', [
        'handle' => 'my-plugin-settings',
        'src' => '...',
        'deps' => ['neo-dashboard-core']
    ]);
});
```

## AJAX API

### Регистрация AJAX обработчиков

```php
add_action('neo_dashboard_init', function() {
    do_action('neo_dashboard_register_ajax_route', [
        'action' => 'my_ajax_action',     // Название действия
        'callback' => 'my_ajax_callback', // Функция обработчик
        'capability' => 'manage_options', // Требуемая capability
        'nonce_action' => 'my_nonce',    // Действие для nonce
    ]);
});
```

### Использование в JavaScript

```javascript
// AJAX запрос в Neo Dashboard
jQuery.post(ajaxurl, {
    action: 'neo_dashboard_ajax',
    route: 'my_ajax_action',
    nonce: neo_dashboard_ajax.nonce,
    data: { /* ваши данные */ }
}, function(response) {
    if (response.success) {
        console.log('Success:', response.data);
    } else {
        console.log('Error:', response.data);
    }
});
```

## REST API

Neo Dashboard предоставляет REST API endpoints:

```
GET    /wp-json/neo-dashboard/v1/notifications     # Получить уведомления
POST   /wp-json/neo-dashboard/v1/notifications/{id}/dismiss  # Закрыть уведомление
```

## Security & Access Control

### Система ролей

Neo Dashboard имеет собственную систему ролей:

- `neo_admin` - Полный доступ к системе
- `neo_manager` - Управление контентом
- `neo_user` - Базовый доступ

### Проверка доступа

```php
// Проверка роли пользователя
$user = wp_get_current_user();
$hasAccess = \NeoDashboard\Core\Helper::user_has_access($user, ['neo_admin', 'administrator']);

// Проверка capability
if (current_user_can('neo_dashboard_access')) {
    // Пользователь имеет доступ к дашборду
}
```

### Security Enforcer

Автоматическая защита:
- CSRF защита через nonce
- Валидация прав доступа
- Санитизация входных данных
- Ограничение прямого доступа к файлам

## Routing System

### URL структура

```
/neo-dashboard/                    # Главная страница
/neo-dashboard/{section}           # Страница секции
/neo-dashboard/{section}/{action}  # Подстраница секции
```

### Получение текущей секции

```php
$current_section = get_query_var('neo_section', '');
```

## Template System

### Основные шаблоны

```
templates/
├── dashboard-layout.php     # Основной макет дашборда
├── dashboard-blank.php      # Blank шаблон без WordPress тем
├── access-denied.php        # Страница отказа в доступе
└── partials/
    ├── navbar.php          # Верхняя навигация
    ├── desktop-sidebar.php # Боковое меню (десктоп)
    ├── offcanvas-sidebar.php # Боковое меню (мобильная)
    ├── notifications.php   # Блок уведомлений
    ├── sections.php       # Рендеринг активной секции
    └── widgets.php        # Сетка виджетов
```

### Переопределение шаблонов

```php
// В теме: neo-dashboard/templates/my-custom-template.php
$template_path = locate_template('neo-dashboard/templates/dashboard-layout.php');
if (!$template_path) {
    $template_path = NEO_DASHBOARD_TEMPLATE_PATH . 'dashboard-layout.php';
}
```

## Lifecycle & Events

### Основные хуки

```php
// Инициализация системы
add_action('neo_dashboard_init', function() {
    // Здесь регистрируются все компоненты
});

// Загрузка ресурсов в head
add_action('neo_dashboard_head', function() {
    // CSS загрузка
});

// Загрузка ресурсов в footer
add_action('neo_dashboard_footer', function() {
    // JS загрузка
});

// Рендеринг контента
add_action('neo_dashboard_body_content', function() {
    // Основное содержимое дашборда
});
```

### Хуки для плагинов

```php
// Регистрация компонентов (выполняется на neo_dashboard_init)
do_action('neo_dashboard_register_section', $args);
do_action('neo_dashboard_register_sidebar_item', $args);
do_action('neo_dashboard_register_widget', $args);
do_action('neo_dashboard_register_notification', $args);
do_action('neo_dashboard_register_plugin_assets', $plugin_name, $assets);
do_action('neo_dashboard_register_page_assets', $page, $type, $asset);
do_action('neo_dashboard_register_ajax_route', $args);

// REST API
do_action('neo_dashboard_register_rest_routes');
```

## Логирование и отладка

### Logger система

```php
use NeoDashboard\Core\Logger;

// Уровни логирования
Logger::info('Message', ['context' => 'data']);
Logger::warning('Warning message');
Logger::error('Error message');
Logger::debug('Debug info');
```

### Отладка в development

```php
// В wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Просмотр логов Neo Dashboard
tail -f wp-content/debug.log | grep "Neo\|Registry\|AssetManager"
```

## Примеры интеграции

### Простой плагин

```php
<?php
/**
 * Plugin Name: Simple Dashboard Plugin
 */

add_action('neo_dashboard_init', function() {
    // Регистрируем секцию
    do_action('neo_dashboard_register_section', [
        'slug' => 'simple-plugin',
        'label' => 'Simple Plugin',
        'callback' => function() {
            echo '<h3>My Simple Plugin</h3>';
            echo '<p>Content goes here...</p>';
        },
        'roles' => null
    ]);
    
    // Добавляем в меню
    do_action('neo_dashboard_register_sidebar_item', [
        'slug' => 'simple-plugin',
        'label' => 'Simple Plugin',
        'icon' => 'bi-puzzle',
        'url' => '/neo-dashboard/simple-plugin',
        'position' => 20,
        'roles' => null
    ]);
    
    // Добавляем стили
    do_action('neo_dashboard_register_plugin_assets', 'simple-plugin', [
        'css' => [
            'simple-plugin-style' => [
                'src' => plugin_dir_url(__FILE__) . 'style.css',
                'contexts' => ['simple-plugin']
            ]
        ]
    ]);
});
```

### Сложный плагин с подстраницами

```php
<?php
/**
 * Plugin Name: Advanced CRM Plugin
 */

class AdvancedCRMPlugin {
    public function __construct() {
        add_action('neo_dashboard_init', [$this, 'register_components']);
    }
    
    public function register_components() {
        // Главная секция CRM
        do_action('neo_dashboard_register_section', [
            'slug' => 'crm',
            'label' => 'CRM Dashboard',
            'callback' => [$this, 'render_dashboard'],
            'roles' => ['neo_manager', 'administrator']
        ]);
        
        // Подсекции
        $subsections = [
            'crm/contacts' => 'Контакты',
            'crm/companies' => 'Компании',
            'crm/reports' => 'Отчеты'
        ];
        
        foreach ($subsections as $slug => $label) {
            do_action('neo_dashboard_register_section', [
                'slug' => $slug,
                'label' => $label,
                'callback' => [$this, 'render_' . str_replace('crm/', '', $slug)],
                'roles' => ['neo_manager', 'administrator']
            ]);
        }
        
        // Группа в меню
        do_action('neo_dashboard_register_sidebar_item', [
            'slug' => 'crm-group',
            'label' => 'CRM',
            'icon' => 'bi-people',
            'url' => '/neo-dashboard/crm',
            'position' => 10,
            'is_group' => true,
            'roles' => ['neo_manager', 'administrator']
        ]);
        
        // Подпункты меню
        do_action('neo_dashboard_register_sidebar_item', [
            'slug' => 'crm-contacts',
            'label' => 'Контакты',
            'icon' => 'bi-person-lines-fill',
            'url' => '/neo-dashboard/crm/contacts',
            'parent' => 'crm-group',
            'roles' => ['neo_manager', 'administrator']
        ]);
        
        // Ресурсы
        do_action('neo_dashboard_register_plugin_assets', 'crm', [
            'css' => [
                'crm-base' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/css/base.css',
                    'contexts' => ['crm', 'crm/contacts', 'crm/companies', 'crm/reports']
                ]
            ],
            'js' => [
                'crm-core' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/js/core.js',
                    'deps' => ['neo-dashboard-core'],
                    'contexts' => ['crm', 'crm/contacts', 'crm/companies', 'crm/reports']
                ]
            ]
        ]);
        
        // AJAX обработчики
        do_action('neo_dashboard_register_ajax_route', [
            'action' => 'crm_save_contact',
            'callback' => [$this, 'ajax_save_contact'],
            'capability' => 'neo_dashboard_access'
        ]);
    }
    
    public function render_dashboard() {
        echo '<div class="crm-dashboard">CRM Dashboard Content</div>';
    }
    
    public function render_contacts() {
        echo '<div class="crm-contacts">Contacts Management</div>';
    }
    
    public function ajax_save_contact() {
        // AJAX логика
        wp_send_json_success(['message' => 'Contact saved']);
    }
}

new AdvancedCRMPlugin();
```

## Troubleshooting

### Частые проблемы

1. **Секция не отображается**
   - Проверьте роли пользователя
   - Убедитесь что callback или template_path указан
   - Проверьте логи регистрации

2. **Пункт меню отсутствует**
   - Нужна отдельная регистрация через `neo_dashboard_register_sidebar_item`
   - Проверьте права доступа

3. **Ресурсы не загружаются**
   - Проверьте правильность путей к файлам
   - Убедитесь что contexts указаны верно
   - Проверьте зависимости

4. **AJAX не работает**
   - Проверьте nonce
   - Убедитесь что route зарегистрирован
   - Проверьте права доступа

### Debug режим

```php
// Включить расширенную отладку
add_action('init', function() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        add_action('neo_dashboard_init', function() {
            $registry = \NeoDashboard\Core\Registry::instance();
            error_log('Registered sections: ' . print_r($registry->getSections(), true));
            error_log('Registered sidebar: ' . print_r($registry->getSidebarItems(), true));
        });
    }
});
```

## Заключение

Neo Dashboard предоставляет мощную и гибкую архитектуру для создания кастомных дашбордов в WordPress. Модульная система позволяет легко расширять функциональность через плагины, сохраняя при этом производительность и безопасность.

Для получения дополнительной информации изучите исходный код в `/src/` директории и примеры в `/docs/` папке.