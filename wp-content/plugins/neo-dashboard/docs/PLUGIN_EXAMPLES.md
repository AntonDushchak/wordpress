# Neo Dashboard Plugin Examples

## Содержание
1. [Простой плагин с одной страницей](#простой-плагин)
2. [Плагин с подстраницами](#плагин-с-подстраницами)  
3. [Плагин с виджетами](#плагин-с-виджетами)
4. [Плагин с AJAX](#плагин-с-ajax)
5. [Плагин с уведомлениями](#плагин-с-уведомлениями)
6. [Сложный CRM плагин](#сложный-crm-плагин)

---

## Простой плагин

Базовый плагин с одной страницей и стилями.

### simple-dashboard-plugin.php
```php
<?php
/**
 * Plugin Name: Simple Dashboard Plugin
 * Description: Простой плагин для Neo Dashboard
 * Version: 1.0.0
 */

defined('ABSPATH') || exit;

class SimpleDashboardPlugin {
    
    public function __construct() {
        add_action('neo_dashboard_init', [$this, 'register_components']);
    }
    
    public function register_components() {
        // Регистрируем страницу
        do_action('neo_dashboard_register_section', [
            'slug' => 'simple-plugin',
            'label' => 'Simple Plugin',
            'callback' => [$this, 'render_page'],
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
        
        // Подключаем стили
        do_action('neo_dashboard_register_plugin_assets', 'simple-plugin', [
            'css' => [
                'simple-plugin-style' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/style.css',
                    'contexts' => ['simple-plugin']
                ]
            ]
        ]);
    }
    
    public function render_page() {
        ?>
        <div class="simple-plugin-container">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Simple Plugin Dashboard</h3>
                </div>
                <div class="card-body">
                    <p>Добро пожаловать в Simple Plugin!</p>
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Статистика</h5>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between">
                                    Всего элементов <span class="badge bg-primary">42</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    Активных <span class="badge bg-success">38</span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Быстрые действия</h5>
                            <button class="btn btn-primary mb-2">Создать новый</button><br>
                            <button class="btn btn-secondary mb-2">Импорт данных</button><br>
                            <button class="btn btn-outline-info">Настройки</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

new SimpleDashboardPlugin();
```

### assets/style.css
```css
.simple-plugin-container {
    max-width: 1200px;
}

.simple-plugin-container .card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.simple-plugin-container .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
```

---

## Плагин с подстраницами

Плагин с несколькими разделами и группировкой в меню.

### multi-page-plugin.php
```php
<?php
/**
 * Plugin Name: Multi Page Plugin
 * Description: Плагин с несколькими страницами
 * Version: 1.0.0
 */

defined('ABSPATH') || exit;

class MultiPagePlugin {
    
    public function __construct() {
        add_action('neo_dashboard_init', [$this, 'register_components']);
    }
    
    public function register_components() {
        // Регистрируем все страницы
        $pages = [
            'multi-plugin' => 'Multi Plugin',
            'multi-plugin/users' => 'Пользователи',
            'multi-plugin/reports' => 'Отчеты',
            'multi-plugin/settings' => 'Настройки'
        ];
        
        foreach ($pages as $slug => $label) {
            do_action('neo_dashboard_register_section', [
                'slug' => $slug,
                'label' => $label,
                'callback' => [$this, 'render_' . str_replace(['multi-plugin/', 'multi-plugin'], ['', 'dashboard'], $slug)],
                'roles' => null
            ]);
        }
        
        // Создаем группу в меню
        do_action('neo_dashboard_register_sidebar_item', [
            'slug' => 'multi-plugin-group',
            'label' => 'Multi Plugin',
            'icon' => 'bi-layers',
            'url' => '/neo-dashboard/multi-plugin',
            'position' => 15,
            'is_group' => true,
            'roles' => null
        ]);
        
        // Подпункты меню
        $menu_items = [
            'multi-plugin-dashboard' => ['Dashboard', 'bi-house', '/neo-dashboard/multi-plugin'],
            'multi-plugin-users' => ['Пользователи', 'bi-people', '/neo-dashboard/multi-plugin/users'],
            'multi-plugin-reports' => ['Отчеты', 'bi-chart-bar', '/neo-dashboard/multi-plugin/reports'],
            'multi-plugin-settings' => ['Настройки', 'bi-gear', '/neo-dashboard/multi-plugin/settings']
        ];
        
        foreach ($menu_items as $slug => [$label, $icon, $url]) {
            do_action('neo_dashboard_register_sidebar_item', [
                'slug' => $slug,
                'label' => $label,
                'icon' => $icon,
                'url' => $url,
                'parent' => 'multi-plugin-group',
                'roles' => null
            ]);
        }
        
        // Общие стили для всех страниц
        do_action('neo_dashboard_register_plugin_assets', 'multi-plugin', [
            'css' => [
                'multi-plugin-base' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/css/base.css',
                    'contexts' => ['multi-plugin', 'multi-plugin/users', 'multi-plugin/reports', 'multi-plugin/settings']
                ]
            ]
        ]);
        
        // Стили только для отчетов (графики)
        do_action('neo_dashboard_register_page_assets', 'multi-plugin/reports', 'css', [
            'handle' => 'multi-plugin-charts',
            'src' => plugin_dir_url(__FILE__) . 'assets/css/charts.css'
        ]);
    }
    
    public function render_dashboard() {
        echo '<h3>Multi Plugin Dashboard</h3>';
        echo '<p>Главная страница Multi Plugin</p>';
    }
    
    public function render_users() {
        ?>
        <h3>Управление пользователями</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>John Doe</td>
                        <td>john@example.com</td>
                        <td><span class="badge bg-success">Активен</span></td>
                        <td>
                            <button class="btn btn-sm btn-primary">Редактировать</button>
                            <button class="btn btn-sm btn-danger">Удалить</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function render_reports() {
        ?>
        <h3>Отчеты и аналитика</h3>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Статистика за месяц</h5>
                        <canvas id="monthlyChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Топ категории</h5>
                        <canvas id="categoryChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function render_settings() {
        ?>
        <h3>Настройки плагина</h3>
        <form class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Название сайта</label>
                <input type="text" class="form-control" value="My Website">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email администратора</label>
                <input type="email" class="form-control" value="admin@site.com">
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" checked>
                    <label class="form-check-label">Включить уведомления</label>
                </div>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Сохранить настройки</button>
            </div>
        </form>
        <?php
    }
}

new MultiPagePlugin();
```

---

## Плагин с виджетами

Плагин, который добавляет виджеты на главную страницу дашборда.

### widget-plugin.php
```php
<?php
/**
 * Plugin Name: Widget Plugin
 * Description: Плагин с виджетами для дашборда
 * Version: 1.0.0
 */

defined('ABSPATH') || exit;

class WidgetPlugin {
    
    public function __construct() {
        add_action('neo_dashboard_init', [$this, 'register_components']);
    }
    
    public function register_components() {
        // Регистрируем виджеты
        do_action('neo_dashboard_register_widget', [
            'id' => 'stats-widget',
            'title' => 'Статистика сайта',
            'callback' => [$this, 'render_stats_widget'],
            'priority' => 5,
            'roles' => null
        ]);
        
        do_action('neo_dashboard_register_widget', [
            'id' => 'recent-posts-widget',
            'title' => 'Последние записи',
            'callback' => [$this, 'render_recent_posts_widget'],
            'priority' => 10,
            'roles' => null
        ]);
        
        do_action('neo_dashboard_register_widget', [
            'id' => 'quick-actions-widget',
            'title' => 'Быстрые действия',
            'callback' => [$this, 'render_quick_actions_widget'],
            'priority' => 15,
            'roles' => ['administrator']
        ]);
        
        // Стили для виджетов
        do_action('neo_dashboard_register_plugin_assets', 'widget-plugin', [
            'css' => [
                'widget-plugin-styles' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/widgets.css',
                    'contexts' => ['dashboard-home'] // Только на главной
                ]
            ]
        ]);
    }
    
    public function render_stats_widget() {
        $posts_count = wp_count_posts()->publish;
        $users_count = count_users()['total_users'];
        $comments_count = wp_count_comments()->approved;
        ?>
        <div class="stats-grid">
            <div class="stat-item">
                <i class="bi-file-post text-primary"></i>
                <div>
                    <div class="stat-number"><?php echo $posts_count; ?></div>
                    <div class="stat-label">Записей</div>
                </div>
            </div>
            <div class="stat-item">
                <i class="bi-people text-success"></i>
                <div>
                    <div class="stat-number"><?php echo $users_count; ?></div>
                    <div class="stat-label">Пользователей</div>
                </div>
            </div>
            <div class="stat-item">
                <i class="bi-chat-dots text-info"></i>
                <div>
                    <div class="stat-number"><?php echo $comments_count; ?></div>
                    <div class="stat-label">Комментариев</div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function render_recent_posts_widget() {
        $posts = get_posts(['numberposts' => 5, 'post_status' => 'publish']);
        ?>
        <div class="recent-posts-list">
            <?php foreach ($posts as $post): ?>
                <div class="recent-post-item">
                    <h6><a href="<?php echo get_permalink($post->ID); ?>" target="_blank">
                        <?php echo esc_html($post->post_title); ?>
                    </a></h6>
                    <small class="text-muted">
                        <?php echo date('d.m.Y', strtotime($post->post_date)); ?>
                    </small>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="/wp-admin/edit.php" class="btn btn-sm btn-outline-primary mt-2">
            Все записи
        </a>
        <?php
    }
    
    public function render_quick_actions_widget() {
        ?>
        <div class="quick-actions-grid">
            <a href="/wp-admin/post-new.php" class="quick-action-btn">
                <i class="bi-plus-circle"></i>
                Новая запись
            </a>
            <a href="/wp-admin/upload.php" class="quick-action-btn">
                <i class="bi-image"></i>
                Медиафайлы
            </a>
            <a href="/wp-admin/users.php" class="quick-action-btn">
                <i class="bi-people"></i>
                Пользователи
            </a>
            <a href="/wp-admin/options-general.php" class="quick-action-btn">
                <i class="bi-gear"></i>
                Настройки
            </a>
        </div>
        <?php
    }
}

new WidgetPlugin();
```

### assets/widgets.css
```css
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-item i {
    font-size: 24px;
}

.stat-number {
    font-size: 20px;
    font-weight: bold;
    line-height: 1;
}

.stat-label {
    font-size: 12px;
    color: #666;
}

.recent-post-item {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.recent-post-item:last-child {
    border-bottom: none;
}

.recent-post-item h6 {
    margin: 0 0 4px 0;
    font-size: 14px;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px;
    text-decoration: none;
    background: #fff;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    transition: all 0.2s;
}

.quick-action-btn:hover {
    border-color: #007bff;
    transform: translateY(-2px);
}

.quick-action-btn i {
    font-size: 24px;
    margin-bottom: 8px;
}
```

---

## Плагин с AJAX

Плагин с динамическим содержимым и AJAX взаимодействием.

### ajax-plugin.php
```php
<?php
/**
 * Plugin Name: Ajax Plugin
 * Description: Плагин с AJAX функциональностью
 * Version: 1.0.0
 */

defined('ABSPATH') || exit;

class AjaxPlugin {
    
    public function __construct() {
        add_action('neo_dashboard_init', [$this, 'register_components']);
    }
    
    public function register_components() {
        // Регистрируем секцию
        do_action('neo_dashboard_register_section', [
            'slug' => 'ajax-plugin',
            'label' => 'Ajax Plugin',
            'callback' => [$this, 'render_page'],
            'roles' => null
        ]);
        
        // Меню
        do_action('neo_dashboard_register_sidebar_item', [
            'slug' => 'ajax-plugin',
            'label' => 'Ajax Plugin',
            'icon' => 'bi-lightning',
            'url' => '/neo-dashboard/ajax-plugin',
            'roles' => null
        ]);
        
        // AJAX роуты
        do_action('neo_dashboard_register_ajax_route', [
            'action' => 'load_data',
            'callback' => [$this, 'ajax_load_data'],
            'capability' => 'read'
        ]);
        
        do_action('neo_dashboard_register_ajax_route', [
            'action' => 'save_item',
            'callback' => [$this, 'ajax_save_item'],
            'capability' => 'edit_posts'
        ]);
        
        do_action('neo_dashboard_register_ajax_route', [
            'action' => 'delete_item',
            'callback' => [$this, 'ajax_delete_item'],
            'capability' => 'delete_posts'
        ]);
        
        // Ресурсы
        do_action('neo_dashboard_register_plugin_assets', 'ajax-plugin', [
            'js' => [
                'ajax-plugin-script' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/ajax-script.js',
                    'deps' => ['neo-dashboard-core', 'jquery'],
                    'contexts' => ['ajax-plugin']
                ]
            ],
            'css' => [
                'ajax-plugin-style' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/ajax-style.css',
                    'contexts' => ['ajax-plugin']
                ]
            ]
        ]);
    }
    
    public function render_page() {
        ?>
        <div class="ajax-plugin-container">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Динамический список</h5>
                            <button class="btn btn-success btn-sm" id="add-item-btn">
                                <i class="bi-plus"></i> Добавить
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="items-container">
                                <div class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Загрузка...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Добавить элемент</h6>
                        </div>
                        <div class="card-body">
                            <form id="add-item-form">
                                <div class="mb-3">
                                    <label class="form-label">Название</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Описание</label>
                                    <textarea class="form-control" name="description" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    Сохранить
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Toast container -->
        <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>
        <?php
    }
    
    public function ajax_load_data() {
        // Получаем данные из базы или создаем тестовые
        $items = get_option('ajax_plugin_items', []);
        
        if (empty($items)) {
            $items = [
                ['id' => 1, 'title' => 'Тестовый элемент 1', 'description' => 'Описание первого элемента'],
                ['id' => 2, 'title' => 'Тестовый элемент 2', 'description' => 'Описание второго элемента']
            ];
        }
        
        wp_send_json_success($items);
    }
    
    public function ajax_save_item() {
        $title = sanitize_text_field($_POST['title'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        
        if (empty($title)) {
            wp_send_json_error('Название обязательно для заполнения');
        }
        
        $items = get_option('ajax_plugin_items', []);
        $new_id = empty($items) ? 1 : max(array_column($items, 'id')) + 1;
        
        $new_item = [
            'id' => $new_id,
            'title' => $title,
            'description' => $description,
            'created_at' => current_time('mysql')
        ];
        
        $items[] = $new_item;
        update_option('ajax_plugin_items', $items);
        
        wp_send_json_success([
            'message' => 'Элемент успешно добавлен',
            'item' => $new_item
        ]);
    }
    
    public function ajax_delete_item() {
        $item_id = intval($_POST['item_id'] ?? 0);
        
        if (!$item_id) {
            wp_send_json_error('Неверный ID элемента');
        }
        
        $items = get_option('ajax_plugin_items', []);
        $items = array_filter($items, fn($item) => $item['id'] !== $item_id);
        
        update_option('ajax_plugin_items', array_values($items));
        
        wp_send_json_success(['message' => 'Элемент удален']);
    }
}

new AjaxPlugin();
```

### assets/ajax-script.js
```javascript
(function($) {
    'use strict';
    
    let AjaxPlugin = {
        init: function() {
            this.loadItems();
            this.bindEvents();
        },
        
        bindEvents: function() {
            $('#add-item-form').on('submit', this.handleAddItem.bind(this));
            $(document).on('click', '.delete-item-btn', this.handleDeleteItem.bind(this));
        },
        
        loadItems: function() {
            $.post(ajaxurl, {
                action: 'neo_dashboard_ajax',
                route: 'load_data',
                nonce: neo_dashboard_ajax.nonce
            }, (response) => {
                if (response.success) {
                    this.renderItems(response.data);
                } else {
                    this.showToast('Ошибка загрузки данных', 'error');
                }
            });
        },
        
        renderItems: function(items) {
            let html = '';
            
            if (items.length === 0) {
                html = '<div class="text-center text-muted">Нет элементов</div>';
            } else {
                items.forEach(item => {
                    html += `
                        <div class="item-row" data-id="${item.id}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">${item.title}</h6>
                                    <p class="mb-0 text-muted">${item.description || ''}</p>
                                </div>
                                <button class="btn btn-sm btn-outline-danger delete-item-btn" data-id="${item.id}">
                                    <i class="bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
            }
            
            $('#items-container').html(html);
        },
        
        handleAddItem: function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'neo_dashboard_ajax');
            formData.append('route', 'save_item');
            formData.append('nonce', neo_dashboard_ajax.nonce);
            
            $.post(ajaxurl, Object.fromEntries(formData), (response) => {
                if (response.success) {
                    this.showToast(response.data.message, 'success');
                    $('#add-item-form')[0].reset();
                    this.loadItems(); // Перезагружаем список
                } else {
                    this.showToast(response.data, 'error');
                }
            });
        },
        
        handleDeleteItem: function(e) {
            const itemId = $(e.target).closest('.delete-item-btn').data('id');
            
            if (!confirm('Удалить этот элемент?')) {
                return;
            }
            
            $.post(ajaxurl, {
                action: 'neo_dashboard_ajax',
                route: 'delete_item',
                item_id: itemId,
                nonce: neo_dashboard_ajax.nonce
            }, (response) => {
                if (response.success) {
                    this.showToast(response.data.message, 'success');
                    $(`.item-row[data-id="${itemId}"]`).fadeOut(() => {
                        this.loadItems();
                    });
                } else {
                    this.showToast(response.data, 'error');
                }
            });
        },
        
        showToast: function(message, type = 'info') {
            const bgClass = {
                'success': 'bg-success',
                'error': 'bg-danger', 
                'warning': 'bg-warning',
                'info': 'bg-info'
            }[type] || 'bg-info';
            
            const toast = $(`
                <div class="toast" role="alert">
                    <div class="toast-body ${bgClass} text-white">
                        ${message}
                    </div>
                </div>
            `);
            
            $('.toast-container').append(toast);
            
            const bsToast = new bootstrap.Toast(toast[0]);
            bsToast.show();
            
            toast.on('hidden.bs.toast', function() {
                $(this).remove();
            });
        }
    };
    
    $(document).ready(function() {
        AjaxPlugin.init();
    });
    
})(jQuery);
```

---

## Плагин с уведомлениями

Плагин, который добавляет различные типы уведомлений.

### notification-plugin.php
```php
<?php
/**
 * Plugin Name: Notification Plugin
 * Description: Плагин с системой уведомлений
 * Version: 1.0.0
 */

defined('ABSPATH') || exit;

class NotificationPlugin {
    
    public function __construct() {
        add_action('neo_dashboard_init', [$this, 'register_components']);
    }
    
    public function register_components() {
        // Разные типы уведомлений
        do_action('neo_dashboard_register_notification', [
            'id' => 'welcome-message',
            'message' => 'Добро пожаловать в Neo Dashboard! Настройте свой профиль для начала работы.',
            'type' => 'info',
            'dismissible' => true,
            'priority' => 1,
            'roles' => null
        ]);
        
        do_action('neo_dashboard_register_notification', [
            'id' => 'update-available',
            'message' => 'Доступно обновление системы до версии 2.0! <a href="#" class="alert-link">Обновить сейчас</a>',
            'type' => 'warning',
            'dismissible' => true,
            'priority' => 5,
            'roles' => ['administrator']
        ]);
        
        // Временное уведомление (истекает через час)
        do_action('neo_dashboard_register_notification', [
            'id' => 'maintenance-notice',
            'message' => 'Запланированные технические работы с 02:00 до 04:00 МСК.',
            'type' => 'error',
            'dismissible' => false,
            'priority' => 10,
            'expires' => time() + 3600, // Через час
            'roles' => null
        ]);
        
        // Уведомление об успехе (только для менеджеров)
        do_action('neo_dashboard_register_notification', [
            'id' => 'monthly-report',
            'message' => 'Месячный отчет готов! Просмотрите статистику и KPI.',
            'type' => 'success',
            'dismissible' => true,
            'priority' => 3,
            'roles' => ['neo_manager', 'administrator']
        ]);
        
        // Страница управления уведомлениями (для админов)
        do_action('neo_dashboard_register_section', [
            'slug' => 'notifications-manager',
            'label' => 'Управление уведомлениями',
            'callback' => [$this, 'render_manager_page'],
            'roles' => ['administrator']
        ]);
        
        do_action('neo_dashboard_register_sidebar_item', [
            'slug' => 'notifications-manager',
            'label' => 'Уведомления',
            'icon' => 'bi-bell',
            'url' => '/neo-dashboard/notifications-manager',
            'roles' => ['administrator']
        ]);
    }
    
    public function render_manager_page() {
        ?>
        <div class="notifications-manager">
            <h3>Управление уведомлениями</h3>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Добавить новое уведомление</h5>
                </div>
                <div class="card-body">
                    <form class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Сообщение</label>
                            <textarea class="form-control" rows="3" placeholder="Текст уведомления..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Тип</label>
                            <select class="form-select">
                                <option value="info">Информация</option>
                                <option value="success">Успех</option>
                                <option value="warning">Предупреждение</option>
                                <option value="error">Ошибка</option>
                            </select>
                            
                            <label class="form-label mt-3">Роли</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="administrator">
                                <label class="form-check-label">Администраторы</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="neo_manager">
                                <label class="form-check-label">Менеджеры</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="">
                                <label class="form-check-label">Все пользователи</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox">
                                <label class="form-check-label">Можно закрыть</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Создать уведомление</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Активные уведомления</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info alert-dismissible">
                        <i class="bi-info-circle me-2"></i>
                        Добро пожаловать в Neo Dashboard! Настройте свой профиль для начала работы.
                        <button type="button" class="btn-close"></button>
                    </div>
                    
                    <div class="alert alert-warning alert-dismissible">
                        <i class="bi-exclamation-triangle me-2"></i>
                        Доступно обновление системы до версии 2.0! <a href="#" class="alert-link">Обновить сейчас</a>
                        <button type="button" class="btn-close"></button>
                    </div>
                    
                    <div class="alert alert-danger">
                        <i class="bi-x-circle me-2"></i>
                        Запланированные технические работы с 02:00 до 04:00 МСК.
                        <small class="d-block mt-1">Нельзя закрыть</small>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

new NotificationPlugin();
```

---

## Сложный CRM плагин

Полнофункциональный CRM плагин с множественными страницами, AJAX, виджетами.

### crm-plugin.php
```php
<?php
/**
 * Plugin Name: Neo CRM Plugin
 * Description: Полнофункциональный CRM для Neo Dashboard
 * Version: 1.0.0
 */

defined('ABSPATH') || exit;

class NeoCRMPlugin {
    
    public function __construct() {
        add_action('neo_dashboard_init', [$this, 'register_components']);
        add_action('init', [$this, 'create_tables']);
    }
    
    public function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crm_contacts';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20),
            company varchar(100),
            status varchar(20) DEFAULT 'new',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function register_components() {
        // Секции CRM
        $sections = [
            'crm' => 'CRM Dashboard',
            'crm/contacts' => 'Контакты',
            'crm/companies' => 'Компании',
            'crm/deals' => 'Сделки',
            'crm/reports' => 'Отчеты',
            'crm/settings' => 'Настройки'
        ];
        
        foreach ($sections as $slug => $label) {
            do_action('neo_dashboard_register_section', [
                'slug' => $slug,
                'label' => $label,
                'callback' => [$this, 'render_' . str_replace(['crm/', 'crm'], ['', 'dashboard'], $slug)],
                'roles' => ['neo_manager', 'administrator']
            ]);
        }
        
        // Группа CRM в меню
        do_action('neo_dashboard_register_sidebar_item', [
            'slug' => 'crm-group',
            'label' => 'CRM',
            'icon' => 'bi-building',
            'url' => '/neo-dashboard/crm',
            'position' => 5,
            'is_group' => true,
            'roles' => ['neo_manager', 'administrator']
        ]);
        
        // Подпункты меню
        $menu_items = [
            ['crm-dashboard', 'Dashboard', 'bi-speedometer2', '/neo-dashboard/crm'],
            ['crm-contacts', 'Контакты', 'bi-people', '/neo-dashboard/crm/contacts'],
            ['crm-companies', 'Компании', 'bi-building', '/neo-dashboard/crm/companies'],
            ['crm-deals', 'Сделки', 'bi-currency-dollar', '/neo-dashboard/crm/deals'],
            ['crm-reports', 'Отчеты', 'bi-graph-up', '/neo-dashboard/crm/reports'],
            ['crm-settings', 'Настройки', 'bi-gear', '/neo-dashboard/crm/settings']
        ];
        
        foreach ($menu_items as [$slug, $label, $icon, $url]) {
            do_action('neo_dashboard_register_sidebar_item', [
                'slug' => $slug,
                'label' => $label,
                'icon' => $icon,
                'url' => $url,
                'parent' => 'crm-group',
                'roles' => ['neo_manager', 'administrator']
            ]);
        }
        
        // Виджеты для главной страницы дашборда
        do_action('neo_dashboard_register_widget', [
            'id' => 'crm-stats-widget',
            'title' => 'CRM Статистика',
            'callback' => [$this, 'render_stats_widget'],
            'priority' => 5,
            'roles' => ['neo_manager', 'administrator']
        ]);
        
        do_action('neo_dashboard_register_widget', [
            'id' => 'crm-recent-contacts',
            'title' => 'Новые контакты',
            'callback' => [$this, 'render_recent_contacts_widget'],
            'priority' => 10,
            'roles' => ['neo_manager', 'administrator']
        ]);
        
        // AJAX маршруты
        $ajax_routes = [
            'crm_get_contacts' => 'ajax_get_contacts',
            'crm_save_contact' => 'ajax_save_contact',
            'crm_delete_contact' => 'ajax_delete_contact',
            'crm_get_stats' => 'ajax_get_stats'
        ];
        
        foreach ($ajax_routes as $action => $callback) {
            do_action('neo_dashboard_register_ajax_route', [
                'action' => $action,
                'callback' => [$this, $callback],
                'capability' => 'neo_dashboard_access'
            ]);
        }
        
        // Ресурсы
        do_action('neo_dashboard_register_plugin_assets', 'crm', [
            'css' => [
                'crm-base' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/css/crm-base.css',
                    'contexts' => ['crm', 'crm/contacts', 'crm/companies', 'crm/deals', 'crm/reports', 'crm/settings', 'dashboard-home']
                ]
            ],
            'js' => [
                'crm-core' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/js/crm-core.js',
                    'deps' => ['neo-dashboard-core', 'jquery'],
                    'contexts' => ['crm', 'crm/contacts', 'crm/companies', 'crm/deals', 'crm/reports']
                ]
            ]
        ]);
        
        // Специфичные ресурсы для отчетов
        do_action('neo_dashboard_register_page_assets', 'crm/reports', 'js', [
            'handle' => 'crm-charts',
            'src' => 'https://cdn.jsdelivr.net/npm/chart.js',
            'deps' => []
        ]);
    }
    
    // Рендеринг секций
    public function render_dashboard() {
        ?>
        <div class="crm-dashboard">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>CRM Dashboard</h3>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addContactModal">
                        <i class="bi-person-plus"></i> Добавить контакт
                    </button>
                </div>
            </div>
            
            <!-- Быстрая статистика -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary"><i class="bi-people"></i></div>
                        <div class="stat-content">
                            <h4 id="total-contacts">-</h4>
                            <p>Всего контактов</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success"><i class="bi-check-circle"></i></div>
                        <div class="stat-content">
                            <h4 id="active-deals">-</h4>
                            <p>Активные сделки</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning"><i class="bi-currency-dollar"></i></div>
                        <div class="stat-content">
                            <h4 id="monthly-revenue">-</h4>
                            <p>Доход за месяц</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info"><i class="bi-graph-up"></i></div>
                        <div class="stat-content">
                            <h4 id="conversion-rate">-</h4>
                            <p>Конверсия</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Последние активности -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Последние контакты</h6>
                        </div>
                        <div class="card-body" id="recent-contacts">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Предстоящие задачи</h6>
                        </div>
                        <div class="card-body">
                            <div class="task-item">
                                <i class="bi-telephone text-primary"></i>
                                Звонок клиенту АВС Компани
                                <small class="text-muted">через 30 мин</small>
                            </div>
                            <div class="task-item">
                                <i class="bi-envelope text-info"></i>
                                Отправить предложение ООО "Рога и Копыта"
                                <small class="text-muted">сегодня 16:00</small>
                            </div>
                            <div class="task-item">
                                <i class="bi-calendar text-warning"></i>
                                Встреча с директором
                                <small class="text-muted">завтра 10:00</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php $this->render_add_contact_modal(); ?>
        <?php
    }
    
    public function render_contacts() {
        ?>
        <div class="crm-contacts">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Управление контактами</h3>
                <div>
                    <div class="input-group" style="width: 300px;">
                        <input type="text" class="form-control" placeholder="Поиск контактов..." id="search-contacts">
                        <button class="btn btn-outline-secondary">
                            <i class="bi-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Имя</th>
                                    <th>Email</th>
                                    <th>Телефон</th>
                                    <th>Компания</th>
                                    <th>Статус</th>
                                    <th>Дата создания</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody id="contacts-table-body">
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="spinner-border"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <?php $this->render_add_contact_modal(); ?>
        <?php
    }
    
    public function render_companies() {
        echo '<h3>Управление компаниями</h3><p>Раздел в разработке...</p>';
    }
    
    public function render_deals() {
        echo '<h3>Управление сделками</h3><p>Раздел в разработке...</p>';
    }
    
    public function render_reports() {
        ?>
        <div class="crm-reports">
            <h3>Отчеты и аналитика</h3>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Конверсия по месяцам</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="conversionChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Источники лидов</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="sourcesChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function render_settings() {
        ?>
        <h3>Настройки CRM</h3>
        <div class="card">
            <div class="card-body">
                <form class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Название компании</label>
                        <input type="text" class="form-control" value="My Company Ltd">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Валюта по умолчанию</label>
                        <select class="form-select">
                            <option>RUB - Российский рубль</option>
                            <option>USD - Доллар США</option>
                            <option>EUR - Евро</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" checked>
                            <label class="form-check-label">Отправлять email уведомления</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Сохранить настройки</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    // Виджеты
    public function render_stats_widget() {
        global $wpdb;
        $contacts_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}crm_contacts");
        ?>
        <div class="crm-stats-widget">
            <div class="row text-center">
                <div class="col-4">
                    <div class="stat-item">
                        <h4><?php echo $contacts_count; ?></h4>
                        <small>Контактов</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stat-item">
                        <h4>23</h4>
                        <small>Сделок</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stat-item">
                        <h4>₽125к</h4>
                        <small>Выручка</small>
                    </div>
                </div>
            </div>
            <hr>
            <a href="/neo-dashboard/crm" class="btn btn-sm btn-primary w-100">
                Открыть CRM
            </a>
        </div>
        <?php
    }
    
    public function render_recent_contacts_widget() {
        global $wpdb;
        $contacts = $wpdb->get_results(
            "SELECT name, email, created_at FROM {$wpdb->prefix}crm_contacts ORDER BY created_at DESC LIMIT 5"
        );
        ?>
        <div class="recent-contacts-widget">
            <?php if ($contacts): ?>
                <?php foreach ($contacts as $contact): ?>
                    <div class="contact-item">
                        <strong><?php echo esc_html($contact->name); ?></strong>
                        <br><small class="text-muted"><?php echo esc_html($contact->email); ?></small>
                        <br><small><?php echo date('d.m.Y H:i', strtotime($contact->created_at)); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">Нет контактов</p>
            <?php endif; ?>
            <hr>
            <a href="/neo-dashboard/crm/contacts" class="btn btn-sm btn-outline-primary w-100">
                Все контакты
            </a>
        </div>
        <?php
    }
    
    // Модальные окна
    private function render_add_contact_modal() {
        ?>
        <div class="modal fade" id="addContactModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Добавить контакт</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addContactForm">
                            <div class="mb-3">
                                <label class="form-label">Имя *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Телефон</label>
                                <input type="tel" class="form-control" name="phone">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Компания</label>
                                <input type="text" class="form-control" name="company">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Статус</label>
                                <select class="form-select" name="status">
                                    <option value="new">Новый</option>
                                    <option value="qualified">Квалифицированный</option>
                                    <option value="customer">Клиент</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" id="saveContactBtn">Сохранить</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    // AJAX методы
    public function ajax_get_contacts() {
        global $wpdb;
        
        $contacts = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}crm_contacts ORDER BY created_at DESC",
            ARRAY_A
        );
        
        wp_send_json_success($contacts);
    }
    
    public function ajax_save_contact() {
        global $wpdb;
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $company = sanitize_text_field($_POST['company'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? 'new');
        
        if (empty($name) || empty($email)) {
            wp_send_json_error('Имя и email обязательны для заполнения');
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'crm_contacts',
            [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'company' => $company,
                'status' => $status
            ]
        );
        
        if ($result) {
            wp_send_json_success(['message' => 'Контакт успешно добавлен']);
        } else {
            wp_send_json_error('Ошибка при сохранении контакта');
        }
    }
    
    public function ajax_delete_contact() {
        global $wpdb;
        
        $contact_id = intval($_POST['contact_id'] ?? 0);
        
        if (!$contact_id) {
            wp_send_json_error('Неверный ID контакта');
        }
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'crm_contacts',
            ['id' => $contact_id]
        );
        
        if ($result) {
            wp_send_json_success(['message' => 'Контакт удален']);
        } else {
            wp_send_json_error('Ошибка при удалении контакта');
        }
    }
    
    public function ajax_get_stats() {
        global $wpdb;
        
        $stats = [
            'total_contacts' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}crm_contacts"),
            'active_deals' => 23, // Mock data
            'monthly_revenue' => '₽125,000',
            'conversion_rate' => '12.5%'
        ];
        
        wp_send_json_success($stats);
    }
}

new NeoCRMPlugin();
```

Эта документация с примерами поможет разработчикам быстро понять, как создавать различные типы плагинов для Neo Dashboard - от простых до сложных многофункциональных решений.