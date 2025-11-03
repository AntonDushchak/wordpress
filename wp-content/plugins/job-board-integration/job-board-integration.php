<?php
/**
 * Plugin Name: Job Board Integration
 * Description: Integration mit bewerberboerse für Vorlagen und Bewerbungen
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: job-board-integration
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('NEO_JOB_BOARD_VERSION')) {
    define('NEO_JOB_BOARD_VERSION', '1.0.0');
}

require_once plugin_dir_path(__FILE__) . 'includes/DataSanitizer.php';

class Job_Board_Integration {

    public function __construct() {
        add_action('plugins_loaded', [$this, 'check_dependencies']);
        add_action('neo_dashboard_init', [$this, 'register_dashboard_components']);
        
        add_action('wp_ajax_jbi_test_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_jbi_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_jbi_get_templates', [$this, 'ajax_get_templates']);
        add_action('wp_ajax_jbi_save_template', [$this, 'ajax_save_template']);
        add_action('wp_ajax_jbi_delete_template', [$this, 'ajax_delete_template']);
        add_action('wp_ajax_jbi_send_template', [$this, 'ajax_send_template']);
        add_action('wp_ajax_jbi_get_template', [$this, 'ajax_get_template']);
        add_action('wp_ajax_jbi_get_applications', [$this, 'ajax_get_applications']);
        add_action('wp_ajax_jbi_get_users', [$this, 'ajax_get_users']);
        add_action('wp_ajax_jbi_sync_application', [$this, 'ajax_sync_application']);
        add_action('wp_ajax_jbi_create_application', [$this, 'ajax_create_application']);
        add_action('wp_ajax_jbi_get_application', [$this, 'ajax_get_application']);
        add_action('wp_ajax_jbi_update_application', [$this, 'ajax_update_application']);
        add_action('wp_ajax_jbi_toggle_application_active', [$this, 'ajax_toggle_application_active']);
        add_action('wp_ajax_jbi_delete_application', [$this, 'ajax_delete_application']);
        add_action('wp_ajax_jbi_get_found_jobs_stats', [$this, 'ajax_get_found_jobs_stats']);
        
        add_action('wp_ajax_nopriv_jbi_get_templates', [$this, 'ajax_get_templates']);
        add_action('wp_ajax_nopriv_jbi_get_template', [$this, 'ajax_get_template']);
        add_action('wp_ajax_nopriv_jbi_get_applications', [$this, 'ajax_get_applications']);
        
        add_action('init', [$this, 'init']);
        
        add_action('jbi_sync_contact_requests', [$this, 'sync_contact_requests']);
        
        register_activation_hook(__FILE__, [$this, 'activate_plugin']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate_plugin']);
        
    }

    public function check_dependencies() {
        if (!class_exists(\NeoDashboard\Core\Router::class)) {
            deactivate_plugins(plugin_basename(__FILE__));
            add_action('admin_notices', static function () {
                echo '<div class="notice notice-error"><p>';
                esc_html_e('Job Board Integration wurde deaktiviert, da "Neo Dashboard Core" nicht aktiv ist.', 'job-board-integration');
                echo '</p></div>';
            });
            return;
        }
    }

    public function register_dashboard_components() {
        do_action('neo_dashboard_register_sidebar_item', [
            'slug' => 'job-board-integration-group',
            'label' => 'Job Board',
            'icon' => 'bi-send',
            'url' => '/neo-dashboard/job-board-integration',
            'position' => 30,
            'is_group' => true,
        ]);

        $user = wp_get_current_user();
        $is_neo_mitarbeiter = in_array('neo_mitarbeiter', $user->roles ?? []);
        
        $sections = [
            'templates' => ['label' => 'Vorlagen', 'icon' => 'bi-file-earmark-text', 'pos' => 31, 'restricted' => true],
            'applications' => ['label' => 'Bewerbungen', 'icon' => 'bi-send-check', 'pos' => 32, 'restricted' => false],
            'settings' => ['label' => 'Einstellungen', 'icon' => 'bi-gear', 'pos' => 33, 'restricted' => true],
        ];

        foreach ($sections as $slug => $data) {
            if ($is_neo_mitarbeiter && !empty($data['restricted'])) {
                continue;
            }
            
            $full_slug = 'job-board-integration/' . $slug;
            do_action('neo_dashboard_register_sidebar_item', [
                'slug' => $full_slug,
                'label' => $data['label'],
                'icon' => $data['icon'],
                'url' => '/neo-dashboard/' . $full_slug,
                'parent' => 'job-board-integration-group',
                'position' => $data['pos'],
            ]);
            do_action('neo_dashboard_register_section', [
                'slug' => $full_slug,
                'label' => $data['label'],
                'callback' => [$this, 'render_' . $slug . '_page'],
            ]);
        }

        if (!$is_neo_mitarbeiter) {
            do_action('neo_dashboard_register_section', [
                'slug' => 'job-board-integration',
                'label' => 'Job Board Integration',
                'callback' => [$this, 'render_templates_page'],
            ]);

            do_action('neo_dashboard_register_widget', [
                'id' => 'job-board-integration-widget',
                'title' => 'Job Board Integration',
                'callback' => [$this, 'render_widget'],
                'priority' => 10,
            ]);
        }

        do_action('neo_dashboard_register_plugin_assets', 'job-board-integration', [
            'css' => [
                'datatables-css' => [
                    'src' => 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css',
                    'deps' => [],
                    'contexts' => ['job-board-integration', 'job-board-integration/templates', 'job-board-integration/applications', 'job-board-integration/settings']
                ],
                'jbi-css' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/css/admin.css',
                    'deps' => ['neo-dashboard-core', 'datatables-css'],
                    'contexts' => ['job-board-integration', 'job-board-integration/templates', 'job-board-integration/applications', 'job-board-integration/settings', 'dashboard-home']
                ],
                'neo-profession-autocomplete-css' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/css/neo-profession-autocomplete.css',
                    'deps' => [],
                    'version' => '1.0.0',
                    'contexts' => ['job-board-integration/applications']
                ],
                'flatpickr-css' => [
                    'src' => 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css',
                    'deps' => [],
                    'version' => '4.6.13',
                    'contexts' => ['job-board-integration/applications']
                ]
            ],
            'js' => [
                'datatables-js' => [
                    'src' => 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
                    'deps' => ['jquery'],
                    'version' => '1.13.6',
                    'contexts' => ['job-board-integration', 'job-board-integration/templates', 'job-board-integration/applications']
                ],
                'flatpickr-js' => [
                    'src' => 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
                    'deps' => ['jquery'],
                    'version' => '4.6.13',
                    'contexts' => ['job-board-integration/applications']
                ],
                'flatpickr-locale-de' => [
                    'src' => 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/de.js',
                    'deps' => ['flatpickr-js'],
                    'version' => '4.6.13',
                    'contexts' => ['job-board-integration/applications']
                ],
                'jbi-js' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/js/admin.js',
                    'deps' => ['jquery', 'datatables-js'],
                    'version' => '1.0.0',
                    'contexts' => ['job-board-integration', 'job-board-integration/templates', 'job-board-integration/applications', 'job-board-integration/settings', 'dashboard-home']
                ],
                'jbi-templates-js' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/js/templates.js',
                    'deps' => ['jquery', 'datatables-js', 'jbi-js'],
                    'version' => '1.0.0',
                    'contexts' => ['job-board-integration', 'job-board-integration/templates']
                ],
                'neo-profession-autocomplete-js' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/js/neo-profession-autocomplete.js',
                    'deps' => ['jquery'],
                    'version' => '1.0.0',
                    'contexts' => ['job-board-integration/applications']
                ],
                'jbi-applications-js' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/js/applications.js',
                    'deps' => ['jquery', 'jbi-js', 'neo-profession-autocomplete-js', 'flatpickr-js'],
                    'version' => '1.0.0',
                    'contexts' => ['job-board-integration/applications']
                ]
            ]
        ]);

        add_action('wp_enqueue_scripts', function() {
            if (wp_script_is('jbi-js', 'enqueued')) {
                wp_localize_script('jbi-js', 'jbiAjax', [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('jbi_nonce'),
                    'strings' => [
                        'error' => 'Ein Fehler ist aufgetreten',
                        'success' => 'Operation erfolgreich ausgeführt',
                        'loading' => 'Laden...',
                        'confirm_delete' => 'Sind Sie sicher, dass Sie dieses Element löschen möchten?'
                    ]
                ]);
            }
        }, 15);

        add_action('admin_enqueue_scripts', function() {
            if (wp_script_is('jbi-js', 'enqueued')) {
                wp_localize_script('jbi-js', 'jbiAjax', [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('jbi_nonce'),
                    'strings' => [
                        'error' => 'Ein Fehler ist aufgetreten',
                        'success' => 'Operation erfolgreich ausgeführt',
                        'loading' => 'Laden...',
                        'confirm_delete' => 'Sind Sie sicher, dass Sie dieses Element löschen möchten?'
                    ]
                ]);
            }
            if (wp_script_is('neo-profession-autocomplete-js', 'enqueued')) {
                wp_localize_script('neo-profession-autocomplete-js', 'neoJobBoardAjax', [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('neo_job_board_nonce'),
                    'pluginUrl' => plugin_dir_url(__FILE__)
                ]);
            }
        }, 15);
    }

    public function init() {
        load_plugin_textdomain('job-board-integration', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        add_filter('cron_schedules', [$this, 'add_ten_minutes_schedule']);
        add_filter('cron_schedules', [$this, 'add_custom_sync_schedule']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_job_board_templates';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            $this->create_database_tables();
        } else {
            $this->migrate_remove_columns();
            $this->migrate_add_is_called_column();
        }
        
        if (!wp_next_scheduled('jbi_sync_contact_requests')) {
            wp_schedule_event(time(), 'jbi_custom_sync_interval', 'jbi_sync_contact_requests');
        }
    }

    public function activate_plugin() {
        $this->create_database_tables();
        
        add_filter('cron_schedules', [$this, 'add_ten_minutes_schedule']);
        add_filter('cron_schedules', [$this, 'add_custom_sync_schedule']);
        
        if (!wp_next_scheduled('jbi_sync_contact_requests')) {
            wp_schedule_event(time(), 'jbi_custom_sync_interval', 'jbi_sync_contact_requests');
        }
    }

    public function deactivate_plugin() {
        $timestamp = wp_next_scheduled('jbi_sync_contact_requests');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'jbi_sync_contact_requests');
        }
    }

    public function add_ten_minutes_schedule($schedules) {
        if (!isset($schedules['jbi_ten_minutes'])) {
            $schedules['jbi_ten_minutes'] = [
                'interval' => 600,
                'display' => 'Каждые 10 минут'
            ];
        }
        return $schedules;
    }

    public function add_custom_sync_schedule($schedules) {
        $interval = get_option('jbi_sync_interval', 600);
        $schedules['jbi_custom_sync_interval'] = [
            'interval' => $interval,
            'display' => $this->format_interval($interval)
        ];
        return $schedules;
    }

    private function format_interval($seconds) {
        $minutes = $seconds / 60;
        $hours = $seconds / 3600;
        $days = $seconds / 86400;
        
        if ($days >= 1 && $days == (int)$days) {
            return sprintf('%d %s', (int)$days, $days == 1 ? 'Tag' : 'Tage');
        } elseif ($hours >= 1 && $hours == (int)$hours) {
            return sprintf('%d %s', (int)$hours, $hours == 1 ? 'Stunde' : 'Stunden');
        } else {
            return sprintf('%d %s', (int)$minutes, $minutes == 1 ? 'Minute' : 'Minuten');
        }
    }

    public function sync_contact_requests() {
        $api_url = get_option('jbi_api_url');
        
        if (empty($api_url)) {
            error_log('JBI Sync: API URL не настроен');
            return;
        }

        $base_url = rtrim($api_url, '/');
        if (strpos($base_url, '/wp-json/bewerberboerse/v1') !== false) {
            $api_base = substr($base_url, 0, strpos($base_url, '/wp-json/bewerberboerse/v1'));
        } else {
            $api_base = $base_url;
        }
        
        $check_endpoint = $api_base . '/wp-json/bewerberboerse/v1/contact-requests/check';

        $check_response = wp_remote_get($check_endpoint, [
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($check_response)) {
            error_log('JBI Sync: Ошибка проверки наличия данных - ' . $check_response->get_error_message());
            return;
        }

        $check_code = wp_remote_retrieve_response_code($check_response);
        if ($check_code !== 200) {
            error_log('JBI Sync: Неверный код ответа при проверке - ' . $check_code);
            return;
        }

        $check_body = wp_remote_retrieve_body($check_response);
        $check_data = json_decode($check_body, true);

        if (!isset($check_data['has_data']) || !$check_data['has_data']) {
            return;
        }

        $limit = $check_data['count'] ?? 100;
        $fetch_endpoint = $api_base . '/wp-json/bewerberboerse/v1/contact-requests?limit=' . $limit;

        $fetch_response = wp_remote_get($fetch_endpoint, [
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($fetch_response)) {
            error_log('JBI Sync: Ошибка получения данных - ' . $fetch_response->get_error_message());
            return;
        }

        $fetch_code = wp_remote_retrieve_response_code($fetch_response);
        if ($fetch_code !== 200) {
            error_log('JBI Sync: Неверный код ответа при получении данных - ' . $fetch_code);
            return;
        }

        $fetch_body = wp_remote_retrieve_body($fetch_response);
        $contact_requests = json_decode($fetch_body, true);

        if (!is_array($contact_requests) || empty($contact_requests)) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_job_board_contact_requests';
        $applications_table = $wpdb->prefix . 'neo_job_board_applications';
        
        $saved_count = 0;

        foreach ($contact_requests as $request) {
            $application_hash = sanitize_text_field($request['application_hash'] ?? '');
            $name = sanitize_text_field($request['name'] ?? '');
            $email = sanitize_email($request['email'] ?? '');
            $phone = isset($request['phone']) ? sanitize_text_field($request['phone']) : null;
            $message = isset($request['message']) ? sanitize_textarea_field($request['message']) : null;
            $created_at = isset($request['created_at']) ? sanitize_text_field($request['created_at']) : current_time('mysql');

            if (empty($application_hash) || empty($name) || empty($email)) {
                continue;
            }

            $application_id = null;
            if (!empty($application_hash)) {
                $application = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $applications_table WHERE hash_id LIKE %s LIMIT 1",
                    $application_hash . '%'
                ));
                
                if ($application) {
                    $application_id = $application->id;
                    $wpdb->update(
                        $applications_table,
                        ['is_called' => 1],
                        ['id' => $application_id],
                        ['%d'],
                        ['%d']
                    );
                }
            }

            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE application_hash = %s AND email = %s AND created_at = %s",
                $application_hash,
                $email,
                $created_at
            ));

            if ($existing) {
                continue;
            }

            $result = $wpdb->insert(
                $table_name,
                [
                    'application_hash' => $application_hash,
                    'application_id' => $application_id,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'message' => $message,
                    'created_at' => $created_at
                ],
                ['%s', '%d', '%s', '%s', '%s', '%s', '%s']
            );

            if ($result !== false) {
                $saved_count++;
            }
        }

        if ($saved_count > 0) {
            $delete_endpoint = $api_base . '/wp-json/bewerberboerse/v1/contact-requests/delete-all';

            $delete_response = wp_remote_request($delete_endpoint, [
                'method' => 'DELETE',
                'timeout' => 30,
                'sslverify' => false
            ]);

            if (!is_wp_error($delete_response)) {
                $delete_code = wp_remote_retrieve_response_code($delete_response);
                if ($delete_code === 200) {
                    error_log("JBI Sync: Успешно синхронизировано $saved_count контактных запросов и удалено на удаленном сайте");
                } else {
                    error_log("JBI Sync: Сохранено $saved_count запросов, но не удалось удалить на удаленном сайте (код: $delete_code)");
                }
            } else {
                error_log("JBI Sync: Сохранено $saved_count запросов, но ошибка при удалении: " . $delete_response->get_error_message());
            }
        }
    }

    private function migrate_add_is_called_column() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_job_board_applications';
        
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s 
             AND TABLE_NAME = %s 
             AND COLUMN_NAME = 'is_called'",
            DB_NAME,
            $table_name
        ));
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN is_called tinyint(1) DEFAULT 0");
            $wpdb->query("ALTER TABLE $table_name ADD INDEX idx_is_called (is_called)");
        }
    }
    
    private function migrate_remove_columns() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_job_board_applications';
        
        $columns_to_drop = ['first_name', 'last_name', 'email', 'phone', 'status'];
        
        foreach ($columns_to_drop as $column) {
            $column_exists = $wpdb->get_results($wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = %s 
                 AND TABLE_NAME = %s 
                 AND COLUMN_NAME = %s",
                DB_NAME,
                $table_name,
                $column
            ));
            
            if (!empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name DROP COLUMN $column");
            }
        }
        
        $index_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 
             WHERE TABLE_SCHEMA = %s 
             AND TABLE_NAME = %s 
             AND INDEX_NAME = 'idx_status'",
            DB_NAME,
            $table_name
        ));
        
        if (!empty($index_exists)) {
            $wpdb->query("ALTER TABLE $table_name DROP INDEX idx_status");
        }
        
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s 
             AND TABLE_NAME = %s 
             AND COLUMN_NAME = 'responsible_employee'
             AND DATA_TYPE = 'varchar'",
            DB_NAME,
            $table_name
        ));
        
        if (!empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name MODIFY COLUMN responsible_employee int(11) DEFAULT NULL");
            $index_check = $wpdb->get_results($wpdb->prepare(
                "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 
                 WHERE TABLE_SCHEMA = %s 
                 AND TABLE_NAME = %s 
                 AND INDEX_NAME = 'idx_responsible_employee'",
                DB_NAME,
                $table_name
            ));
            
            if (empty($index_check)) {
                $wpdb->query("ALTER TABLE $table_name ADD INDEX idx_responsible_employee (responsible_employee)");
            }
        }
    }

    public function create_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_templates = $wpdb->prefix . 'neo_job_board_templates';
        $sql_templates = "CREATE TABLE $table_templates (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            fields longtext NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_by int(11) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_created_by (created_by)
        ) $charset_collate;";

        $table_applications = $wpdb->prefix . 'neo_job_board_applications';
        $sql_applications = "CREATE TABLE $table_applications (
            id int(11) NOT NULL AUTO_INCREMENT,
            hash_id varchar(32) NOT NULL UNIQUE,
            template_id int(11) NOT NULL,
            position varchar(255),
            responsible_employee int(11) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            is_called tinyint(1) DEFAULT 0,
            application_data longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_hash_id (hash_id),
            KEY idx_template_id (template_id),
            KEY idx_responsible_employee (responsible_employee),
            KEY idx_created_at (created_at),
            KEY idx_is_called (is_called)
        ) $charset_collate;";

        $table_application_data = $wpdb->prefix . 'neo_job_board_application_data';
        $sql_application_data = "CREATE TABLE $table_application_data (
            id int(11) NOT NULL AUTO_INCREMENT,
            application_id int(11) NOT NULL,
            field_name varchar(100) NOT NULL,
            field_value longtext,
            field_type varchar(50) DEFAULT 'text',
            is_personal tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_application_id (application_id),
            KEY idx_field_name (field_name)
        ) $charset_collate;";

        $table_application_details = $wpdb->prefix . 'neo_job_board_application_details';
        $sql_application_details = "CREATE TABLE $table_application_details (
            id int(11) NOT NULL AUTO_INCREMENT,
            application_id int(11) NOT NULL,
            field_name varchar(100) NOT NULL,
            field_value longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_application_id (application_id),
            KEY idx_field_name (field_name)
        ) $charset_collate;";

        $table_files = $wpdb->prefix . 'neo_job_board_files';
        $sql_files = "CREATE TABLE $table_files (
            id int(11) NOT NULL AUTO_INCREMENT,
            application_id int(11) NOT NULL,
            field_name varchar(100) NOT NULL,
            original_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size int(11) DEFAULT 0,
            file_type varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_application_id (application_id)
        ) $charset_collate;";

        $table_api_logs = $wpdb->prefix . 'neo_job_board_api_logs';
        $sql_api_logs = "CREATE TABLE $table_api_logs (
            id int(11) NOT NULL AUTO_INCREMENT,
            template_id int(11),
            action varchar(50) NOT NULL,
            endpoint varchar(255) NOT NULL,
            method varchar(10) NOT NULL,
            request_data longtext,
            response_data longtext,
            status_code int(11),
            success tinyint(1) DEFAULT 0,
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_template_id (template_id),
            KEY idx_action (action),
            KEY idx_success (success),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        $table_contact_requests = $wpdb->prefix . 'neo_job_board_contact_requests';
        $sql_contact_requests = "CREATE TABLE $table_contact_requests (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            application_hash varchar(8) NOT NULL,
            application_id int(11) DEFAULT NULL,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) DEFAULT NULL,
            message longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_application_hash (application_hash),
            KEY idx_application_id (application_id),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_templates);
        dbDelta($sql_applications);
        dbDelta($sql_application_data);
        dbDelta($sql_application_details);
        dbDelta($sql_files);
        dbDelta($sql_api_logs);
        dbDelta($sql_contact_requests);
    }

    public function render_templates_page() {
        $user = wp_get_current_user();
        if (in_array('neo_mitarbeiter', $user->roles ?? [])) {
            wp_die('Sie haben keine Berechtigung, auf diese Seite zuzugreifen.', 'Zugriff verweigert', ['response' => 403]);
            return;
        }
        
        $nonce = wp_create_nonce('jbi_nonce');
        ?>
        <script type="text/javascript">
            window.jbiAjax = {
                ajaxurl: "<?php echo admin_url('admin-ajax.php'); ?>",
                nonce: "<?php echo $nonce; ?>",
                strings: {
                    error: "Ein Fehler ist aufgetreten",
                    success: "Operation erfolgreich ausgeführt",
                    loading: "Laden...",
                    confirm_delete: "Sind Sie sicher, dass Sie diese Vorlage löschen möchten?"
                }
            };
        </script>
        <div class="jbi-container">
            <div class="jbi-card">
                <div class="jbi-card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="jbi-card-title">Vorlagen</h2>
                        <button class="btn btn-primary" onclick="openAddTemplateModal()">
                            <i class="bi bi-plus-circle"></i> Vorlage erstellen
                        </button>
                    </div>
                </div>
                <div class="jbi-card-body">
                    <div id="templates-list">Laden...</div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="templateModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Vorlage erstellen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="template-form">
                            <div class="mb-3">
                                <label class="form-label">Name der Vorlage *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Beschreibung</label>
                                <textarea class="form-control" name="description" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Felder</label>
                                <div id="template-fields"></div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-field-btn">
                                    <i class="bi bi-plus"></i> Feld hinzufügen
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="button" class="btn btn-primary" id="save-template-btn">Speichern</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_applications_page() {
        $nonce = wp_create_nonce('jbi_nonce');
        ?>
        <script type="text/javascript">
            <?php
            $user = wp_get_current_user();
            $user_roles = $user->roles;
            ?>
            window.jbiAjax = {
                ajaxurl: "<?php echo admin_url('admin-ajax.php'); ?>",
                nonce: "<?php echo $nonce; ?>",
                currentUserId: <?php echo get_current_user_id(); ?>,
                userRoles: <?php echo json_encode($user_roles); ?>,
                strings: {
                    error: "Ein Fehler ist aufgetreten",
                    success: "Operation erfolgreich ausgeführt",
                    loading: "Laden...",
                    confirm_delete: "Sind Sie sicher, dass Sie diese Bewerbung löschen möchten?"
                }
            };
            window.neoJobBoardAjax = {
                ajaxurl: "<?php echo admin_url('admin-ajax.php'); ?>",
                nonce: "<?php echo wp_create_nonce('neo_job_board_nonce'); ?>",
                pluginUrl: "<?php echo plugin_dir_url(__FILE__); ?>"
            };
        </script>
        <div class="jbi-container">
            <div class="jbi-card">
                <div class="jbi-card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="jbi-card-title">Bewerbungen</h2>
                        <button class="btn btn-primary" onclick="openAddApplicationModal()">
                            <i class="bi bi-plus-circle"></i> Neue Bewerbung
                        </button>
                    </div>
                </div>
                <div class="jbi-card-body">
                    <div id="applications-list">Laden...</div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="applicationModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="applicationModalLabel">Neue Bewerbung erstellen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            Nach dem Speichern wird die Bewerbung automatisch an die externe API gesendet.
                        </div>
                        
                        <div id="application-fields-container">
                            <form id="application-form">
                                <div id="dynamic-fields">
                                    <div class="text-center">
                                        <div class="spinner-border" role="status"></div>
                                        <p>Aktives Template laden...</p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="button" class="btn btn-primary" id="save-application-btn" style="display: none;">
                            <i class="bi bi-check-circle"></i> Speichern und senden
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings_page() {
        $user = wp_get_current_user();
        if (in_array('neo_mitarbeiter', $user->roles ?? [])) {
            wp_die('Sie haben keine Berechtigung, auf diese Seite zuzugreifen.', 'Zugriff verweigert', ['response' => 403]);
            return;
        }
        
        $api_url = get_option('jbi_api_url', '');
        $api_key = get_option('jbi_api_key', '');
        $auto_send = get_option('jbi_auto_send', 1);
        $sync_interval = get_option('jbi_sync_interval', 600);
        $nonce = wp_create_nonce('jbi_nonce');
        ?>
        <script type="text/javascript">
            window.jbiAjax = {
                ajaxurl: "<?php echo admin_url('admin-ajax.php'); ?>",
                nonce: "<?php echo $nonce; ?>",
                strings: {
                    error: "Ein Fehler ist aufgetreten",
                    success: "Operation erfolgreich ausgeführt",
                    loading: "Laden...",
                    testConnection: "Verbindung testen...",
                    connectionSuccess: "Verbindung erfolgreich!",
                    connectionError: "Verbindungsfehler!"
                }
            };
        </script>
        <div class="jbi-container">
            <div class="jbi-card">
                <div class="jbi-card-header">
                    <h2 class="jbi-card-title">API Einstellungen</h2>
                </div>
                <div class="jbi-card-body">
                    <form id="jbi-settings-form">
                        <div class="mb-3">
                            <label class="form-label">API URL</label>
                            <input type="text" class="form-control" name="api_url" value="<?php echo esc_attr($api_url); ?>" placeholder="https://site.com/wp-json/bewerberboerse/v1" required>
                            <small class="form-text text-muted">Vollständige URL zum API Endpoint</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">API Key</label>
                            <input type="text" class="form-control" name="api_key" value="<?php echo esc_attr($api_key); ?>" placeholder="Ihr API Key" required>
                            <small class="form-text text-muted">Ihr API Authentifizierungsschlüssel</small>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="auto_send" id="auto_send" value="1" <?php checked($auto_send, 1); ?>>
                                <label class="form-check-label" for="auto_send">Automatische Synchronisierung aktivieren</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Intervall für Kontaktanfragen-Synchronisierung</label>
                            <select class="form-control" name="sync_interval" id="sync_interval">
                                <option value="300" <?php selected($sync_interval, 300); ?>>5 Minuten</option>
                                <option value="600" <?php selected($sync_interval, 600); ?>>10 Minuten</option>
                                <option value="900" <?php selected($sync_interval, 900); ?>>15 Minuten</option>
                                <option value="1800" <?php selected($sync_interval, 1800); ?>>30 Minuten</option>
                                <option value="3600" <?php selected($sync_interval, 3600); ?>>1 Stunde</option>
                                <option value="7200" <?php selected($sync_interval, 7200); ?>>2 Stunden</option>
                                <option value="14400" <?php selected($sync_interval, 14400); ?>>4 Stunden</option>
                                <option value="86400" <?php selected($sync_interval, 86400); ?>>24 Stunden</option>
                            </select>
                            <small class="form-text text-muted">Wie oft sollen Kontaktanfragen vom anderen Server synchronisiert werden?</small>
                        </div>
                        <div class="mb-3">
                            <button type="button" class="btn btn-secondary" id="test-connection-btn">Verbindung testen</button>
                            <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
                        </div>
                        <div id="connection-result"></div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_widget() {
        $nonce = wp_create_nonce('jbi_nonce');
        ?>
        <script type="text/javascript">
            window.jbiAjax = {
                ajaxurl: "<?php echo admin_url('admin-ajax.php'); ?>",
                nonce: "<?php echo $nonce; ?>"
            };
        </script>
        <div class="jbi-widget">
            <div class="jbi-widget-body">
                <p>Integration Status</p>
                <div id="widget-status"></div>
            </div>
        </div>
        <?php
    }

    public function ajax_test_connection() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        $api_url = get_option('jbi_api_url');
        $api_key = get_option('jbi_api_key');

        if (empty($api_url)) {
            wp_send_json_error(['message' => 'Bitte geben Sie die API URL ein']);
            return;
        }

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'Bitte geben Sie den API Key ein']);
            return;
        }

        if (!filter_var($api_url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(['message' => 'Ungültige URL. Format: https://site.com/wp-json/bewerberboerse/v1']);
            return;
        }

        $parsed_url = parse_url($api_url);
        if (isset($parsed_url['host']) && ($parsed_url['host'] === 'localhost' || $parsed_url['host'] === '127.0.0.1')) {
            $server_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $clean_host = explode(':', $server_host)[0];
            
            $new_host = $clean_host;
            if (isset($parsed_url['port'])) {
                $new_host .= ':' . $parsed_url['port'];
            }
            
            $api_url = ($parsed_url['scheme'] ?? 'http') . '://' . $new_host . ($parsed_url['path'] ?? '');
        }

        $test_url = rtrim($api_url, '/') . '/templates';
        
        $args = [
            'headers' => [
                'X-API-Key' => $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'timeout' => 30,
            'sslverify' => false,
            'httpversion' => '1.1',
            'blocking' => true
        ];

        $response = wp_remote_get($test_url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $debug_info = [
                'url' => $test_url,
                'error' => $error_message,
                'has_curl' => function_exists('curl_version') ? 'yes' : 'no'
            ];
            
            wp_send_json_error([
                'message' => 'Verbindung fehlgeschlagen: ' . $error_message,
                'debug' => $debug_info
            ]);
            return;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($code === 200) {
            wp_send_json_success([
                'message' => 'Verbindung erfolgreich! API antwortet (200)',
                'url' => $test_url
            ]);
        } elseif ($code === 401) {
            wp_send_json_error([
                'message' => 'API Key ungültig (401). Prüfen Sie Ihren API Key.',
                'url' => $test_url
            ]);
        } elseif ($code === 404) {
            wp_send_json_error([
                'message' => 'Endpoint nicht gefunden (404). URL: ' . $test_url,
                'url' => $test_url
            ]);
        } elseif (empty($code)) {
            wp_send_json_error([
                'message' => 'Keine Antwort vom Server. Prüfen Sie die URL.',
                'url' => $test_url
            ]);
        } else {
            wp_send_json_error([
                'message' => 'API Fehler: HTTP ' . $code,
                'url' => $test_url,
                'body' => substr($body, 0, 200)
            ]);
        }
    }

    public function ajax_save_settings() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        $api_url = sanitize_text_field($_POST['api_url'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        $auto_send = isset($_POST['auto_send']) ? 1 : 0;
        $sync_interval = absint($_POST['sync_interval'] ?? 600);

        $valid_intervals = [300, 600, 900, 1800, 3600, 7200, 14400, 86400];
        if (!in_array($sync_interval, $valid_intervals)) {
            $sync_interval = 600;
        }

        $old_interval = get_option('jbi_sync_interval', 600);
        
        update_option('jbi_api_url', $api_url);
        update_option('jbi_api_key', $api_key);
        update_option('jbi_auto_send', $auto_send);
        update_option('jbi_sync_interval', $sync_interval);

        if ($old_interval != $sync_interval) {
            $timestamp = wp_next_scheduled('jbi_sync_contact_requests');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'jbi_sync_contact_requests');
            }
            
            add_filter('cron_schedules', [$this, 'add_custom_sync_schedule']);
            
            if (!wp_next_scheduled('jbi_sync_contact_requests')) {
                wp_schedule_event(time(), 'jbi_custom_sync_interval', 'jbi_sync_contact_requests');
            }
        }

        wp_send_json_success(['message' => 'Einstellungen erfolgreich gespeichert']);
    }

    public function ajax_get_templates() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_job_board_templates';
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            wp_send_json_success(['templates' => []]);
            return;
        }

        $templates = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);

        if ($templates === null) {
            wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
            return;
        }

        foreach ($templates as &$template) {
            if (!empty($template['fields'])) {
                $decoded = json_decode($template['fields'], true);
                $template['fields'] = $decoded !== null ? $decoded : [];
            } else {
                $template['fields'] = [];
            }
        }

        wp_send_json_success(['templates' => $templates]);
    }

    public function ajax_get_template() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $template_id = intval($_POST['template_id'] ?? 0);
        $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_templates WHERE id = %d", $template_id), ARRAY_A);

        if ($template) {
            $template['fields'] = json_decode($template['fields'], true);
            wp_send_json_success(['template' => $template]);
        } else {
            wp_send_json_error(['message' => 'Template nicht gefunden']);
        }
    }

    public function ajax_save_template() {
        try {
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }

            global $wpdb;
            $name = sanitize_text_field($_POST['name'] ?? '');
            $description = sanitize_textarea_field($_POST['description'] ?? '');
            
            // Получаем поля - могут быть в разных форматах
            $fields = [];
            if (isset($_POST['fields']) && is_array($_POST['fields'])) {
                $fields = $_POST['fields'];
            }

            if (empty($fields)) {
                wp_send_json_error(['message' => 'Keine Felder vorhanden']);
                return;
            }

            $processed_fields = [];
            foreach ($fields as $field) {
                if (!is_array($field)) {
                    continue;
                }
                
                $processed_fields[] = [
                    'type' => sanitize_text_field($field['type'] ?? 'text'),
                    'label' => sanitize_text_field($field['label'] ?? ''),
                    'required' => ($field['required'] ?? 'false') === 'true' || ($field['required'] ?? false) === true || $field['required'] === '1',
                    'personal_data' => ($field['personal_data'] ?? 'false') === 'true' || ($field['personal_data'] ?? false) === true || $field['personal_data'] === '1',
                    'filterable' => ($field['filterable'] ?? 'false') === 'true' || ($field['filterable'] ?? false) === true || $field['filterable'] === '1',
                    'options' => isset($field['options']) ? sanitize_textarea_field($field['options']) : '',
                    'name' => sanitize_text_field($field['name'] ?? ''),
                    'field_name' => sanitize_text_field($field['field_name'] ?? '')
                ];
            }
            
            if (empty($processed_fields)) {
                wp_send_json_error(['message' => 'Keine gültigen Felder gefunden']);
                return;
            }
            
            $processed_fields = \NeoJobBoard\DataSanitizer::ensure_required_name_field($processed_fields);

            $fields_json = json_encode($processed_fields, JSON_UNESCAPED_UNICODE);
            if ($fields_json === false) {
                error_log('JBI: JSON encoding error: ' . json_last_error_msg());
                wp_send_json_error(['message' => 'Fehler beim Kodieren der Felder']);
                return;
            }

            $result = $wpdb->insert(
                $wpdb->prefix . 'neo_job_board_templates',
                [
                    'name' => $name,
                    'description' => $description,
                    'fields' => $fields_json,
                    'is_active' => 0,
                    'created_by' => get_current_user_id()
                ],
                ['%s', '%s', '%s', '%d', '%d']
            );

            if ($result === false) {
                error_log('JBI: Database error: ' . $wpdb->last_error);
                wp_send_json_error(['message' => 'Fehler beim Speichern der Vorlage: ' . $wpdb->last_error]);
                return;
            }

            wp_send_json_success(['message' => 'Vorlage erfolgreich gespeichert']);
        } catch (\Exception $e) {
            error_log('JBI Error in ajax_save_template: ' . $e->getMessage());
            error_log('JBI Error trace: ' . $e->getTraceAsString());
            wp_send_json_error(['message' => 'Kritischer Fehler: ' . $e->getMessage()]);
        }
    }

    public function ajax_delete_template() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $template_id = intval($_POST['template_id'] ?? 0);

        $result = $wpdb->delete($wpdb->prefix . 'neo_job_board_templates', ['id' => $template_id], ['%d']);

        if ($result) {
            wp_send_json_success(['message' => 'Vorlage erfolgreich gelöscht']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Löschen der Vorlage']);
        }
    }

    public function ajax_create_application() {
        try {
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }

            global $wpdb;
            $template_id = intval($_POST['template_id'] ?? 0);
            $fields_data = $_POST['fields'] ?? [];

            if (!$template_id) {
                wp_send_json_error(['message' => 'Template ID fehlt']);
                return;
            }

            $hash_id = $this->generate_unique_hash();
            
            $position = sanitize_text_field($fields_data['wunschposition'] ?? $fields_data['position'] ?? '');
            $responsible_employee = get_current_user_id();

            $wpdb->query('START TRANSACTION');

            $result = $wpdb->insert(
                $wpdb->prefix . 'neo_job_board_applications',
                [
                    'hash_id' => $hash_id,
                    'template_id' => $template_id,
                    'position' => $position,
                    'responsible_employee' => $responsible_employee,
                    'is_active' => 1,
                    'application_data' => json_encode($fields_data, JSON_UNESCAPED_UNICODE)
                ],
                ['%s', '%d', '%s', '%d', '%d', '%s']
            );

            if (!$result) {
                $wpdb->query('ROLLBACK');
                wp_send_json_error(['message' => 'Fehler beim Speichern der Bewerbung']);
                return;
            }

            $application_id = $wpdb->insert_id;

            $template = $wpdb->get_row(
                $wpdb->prepare("SELECT fields FROM {$wpdb->prefix}neo_job_board_templates WHERE id = %d", $template_id),
                ARRAY_A
            );
            
            $template_fields = [];
            if ($template && !empty($template['fields'])) {
                $decoded = json_decode($template['fields'], true);
                if (is_array($decoded)) {
                    $template_fields = $decoded;
                }
            }

            $private_field_map = [];
            foreach ($template_fields as $tf) {
                $field_name_in_template = strtolower(trim($tf['name'] ?? $tf['field_name'] ?? ''));
                $field_label = strtolower(trim($tf['label'] ?? ''));
                $field_type = strtolower(trim($tf['type'] ?? ''));
                $is_private = !empty($tf['personal_data']);
                
                if ($field_name_in_template) {
                    $private_field_map[$field_name_in_template] = $is_private;
                }
                if ($field_label) {
                    $private_field_map[$field_label] = $is_private;
                }
                if ($field_type) {
                    $private_field_map[$field_type] = $is_private;
                }
            }

            foreach ($fields_data as $field_name => $field_value) {
                $field_value_to_save = $field_value;
                if (is_array($field_value)) {
                    $field_value_to_save = json_encode($field_value, JSON_UNESCAPED_UNICODE);
                }
                
                $field_name_lower = strtolower(trim($field_name));
                $is_personal = isset($private_field_map[$field_name_lower]) && $private_field_map[$field_name_lower] ? 1 : 0;
                
                $wpdb->insert(
                    $wpdb->prefix . 'neo_job_board_application_data',
                    [
                        'application_id' => $application_id,
                        'field_name' => $field_name,
                        'field_value' => $field_value_to_save,
                        'field_type' => 'text',
                        'is_personal' => $is_personal
                    ],
                    ['%d', '%s', '%s', '%s', '%d']
                );
            }

            $wpdb->query('COMMIT');

            $send_result = null;
            $auto_send = get_option('jbi_auto_send', 1);
            if ($auto_send) {
                $send_result = $this->send_application($application_id, 'create');
                if (!$send_result['success']) {
                    $wpdb->update(
                        $wpdb->prefix . 'neo_job_board_applications',
                        ['is_active' => 0, 'updated_at' => current_time('mysql')],
                        ['id' => $application_id],
                        ['%d', '%s'],
                        ['%d']
                    );
                }
            }

            $message = 'Bewerbung erfolgreich erstellt';
            if ($send_result && $send_result['success']) {
                $message .= ' und gesendet';
            } elseif ($send_result && !$send_result['success']) {
                $message .= '. Fehler beim Senden an API - Bewerbung ist nicht aktiv. Bitte synchronisieren.';
            }

            wp_send_json_success([
                'message' => $message,
                'application_id' => $application_id,
                'hash' => $hash_id
            ]);
        } catch (Exception $e) {
            if (isset($wpdb)) {
                $wpdb->query('ROLLBACK');
            }
            error_log('JBI Error in ajax_create_application: ' . $e->getMessage());
            error_log('JBI Error trace: ' . $e->getTraceAsString());
            wp_send_json_error(['message' => 'Fehler beim Erstellen der Bewerbung: ' . $e->getMessage()]);
        }
    }

    private function generate_unique_hash() {
        global $wpdb;
        $attempts = 0;
        $max_attempts = 10;

        do {
            $hash = substr(md5(uniqid((string)rand(), true)), 0, 32);
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}neo_job_board_applications WHERE hash_id = %s",
                $hash
            ));
            $attempts++;
        } while ($exists > 0 && $attempts < $max_attempts);

        return $hash;
    }

    public function ajax_get_applications() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_job_board_applications';
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            wp_send_json_success(['applications' => []]);
            return;
        }

        $current_user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        $where_clauses = [];
        $where_values = [];
        
        $requested_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if ($requested_user_id > 0) {
            $where_clauses[] = "a.responsible_employee = %d";
            $where_values[] = $requested_user_id;
        } elseif (!in_array('administrator', $user->roles) && !in_array('neo_editor', $user->roles)) {
            $where_clauses[] = "a.responsible_employee = %d";
            $where_values[] = $current_user_id;
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $query = "SELECT a.*, t.name as template_name, u.display_name as user_name
                  FROM $table_name a 
                  LEFT JOIN {$wpdb->prefix}neo_job_board_templates t ON a.template_id = t.id
                  LEFT JOIN {$wpdb->users} u ON a.responsible_employee = u.ID
                  $where_sql
                  ORDER BY a.is_called DESC, a.created_at DESC LIMIT 100";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $applications = $wpdb->get_results($query, ARRAY_A);

        if ($applications === null) {
            wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
            return;
        }

        
        foreach ($applications as &$application) {
            $application_data_json = $application['application_data'] ?? null;
            if ($application_data_json) {
                $application_data = json_decode($application_data_json, true);
                if (is_array($application_data)) {
                    $name_value = null;
                    foreach ($application_data as $key => $value) {
                        if (strpos(strtolower($key), 'name') === 0) {
                            $name_value = $value;
                            break;
                        }
                    }
                    if ($name_value !== null) {
                        if (is_array($name_value)) {
                            $application['name'] = implode(' ', array_filter($name_value));
                        } else {
                            $application['name'] = $name_value;
                        }
                    } else {
                        $application['name'] = null;
                    }
                } else {
                    $application['name'] = null;
                }
            } else {
                $application['name'] = null;
            }
        }
        unset($application);

        wp_send_json_success(['applications' => $applications]);
    }

    private function check_application_permission($application_id) {
        global $wpdb;
        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT responsible_employee FROM {$wpdb->prefix}neo_job_board_applications WHERE id = %d",
            $application_id
        ));
        
        if (!$application) {
            return false;
        }
        
        $current_user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        if (in_array('administrator', $user->roles) || in_array('neo_editor', $user->roles)) {
            return true;
        }
        
        if ($application->responsible_employee == $current_user_id) {
            return true;
        }
        
        return false;
    }

    public function ajax_get_application() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $application_id = intval($_POST['application_id'] ?? 0);
        
        if (!$application_id) {
            wp_send_json_error(['message' => 'Application ID fehlt']);
            return;
        }
        
        if (!$this->check_application_permission($application_id)) {
            wp_send_json_error(['message' => 'Sie haben keine Berechtigung, auf diese Bewerbung zuzugreifen.']);
            return;
        }
        
        $wpdb->update(
            $wpdb->prefix . 'neo_job_board_applications',
            ['is_called' => 0],
            ['id' => $application_id],
            ['%d'],
            ['%d']
        );

        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, t.name as template_name, t.fields as template_fields
             FROM {$wpdb->prefix}neo_job_board_applications a 
             LEFT JOIN {$wpdb->prefix}neo_job_board_templates t ON a.template_id = t.id 
             WHERE a.id = %d",
            $application_id
        ), ARRAY_A);

        if (!$application) {
            wp_send_json_error(['message' => 'Bewerbung nicht gefunden']);
            return;
        }

        $application_data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}neo_job_board_application_data WHERE application_id = %d",
            $application_id
        ), ARRAY_A);

        $fields_data = [];
        foreach ($application_data as $row) {
            $decoded = json_decode($row['field_value'], true);
            $fields_data[$row['field_name']] = $decoded !== null ? $decoded : $row['field_value'];
        }

        $application['fields_data'] = $fields_data;
        $application['template_fields'] = json_decode($application['template_fields'] ?? '[]', true);

        wp_send_json_success(['application' => $application]);
    }

    public function ajax_update_application() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $application_id = intval($_POST['application_id'] ?? 0);
        $fields_data = $_POST['fields'] ?? [];

        if (!$application_id) {
            wp_send_json_error(['message' => 'Application ID fehlt']);
            return;
        }
        
        if (!$this->check_application_permission($application_id)) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }

        // Сначала отправляем на API (с временными данными)
        // Для этого нужно получить текущие данные приложения и подготовить payload с новыми данными
        $send_result = $this->send_application_update_preview($application_id, $fields_data);
        
        if (!$send_result['success']) {
            wp_send_json_error(['message' => 'Fehler beim Senden an API: ' . $send_result['message']]);
            return;
        }

        $responsible_employee = isset($_POST['responsible_employee']) ? intval($_POST['responsible_employee']) : null;
        
        $update_data = [
            'application_data' => json_encode($fields_data, JSON_UNESCAPED_UNICODE),
            'updated_at' => current_time('mysql')
        ];
        $update_format = ['%s', '%s'];
        
        if ($responsible_employee !== null && $responsible_employee > 0) {
            $update_data['responsible_employee'] = $responsible_employee;
            $update_format[] = '%d';
        }

        $wpdb->query('START TRANSACTION');

        $result = $wpdb->update(
            $wpdb->prefix . 'neo_job_board_applications',
            $update_data,
            ['id' => $application_id],
            $update_format,
            ['%d']
        );

        if ($result === false) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => 'Fehler beim Aktualisieren']);
            return;
        }

        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT fields FROM {$wpdb->prefix}neo_job_board_templates WHERE id = (SELECT template_id FROM {$wpdb->prefix}neo_job_board_applications WHERE id = %d)",
            $application_id
        ));

        if ($application) {
            $template_fields = json_decode($application->fields ?? '[]', true);
            $private_field_map = [];
            foreach ($template_fields as $tf) {
                $field_name_in_template = strtolower(trim($tf['name'] ?? $tf['field_name'] ?? ''));
                $field_label = strtolower(trim($tf['label'] ?? ''));
                $is_private = !empty($tf['personal_data']);
                
                if ($field_name_in_template) {
                    $private_field_map[$field_name_in_template] = $is_private;
                }
                if ($field_label) {
                    $private_field_map[$field_label] = $is_private;
                }
            }

            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}neo_job_board_application_data WHERE application_id = %d",
                $application_id
            ));

            foreach ($fields_data as $field_name => $field_value) {
                $field_value_to_save = is_array($field_value) ? json_encode($field_value, JSON_UNESCAPED_UNICODE) : $field_value;
                $field_name_lower = strtolower(trim($field_name));
                $is_personal = isset($private_field_map[$field_name_lower]) && $private_field_map[$field_name_lower] ? 1 : 0;

                $wpdb->insert(
                    $wpdb->prefix . 'neo_job_board_application_data',
                    [
                        'application_id' => $application_id,
                        'field_name' => $field_name,
                        'field_value' => $field_value_to_save,
                        'field_type' => 'text',
                        'is_personal' => $is_personal
                    ],
                    ['%d', '%s', '%s', '%s', '%d']
                );
            }
        }

        $wpdb->query('COMMIT');

        wp_send_json_success(['message' => 'Bewerbung erfolgreich aktualisiert']);
    }

    public function ajax_toggle_application_active() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $application_id = intval($_POST['application_id'] ?? 0);
        $is_active = intval($_POST['is_active'] ?? 0);

        if (!$application_id) {
            wp_send_json_error(['message' => 'Application ID fehlt']);
            return;
        }
        
        if (!$this->check_application_permission($application_id)) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }

        // Сначала отправляем на API с новым статусом
        $send_result = $this->send_application_status_change($application_id, $is_active);
        
        if (!$send_result['success']) {
            wp_send_json_error(['message' => 'Fehler beim Senden an API: ' . $send_result['message']]);
            return;
        }

        // Только после успешного ответа от API - обновляем локально
        $result = $wpdb->update(
            $wpdb->prefix . 'neo_job_board_applications',
            ['is_active' => $is_active, 'updated_at' => current_time('mysql')],
            ['id' => $application_id],
            ['%d', '%s'],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error(['message' => 'Fehler beim Aktualisieren']);
            return;
        }

        wp_send_json_success(['message' => 'Status erfolgreich geändert', 'is_active' => $is_active]);
    }

    public function ajax_delete_application() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $application_id = intval($_POST['application_id'] ?? 0);
        $job_found = isset($_POST['job_found']) ? (intval($_POST['job_found']) == 1) : false;

        if (!$application_id) {
            wp_send_json_error(['message' => 'Application ID fehlt']);
            return;
        }
        
        if (!$this->check_application_permission($application_id)) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }

        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT hash_id FROM {$wpdb->prefix}neo_job_board_applications WHERE id = %d",
            $application_id
        ));

        if (!$application) {
            wp_send_json_error(['message' => 'Bewerbung nicht gefunden']);
            return;
        }

        $hash_id = $application->hash_id;
        
        // Сначала отправляем запрос на удаление в API
        $send_result = $this->send_application_delete($application_id, $hash_id);
        
        if (!$send_result['success']) {
            wp_send_json_error(['message' => 'Fehler beim Senden an API: ' . $send_result['message']]);
            return;
        }

        // Только после успешного ответа от API - удаляем локально
        $wpdb->query('START TRANSACTION');

        if ($job_found) {
            $wpdb->insert(
                $wpdb->prefix . 'neo_job_board_found_jobs',
                [
                    'application_id' => $application_id,
                    'application_hash' => $hash_id,
                    'found_date' => current_time('mysql')
                ],
                ['%d', '%s', '%s']
            );
        }

        $wpdb->delete(
            $wpdb->prefix . 'neo_job_board_applications',
            ['id' => $application_id],
            ['%d']
        );

        $wpdb->query('COMMIT');

        wp_send_json_success(['message' => 'Bewerbung erfolgreich gelöscht']);
    }

    public function ajax_get_found_jobs_stats() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}neo_job_board_found_jobs");
        $this_month = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}neo_job_board_found_jobs WHERE MONTH(found_date) = MONTH(CURRENT_DATE()) AND YEAR(found_date) = YEAR(CURRENT_DATE())");
        $this_year = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}neo_job_board_found_jobs WHERE YEAR(found_date) = YEAR(CURRENT_DATE())");

        wp_send_json_success([
            'total' => intval($total),
            'this_month' => intval($this_month),
            'this_year' => intval($this_year)
        ]);
    }

    public function ajax_get_users() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        $users = get_users(['fields' => ['ID', 'display_name', 'user_email']]);
        wp_send_json_success(['users' => $users]);
    }

    public function ajax_sync_application() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $application_id = intval($_POST['application_id'] ?? 0);

        if (!$application_id) {
            wp_send_json_error(['message' => 'Application ID fehlt']);
            return;
        }

        if (!$this->check_application_permission($application_id)) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }

        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}neo_job_board_applications WHERE id = %d",
            $application_id
        ));

        if (!$application) {
            wp_send_json_error(['message' => 'Bewerbung nicht gefunden']);
            return;
        }

        $api_url = get_option('jbi_api_url');
        $api_key = get_option('jbi_api_key');

        if (empty($api_url) || empty($api_key)) {
            wp_send_json_error(['message' => 'API nicht konfiguriert']);
            return;
        }

        $hash = substr($application->hash_id, 0, 8);
        $check_endpoint = rtrim($api_url, '/') . '/applications/check/' . $hash;

        $check_response = wp_remote_get($check_endpoint, [
            'headers' => [
                'X-API-Key' => $api_key,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30,
            'sslverify' => false
        ]);

        $exists_on_remote = false;
        if (!is_wp_error($check_response)) {
            $code = wp_remote_retrieve_response_code($check_response);
            if ($code === 200) {
                $body = wp_remote_retrieve_body($check_response);
                $data = json_decode($body, true);
                $exists_on_remote = isset($data['exists']) && $data['exists'] === true;
            }
        }

        if ($exists_on_remote) {
            $wpdb->update(
                $wpdb->prefix . 'neo_job_board_applications',
                ['is_active' => 1, 'updated_at' => current_time('mysql')],
                ['id' => $application_id],
                ['%d', '%s'],
                ['%d']
            );
            wp_send_json_success(['message' => 'Bewerbung existiert bereits auf dem entfernten Server. Status aktualisiert.']);
            return;
        }

        $send_result = $this->send_application($application_id, 'create');

        if ($send_result['success']) {
            $wpdb->update(
                $wpdb->prefix . 'neo_job_board_applications',
                ['is_active' => 1, 'updated_at' => current_time('mysql')],
                ['id' => $application_id],
                ['%d', '%s'],
                ['%d']
            );
            wp_send_json_success(['message' => 'Bewerbung erfolgreich synchronisiert und aktiviert.']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Senden: ' . $send_result['message']]);
        }
    }

    public function ajax_send_template() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $template_id = intval($_POST['template_id'] ?? 0);
        
        $result = $this->send_template($template_id);
        
        if ($result['success']) {
            $wpdb->update(
                $wpdb->prefix . 'neo_job_board_templates',
                ['is_active' => 0],
                ['is_active' => 1]
            );
            
            $wpdb->update(
                $wpdb->prefix . 'neo_job_board_templates',
                ['is_active' => 1],
                ['id' => $template_id]
            );
            
            wp_send_json_success(['message' => $result['message'] . ' Template ist jetzt aktiv.']);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    public function auto_send_application($application_id) {
        $auto_send = get_option('jbi_auto_send', 1);
        if ($auto_send) {
            $send_result = $this->send_application($application_id);
            if (!$send_result['success']) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'neo_job_board_applications',
                    ['is_active' => 0, 'updated_at' => current_time('mysql')],
                    ['id' => $application_id],
                    ['%d', '%s'],
                    ['%d']
                );
            }
        }
    }

    private function send_template($template_id) {
        global $wpdb;
        $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_templates WHERE id = %d", $template_id));

        if (!$template) {
            $this->log('template', $template_id, 'error', 'Template nicht gefunden');
            return ['success' => false, 'message' => 'Template nicht gefunden'];
        }

        $api_url = get_option('jbi_api_url');
        $api_key = get_option('jbi_api_key');

        if (empty($api_url) || empty($api_key)) {
            $this->log('template', $template_id, 'error', 'API nicht konfiguriert');
            return ['success' => false, 'message' => 'API nicht konfiguriert'];
        }

        $fields = json_decode($template->fields, true);
        
        if (empty($fields) || !is_array($fields)) {
            $this->log('template', $template_id, 'error', 'Keine Felder im Template');
            return ['success' => false, 'message' => 'Template hat keine Felder'];
        }
        
        $formatted_data = $this->format_template_for_api($fields);
        
        $payload = [
            'template_id' => $template_id,
            'template_name' => $template->name,
            'fields' => $formatted_data['fields'],
            'filterable_fields' => $formatted_data['filterable_fields']
        ];

        error_log('JBI Template payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));
        
        $endpoint = rtrim($api_url, '/') . '/templates/receive';
        
        $response = wp_remote_post($endpoint, [
            'headers' => [
                'X-API-Key' => $api_key,
                'Content-Type' => 'application/json; charset=utf-8'
            ],
            'body' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            $this->log('template', $template_id, 'error', $response->get_error_message());
            return ['success' => false, 'message' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($code === 200 || $code === 201) {
            $this->log('template', $template_id, 'success', 'Erfolgreich gesendet', $body);
            return ['success' => true, 'message' => 'Template erfolgreich gesendet an ' . $endpoint];
        } else {
            $this->log('template', $template_id, 'error', 'Fehler ' . $code, $body);
            return ['success' => false, 'message' => 'Fehler: ' . $code . ' - URL: ' . $endpoint . ' - Response: ' . substr($body, 0, 100)];
        }
    }

    private function send_application_update_preview($application_id, $new_fields_data) {
        try {
            global $wpdb;
            $application = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_applications WHERE id = %d", $application_id));

            if (!$application) {
                $this->log('application', $application_id, 'error', 'Bewerbung nicht gefunden');
                return ['success' => false, 'message' => 'Bewerbung nicht gefunden'];
            }

            $api_url = get_option('jbi_api_url');
            $api_key = get_option('jbi_api_key');

            if (empty($api_url) || empty($api_key)) {
                $this->log('application', $application_id, 'error', 'API nicht konfiguriert');
                return ['success' => false, 'message' => 'API nicht konfiguriert'];
            }

            $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_templates WHERE id = %d", $application->template_id));
            if (!$template) {
                $this->log('application', $application_id, 'error', 'Template nicht gefunden');
                return ['success' => false, 'message' => 'Template nicht gefunden'];
            }

            $template_fields = json_decode($template->fields, true);
            if (!is_array($template_fields)) {
                $template_fields = [];
            }
            
            $hash = substr($application->hash_id, 0, 8);
            
            $private_field_map = [];
            foreach ($template_fields as $tf) {
                $field_name_in_template = strtolower(trim($tf['name'] ?? $tf['field_name'] ?? ''));
                $field_label = strtolower(trim($tf['label'] ?? ''));
                $is_private = !empty($tf['personal_data']);
                
                if ($field_name_in_template) {
                    $private_field_map[$field_name_in_template] = $is_private;
                }
                if ($field_label) {
                    $private_field_map[$field_label] = $is_private;
                }
            }
            
            $filled_data = [];
            foreach ($new_fields_data as $field_name => $field_value) {
                $field_name_lower = strtolower(trim($field_name));
                
                $is_personal = false;
                if (isset($private_field_map[$field_name_lower])) {
                    $is_personal = $private_field_map[$field_name_lower];
                }
                
                if (!$is_personal) {
                    $filled_data[$field_name] = is_array($field_value) ? $field_value : $field_value;
                }
            }

            $payload = [
                'template_id' => $application->template_id,
                'hash' => $hash,
                'filled_data' => $filled_data,
                'action' => 'update',
                'is_active' => $application->is_active ?? 1
            ];

            error_log('JBI Application update preview payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));

            $endpoint = rtrim($api_url, '/') . '/applications/receive';
            
            $response = wp_remote_post($endpoint, [
                'headers' => [
                    'X-API-Key' => $api_key,
                    'Content-Type' => 'application/json; charset=utf-8'
                ],
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'timeout' => 30,
                'sslverify' => false
            ]);

            if (is_wp_error($response)) {
                $this->log('application', $application_id, 'error', $response->get_error_message());
                return ['success' => false, 'message' => $response->get_error_message()];
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($code === 200 || $code === 201) {
                $this->log('application', $application_id, 'success', 'Update erfolgreich gesendet', $body);
                return ['success' => true, 'message' => 'Bewerbung erfolgreich aktualisiert'];
            } else {
                $this->log('application', $application_id, 'error', 'Fehler ' . $code, $body);
                return ['success' => false, 'message' => 'Fehler: ' . $code . ' - ' . substr($body, 0, 100)];
            }
        } catch (Exception $e) {
            error_log('JBI Error in send_application_update_preview: ' . $e->getMessage());
            error_log('JBI Error trace: ' . $e->getTraceAsString());
            $this->log('application', $application_id, 'error', $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function send_application_status_change($application_id, $new_is_active) {
        try {
            global $wpdb;
            $application = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_applications WHERE id = %d", $application_id));

            if (!$application) {
                $this->log('application', $application_id, 'error', 'Bewerbung nicht gefunden');
                return ['success' => false, 'message' => 'Bewerbung nicht gefunden'];
            }

            $api_url = get_option('jbi_api_url');
            $api_key = get_option('jbi_api_key');

            if (empty($api_url) || empty($api_key)) {
                $this->log('application', $application_id, 'error', 'API nicht konfiguriert');
                return ['success' => false, 'message' => 'API nicht konfiguriert'];
            }

            $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_templates WHERE id = %d", $application->template_id));
            if (!$template) {
                $this->log('application', $application_id, 'error', 'Template nicht gefunden');
                return ['success' => false, 'message' => 'Template nicht gefunden'];
            }

            $template_fields = json_decode($template->fields, true);
            if (!is_array($template_fields)) {
                $template_fields = [];
            }
            
            $hash = substr($application->hash_id, 0, 8);
            $filled_data = $this->prepare_application_data($application_id, $template_fields);

            $payload = [
                'template_id' => $application->template_id,
                'hash' => $hash,
                'filled_data' => $filled_data,
                'action' => 'update_status',
                'is_active' => $new_is_active
            ];

            error_log('JBI Application status change payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));

            $endpoint = rtrim($api_url, '/') . '/applications/receive';
            
            $response = wp_remote_post($endpoint, [
                'headers' => [
                    'X-API-Key' => $api_key,
                    'Content-Type' => 'application/json; charset=utf-8'
                ],
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'timeout' => 30,
                'sslverify' => false
            ]);

            if (is_wp_error($response)) {
                $this->log('application', $application_id, 'error', $response->get_error_message());
                return ['success' => false, 'message' => $response->get_error_message()];
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($code === 200 || $code === 201) {
                $this->log('application', $application_id, 'success', 'Statusänderung erfolgreich gesendet', $body);
                return ['success' => true, 'message' => 'Status erfolgreich geändert'];
            } else {
                $this->log('application', $application_id, 'error', 'Fehler ' . $code, $body);
                return ['success' => false, 'message' => 'Fehler: ' . $code . ' - ' . substr($body, 0, 100)];
            }
        } catch (Exception $e) {
            error_log('JBI Error in send_application_status_change: ' . $e->getMessage());
            error_log('JBI Error trace: ' . $e->getTraceAsString());
            $this->log('application', $application_id, 'error', $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function send_application($application_id, $action = 'create') {
        try {
            global $wpdb;
            $application = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_applications WHERE id = %d", $application_id));

            if (!$application) {
                $this->log('application', $application_id, 'error', 'Bewerbung nicht gefunden');
                return ['success' => false, 'message' => 'Bewerbung nicht gefunden'];
            }

            $api_url = get_option('jbi_api_url');
            $api_key = get_option('jbi_api_key');

            if (empty($api_url) || empty($api_key)) {
                $this->log('application', $application_id, 'error', 'API nicht konfiguriert');
                return ['success' => false, 'message' => 'API nicht konfiguriert'];
            }

            $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_templates WHERE id = %d", $application->template_id));
            if (!$template) {
                $this->log('application', $application_id, 'error', 'Template nicht gefunden');
                return ['success' => false, 'message' => 'Template nicht gefunden'];
            }

            $template_fields = json_decode($template->fields, true);
            if (!is_array($template_fields)) {
                $template_fields = [];
            }
            
            $hash = substr($application->hash_id, 0, 8);
            $filled_data = $this->prepare_application_data($application_id, $template_fields);

            $payload = [
                'template_id' => $application->template_id,
                'hash' => $hash,
                'filled_data' => $filled_data,
                'action' => $action,
                'is_active' => $application->is_active ?? 1
            ];

            if ($action === 'update_status') {
                $payload['is_active'] = $application->is_active;
            }

            error_log('JBI Application payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));

            $endpoint = rtrim($api_url, '/') . '/applications/receive';
            
            $response = wp_remote_post($endpoint, [
                'headers' => [
                    'X-API-Key' => $api_key,
                    'Content-Type' => 'application/json; charset=utf-8'
                ],
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'timeout' => 30,
                'sslverify' => false
            ]);

            if (is_wp_error($response)) {
                $this->log('application', $application_id, 'error', $response->get_error_message());
                return ['success' => false, 'message' => $response->get_error_message()];
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($code === 200 || $code === 201) {
                $this->log('application', $application_id, 'success', 'Erfolgreich gesendet', $body);
                return ['success' => true, 'message' => 'Bewerbung erfolgreich gesendet'];
            } else {
                $this->log('application', $application_id, 'error', 'Fehler ' . $code, $body);
                return ['success' => false, 'message' => 'Fehler: ' . $code . ' - ' . substr($body, 0, 100)];
            }
        } catch (Exception $e) {
            error_log('JBI Error in send_application: ' . $e->getMessage());
            error_log('JBI Error trace: ' . $e->getTraceAsString());
            $this->log('application', $application_id, 'error', $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function send_application_delete($application_id, $hash_id) {
        try {
            $api_url = get_option('jbi_api_url');
            $api_key = get_option('jbi_api_key');

            if (empty($api_url) || empty($api_key)) {
                $this->log('application', $application_id, 'error', 'API nicht konfiguriert');
                return ['success' => false, 'message' => 'API nicht konfiguriert'];
            }

            $hash = substr($hash_id, 0, 8);
            $payload = [
                'hash' => $hash,
                'action' => 'delete'
            ];

            error_log('JBI Application delete payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));

            $endpoint = rtrim($api_url, '/') . '/applications/receive';
            
            $response = wp_remote_post($endpoint, [
                'headers' => [
                    'X-API-Key' => $api_key,
                    'Content-Type' => 'application/json; charset=utf-8'
                ],
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'timeout' => 30,
                'sslverify' => false
            ]);

            if (is_wp_error($response)) {
                $this->log('application', $application_id, 'error', $response->get_error_message());
                return ['success' => false, 'message' => $response->get_error_message()];
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($code === 200 || $code === 201 || $code === 204) {
                $this->log('application', $application_id, 'success', 'Erfolgreich gelöscht', $body);
                return ['success' => true, 'message' => 'Bewerbung erfolgreich gelöscht'];
            } else {
                $this->log('application', $application_id, 'error', 'Fehler ' . $code, $body);
                return ['success' => false, 'message' => 'Fehler: ' . $code . ' - ' . substr($body, 0, 100)];
            }
        } catch (Exception $e) {
            error_log('JBI Error in send_application_delete: ' . $e->getMessage());
            $this->log('application', $application_id, 'error', $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function format_template_for_api($fields) {
        $formatted_fields = [];
        $filterable_fields = [];
        
        foreach ($fields as $index => $field) {
            $field_name = $field['label'] ?? '';
            $field_type = $field['type'] ?? 'text';
            
            $formatted_fields[] = [
                'field_id' => 'field_' . ($index + 1),
                'name' => $field_name,
                'type' => $field_type,
                'label' => $field_name,
                'required' => $field['required'] ?? false,
                'is_personal_data' => $field['personal_data'] ?? false,
                'is_system_field' => false
            ];
            
            if (!empty($field['filterable'])) {
                $filterable_fields[] = $field_name;
            }
        }
        
        return [
            'fields' => $formatted_fields,
            'filterable_fields' => $filterable_fields
        ];
    }

    private function prepare_application_data($application_id, $template_fields) {
        global $wpdb;
        
        $application_data = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_application_data WHERE application_id = %d", $application_id),
            ARRAY_A
        );

        $data = [];
        foreach ($application_data as $row) {
            $field_name = $row['field_name'] ?? '';
            $field_value = $row['field_value'] ?? '';
            $is_personal = intval($row['is_personal'] ?? 0);
            
            if ($is_personal === 1) {
                continue;
            }
            
            $decoded = json_decode($field_value, true);
            if ($decoded !== null && is_array($decoded)) {
                $data[$field_name] = $decoded;
            } else {
                $data[$field_name] = $field_value;
            }
        }

        return $data;
    }

    private function log($type, $entity_id, $status, $message, $response_data = null) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'neo_job_board_api_logs',
            [
                'template_id' => $entity_id,
                'action' => $type,
                'endpoint' => get_option('jbi_api_url'),
                'method' => 'POST',
                'request_data' => $message,
                'response_data' => $response_data,
                'status_code' => 0,
                'success' => $status === 'success' ? 1 : 0,
                'error_message' => $status === 'error' ? $message : null
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s']
        );
    }
}

new Job_Board_Integration();
