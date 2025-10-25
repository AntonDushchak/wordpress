# Neo Dashboard Quick Reference

## 🚀 Быстрый старт

### Базовая интеграция плагина

```php
add_action('neo_dashboard_init', function() {
    // 1. Регистрируем секцию (страницу)
    do_action('neo_dashboard_register_section', [
        'slug' => 'my-plugin',
        'label' => 'My Plugin',
        'callback' => function() { echo '<h3>Content</h3>'; },
        'roles' => null // null = доступно всем
    ]);
    
    // 2. Добавляем в меню
    do_action('neo_dashboard_register_sidebar_item', [
        'slug' => 'my-plugin',
        'label' => 'My Plugin',
        'icon' => 'bi-puzzle',
        'url' => '/neo-dashboard/my-plugin',
        'roles' => null
    ]);
    
    // 3. Подключаем стили/скрипты
    do_action('neo_dashboard_register_plugin_assets', 'my-plugin', [
        'css' => [
            'my-plugin-style' => [
                'src' => plugin_dir_url(__FILE__) . 'style.css',
                'contexts' => ['my-plugin']
            ]
        ]
    ]);
});
```

## 📋 API Reference

### Sections (Страницы)
```php
do_action('neo_dashboard_register_section', [
    'slug' => 'page-id',              // ✓ Обязательно
    'label' => 'Page Title',          // ✓ Обязательно  
    'callback' => 'my_function',      // ✓ Функция отображения
    'template_path' => '/path.php',   // Альтернатива callback
    'roles' => ['administrator']      // null = все роли
]);
```

### Sidebar Menu (Меню)
```php
do_action('neo_dashboard_register_sidebar_item', [
    'slug' => 'menu-id',              // ✓ Обязательно
    'label' => 'Menu Item',           // ✓ Обязательно
    'icon' => 'bi-house',             // ✓ Bootstrap Icons
    'url' => '/neo-dashboard/page',   // ✓ Обязательно
    'position' => 10,                 // Порядок сортировки
    'parent' => 'parent-slug',        // Для подменю
    'is_group' => false,              // Группа или элемент
    'roles' => null                   // Доступные роли
]);
```

### Widgets (Виджеты)
```php
do_action('neo_dashboard_register_widget', [
    'id' => 'widget-id',              // ✓ Обязательно
    'title' => 'Widget Title',        // Заголовок виджета
    'callback' => 'widget_function',  // ✓ Функция содержимого
    'priority' => 10,                 // Приоритет отображения
    'roles' => null                   // Доступные роли
]);
```

### Notifications (Уведомления)
```php
do_action('neo_dashboard_register_notification', [
    'id' => 'notification-id',        // ✓ Обязательно
    'message' => 'Hello World!',      // ✓ Текст уведомления
    'type' => 'info',                 // info|success|warning|error
    'dismissible' => true,            // Можно ли закрыть
    'priority' => 10,                 // Приоритет
    'expires' => time() + 3600,       // Время истечения
    'roles' => null                   // Доступные роли
]);
```

### Assets (CSS/JS)
```php
// Ресурсы плагина
do_action('neo_dashboard_register_plugin_assets', 'plugin-name', [
    'css' => [
        'handle' => [
            'src' => 'path/to/style.css',
            'deps' => ['neo-dashboard-core'],
            'version' => '1.0.0',
            'contexts' => ['page1', 'page2'] // На каких страницах
        ]
    ],
    'js' => [
        'handle' => [
            'src' => 'path/to/script.js',
            'deps' => ['neo-dashboard-core', 'jquery'],
            'version' => '1.0.0',
            'in_footer' => true,
            'contexts' => ['page1']
        ]
    ]
]);

// Ресурс для конкретной страницы
do_action('neo_dashboard_register_page_assets', 'page-name', 'css', [
    'handle' => 'page-style',
    'src' => 'path/to/page.css',
    'deps' => ['neo-dashboard-core']
]);
```

### AJAX Routes
```php
do_action('neo_dashboard_register_ajax_route', [
    'action' => 'my_ajax_action',     // ✓ Имя действия
    'callback' => 'my_ajax_callback', // ✓ Функция обработчик
    'capability' => 'manage_options', // Требуемые права
    'nonce_action' => 'my_nonce'     // Nonce действие
]);

// В JS:
jQuery.post(ajaxurl, {
    action: 'neo_dashboard_ajax',
    route: 'my_ajax_action',
    nonce: neo_dashboard_ajax.nonce,
    data: { key: 'value' }
});
```

## 🔧 Прямые методы (v4.0.0)

```php
add_action('neo_dashboard_init', function() {
    $assetManager = \NeoDashboard\Core\Registry::instance()->getAssetManager();
    
    // Прямая регистрация ресурсов
    $assetManager->registerPluginAssets('plugin-name', $assets);
    $assetManager->registerPageAssets('page', 'css', $asset);
    
    // Получить Registry
    $registry = \NeoDashboard\Core\Registry::instance();
    $sections = $registry->getSections();
    $sidebar = $registry->getSidebarTree();
});
```

## 🎨 Bootstrap Icons

Популярные иконки для меню:
```
bi-house           # Главная
bi-people          # Пользователи  
bi-gear            # Настройки
bi-chart-bar       # Аналитика
bi-calendar        # Календарь
bi-envelope        # Почта
bi-file-text       # Документы
bi-puzzle          # Плагины
bi-shield          # Безопасность
bi-tools           # Инструменты
```

## 🛡️ Security & Roles

### Доступные роли
```
null                    # Все пользователи
['administrator']       # Только администраторы  
['neo_admin']          # Neo админы
['neo_manager']        # Neo менеджеры
['neo_user']           # Neo пользователи
['administrator', 'editor'] # Несколько ролей
```

### Проверка доступа
```php
$user = wp_get_current_user();
$hasAccess = \NeoDashboard\Core\Helper::user_has_access($user, ['administrator']);

if (current_user_can('neo_dashboard_access')) {
    // Есть доступ к дашборду
}
```

## 📍 URL Structure

```
/neo-dashboard/                    # Главная
/neo-dashboard/my-section          # Секция
/neo-dashboard/my-section/action   # Подсекция
```

## 🔍 Debugging

```php
// Включить логи
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Просмотр логов
tail -f wp-content/debug.log | grep "Neo\|Registry"

// Debug информация
add_action('neo_dashboard_init', function() {
    $registry = \NeoDashboard\Core\Registry::instance();
    error_log('Sections: ' . json_encode(array_keys($registry->getSections())));
});
```

## ⚡ Troubleshooting

| Проблема | Решение |
|----------|---------|
| Секция не видна | Проверьте `roles` и добавьте `callback` |
| Нет пункта в меню | Нужен отдельный `register_sidebar_item` |
| CSS не загружается | Проверьте `contexts` и пути к файлам |
| AJAX не работает | Проверьте nonce и регистрацию route |

## 📁 Структура файлов плагина

```
my-plugin/
├── my-plugin.php          # Главный файл
├── assets/
│   ├── css/
│   │   ├── core.css       # Базовые стили
│   │   └── admin.css      # Админские стили  
│   └── js/
│       ├── core.js        # Базовые скрипты
│       └── admin.js       # Админские скрипты
├── templates/
│   ├── dashboard.php      # Главная страница
│   └── settings.php       # Настройки
└── includes/
    └── functions.php      # Вспомогательные функции
```

## 🚨 Важные моменты

1. **Всегда используйте хук `neo_dashboard_init`** для регистрации компонентов
2. **Секции и меню регистрируются отдельно** - одно не создает другое автоматически  
3. **Роли `null`** означает доступ для всех пользователей
4. **Contexts в assets** определяют на каких страницах загружать ресурсы
5. **Используйте префиксы** в handle'ах для избежания конфликтов

---
*Полная документация: `NEO_DASHBOARD_API.md`*