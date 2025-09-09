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

    // Добавляем хук для подключения CSS в виджете
    add_action('neo_dashboard_enqueue_widget_assets_css', function () {
            // Подключаем CSS
            wp_enqueue_style(
                'neo-umfrage-widget-css',
                NEO_UMFRAGE_PLUGIN_URL . 'assets/css/neo-umfrage.css',
                [],
                NEO_UMFRAGE_VERSION
            );
    });

    // Добавляем хук для подключения JS в виджете
    add_action('neo_dashboard_enqueue_widget_assets_js', function () {
            // Подключаем JS
            wp_enqueue_script(
                'neo-umfrage-widget-js',
                NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage.js',
                ['jquery'],
                NEO_UMFRAGE_VERSION,
                true
            );

            wp_enqueue_script(
                'neo-umfrage-widget-modals-js',
                NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage-modals.js',
                ['jquery', 'neo-umfrage-widget-js'],
                NEO_UMFRAGE_VERSION,
                true
            );

            wp_enqueue_script(
                'neo-umfrage-widget-templates-js',
                NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage-templates.js',
                ['jquery', 'neo-umfrage-widget-js'],
                NEO_UMFRAGE_VERSION,
                true
            );

            // Локализация для виджета
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

            wp_localize_script('neo-umfrage-widget-js', 'neoUmfrageAjax', $ajax_data);
            wp_localize_script('neo-umfrage-widget-modals-js', 'neoUmfrageAjax', $ajax_data);
    });

    // Подключаем CSS для плагина
    add_action('neo_dashboard_enqueue_neo-umfrage_assets_css', function () {
        // CSS DataTables
        wp_enqueue_style(
            'datatables-css',
            'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css'
        );

        wp_enqueue_style(
            'neo-umfrage-css',
            NEO_UMFRAGE_PLUGIN_URL . 'assets/css/neo-umfrage.css',
            [],
            NEO_UMFRAGE_VERSION
        );
    });

    // Подключаем JS для плагина в зависимости от секции
    add_action('neo_dashboard_enqueue_neo-umfrage_assets_js', function ($section) {
        // Подключаем CSS DataTables
        wp_enqueue_style(
            'datatables-css',
            'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css'
        );

        // Основной JS DataTables (зависит от jQuery)
        wp_enqueue_script(
            'datatables-js',
            'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
            ['jquery'],
            '1.13.6',
            true
        );

        // Основной координатор (загружается всегда)
        wp_enqueue_script(
            'neo-umfrage-js',
            NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage.js',
            ['jquery'],
            NEO_UMFRAGE_VERSION,
            true
        );

        // Модальные окна и формы (загружаются всегда)
        wp_enqueue_script(
            'neo-umfrage-modals-js',
            NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage-modals.js',
            ['jquery', 'neo-umfrage-js'],
            NEO_UMFRAGE_VERSION,
            true
        );

        // Загружаем специфичные скрипты в зависимости от секции
        switch ($section) {
            case 'neo-umfrage/surveys':
                wp_enqueue_script(
                    'neo-umfrage-surveys-js',
                    NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage-surveys.js',
                    ['jquery', 'neo-umfrage-js'],
                    NEO_UMFRAGE_VERSION,
                    true
                );
                // Также загружаем шаблоны для фильтрации анкет
                wp_enqueue_script(
                    'neo-umfrage-templates-js',
                    NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage-templates.js',
                    ['jquery', 'neo-umfrage-js'],
                    NEO_UMFRAGE_VERSION,
                    true
                );
                break;

            case 'neo-umfrage/templates':
                wp_enqueue_script(
                    'neo-umfrage-templates-js',
                    NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage-templates.js',
                    ['jquery', 'neo-umfrage-js'],
                    NEO_UMFRAGE_VERSION,
                    true
                );
                break;

            case 'neo-umfrage/statistics':
                wp_enqueue_script(
                    'neo-umfrage-statistics-js',
                    NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage-statistics.js',
                    ['jquery', 'neo-umfrage-js'],
                    NEO_UMFRAGE_VERSION,
                    true
                );
                break;

            case 'neo-umfrage':
                // Главная страница - загружаем все скрипты
                wp_enqueue_script(
                    'neo-umfrage-surveys-js',
                    NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage-surveys.js',
                    ['jquery', 'neo-umfrage-js'],
                    NEO_UMFRAGE_VERSION,
                    true
                );
                wp_enqueue_script(
                    'neo-umfrage-templates-js',
                    NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage-templates.js',
                    ['jquery', 'neo-umfrage-js'],
                    NEO_UMFRAGE_VERSION,
                    true
                );
                wp_enqueue_script(
                    'neo-umfrage-statistics-js',
                    NEO_UMFRAGE_PLUGIN_URL . 'assets/js/neo-umfrage-statistics.js',
                    ['jquery', 'neo-umfrage-js'],
                    NEO_UMFRAGE_VERSION,
                    true
                );
                break;
        }

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

        // Локализуем для основных JS файлов
        wp_localize_script('neo-umfrage-js', 'neoUmfrageAjax', $ajax_data);
        wp_localize_script('neo-umfrage-modals-js', 'neoUmfrageAjax', $ajax_data);

        // Локализуем для специфичных скриптов, если они загружены
        if (wp_script_is('neo-umfrage-surveys-js', 'enqueued')) {
            wp_localize_script('neo-umfrage-surveys-js', 'neoUmfrageAjax', $ajax_data);
        }
        if (wp_script_is('neo-umfrage-templates-js', 'enqueued')) {
            wp_localize_script('neo-umfrage-templates-js', 'neoUmfrageAjax', $ajax_data);
        }
        if (wp_script_is('neo-umfrage-statistics-js', 'enqueued')) {
            wp_localize_script('neo-umfrage-statistics-js', 'neoUmfrageAjax', $ajax_data);
        }
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

        if (current_user_can('manage_options')) {
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
class NeoUmfrage
{

    public function __construct()
    {
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
        add_action('wp_ajax_neo_umfrage_get_users', [$this, 'get_users']);
        add_action('wp_ajax_neo_umfrage_toggle_template_status', [$this, 'toggle_template_status']);
        add_action('wp_ajax_neo_umfrage_restore_template', [$this, 'restore_template']);
        add_action('wp_ajax_neo_umfrage_deactivate_template', [$this, 'deactivate_template']);
        add_action('wp_ajax_neo_umfrage_delete_template_with_surveys', [$this, 'delete_template_with_surveys']);
    }

    public function init()
    {
        // Загружаем текстовый домен для переводов
        load_plugin_textdomain('neo-umfrage', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Таблица шаблонов
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        $templates_sql = "CREATE TABLE $templates_table (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            fields JSON NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;";

        // Таблица анкет
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $surveys_sql = "CREATE TABLE $surveys_table (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            template_id BIGINT NOT NULL,
            user_id BIGINT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY template_id (template_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Таблица значений полей (EAV)
        $survey_values_table = $wpdb->prefix . 'neo_umfrage_survey_values';
        $survey_values_sql = "CREATE TABLE $survey_values_table (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            survey_id BIGINT NOT NULL,
            field_name VARCHAR(255) NOT NULL,
            field_value TEXT,
            KEY survey_id (survey_id),
            KEY field_name (field_name)
        ) $charset_collate;";


        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($templates_sql);
        dbDelta($surveys_sql);
        dbDelta($survey_values_sql);
    }

    public function save_template()
    {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }

        global $wpdb;
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';

        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

        $fields = [];

        if (isset($_POST['fields'])) {
            if (is_string($_POST['fields'])) {
                $fields_data = json_decode(stripslashes($_POST['fields']), true);
                if (is_array($fields_data)) {
                    foreach ($fields_data as $field) {
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
                'fields' => json_encode($fields),
                'is_active' => $is_active
            ],
            ['%s', '%s', '%s', '%d']
        );

        if ($result) {
            wp_send_json_success(['message' => 'Vorlage erfolgreich gespeichert']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Speichern der Vorlage']);
        }
    }

    public function save_survey()
    {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }

        global $wpdb;
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $responses_table = $wpdb->prefix . 'neo_umfrage_survey_values';

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

        // Создаем новую анкету
        $result = $wpdb->insert(
            $surveys_table,
            [
                'template_id' => $template_id,
                'user_id' => get_current_user_id()
            ],
            ['%d', '%d']
        );

        if (!$result) {
            wp_send_json_error(['message' => 'Fehler beim Erstellen der Umfrage']);
        }

        $survey_id = $wpdb->insert_id;

        // Проверяем, редактируем ли мы существующий ответ
        $response_id = isset($_POST['response_id']) ? intval($_POST['response_id']) : 0;

        if ($response_id > 0) {
            // Удаляем старые значения ответа
            $wpdb->delete(
                $responses_table,
                ['survey_id' => $response_id],
                ['%d']
            );
        }

        // Сохраняем каждое поле отдельно в таблицу survey_values
        $success = true;
        foreach ($survey_fields as $field) {
            $field_value = is_array($field['value']) ? implode(', ', $field['value']) : $field['value'];

            $result = $wpdb->insert(
                $responses_table,
                [
                    'survey_id' => $response_id > 0 ? $response_id : $survey_id,
                    'field_name' => $field['label'],
                    'field_value' => $field_value
                ],
                ['%d', '%s', '%s']
            );

            if (!$result) {
                $success = false;
                break;
            }
        }

        if ($success) {
            $message = $response_id > 0 ? 'Umfrage erfolgreich aktualisiert' : 'Umfrage erfolgreich gespeichert';
            wp_send_json_success(['message' => $message]);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Speichern der Umfrage']);
        }
    }

    public function get_surveys()
    {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            error_log('Neo Umfrage: Fehler bei der nonce-Überprüfung');
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }

        global $wpdb;
        $responses_table = $wpdb->prefix . 'neo_umfrage_survey_values';
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';

        // Проверяем фильтры
        $template_filter = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        $user_filter = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $template_name_filter = isset($_POST['template_name']) ? sanitize_text_field($_POST['template_name']) : '';

        // Получаем уникальные survey_id из таблицы ответов
        $sql = "
            SELECT DISTINCT sv.survey_id, s.user_id, s.created_at, t.name as template_name
            FROM $responses_table sv
            LEFT JOIN $surveys_table s ON sv.survey_id = s.id
            LEFT JOIN $templates_table t ON s.template_id = t.id
        ";

        $where_conditions = [];
        $prepare_values = [];

        // Добавляем фильтры
        if ($template_filter > 0) {
            $where_conditions[] = "t.id = %d";
            $prepare_values[] = $template_filter;
        }

        if ($user_filter > 0) {
            $where_conditions[] = "s.user_id = %d";
            $prepare_values[] = $user_filter;
        }

        if (!empty($template_name_filter)) {
            $where_conditions[] = "t.name = %s";
            $prepare_values[] = $template_name_filter;
        }

        // Добавляем WHERE условия
        if (!empty($where_conditions)) {
            $sql .= " WHERE " . implode(" AND ", $where_conditions);
        }

        $sql .= " ORDER BY s.created_at DESC";

        // Выполняем запрос
        if (!empty($prepare_values)) {
            $surveys_data = $wpdb->get_results($wpdb->prepare($sql, $prepare_values));
        } else {
            $surveys_data = $wpdb->get_results($sql);
        }

        $surveys = [];
        foreach ($surveys_data as $survey_data) {
            // Получаем все поля для этого survey_id
            $fields = $wpdb->get_results($wpdb->prepare(
                "SELECT field_name, field_value FROM $responses_table WHERE survey_id = %d",
                $survey_data->survey_id
            ));

            // Получаем информацию о пользователе с именем и фамилией
            $user = get_user_by('id', $survey_data->user_id);
            $wp_user_name = 'Unbekannter Benutzer';

            if ($user) {
                $first_name = get_user_meta($user->ID, 'first_name', true);
                $last_name = get_user_meta($user->ID, 'last_name', true);

                if (!empty($first_name) && !empty($last_name)) {
                    $wp_user_name = $first_name . ' ' . $last_name;
                } elseif (!empty($first_name)) {
                    $wp_user_name = $first_name;
                } elseif (!empty($last_name)) {
                    $wp_user_name = $last_name;
                } else {
                    $wp_user_name = $user->display_name;
                }
            }

            $surveys[] = [
                'id' => $survey_data->survey_id,
                'response_id' => $survey_data->survey_id,
                'survey_id' => $survey_data->survey_id,
                'template_name' => $survey_data->template_name ?: 'Nicht angegeben',
                'wp_user_name' => $wp_user_name,
                'submitted_at' => $survey_data->created_at,
                'user_id' => $survey_data->user_id,
            ];
        }

        wp_send_json_success($surveys);
    }

    public function get_templates()
    {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }

        global $wpdb;
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';

        // Проверяем, нужно ли фильтровать только активные шаблоны
        $show_only_active = isset($_POST['show_only_active']) ? intval($_POST['show_only_active']) : 1; // По умолчанию показываем только активные

        $where_clause = '';
        if ($show_only_active) {
            $where_clause = ' WHERE is_active = 1';
        }

        $templates = $wpdb->get_results("
            SELECT * FROM $templates_table 
            $where_clause
            ORDER BY is_active DESC, created_at DESC
        ");

        wp_send_json_success($templates);
    }

    public function get_template_fields()
    {
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

    public function get_survey_data()
    {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }

        $survey_id = intval($_POST['survey_id']);

        global $wpdb;
        $responses_table = $wpdb->prefix . 'neo_umfrage_survey_values';
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';

        // Получаем информацию об анкете
        $survey = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, t.name as template_name, t.id as template_id
             FROM $surveys_table s
             LEFT JOIN $templates_table t ON s.template_id = t.id
             WHERE s.id = %d",
            $survey_id
        ));

        if (!$survey) {
            wp_send_json_error(['message' => 'Umfrage nicht gefunden']);
        }

        // Получаем все поля ответа
        $fields = $wpdb->get_results($wpdb->prepare(
            "SELECT field_name, field_value FROM $responses_table WHERE survey_id = %d",
            $survey_id
        ));

        // Преобразуем поля в массив
        $response_data = [];
        foreach ($fields as $field) {
            $response_data[] = [
                'label' => $field->field_name,
                'value' => $field->field_value
            ];
        }

        // Получаем информацию о пользователе с именем и фамилией
        $user = get_user_by('id', $survey->user_id);
        $user_info = [
            'user_display_name' => $user ? $user->display_name : 'Unbekannter Benutzer',
            'first_name' => '',
            'last_name' => ''
        ];

        if ($user) {
            $first_name = get_user_meta($user->ID, 'first_name', true);
            $last_name = get_user_meta($user->ID, 'last_name', true);

            if (!empty($first_name)) {
                $user_info['first_name'] = $first_name;
            }
            if (!empty($last_name)) {
                $user_info['last_name'] = $last_name;
            }
        }

        wp_send_json_success([
            'response' => array_merge((array)$survey, $user_info),
            'response_data' => $response_data,
            'template_name' => $survey->template_name,
            'template_id' => $survey->template_id
        ]);
    }

    public function delete_survey()
    {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }

        $survey_id = intval($_POST['survey_id']);

        global $wpdb;
        $responses_table = $wpdb->prefix . 'neo_umfrage_survey_values';
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';

        // Удаляем все поля ответа
        $wpdb->delete($responses_table, ['survey_id' => $survey_id], ['%d']);

        // Удаляем саму анкету
        $result = $wpdb->delete($surveys_table, ['id' => $survey_id], ['%d']);

        if ($result) {
            wp_send_json_success(['message' => 'Umfrage erfolgreich gelöscht']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Löschen der Umfrage']);
        }
    }

    public function deactivate_template()
    {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }

        $template_id = intval($_POST['template_id']);

        global $wpdb;
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';

        // Устанавливаем is_active = 0
        $result = $wpdb->update(
            $templates_table,
            [
                'is_active' => 0,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $template_id],
            ['%d', '%s'],
            ['%d']
        );

        if ($result !== false) {
            wp_send_json_success(['message' => 'Vorlage erfolgreich deaktiviert']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Deaktivieren der Vorlage']);
        }
    }

    public function delete_template_with_surveys()
    {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }

        $template_id = intval($_POST['template_id']);

        global $wpdb;
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $responses_table = $wpdb->prefix . 'neo_umfrage_survey_values';

        // Получаем все анкеты этого шаблона
        $surveys = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM $surveys_table WHERE template_id = %d",
            $template_id
        ));

        // Удаляем все ответы анкет
        foreach ($surveys as $survey) {
            $wpdb->delete($responses_table, ['survey_id' => $survey->id], ['%d']);
        }

        // Удаляем все анкеты
        $wpdb->delete($surveys_table, ['template_id' => $template_id], ['%d']);

        // Удаляем сам шаблон
        $result = $wpdb->delete($templates_table, ['id' => $template_id], ['%d']);

        if ($result) {
            $surveys_count = count($surveys);
            $message = "Vorlage und $surveys_count zugehörige Umfragen erfolgreich gelöscht";
            wp_send_json_success(['message' => $message]);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Löschen der Vorlage']);
        }
    }

    public function get_statistics()
    {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }

        global $wpdb;
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        $responses_table = $wpdb->prefix . 'neo_umfrage_survey_values';

        $total_surveys = $wpdb->get_var("SELECT COUNT(*) FROM $surveys_table");
        $total_templates = $wpdb->get_var("SELECT COUNT(*) FROM $templates_table");
        $total_responses = $wpdb->get_var("SELECT COUNT(*) FROM $responses_table");

        wp_send_json_success([
            'total_surveys' => $total_surveys,
            'total_templates' => $total_templates,
            'total_responses' => $total_responses
        ]);
    }

    public function get_field_statistics()
    {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }

        $field_label = sanitize_text_field($_POST['field_label']);
        $template_id = intval($_POST['template_id']);

        global $wpdb;
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $responses_table = $wpdb->prefix . 'neo_umfrage_survey_values';

        // Получаем все ответы для анкет этого шаблона
        $responses = $wpdb->get_results($wpdb->prepare(
            "SELECT sv.field_value 
             FROM $responses_table sv 
             JOIN $surveys_table s ON sv.survey_id = s.id 
             WHERE s.template_id = %d AND sv.field_name = %s",
            $template_id,
            $field_label
        ));

        $field_data = [
            'label' => $field_label,
            'type' => 'text' // По умолчанию
        ];

        $stats = $this->analyze_field_responses($field_data, $responses);

        wp_send_json_success($stats);
    }

    private function analyze_field_responses($field_data, $responses)
    {
        $stats = [
            'total_responses' => count($responses),
            'filled_responses' => 0,
            'empty_responses' => 0,
            'values' => []
        ];

        foreach ($responses as $response) {
            $field_value = $response->field_value;

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

    private function calculate_median($numbers)
    {
        sort($numbers);
        $count = count($numbers);
        $middle = floor($count / 2);

        if ($count % 2 === 0) {
            return ($numbers[$middle - 1] + $numbers[$middle]) / 2;
        } else {
            return $numbers[$middle];
        }
    }

    public function get_template()
    {
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

    public function update_template()
    {
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
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

        $fields = [];

        if (isset($_POST['fields'])) {
            if (is_string($_POST['fields'])) {
                $fields_data = json_decode(stripslashes($_POST['fields']), true);
                if (is_array($fields_data)) {
                    foreach ($fields_data as $field) {
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
                'is_active' => $is_active,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $template_id],
            ['%s', '%s', '%s', '%d', '%s'],
            ['%d']
        );

        if ($result !== false) {
            wp_send_json_success(['message' => 'Шаблон успешно обновлен']);
        } else {
            wp_send_json_error(['message' => 'Ошибка обновления шаблона']);
        }
    }

    public function get_users()
    {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }

        // Получаем всех пользователей
        $users = get_users([
            'fields' => ['ID', 'display_name'],
            'orderby' => 'display_name',
            'order' => 'ASC'
        ]);

        // Формируем массив с полными именами
        $users_with_names = [];
        foreach ($users as $user) {
            // Получаем имя и фамилию из мета-полей
            $first_name = get_user_meta($user->ID, 'first_name', true);
            $last_name = get_user_meta($user->ID, 'last_name', true);

            $full_name = '';
            if (!empty($first_name) && !empty($last_name)) {
                $full_name = $first_name . ' ' . $last_name;
            } elseif (!empty($first_name)) {
                $full_name = $first_name;
            } elseif (!empty($last_name)) {
                $full_name = $last_name;
            } else {
                $full_name = $user->display_name;
            }


            $users_with_names[] = [
                'ID' => $user->ID,
                'display_name' => $full_name
            ];
        }

        // Сортируем по полному имени
        usort($users_with_names, function ($a, $b) {
            return strcmp($a['display_name'], $b['display_name']);
        });

        wp_send_json_success($users_with_names);
    }

    public function toggle_template_status()
    {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности: неверный nonce']);
            return;
        }

        $template_id = intval($_POST['template_id']);
        $is_active = intval($_POST['is_active']);

        if (!$template_id) {
            wp_send_json_error(['message' => 'Неверный ID шаблона']);
            return;
        }

        global $wpdb;
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';

        $result = $wpdb->update(
            $templates_table,
            [
                'is_active' => $is_active,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $template_id],
            ['%d', '%s'],
            ['%d']
        );

        if ($result !== false) {
            $status_text = $is_active ? 'активирован' : 'деактивирован';
            wp_send_json_success(['message' => "Шаблон успешно $status_text"]);
        } else {
            wp_send_json_error(['message' => 'Ошибка обновления статуса шаблона']);
        }
    }
}

/**
 * Callback функции для страниц
 */
function neo_umfrage_main_section_callback()
{
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

function neo_umfrage_surveys_callback()
{
?>
    <div class="neo-umfrage-container">
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

function neo_umfrage_templates_callback()
{
?>
    <div class="neo-umfrage-container">
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

function neo_umfrage_statistics_callback()
{
?>
    <div class="neo-umfrage-container">
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

function neo_umfrage_widget_callback()
{
?>
    <div class="neo-umfrage-widget">
        <div class="neo-umfrage-widget-body">
            <div class="neo-umfrage-widget-actions">
                <button class="neo-umfrage-button neo-umfrage-button-primary" id="widget-add-survey-btn">
                    <i class="bi bi-plus-circle"></i>
                    Umfrage hinzufügen
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modale Fenster beim Laden des Widgets initialisieren
            if (window.NeoUmfrageModals) {
                NeoUmfrageModals.createModals();
            }

            // Button-Klick-Handler
            const button = document.getElementById('widget-add-survey-btn');
            if (button) {
                button.addEventListener('click', function() {
                    if (window.NeoUmfrageModals && NeoUmfrageModals.openAddSurveyModal) {
                        NeoUmfrageModals.openAddSurveyModal();
                    } else {
                        window.location.href = '/neo-dashboard/neo-umfrage/surveys';
                    }
                });
            }
        });
    </script>
<?php
}

/**
 * Активация плагина
 */
function neo_umfrage_activate()
{
    // Создаем таблицы БД при активации
    $neo_umfrage = new NeoUmfrage();
    $neo_umfrage->create_tables();

    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'neo_umfrage_activate');

/**
 * Деактивация плагина
 */
function neo_umfrage_deactivate()
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'neo_umfrage_deactivate');
