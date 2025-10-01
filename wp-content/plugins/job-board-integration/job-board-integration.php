<?php

/**
 * Plugin Name: Neo Job Board
 * Author: Du
 * Text Domain: neo-job-board
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Отладка загрузки плагина
error_log('Neo Job Board: Plugin file loaded');

// Plugin-Konstanten definieren
define('NEO_JOB_BOARD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NEO_JOB_BOARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NEO_JOB_BOARD_VERSION', '1.0.0');

// Klassen-Autoloading
spl_autoload_register(function ($class) {
    if (strpos($class, 'NeoJobBoard\\') === 0) {
        $class_file = str_replace('NeoJobBoard\\', '', $class);
        $file_path = NEO_JOB_BOARD_PLUGIN_DIR . 'includes/' . $class_file . '.php';
        
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
});

add_action('plugins_loaded', static function () {
    if (!class_exists(\NeoDashboard\Core\Router::class)) {
        deactivate_plugins(plugin_basename(__FILE__));
        add_action('admin_notices', static function () {
            echo '<div class="notice notice-error"><p>';
            esc_html_e(
                'Neo Job Board wurde deaktiviert, weil "Neo Dashboard Core" nicht aktiv ist.',
                'neo-job-board'
            );
            echo '</p></div>';
        });
        return;
    }

    error_log('Neo Job Board: Neo Dashboard Core found, initializing...');
    
    // Проверяем Router
    if (class_exists(\NeoDashboard\Core\Router::class)) {
        error_log('Neo Job Board: Router class exists');
    } else {
        error_log('Neo Job Board: Router class NOT found!');
    }
    
    // Проверяем существование хука neo_dashboard_init
    error_log('Neo Job Board: Checking for neo_dashboard_init hook...');
    
    // AJAX-Handler initiализировать (используем новую версию с улучшенной безопасностью)
    \NeoJobBoard\AJAXV2::init();
    error_log('Neo Job Board: AJAX V2 initialized with enhanced security');

    // CSS подключение для Neo Job Board
    add_action('neo_dashboard_enqueue_neo-job-board_assets_css', function($section = '') {
        error_log('Neo Job Board: CSS hook triggered for section: ' . $section);
        
        // Bootstrap CSS (если не подключен)
        wp_enqueue_style(
            'bootstrap-css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
            [],
            '5.1.3'
        );
        
        // Select2 CSS
        wp_enqueue_style(
            'select2-css',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
            [],
            '4.1.0'
        );
        
        wp_enqueue_style(
            'neo-job-board-css',
            NEO_JOB_BOARD_PLUGIN_URL . 'assets/css/neo-job-board.css',
            ['bootstrap-css', 'select2-css'],
            NEO_JOB_BOARD_VERSION
        );
        
        wp_enqueue_style(
            'neo-profession-autocomplete-css',
            NEO_JOB_BOARD_PLUGIN_URL . 'assets/css/neo-profession-autocomplete.css',
            ['neo-job-board-css'],
            NEO_JOB_BOARD_VERSION
        );
        
        error_log('Neo Job Board: CSS files enqueued for section: ' . $section);
    });
    
    // Также пробуем альтернативные названия хуков
    add_action('neo_dashboard_enqueue_assets_css', function() {
        error_log('Neo Job Board: Generic CSS hook triggered!');
        
        wp_enqueue_style(
            'neo-job-board-css',
            NEO_JOB_BOARD_PLUGIN_URL . 'assets/css/neo-job-board.css',
            [],
            NEO_JOB_BOARD_VERSION
        );
    });
        
    // JavaScript подключение для Neo Job Board
    add_action('neo_dashboard_enqueue_neo-job-board_assets_js', function($section = '') {
        error_log('Neo Job Board: JS hook triggered for section: ' . $section);
        
        // Принудительно подключаем jQuery
        wp_enqueue_script('jquery');
        
        // Bootstrap JS (если не подключен)
        wp_enqueue_script(
            'bootstrap-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js',
            ['jquery'],
            '5.1.3',
            true
        );
        
        // Select2 JS
        wp_enqueue_script(
            'select2-js',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            ['jquery'],
            '4.1.0',
            true
        );
        
        // Основные скрипты загружаются всегда
        wp_enqueue_script(
            'neo-job-submit',
            NEO_JOB_BOARD_PLUGIN_URL . 'assets/js/neo-job-submit.js',
            ['jquery'],
            NEO_JOB_BOARD_VERSION,
            true
        );
        
        // Загружаем специфичные скрипты в зависимости от секции
        switch ($section) {
            case 'neo-job-board/templates':
            case 'neo-job-board':
                wp_enqueue_script(
                    'neo-templates-core',
                    NEO_JOB_BOARD_PLUGIN_URL . 'assets/js/neo-templates-core.js',
                    ['jquery', 'bootstrap-js', 'select2-js', 'neo-job-submit'],
                    NEO_JOB_BOARD_VERSION,
                    true
                );
                wp_enqueue_script(
                    'neo-templates-modal',
                    NEO_JOB_BOARD_PLUGIN_URL . 'assets/js/neo-templates-modal.js',
                    ['jquery', 'bootstrap-js', 'select2-js', 'neo-templates-core'],
                    NEO_JOB_BOARD_VERSION,
                    true
                );
                wp_enqueue_script(
                    'neo-templates-fields',
                    NEO_JOB_BOARD_PLUGIN_URL . 'assets/js/neo-templates-fields.js',
                    ['jquery', 'bootstrap-js', 'select2-js', 'neo-templates-core'],
                    NEO_JOB_BOARD_VERSION,
                    true
                );
                break;
                
            case 'neo-job-board/jobs':
                wp_enqueue_script(
                    'neo-job-form',
                    NEO_JOB_BOARD_PLUGIN_URL . 'assets/js/neo-job-form.js',
                    ['jquery', 'neo-job-submit'],
                    NEO_JOB_BOARD_VERSION,
                    true
                );
                break;
        }
        
        // Автокомплит профессий для всех страниц
        wp_enqueue_script(
            'neo-profession-autocomplete',
            NEO_JOB_BOARD_PLUGIN_URL . 'assets/js/neo-profession-autocomplete.js',
            ['jquery'],
            NEO_JOB_BOARD_VERSION,
            true
        );
        
        // Localize AJAX data для всех скриптов
        $ajax_data = [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('neo_job_board_nonce'),
            'pluginUrl' => plugin_dir_url(__FILE__)
        ];
        
        wp_localize_script('neo-job-submit', 'neoJobBoardAjax', $ajax_data);
        
        if (wp_script_is('neo-templates-core', 'enqueued')) {
            wp_localize_script('neo-templates-core', 'neoJobBoardAjax', $ajax_data);
        }
        if (wp_script_is('neo-templates-modal', 'enqueued')) {
            wp_localize_script('neo-templates-modal', 'neoJobBoardAjax', $ajax_data);
        }
        if (wp_script_is('neo-templates-fields', 'enqueued')) {
            wp_localize_script('neo-templates-fields', 'neoJobBoardAjax', $ajax_data);
        }
        if (wp_script_is('neo-job-form', 'enqueued')) {
            wp_localize_script('neo-job-form', 'neoJobBoardAjax', $ajax_data);
        }
        
        wp_localize_script('neo-profession-autocomplete', 'neoJobBoardAjax', $ajax_data);
        
        error_log('Neo Job Board: JavaScript files enqueued for section: ' . $section);
    });
    
    // Также пробуем альтернативные названия хуков
    add_action('neo_dashboard_enqueue_assets_js', function() {
        error_log('Neo Job Board: Generic JS hook triggered!');
        
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'neo-job-submit-generic',
            NEO_JOB_BOARD_PLUGIN_URL . 'assets/js/neo-job-submit.js',
            ['jquery'],
            NEO_JOB_BOARD_VERSION,
            true
        );
        
        wp_localize_script('neo-job-submit-generic', 'neoJobBoardAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('neo_job_board_nonce'),
            'pluginUrl' => plugin_dir_url(__FILE__)
        ]);
    });

    // Также добавляем стандартные WordPress хуки как fallback
    add_action('wp_enqueue_scripts', function() {
        if (is_admin() && isset($_GET['page']) && strpos($_GET['page'], 'neo-dashboard') !== false) {
            error_log('Neo Job Board: WordPress fallback enqueue triggered');
            
            wp_enqueue_style(
                'neo-job-board-css-fallback',
                NEO_JOB_BOARD_PLUGIN_URL . 'assets/css/neo-job-board.css',
                [],
                NEO_JOB_BOARD_VERSION
            );
            
            wp_enqueue_script(
                'neo-job-submit-fallback',
                NEO_JOB_BOARD_PLUGIN_URL . 'assets/js/neo-job-submit.js',
                ['jquery'],
                NEO_JOB_BOARD_VERSION,
                true
            );
            
            wp_localize_script('neo-job-submit-fallback', 'neoJobBoardAjax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('neo_job_board_nonce'),
                'pluginUrl' => plugin_dir_url(__FILE__)
            ]);
        }
    });

    // Neo Dashboard Integration
    add_action('neo_dashboard_init', function () {
        
        // Console logging for debugging
        error_log('Neo Job Board: neo_dashboard_init hook triggered!');
        error_log('Neo Job Board: Registering sidebar items and routes...');
        
        // Hauptgruppe in der Sidebar
        do_action('neo_dashboard_register_sidebar_item', [
            'slug'     => 'neo-job-board',
            'label'    => __('Job Board', 'neo-job-board'),
            'icon'     => 'bi-briefcase',
            'url'      => '/neo-dashboard/neo-job-board',
            'position' => 20,
            'is_group' => true,
        ]);

        // Untermenü
        $sections = [
            'templates' => [
                'label' => __('Vorlagen', 'neo-job-board'),
                'icon'  => 'bi-file-earmark-text',
                'pos'   => 21,
            ],
            'jobs' => [
                'label' => __('Bewerbungen', 'neo-job-board'),
                'icon'  => 'bi-list-ul',
                'pos'   => 22,
            ],
            'jobs-new' => [
                'label' => __('Bewerbung erstellen', 'neo-job-board'),
                'icon'  => 'bi-plus-circle',
                'pos'   => 23,
            ],
            'settings' => [
                'label' => __('API-Einstellungen', 'neo-job-board'),
                'icon'  => 'bi-gear',
                'pos'   => 24,
            ],
        ];

        foreach ($sections as $slug => $section) {
            do_action('neo_dashboard_register_sidebar_item', [
                'slug'     => "neo-job-board-{$slug}",
                'label'    => $section['label'],
                'icon'     => $section['icon'],
                'url'      => "/neo-dashboard/neo-job-board/{$slug}",
                'position' => $section['pos'],
                'parent'   => 'neo-job-board',
            ]);
        }

        // Регистрируем главную секцию
        do_action('neo_dashboard_register_section', [
            'slug'     => 'neo-job-board',
            'label'    => 'Neo Job Board',
            'callback' => 'neo_job_board_main_section_callback',
        ]);

        // Регистрируем подсекции с полным путем как в neo-calendar
        $sections = [
            'templates' => 'neo_job_board_templates_callback',
            'jobs' => 'neo_job_board_jobs_callback', 
            'jobs-new' => 'neo_job_board_jobs_new_callback',
            'settings' => 'neo_job_board_settings_callback'
        ];

        foreach ($sections as $slug => $callback) {
            $full_slug = 'neo-job-board/' . $slug;
            
            do_action('neo_dashboard_register_section', [
                'slug'     => $full_slug,
                'label'    => $slug,
                'callback' => $callback,
            ]);
        }
        
        error_log('Neo Job Board: All sections registered');
    });
});

// Plugin-Aktivierung
register_activation_hook(__FILE__, function () {
    \NeoJobBoard\Database::create_tables();
    \NeoJobBoard\Database::migrate_legacy_data();
});

// Plugin-Update-Hooks
add_action('upgrader_process_complete', function($upgrader_object, $options) {
    if ($options['action'] == 'update' && $options['type'] == 'plugin') {
        if (isset($options['plugins'])) {
            foreach($options['plugins'] as $plugin) {
                if ($plugin == plugin_basename(__FILE__)) {
                    \NeoJobBoard\Database::migrate_legacy_data();
                }
            }
        }
    }
}, 10, 2);

// Plugin-Deaktivierung
register_deactivation_hook(__FILE__, function () {
    // Bereinigung von Cron-Jobs, temporären Daten usw.
    wp_clear_scheduled_hook('neo_job_board_cleanup');
});

// Scheduler für die Bereinigung alter Bewerbungen
add_action('init', function() {
    if (!wp_next_scheduled('neo_job_board_cleanup')) {
        wp_schedule_event(time(), 'daily', 'neo_job_board_cleanup');
    }
});

add_action('neo_job_board_cleanup', function() {
    $settings = \NeoJobBoard\Settings::get_settings();
    
    if ($settings['auto_delete_applications'] ?? false) {
        global $wpdb;
        
        $applications_table = $wpdb->prefix . 'neo_job_board_applications';
        
        // Bewerbungen älter als 1 Jahr löschen
        $wpdb->query("
            DELETE FROM $applications_table 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
        ");
    }
});

// Функции-коллбеки для секций
function neo_job_board_main_section_callback() {
    error_log('Neo Job Board: Main section callback triggered');
    \NeoJobBoard\Templates::render_page();
}

function neo_job_board_templates_callback() {
    error_log('Neo Job Board: Templates section callback triggered');
    \NeoJobBoard\Templates::render_page();
}

function neo_job_board_jobs_callback() {
    error_log('Neo Job Board: Jobs section callback triggered');
    \NeoJobBoard\Jobs::render_page();
}

function neo_job_board_jobs_new_callback() {
    error_log('Neo Job Board: Jobs new section callback triggered');
    \NeoJobBoard\Jobs::render_new_page();
}

function neo_job_board_settings_callback() {
    error_log('Neo Job Board: Settings section callback triggered');
    \NeoJobBoard\Settings::render_page();
}