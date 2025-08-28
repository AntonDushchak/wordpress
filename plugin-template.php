<?php
/**
 * Plugin Name: Plugin Template
 * Description: Шаблон для создания плагинов на базе Neo Dashboard
 * Version: 1.0.0
 * Author: Ваше имя
 * License: GPL-2.0-or-later
 */

// Проверяем что Neo Dashboard активен
if (!class_exists('\NeoDashboard\Core\Router')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>Plugin Template требует Neo Dashboard Core</p></div>';
    });
    return;
}

// Подключаем стили и скрипты
add_action('neo_dashboard_enqueue_assets', function() {
    wp_enqueue_style(
        'plugin-template-styles',
        plugin_dir_url(__FILE__) . 'assets/css/plugin-template.css',
        [],
        '1.0.0'
    );
    
    wp_enqueue_script(
        'plugin-template-scripts',
        plugin_dir_url(__FILE__) . 'assets/js/plugin-template.js',
        ['jquery'],
        '1.0.0',
        true
    );
});

// Регистрируем секции
add_action('neo_dashboard_init', function() {
    // Основная секция
    do_action('neo_dashboard_register_section', [
        'slug'          => 'plugin-template',
        'label'         => 'Plugin Template',
        'icon'          => 'bi-gear',
        'template_path' => plugin_dir_path(__FILE__) . 'templates/main-section.php',
        'priority'      => 10,
    ]);
    
    // Дополнительная секция
    do_action('neo_dashboard_register_section', [
        'slug'          => 'plugin-template-settings',
        'label'         => 'Настройки',
        'icon'          => 'bi-sliders',
        'template_path' => plugin_dir_path(__FILE__) . 'templates/settings-section.php',
        'priority'      => 20,
    ]);
});

// Регистрируем виджеты
add_action('neo_dashboard_init', function() {
    // Основной виджет
    do_action('neo_dashboard_register_widget', [
        'id'       => 'plugin-template-main',
        'label'    => 'Основной виджет',
        'icon'     => 'bi-box',
        'priority' => 10,
        'callback' => function() {
            ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-box me-2"></i>
                        Основной виджет
                    </h5>
                </div>
                <div class="card-body">
                    <p>Это основной виджет вашего плагина.</p>
                    <button class="btn btn-primary btn-sm" onclick="pluginTemplateAction()">
                        Действие
                    </button>
                </div>
            </div>
            <?php
        },
    ]);
    
    // Статистика
    do_action('neo_dashboard_register_widget', [
        'id'       => 'plugin-template-stats',
        'label'    => 'Статистика',
        'icon'     => 'bi-graph-up',
        'priority' => 20,
        'callback' => function() {
            ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>
                        Статистика
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h3 class="text-primary">25</h3>
                            <small class="text-muted">Элементов</small>
                        </div>
                        <div class="col-6">
                            <h3 class="text-success">89%</h3>
                            <small class="text-muted">Эффективность</small>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        },
    ]);
});

// Регистрируем sidebar элементы
add_action('neo_dashboard_init', function() {
    do_action('neo_dashboard_register_sidebar_item', [
        'id'       => 'plugin-template-menu',
        'label'    => 'Plugin Template',
        'icon'     => 'bi-gear',
        'url'      => '#',
        'priority' => 10,
        'children' => [
            [
                'id'    => 'plugin-template-main',
                'label' => 'Главная',
                'icon'  => 'bi-house',
                'url'   => '#',
            ],
            [
                'id'    => 'plugin-template-settings',
                'label' => 'Настройки',
                'icon'  => 'bi-sliders',
                'url'   => '#',
            ],
        ],
    ]);
});

// Регистрируем уведомления
add_action('neo_dashboard_init', function() {
    do_action('neo_dashboard_register_notification', [
        'id'          => 'plugin-template-welcome',
        'type'        => 'info',
        'message'     => 'Добро пожаловать в Plugin Template!',
        'dismissible' => true,
    ]);
});

// AJAX обработчики
add_action('wp_ajax_plugin_template_action', function() {
    // Проверяем nonce для безопасности
    if (!wp_verify_nonce($_POST['nonce'], 'plugin_template_nonce')) {
        wp_send_json_error('Ошибка безопасности');
    }
    
    // Ваша логика здесь
    $result = 'Действие выполнено успешно!';
    
    wp_send_json_success([
        'message' => $result,
        'timestamp' => current_time('mysql')
    ]);
});

// Активация плагина
register_activation_hook(__FILE__, function() {
    // Создаем таблицы в базе данных если нужно
    // Добавляем опции по умолчанию
    add_option('plugin_template_version', '1.0.0');
    add_option('plugin_template_settings', [
        'enabled' => true,
        'debug_mode' => false,
    ]);
});

// Деактивация плагина
register_deactivation_hook(__FILE__, function() {
    // Очищаем временные данные
    // НЕ удаляем основные данные пользователя
});

// Удаление плагина
register_uninstall_hook(__FILE__, function() {
    // Удаляем все данные плагина
    delete_option('plugin_template_version');
    delete_option('plugin_template_settings');
});

// Добавляем ссылки в админ-панель
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=neo-dashboard&section=plugin-template-settings') . '">Настройки</a>';
    array_unshift($links, $settings_link);
    return $links;
});

// Хук для инициализации после загрузки WordPress
add_action('init', function() {
    // Ваша логика инициализации
});

// Хук для загрузки текстового домена (для интернационализации)
add_action('plugins_loaded', function() {
    load_plugin_textdomain('plugin-template', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Добавляем мета-бокс в админ-панель WordPress
add_action('add_meta_boxes', function() {
    add_meta_box(
        'plugin-template-meta',
        'Plugin Template',
        function($post) {
            // Содержимое мета-бокса
            wp_nonce_field('plugin_template_meta', 'plugin_template_nonce');
            $value = get_post_meta($post->ID, '_plugin_template_field', true);
            ?>
            <label for="plugin_template_field">Поле:</label>
            <input type="text" id="plugin_template_field" name="plugin_template_field" value="<?php echo esc_attr($value); ?>" size="25" />
            <?php
        },
        'post',
        'side',
        'high'
    );
});

// Сохраняем мета-данные
add_action('save_post', function($post_id) {
    if (!wp_verify_nonce($_POST['plugin_template_nonce'], 'plugin_template_meta')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (isset($_POST['plugin_template_field'])) {
        update_post_meta($post_id, '_plugin_template_field', sanitize_text_field($_POST['plugin_template_field']));
    }
});

// Добавляем REST API endpoint
add_action('rest_api_init', function() {
    register_rest_route('plugin-template/v1', '/data', [
        'methods' => 'GET',
        'callback' => function($request) {
            return [
                'status' => 'success',
                'data' => [
                    'message' => 'Данные из Plugin Template',
                    'timestamp' => current_time('mysql'),
                ]
            ];
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ]);
});

// Добавляем shortcode
add_shortcode('plugin_template', function($atts) {
    $atts = shortcode_atts([
        'type' => 'default',
        'title' => 'Plugin Template',
    ], $atts);
    
    ob_start();
    ?>
    <div class="plugin-template-shortcode">
        <h3><?php echo esc_html($atts['title']); ?></h3>
        <p>Это shortcode плагина Plugin Template.</p>
        <p>Тип: <?php echo esc_html($atts['type']); ?></p>
    </div>
    <?php
    return ob_get_clean();
});

// Добавляем виджет для WordPress
add_action('widgets_init', function() {
    register_widget('Plugin_Template_Widget');
});

// Класс виджета WordPress
class Plugin_Template_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'plugin_template_widget',
            'Plugin Template Widget',
            ['description' => 'Виджет для Plugin Template']
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        echo '<p>Это виджет Plugin Template.</p>';
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Заголовок:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}


