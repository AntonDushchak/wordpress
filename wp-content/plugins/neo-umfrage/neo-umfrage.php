<?php
/**
 * Plugin Name: Neo Umfrage
 * Description: Plugin für Erstellung und Verwaltung von Umfragen in WordPress
 * Version: 1.0.0
 * Author: Anton Dushchak
 * Text Domain: neo-umfrage
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Определяем константы плагина
define('NEO_UMFRAGE_VERSION', '1.0.0');
define('NEO_UMFRAGE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NEO_UMFRAGE_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Проверяем наличие Neo Dashboard
add_action('plugins_loaded', static function () {
    if (!class_exists(\NeoDashboard\Core\Router::class)) {
        deactivate_plugins(plugin_basename(__FILE__));
        add_action('admin_notices', static function () {
            echo '<div class="notice notice-error"><p>';
            esc_html_e(
                'Neo Umfrage wurde deaktiviert, da "Neo Dashboard Core" nicht aktiv ist.',
                'neo-umfrage'
            );
            echo '</p></div>';
        });
        return;
    }

    // Подключаем CSS для плагина
    add_action('neo_dashboard_enqueue_plugin_assets_css', function () {
        wp_enqueue_style(
            'neo-umfrage-css',
            NEO_UMFRAGE_PLUGIN_URL . 'assets/css/neo-umfrage.css',
            [],
            NEO_UMFRAGE_VERSION
        );
    });

    // Подключаем JS для плагина
    add_action('neo_dashboard_enqueue_plugin_assets_js', function () {
        // Основной координатор (загружается первым)
        wp_enqueue_script(
            'neo-umfrage-js',
            NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage.js',
            ['jquery'],
            NEO_UMFRAGE_VERSION,
            true
        );

        // Модальные окна и формы
        wp_enqueue_script(
            'neo-umfrage-modals-js',
            NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage-modals.js',
            ['jquery', 'neo-umfrage-js'],
            NEO_UMFRAGE_VERSION,
            true
        );

        // Работа с анкетами
        wp_enqueue_script(
            'neo-umfrage-surveys-js',
            NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage-surveys.js',
            ['jquery', 'neo-umfrage-js'],
            NEO_UMFRAGE_VERSION,
            true
        );

        // Работа с шаблонами
        wp_enqueue_script(
            'neo-umfrage-templates-js',
            NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage-templates.js',
            ['jquery', 'neo-umfrage-js'],
            NEO_UMFRAGE_VERSION,
            true
        );

        // Статистика
        wp_enqueue_script(
            'neo-umfrage-statistics-js',
            NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage-statistics.js',
            ['jquery', 'neo-umfrage-js'],
            NEO_UMFRAGE_VERSION,
            true
        );

        // Локализация скрипта
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        $ajax_data = [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('neo_umfrage_nonce'),
            'userRoles' => $user_roles,
            'currentUserId' => $current_user->ID,
            'strings' => [
                'error' => 'Ein Fehler ist aufgetreten',
                'success' => 'Operation erfolgreich ausgeführt',
                'confirm_delete' => 'Sind Sie sicher, dass Sie dieses Element löschen möchten?',
                'loading' => 'Laden...',
                'no_data' => 'Keine Daten gefunden'
            ]
        ];
        
        // Локализуем для всех JS файлов
        wp_localize_script('neo-umfrage-js', 'neoUmfrageAjax', $ajax_data);
        wp_localize_script('neo-umfrage-modals-js', 'neoUmfrageAjax', $ajax_data);
        wp_localize_script('neo-umfrage-surveys-js', 'neoUmfrageAjax', $ajax_data);
        wp_localize_script('neo-umfrage-templates-js', 'neoUmfrageAjax', $ajax_data);
        wp_localize_script('neo-umfrage-statistics-js', 'neoUmfrageAjax', $ajax_data);
    });

    // Регистрируем элемент в боковом меню
    add_action('neo_dashboard_register_sidebar_item', function () {
        return [
            'id' => 'neo-umfrage',
            'title' => 'Umfragen',
            'icon' => 'dashicons-feedback',
            'order' => 10
        ];
    });

    // Регистрируем элементы Dashboard
    add_action('neo_dashboard_init', static function () {

        // Создаем группу в боковой панели
        do_action('neo_dashboard_register_sidebar_item', [
            'slug'     => 'neo-umfrage-group',
            'label'    => 'Neo Umfrage',
            'icon'     => 'bi-clipboard-data',
            'url'      => '/neo-dashboard/neo-umfrage',
            'position' => 25,
            'is_group' => true,
        ]);

        // Создаем подсекции
        $sections = [
            'surveys' => [
                'label' => 'Umfragen',
                'icon'  => 'bi-clipboard-check',
                'pos'   => 26,
            ],
            
            'statistics' => [
                'label' => 'Statistik',
                'icon'  => 'bi-graph-up',
                'pos'   => 28,
            ],
        ];

        if(current_user_can('manage_options')) {
            $sections['templates'] = [
                'label' => 'Vorlagen',
                'icon'  => 'bi-file-earmark-text',
                    'pos'   => 27,
                ];
            }

        foreach ($sections as $slug => $data) {
            $full_slug = 'neo-umfrage/' . $slug;

            // Регистрируем элемент боковой панели
            do_action('neo_dashboard_register_sidebar_item', [
                'slug'     => $full_slug,
                'label'    => $data['label'],
                'icon'     => $data['icon'],
                'url'      => '/neo-dashboard/' . $full_slug,
                'parent'   => 'neo-umfrage-group',
                'position' => $data['pos'],
            ]);

            // Регистрируем секцию с уникальным callback
            do_action('neo_dashboard_register_section', [
                'slug'     => $full_slug,
                'label'    => $data['label'],
                'callback' => 'neo_umfrage_' . $slug . '_callback',
            ]);
        }

        // Регистрируем главную секцию
        do_action('neo_dashboard_register_section', [
            'slug'     => 'neo-umfrage',
            'label'    => 'Neo Umfrage',
            'callback' => 'neo_umfrage_main_section_callback',
        ]);

        // Регистрируем виджет
        do_action('neo_dashboard_register_widget', [
            'id'       => 'neo-umfrage-widget',
            'label'    => 'Umfragen',
            'icon'     => 'bi-clipboard-data',
            'priority' => 10,
            'callback' => 'neo_umfrage_widget_callback',
            'order' => 10
        ]);
    });

    // Инициализируем плагин
    new NeoUmfrage();
});

/**
 * Основной класс плагина
 */
class NeoUmfrage {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_ajax_neo_umfrage_save_survey', [$this, 'save_survey']);
        add_action('wp_ajax_neo_umfrage_save_template', [$this, 'save_template']);
        add_action('wp_ajax_neo_umfrage_delete_survey', [$this, 'delete_survey']);
        add_action('wp_ajax_neo_umfrage_delete_template', [$this, 'delete_template']);
        add_action('wp_ajax_neo_umfrage_get_surveys', [$this, 'get_surveys']);
        add_action('wp_ajax_neo_umfrage_get_templates', [$this, 'get_templates']);
        add_action('wp_ajax_neo_umfrage_get_template', [$this, 'get_template']);
        add_action('wp_ajax_neo_umfrage_get_statistics', [$this, 'get_statistics']);
        add_action('wp_ajax_neo_umfrage_get_template_fields', [$this, 'get_template_fields']);
        add_action('wp_ajax_neo_umfrage_get_field_statistics', [$this, 'get_field_statistics']);
        add_action('wp_ajax_neo_umfrage_update_template', [$this, 'update_template']);
        add_action('wp_ajax_neo_umfrage_get_survey_data', [$this, 'get_survey_data']);
        
        // Устанавливаем часовой пояс Германии
        date_default_timezone_set('Europe/Berlin');
    }
    
    public function init() {
        // Загружаем текстовый домен для переводов
        load_plugin_textdomain('neo-umfrage', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Таблица шаблонов
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        $templates_sql = "CREATE TABLE $templates_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            fields longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Таблица анкет
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $surveys_sql = "CREATE TABLE $surveys_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            template_id mediumint(9) NOT NULL,
            name varchar(255) NOT NULL,
            description text,
            fields_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY template_id (template_id)
        ) $charset_collate;";
        
        // Таблица ответов
        $responses_table = $wpdb->prefix . 'neo_umfrage_responses';
        $responses_sql = "CREATE TABLE $responses_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            survey_id mediumint(9) NOT NULL,
            response_data longtext NOT NULL,
            user_id bigint(20),
            ip_address varchar(45),
            user_agent text,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY survey_id (survey_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($templates_sql);
        dbDelta($surveys_sql);
        dbDelta($responses_sql);
    }
    
    public function save_template() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }
        
        global $wpdb;
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        
        $fields = [];
        // Добавляем обязательные поля в начало
        $fields[] = [
            'label' => 'Name',
            'type' => 'text',
            'required' => true,
            'options' => []
        ];
        $fields[] = [
            'label' => 'Telefonnummer',
            'type' => 'tel',
            'required' => true,
            'options' => []
        ];
        
        if (isset($_POST['fields'])) {
            if (is_string($_POST['fields'])) {
                $fields_data = json_decode(stripslashes($_POST['fields']), true);
                if (is_array($fields_data)) {
                    foreach ($fields_data as $field) {
                        // Пропускаем обязательные поля, так как они уже добавлены выше
                        if ($field['label'] === 'Name' || $field['label'] === 'Telefonnummer') {
                            continue;
                        }
                        $fields[] = [
                            'label' => sanitize_text_field($field['label']),
                            'type' => sanitize_text_field($field['type']),
                            'required' => isset($field['required']) && $field['required'] === true,
                            'options' => isset($field['options']) ? array_map('sanitize_text_field', $field['options']) : []
                        ];
                    }
                }
            } elseif (is_array($_POST['fields'])) {
                foreach ($_POST['fields'] as $field) {
                    // Пропускаем обязательные поля, так как они уже добавлены выше
                    if ($field['label'] === 'Name' || $field['label'] === 'Telefonnummer') {
                        continue;
                    }
                    $fields[] = [
                        'label' => sanitize_text_field($field['label']),
                        'type' => sanitize_text_field($field['type']),
                        'required' => isset($field['required']) && $field['required'] === true,
                        'options' => isset($field['options']) ? array_map('sanitize_text_field', $field['options']) : []
                    ];
                }
            }
        }
        
        $result = $wpdb->insert(
            $templates_table,
            [
                'name' => $name,
                'description' => $description,
                'fields' => json_encode($fields)
            ],
            ['%s', '%s', '%s']
        );
        
        if ($result) {
            wp_send_json_success(['message' => 'Vorlage erfolgreich gespeichert']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Speichern der Vorlage']);
        }
    }
    
    public function save_survey() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }
        
        global $wpdb;
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $responses_table = $wpdb->prefix . 'neo_umfrage_responses';
        
        $template_id = intval($_POST['template_id']);
        
        if (!$template_id) {
            wp_send_json_error(['message' => 'Keine Vorlage ausgewählt']);
        }
        
        // Получаем данные шаблона
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $templates_table WHERE id = %d",
            $template_id
        ));
        
        if (!$template) {
            wp_send_json_error(['message' => 'Vorlage nicht gefunden']);
        }
        
        // Обрабатываем поля анкеты
        $survey_fields = [];
        if (isset($_POST['survey_fields'])) {
            if (is_string($_POST['survey_fields'])) {
                $survey_fields = json_decode(stripslashes($_POST['survey_fields']), true);
            }
        }
        
        // Создаем или обновляем анкету
        $survey_name = 'Umfrage nach Vorlage ' . $template->name;
        $survey_description = 'Umfrage, erstellt basierend auf Vorlage';
        
        // Проверяем, существует ли уже анкета для этого шаблона
        $existing_survey = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $surveys_table WHERE template_id = %d AND is_active = 1",
            $template_id
        ));
        
        if ($existing_survey) {
            $survey_id = $existing_survey->id;
        } else {
            $result = $wpdb->insert(
                $surveys_table,
                [
                    'template_id' => $template_id,
                    'name' => $survey_name,
                    'description' => $survey_description,
                    'fields_data' => json_encode($survey_fields)
                ],
                ['%d', '%s', '%s', '%s']
            );
            
            if (!$result) {
                wp_send_json_error(['message' => 'Fehler beim Erstellen der Umfrage']);
            }
            
            $survey_id = $wpdb->insert_id;
        }
        
        // Проверяем, редактируем ли мы существующий ответ
        $response_id = isset($_POST['response_id']) ? intval($_POST['response_id']) : 0;
        
        if ($response_id > 0) {
            // Обновляем существующий ответ
            $result = $wpdb->update(
                $responses_table,
                [
                    'response_data' => json_encode($survey_fields),
                    'user_id' => get_current_user_id(),
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT']
                ],
                ['id' => $response_id],
                ['%s', '%d', '%s', '%s'],
                ['%d']
            );
            
            if ($result !== false) {
                wp_send_json_success(['message' => 'Umfrage erfolgreich aktualisiert']);
            } else {
                wp_send_json_error(['message' => 'Fehler beim Aktualisieren der Umfrage']);
            }
        } else {
            // Создаем новый ответ
            $result = $wpdb->insert(
                $responses_table,
                [
                    'survey_id' => $survey_id,
                    'response_data' => json_encode($survey_fields),
                    'user_id' => get_current_user_id(),
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT']
                ],
                ['%d', '%s', '%d', '%s', '%s']
            );
            
            if ($result) {
                wp_send_json_success(['message' => 'Umfrage erfolgreich gespeichert']);
            } else {
                wp_send_json_error(['message' => 'Fehler beim Speichern der Umfrage']);
            }
        }
    }
    
    public function get_surveys() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            error_log('Neo Umfrage: Fehler bei der nonce-Überprüfung');
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }
        
        global $wpdb;
        $responses_table = $wpdb->prefix . 'neo_umfrage_responses';
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        // Проверяем, есть ли фильтр по шаблону
        $template_filter = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        $template_name_filter = isset($_POST['template_name']) ? sanitize_text_field($_POST['template_name']) : '';
        
        // Получаем все ответы (конкретные анкеты пользователей)
        $sql = "
            SELECT r.*, s.name as survey_name, t.name as template_name,
                   u.display_name as user_display_name,
                   um1.meta_value as first_name,
                   um2.meta_value as last_name
            FROM $responses_table r
            LEFT JOIN $surveys_table s ON r.survey_id = s.id
            LEFT JOIN $templates_table t ON s.template_id = t.id
            LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
            LEFT JOIN {$wpdb->usermeta} um1 ON (u.ID = um1.user_id AND um1.meta_key = 'first_name')
            LEFT JOIN {$wpdb->usermeta} um2 ON (u.ID = um2.user_id AND um2.meta_key = 'last_name')
        ";
        
        // Добавляем фильтр по шаблону, если указан
        if ($template_filter > 0) {
            $sql .= " WHERE t.id = %d";
            $sql .= " ORDER BY r.submitted_at DESC";
            $responses = $wpdb->get_results($wpdb->prepare($sql, $template_filter));
        } elseif (!empty($template_name_filter)) {
            $sql .= " WHERE t.name = %s";
            $sql .= " ORDER BY r.submitted_at DESC";
            $responses = $wpdb->get_results($wpdb->prepare($sql, $template_name_filter));
        } else {
            $sql .= " ORDER BY r.submitted_at DESC";
            $responses = $wpdb->get_results($sql);
        }
        
        $surveys = [];
        foreach ($responses as $response) {
            $response_data = json_decode($response->response_data, true);
            $name_value = 'Nicht ausgefüllt';
            $phone_value = 'Nicht ausgefüllt';
            
            if (is_array($response_data)) {
                foreach ($response_data as $field) {
                    if (isset($field['label']) && isset($field['value'])) {
                        if ($field['label'] === 'Name') {
                            $name_value = $field['value'] ?: 'Nicht ausgefüllt';
                        } elseif ($field['label'] === 'Telefonnummer') {
                            $phone_value = $field['value'] ?: 'Nicht ausgefüllt';
                        }
                    }
                }
            }
            
            // Формируем имя пользователя WordPress
            $wp_user_name = 'Unbekannter Benutzer';
            if ($response->first_name && $response->last_name) {
                $wp_user_name = $response->first_name . ' ' . $response->last_name;
            } elseif ($response->user_display_name) {
                $wp_user_name = $response->user_display_name;
            }
            
            $surveys[] = [
                'id' => $response->id,
                'response_id' => $response->id,
                'survey_id' => $response->survey_id,
                'template_name' => $response->template_name ?: 'Nicht angegeben',
                'survey_name' => $response->survey_name ?: 'Umfrage',
                'name_value' => $name_value,
                'phone_value' => $phone_value,
                'wp_user_name' => $wp_user_name,
                'submitted_at' => $response->submitted_at,
                'user_id' => $response->user_id,
                'ip_address' => $response->ip_address
            ];
        }
        
        wp_send_json_success($surveys);
    }
    
    public function get_templates() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }
        
        global $wpdb;
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        $templates = $wpdb->get_results("
            SELECT * FROM $templates_table 
            ORDER BY created_at DESC
        ");
        
        wp_send_json_success($templates);
    }
    
    public function get_template_fields() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }
        
        $template_id = intval($_POST['template_id']);
        
        global $wpdb;
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $templates_table WHERE id = %d",
            $template_id
        ));
        
        if (!$template) {
            wp_send_json_error(['message' => 'Vorlage nicht gefunden']);
        }
        
        $fields = json_decode($template->fields, true);
        if (!is_array($fields)) {
            $fields = [];
        }
        
        wp_send_json_success(['fields' => $fields]);
    }
    
    public function get_survey_data() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }
        
        $response_id = intval($_POST['survey_id']); // Это на самом деле response_id
        
        global $wpdb;
        $responses_table = $wpdb->prefix . 'neo_umfrage_responses';
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        // Получаем конкретный ответ (анкету пользователя)
        $response = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, s.name as survey_name, s.template_id, t.name as template_name,
                    u.display_name as user_display_name,
                    um1.meta_value as first_name,
                    um2.meta_value as last_name
             FROM $responses_table r
             LEFT JOIN $surveys_table s ON r.survey_id = s.id
             LEFT JOIN $templates_table t ON s.template_id = t.id
             LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
             LEFT JOIN {$wpdb->usermeta} um1 ON (u.ID = um1.user_id AND um1.meta_key = 'first_name')
             LEFT JOIN {$wpdb->usermeta} um2 ON (u.ID = um2.user_id AND um2.meta_key = 'last_name')
             WHERE r.id = %d",
            $response_id
        ));
        
        if (!$response) {
            wp_send_json_error(['message' => 'Umfrage nicht gefunden']);
        }
        
        // Парсим данные ответа
        $response_data = json_decode($response->response_data, true);
        if (!is_array($response_data)) {
            $response_data = [];
        }
        
        wp_send_json_success([
            'response' => $response,
            'response_data' => $response_data,
            'template_name' => $response->template_name,
            'survey_name' => $response->survey_name,
            'template_id' => $response->template_id
        ]);
    }
    
    public function delete_survey() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }
        
        $response_id = intval($_POST['survey_id']); // Это на самом деле response_id
        
        global $wpdb;
        $responses_table = $wpdb->prefix . 'neo_umfrage_responses';
        
        // Удаляем конкретный ответ (анкету пользователя)
        $result = $wpdb->delete($responses_table, ['id' => $response_id], ['%d']);
        
        if ($result) {
            wp_send_json_success(['message' => 'Umfrage erfolgreich gelöscht']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Löschen der Umfrage']);
        }
    }
    
    public function delete_template() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }
        
        $template_id = intval($_POST['template_id']);
        
        global $wpdb;
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        $result = $wpdb->delete($templates_table, ['id' => $template_id], ['%d']);
        
        if ($result) {
            wp_send_json_success(['message' => 'Vorlage erfolgreich gelöscht']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Löschen der Vorlage']);
        }
    }
    
    public function get_statistics() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }
        
        global $wpdb;
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        $responses_table = $wpdb->prefix . 'neo_umfrage_responses';
        
        $total_surveys = $wpdb->get_var("SELECT COUNT(*) FROM $surveys_table");
        $total_templates = $wpdb->get_var("SELECT COUNT(*) FROM $templates_table");
        $total_responses = $wpdb->get_var("SELECT COUNT(*) FROM $responses_table");
        
        wp_send_json_success([
            'total_surveys' => $total_surveys,
            'total_templates' => $total_templates,
            'total_responses' => $total_responses
        ]);
    }
    
    public function get_field_statistics() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }
        
        $field_label = sanitize_text_field($_POST['field_label']);
        $template_id = intval($_POST['template_id']);
        
        global $wpdb;
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $responses_table = $wpdb->prefix . 'neo_umfrage_responses';
        
        // Получаем все ответы для анкет этого шаблона
        $responses = $wpdb->get_results($wpdb->prepare(
            "SELECT r.response_data 
             FROM $responses_table r 
             JOIN $surveys_table s ON r.survey_id = s.id 
             WHERE s.template_id = %d",
            $template_id
        ));
        
        $field_data = [
            'label' => $field_label,
            'type' => 'text' // По умолчанию
        ];
        
        $stats = $this->analyze_field_responses($field_data, $responses);
        
        wp_send_json_success($stats);
    }
    
    private function analyze_field_responses($field_data, $responses) {
        $stats = [
            'total_responses' => count($responses),
            'filled_responses' => 0,
            'empty_responses' => 0,
            'values' => []
        ];
        
        foreach ($responses as $response) {
            $response_data = json_decode($response->response_data, true);
            if (!is_array($response_data)) {
                continue;
            }
            
            $field_value = null;
            foreach ($response_data as $field) {
                if (isset($field['label']) && $field['label'] === $field_data['label']) {
                    $field_value = $field['value'];
                    break;
                }
            }
            
            if (!empty($field_value)) {
                $stats['filled_responses']++;
                $stats['values'][] = $field_value;
            } else {
                $stats['empty_responses']++;
            }
        }
        
        // Дополнительная статистика в зависимости от типа поля
        if ($field_data['type'] === 'number' && !empty($stats['values'])) {
            $numbers = array_map('floatval', $stats['values']);
            $stats['min'] = min($numbers);
            $stats['max'] = max($numbers);
            $stats['avg'] = array_sum($numbers) / count($numbers);
            $stats['median'] = $this->calculate_median($numbers);
        } elseif (in_array($field_data['type'], ['radio', 'select', 'checkbox'])) {
            $stats['frequency'] = array_count_values($stats['values']);
            arsort($stats['frequency']);
        }
        
        return $stats;
    }
    
    private function calculate_median($numbers) {
        sort($numbers);
        $count = count($numbers);
        $middle = floor($count / 2);
        
        if ($count % 2 === 0) {
            return ($numbers[$middle - 1] + $numbers[$middle]) / 2;
        } else {
            return $numbers[$middle];
        }
    }
    
    public function get_template() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }
        
        global $wpdb;
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        $template_id = intval($_POST['template_id']);
        
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $templates_table WHERE id = %d",
            $template_id
        ));
        
        if ($template) {
            $template->fields = json_decode($template->fields, true);
            wp_send_json_success(['template' => $template]);
        } else {
            wp_send_json_error(['message' => 'Шаблон не найден']);
        }
    }
    
    public function update_template() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }
        
        global $wpdb;
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        $template_id = intval($_POST['template_id']);
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        
        $fields = [];
        // Добавляем обязательные поля в начало
        $fields[] = [
            'label' => 'Name',
            'type' => 'text',
            'required' => true,
            'options' => []
        ];
        $fields[] = [
            'label' => 'Telefonnummer',
            'type' => 'tel',
            'required' => true,
            'options' => []
        ];
        
        if (isset($_POST['fields'])) {
            if (is_string($_POST['fields'])) {
                $fields_data = json_decode(stripslashes($_POST['fields']), true);
                if (is_array($fields_data)) {
                    foreach ($fields_data as $field) {
                        // Пропускаем обязательные поля, так как они уже добавлены выше
                        if ($field['label'] === 'Name' || $field['label'] === 'Telefonnummer') {
                            continue;
                        }
                        $fields[] = [
                            'label' => sanitize_text_field($field['label']),
                            'type' => sanitize_text_field($field['type']),
                            'required' => isset($field['required']) && $field['required'] === true,
                            'options' => isset($field['options']) ? array_map('sanitize_text_field', $field['options']) : []
                        ];
                    }
                }
            } elseif (is_array($_POST['fields'])) {
                foreach ($_POST['fields'] as $field) {
                    // Пропускаем обязательные поля, так как они уже добавлены выше
                    if ($field['label'] === 'Name' || $field['label'] === 'Telefonnummer') {
                        continue;
                    }
                    $fields[] = [
                        'label' => sanitize_text_field($field['label']),
                        'type' => sanitize_text_field($field['type']),
                        'required' => isset($field['required']) && $field['required'] === true,
                        'options' => isset($field['options']) ? array_map('sanitize_text_field', $field['options']) : []
                    ];
                }
            }
        }
        
        $result = $wpdb->update(
            $templates_table,
            [
                'name' => $name,
                'description' => $description,
                'fields' => json_encode($fields),
                'updated_at' => current_time('mysql')
            ],
            ['id' => $template_id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Шаблон успешно обновлен']);
        } else {
            wp_send_json_error(['message' => 'Ошибка обновления шаблона']);
        }
    }
}

/**
 * Callback функции для страниц
 */
function neo_umfrage_main_section_callback() {
    ?>
    <div class="neo-umfrage-container">
        <div class="neo-umfrage-header">
            <h1 class="neo-umfrage-title">Neo Umfrage</h1>
            <p class="neo-umfrage-subtitle">System zur Verwaltung von Umfragen und Befragungen</p>
        </div>
        <div class="neo-umfrage-card">
            <div class="neo-umfrage-card-body">
                <p>Willkommen bei Neo Umfrage! Verwenden Sie das Seitenmenü zur Navigation durch die Bereiche.</p>
                <div class="neo-umfrage-stats" id="main-stats">
                    <div class="neo-umfrage-stat-card">
                        <div class="neo-umfrage-stat-number" id="total-surveys">-</div>
                        <div class="neo-umfrage-stat-label">Gesamt Umfragen</div>
                    </div>
                    <div class="neo-umfrage-stat-card">
                        <div class="neo-umfrage-stat-number" id="total-templates">-</div>
                        <div class="neo-umfrage-stat-label">Vorlagen</div>
                    </div>
                    <div class="neo-umfrage-stat-card">
                        <div class="neo-umfrage-stat-number" id="total-responses">-</div>
                        <div class="neo-umfrage-stat-label">Antworten</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function neo_umfrage_surveys_callback() {
    ?>
    <div class="neo-umfrage-container">
        <div class="neo-umfrage-header">
            <h1 class="neo-umfrage-title">Umfragenverwaltung</h1>
            <p class="neo-umfrage-subtitle">Erstellung und Bearbeitung von Umfragen</p>
        </div>
        
        <div class="neo-umfrage-card">
            <div class="neo-umfrage-card-header">
                <h2 class="neo-umfrage-card-title">Umfragenliste</h2>
            </div>
            <div class="neo-umfrage-card-body">
                <div style="margin-bottom: 20px;">
                    <button class="neo-umfrage-button" onclick="openAddSurveyModal()">Umfrage hinzufügen</button>
                </div>
                <div id="surveys-list">Laden...</div>
            </div>
        </div>
    </div>
    <?php
}

function neo_umfrage_templates_callback() {
    ?>
    <div class="neo-umfrage-container">
        <div class="neo-umfrage-header">
            <h1 class="neo-umfrage-title">Vorlagenverwaltung</h1>
            <p class="neo-umfrage-subtitle">Erstellung und Bearbeitung von Umfragevorlagen</p>
        </div>
        
        <div class="neo-umfrage-card">
            <div class="neo-umfrage-card-header">
                <h2 class="neo-umfrage-card-title">Vorlagenliste</h2>
            </div>
            <div class="neo-umfrage-card-body">
                <div style="margin-bottom: 20px;">
                    <button class="neo-umfrage-button" onclick="openAddTemplateModal()">Vorlage hinzufügen</button>
                </div>
                <div id="templates-list">Laden...</div>
            </div>
        </div>
    </div>
    <?php
}

function neo_umfrage_statistics_callback() {
    ?>
    <div class="neo-umfrage-container">
        <div class="neo-umfrage-header">
            <h1 class="neo-umfrage-title">Statistik</h1>
            <p class="neo-umfrage-subtitle">Analytik für Umfragen und Antworten</p>
        </div>
        
        <div class="neo-umfrage-stats" id="statistics-stats">
            <div class="neo-umfrage-stat-card">
                <div class="neo-umfrage-stat-number" id="stats-total-surveys">-</div>
                <div class="neo-umfrage-stat-label">Gesamt Umfragen</div>
            </div>
            <div class="neo-umfrage-stat-card">
                <div class="neo-umfrage-stat-number" id="stats-total-templates">-</div>
                <div class="neo-umfrage-stat-label">Vorlagen</div>
            </div>
            <div class="neo-umfrage-stat-card">
                <div class="neo-umfrage-stat-number" id="stats-total-responses">-</div>
                <div class="neo-umfrage-stat-label">Antworten</div>
            </div>
        </div>
        
        <div class="neo-umfrage-card">
            <div class="neo-umfrage-card-header">
                <h2 class="neo-umfrage-card-title">Letzte Umfragen</h2>
            </div>
            <div class="neo-umfrage-card-body">
                <div id="recent-surveys">Laden...</div>
            </div>
        </div>
    </div>
    <?php
}

function neo_umfrage_widget_callback() {
    ?>
    <div class="neo-umfrage-widget">
        <h3>Neo Umfrage</h3>
        <div style="text-align: center;">
            <button class="neo-umfrage-button" onclick="openAddSurveyModal()">Umfrage hinzufügen</button>
        </div>
    </div>
    <?php
}

/**
 * Активация плагина
 */
function neo_umfrage_activate() {
    // Создаем таблицы БД при активации
    $neo_umfrage = new NeoUmfrage();
    $neo_umfrage->create_tables();
    
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'neo_umfrage_activate');

/**
 * Деактивация плагина
 */
function neo_umfrage_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'neo_umfrage_deactivate');