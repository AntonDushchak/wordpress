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

class Neo_Umfrage {

    public function __construct() {
        add_action('plugins_loaded', [$this, 'check_dependencies']);
        add_action('neo_dashboard_init', [$this, 'register_dashboard_components']);
        
        add_action('wp_ajax_neo_umfrage_save_survey', [$this, 'ajax_save_survey']);
        add_action('wp_ajax_neo_umfrage_save_template', [$this, 'ajax_save_template']);
        add_action('wp_ajax_neo_umfrage_delete_survey', [$this, 'ajax_delete_survey']);
        add_action('wp_ajax_neo_umfrage_delete_template', [$this, 'ajax_delete_template']);
        add_action('wp_ajax_neo_umfrage_get_surveys', [$this, 'ajax_get_surveys']);
        add_action('wp_ajax_neo_umfrage_get_templates', [$this, 'ajax_get_templates']);
        add_action('wp_ajax_neo_umfrage_get_template', [$this, 'ajax_get_template']);
        add_action('wp_ajax_neo_umfrage_get_statistics', [$this, 'ajax_get_statistics']);
        add_action('wp_ajax_neo_umfrage_get_template_fields', [$this, 'ajax_get_template_fields']);
        add_action('wp_ajax_neo_umfrage_get_field_statistics', [$this, 'ajax_get_field_statistics']);
        add_action('wp_ajax_neo_umfrage_update_template', [$this, 'ajax_update_template']);
        add_action('wp_ajax_neo_umfrage_get_survey_data', [$this, 'ajax_get_survey_data']);
        add_action('wp_ajax_neo_umfrage_get_users', [$this, 'ajax_get_users']);
        add_action('wp_ajax_neo_umfrage_toggle_template_status', [$this, 'ajax_toggle_template_status']);
        add_action('wp_ajax_neo_umfrage_deactivate_template', [$this, 'ajax_deactivate_template']);
        add_action('wp_ajax_neo_umfrage_delete_template_with_surveys', [$this, 'ajax_delete_template_with_surveys']);
        add_action('wp_ajax_neo_umfrage_get_template_statistics', [$this, 'ajax_get_template_statistics']);
        
        add_action('init', [$this, 'init']);
        
        register_activation_hook(__FILE__, [$this, 'create_database_tables']);
    }

    public function check_dependencies() {
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
    }

    public function register_dashboard_components() {
        do_action('neo_dashboard_register_sidebar_item', [
            'slug' => 'neo-umfrage-group',
            'label' => 'Neo Umfrage',
            'icon' => 'bi-clipboard-data',
            'url' => '/neo-dashboard/neo-umfrage',
            'position' => 25,
            'is_group' => true,
        ]);

        $sections = [
            'surveys' => [
                'label' => 'Umfragen',
                'icon' => 'bi-clipboard-check',
                'pos' => 26,
            ],
            'statistics' => [
                'label' => 'Statistik', 
                'icon' => 'bi-graph-up',
                'pos' => 28,
            ],
        ];

        if (current_user_can('manage_options')) {
            $sections['templates'] = [
                'label' => 'Vorlagen',
                'icon' => 'bi-file-earmark-text',
                'pos' => 27,
            ];
        }

        foreach ($sections as $slug => $data) {
            $full_slug = 'neo-umfrage/' . $slug;

            do_action('neo_dashboard_register_sidebar_item', [
                'slug' => $full_slug,
                'label' => $data['label'],
                'icon' => $data['icon'],
                'url' => '/neo-dashboard/' . $full_slug,
                'parent' => 'neo-umfrage-group',
                'position' => $data['pos'],
            ]);

            do_action('neo_dashboard_register_section', [
                'slug' => $full_slug,
                'label' => $data['label'],
                'callback' => [$this, 'render_' . $slug . '_page'],
            ]);
        }

        do_action('neo_dashboard_register_section', [
            'slug' => 'neo-umfrage',
            'label' => 'Neo Umfrage',
            'callback' => [$this, 'render_main_page'],
        ]);

        do_action('neo_dashboard_register_widget', [
            'id' => 'neo-umfrage-widget',
            'title' => 'Umfragen',
            'callback' => [$this, 'render_widget'],
            'priority' => 10,
        ]);

        do_action('neo_dashboard_register_plugin_assets', 'neo-umfrage', [
            'css' => [
                'datatables-css' => [
                    'src' => 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css',
                    'deps' => [],
                    'contexts' => ['neo-umfrage', 'neo-umfrage/surveys', 'neo-umfrage/templates', 'neo-umfrage/statistics']
                ],
                'datatables-css-fix' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/css/datatables-theme-fix.css',
                    'deps' => ['datatables-css'],
                    'contexts' => ['neo-umfrage', 'neo-umfrage/surveys', 'neo-umfrage/templates', 'neo-umfrage/statistics']
                ],
                'neo-umfrage-css' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/css/neo-umfrage.css',
                    'deps' => ['neo-dashboard-core'],
                    'contexts' => ['neo-umfrage', 'neo-umfrage/surveys', 'neo-umfrage/templates', 'neo-umfrage/statistics', 'dashboard-home']
                ]
            ],
            'js' => [
                'datatables-js' => [
                    'src' => 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
                    'deps' => ['jquery'],
                    'version' => '1.13.6',
                    'contexts' => ['neo-umfrage', 'neo-umfrage/surveys', 'neo-umfrage/templates', 'neo-umfrage/statistics']
                ],
                'neo-umfrage-js' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/js/neo-umfrage.js',
                    'deps' => ['jquery'],
                    'version' => '1.0.0',
                    'contexts' => ['neo-umfrage', 'neo-umfrage/surveys', 'neo-umfrage/templates', 'neo-umfrage/statistics', 'dashboard-home']
                ],
                'neo-umfrage-modals-js' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/js/neo-umfrage-modals.js',
                    'deps' => ['jquery', 'neo-umfrage-js'],
                    'version' => '1.0.0',
                    'contexts' => ['neo-umfrage', 'neo-umfrage/surveys', 'neo-umfrage/templates', 'neo-umfrage/statistics', 'dashboard-home']
                ],
                'neo-umfrage-surveys-js' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/js/neo-umfrage-surveys.js',
                    'deps' => ['jquery', 'neo-umfrage-js'],
                    'version' => '1.0.0',
                    'contexts' => ['neo-umfrage', 'neo-umfrage/surveys']
                ],
                'neo-umfrage-templates-js' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/js/neo-umfrage-templates.js',
                    'deps' => ['jquery', 'neo-umfrage-js'],
                    'version' => '1.0.0',
                    'contexts' => ['neo-umfrage', 'neo-umfrage/surveys', 'neo-umfrage/templates', 'dashboard-home']
                ],
                'neo-umfrage-statistics-js' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/js/neo-umfrage-statistics.js',
                    'deps' => ['jquery', 'neo-umfrage-js'],
                    'version' => '1.0.0',
                    'contexts' => ['neo-umfrage', 'neo-umfrage/statistics']
                ]
            ]
        ]);

        add_action('wp_enqueue_scripts', function() {
            if (wp_script_is('neo-umfrage-js', 'enqueued')) {
                wp_localize_script('neo-umfrage-js', 'neoUmfrageAjax', [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('neo_umfrage_nonce'),
                    'currentUserId' => get_current_user_id(),
                    'strings' => [
                        'error' => 'Ein Fehler ist aufgetreten',
                        'success' => 'Operation erfolgreich ausgeführt',
                        'confirm_delete' => 'Sind Sie sicher, dass Sie dieses Element löschen möchten?',
                        'loading' => 'Laden...',
                        'no_data' => 'Keine Daten gefunden'
                    ]
                ]);
            }
        }, 15);

        add_action('admin_enqueue_scripts', function() {
            if (wp_script_is('neo-umfrage-js', 'enqueued')) {
                wp_localize_script('neo-umfrage-js', 'neoUmfrageAjax', [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('neo_umfrage_nonce'),
                    'currentUserId' => get_current_user_id(),
                    'strings' => [
                        'error' => 'Ein Fehler ist aufgetreten',
                        'success' => 'Operation erfolgreich ausgeführt',
                        'confirm_delete' => 'Sind Sie sicher, dass Sie dieses Element löschen möchten?',
                        'loading' => 'Laden...',
                        'no_data' => 'Keine Daten gefunden'
                    ]
                ]);
            }
        }, 15);
    }

    public function init() {
        load_plugin_textdomain('neo-umfrage', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function render_main_page() {
        $nonce = wp_create_nonce('neo_umfrage_nonce');
        ?>
        <script type="text/javascript">
            window.neoUmfrageAjax = {
                ajaxurl: "<?php echo admin_url('admin-ajax.php'); ?>",
                nonce: "<?php echo $nonce; ?>",
                currentUserId: <?php echo get_current_user_id(); ?>,
                strings: {
                    error: "Ein Fehler ist aufgetreten",
                    success: "Operation erfolgreich ausgeführt",
                    confirm_delete: "Sind Sie sicher, dass Sie dieses Element löschen möchten?",
                    loading: "Laden...",
                    no_data: "Keine Daten gefunden"
                }
            };
        </script>
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

    public function render_surveys_page() {
        $nonce = wp_create_nonce('neo_umfrage_nonce');
        ?>
        <script type="text/javascript">
            window.neoUmfrageAjax = {
                ajaxurl: "<?php echo admin_url('admin-ajax.php'); ?>",
                nonce: "<?php echo $nonce; ?>",
                currentUserId: <?php echo get_current_user_id(); ?>,
                strings: {
                    error: "Ein Fehler ist aufgetreten",
                    success: "Operation erfolgreich ausgeführt",
                    confirm_delete: "Sind Sie sicher, dass Sie dieses Element löschen möchten?",
                    loading: "Laden...",
                    no_data: "Keine Daten gefunden"
                }
            };
        </script>
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

    public function render_templates_page() {
        $nonce = wp_create_nonce('neo_umfrage_nonce');
        ?>
        <script type="text/javascript">
            window.neoUmfrageAjax = {
                ajaxurl: "<?php echo admin_url('admin-ajax.php'); ?>",
                nonce: "<?php echo $nonce; ?>",
                currentUserId: <?php echo get_current_user_id(); ?>,
                strings: {
                    error: "Ein Fehler ist aufgetreten",
                    success: "Operation erfolgreich ausgeführt",
                    confirm_delete: "Sind Sie sicher, dass Sie dieses Element löschen möchten?",
                    loading: "Laden...",
                    no_data: "Keine Daten gefunden"
                }
            };
        </script>
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

    public function render_statistics_page() {
        $nonce = wp_create_nonce('neo_umfrage_nonce');
        ?>
        <script type="text/javascript">
            window.neoUmfrageAjax = {
                ajaxurl: "<?php echo admin_url('admin-ajax.php'); ?>",
                nonce: "<?php echo $nonce; ?>",
                currentUserId: <?php echo get_current_user_id(); ?>,
                strings: {
                    error: "Ein Fehler ist aufgetreten",
                    success: "Operation erfolgreich ausgeführt",
                    confirm_delete: "Sind Sie sicher, dass Sie dieses Element löschen möchten?",
                    loading: "Laden...",
                    no_data: "Keine Daten gefunden"
                }
            };
        </script>
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
                    <h2 class="neo-umfrage-card-title">Vorlagen-Statistik</h2>
                </div>
                <div class="neo-umfrage-card-body">
                    <div class="neo-umfrage-form-group">
                        <label class="neo-umfrage-label">Vorlage auswählen:</label>
                        <select id="statistics-template-select" class="neo-umfrage-select" style="max-width: 400px;">
                            <option value="">Bitte wählen Sie eine Vorlage</option>
                        </select>
                    </div>
                    <div id="template-statistics-container">
                        <p class="neo-umfrage-info">Bitte wählen Sie eine Vorlage aus, um die Statistik anzuzeigen.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_widget() {
        $nonce = wp_create_nonce('neo_umfrage_nonce');
        ?>
        <script type="text/javascript">
            window.neoUmfrageAjax = {
                ajaxurl: "<?php echo admin_url('admin-ajax.php'); ?>",
                nonce: "<?php echo $nonce; ?>",
                currentUserId: <?php echo get_current_user_id(); ?>,
                strings: {
                    error: "Ein Fehler ist aufgetreten",
                    success: "Operation erfolgreich ausgeführ t",
                    confirm_delete: "Sind Sie sicher, dass Sie dieses Element löschen möchten?",
                    loading: "Laden...",
                    no_data: "Keine Daten gefunden"
                }
            };
        </script>
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
                if (window.NeoUmfrageModals) {
                    NeoUmfrageModals.createModals();
                }
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

    public function create_database_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

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

    public function ajax_save_template() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
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

    public function ajax_save_survey() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        
        $template_id = intval($_POST['template_id']);
        $user_id = get_current_user_id();
        
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $values_table = $wpdb->prefix . 'neo_umfrage_survey_values';
        
        $wpdb->query('START TRANSACTION');
        
        $survey_result = $wpdb->insert(
            $surveys_table,
            [
                'template_id' => $template_id,
                'user_id' => $user_id
            ],
            ['%d', '%d']
        );
        
        if (!$survey_result) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => 'Fehler beim Erstellen der Umfrage']);
            return;
        }
        
        $survey_id = $wpdb->insert_id;
        
        if (isset($_POST['survey_fields'])) {
            $survey_fields = [];
            
            if (is_string($_POST['survey_fields'])) {
                $survey_fields = json_decode(stripslashes($_POST['survey_fields']), true);
            } elseif (is_array($_POST['survey_fields'])) {
                $survey_fields = $_POST['survey_fields'];
            }
            
            if (is_array($survey_fields) && !empty($survey_fields)) {
                foreach ($survey_fields as $field) {
                    $field_name = isset($field['label']) ? sanitize_text_field($field['label']) : '';
                    $field_value = isset($field['value']) ? $field['value'] : '';
                    
                    if (is_array($field_value)) {
                        $field_value = implode(', ', array_map('sanitize_text_field', $field_value));
                    } else {
                        $field_value = sanitize_textarea_field($field_value);
                    }
                    
                    if (!empty($field_name)) {
                        $value_result = $wpdb->insert(
                            $values_table,
                            [
                                'survey_id' => $survey_id,
                                'field_name' => $field_name,
                                'field_value' => $field_value
                            ],
                            ['%d', '%s', '%s']
                        );
                        
                        if (!$value_result) {
                            $wpdb->query('ROLLBACK');
                            wp_send_json_error(['message' => 'Fehler beim Speichern der Felddaten']);
                            return;
                        }
                    }
                }
            }
        }
        
        $wpdb->query('COMMIT');
        wp_send_json_success(['message' => 'Umfrage erfolgreich gespeichert']);
    }

    public function ajax_delete_survey() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $survey_id = intval($_POST['survey_id']);
        
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $values_table = $wpdb->prefix . 'neo_umfrage_survey_values';
        
        $wpdb->query('START TRANSACTION');
        
        $values_deleted = $wpdb->delete($values_table, ['survey_id' => $survey_id], ['%d']);
        $survey_deleted = $wpdb->delete($surveys_table, ['id' => $survey_id], ['%d']);
        
        if ($survey_deleted !== false) {
            $wpdb->query('COMMIT');
            wp_send_json_success(['message' => 'Umfrage erfolgreich gelöscht']);
        } else {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => 'Fehler beim Löschen der Umfrage']);
        }
    }

    public function ajax_delete_template() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }

        global $wpdb;
        $template_id = intval($_POST['template_id']);
        
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        $result = $wpdb->delete($templates_table, ['id' => $template_id], ['%d']);
        
        if ($result) {
            wp_send_json_success(['message' => 'Vorlage erfolgreich gelöscht']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Löschen der Vorlage']);
        }
    }

    public function ajax_get_surveys() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        $where_clauses = [];
        $where_values = [];
        
        if (isset($_POST['template_id']) && !empty($_POST['template_id'])) {
            $where_clauses[] = "s.template_id = %d";
            $where_values[] = intval($_POST['template_id']);
        }
        
        if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
            $where_clauses[] = "s.user_id = %d";
            $where_values[] = intval($_POST['user_id']);
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $query = "
            SELECT s.id, 
                   s.template_id, 
                   s.user_id, 
                   s.created_at, 
                   s.created_at as submitted_at,
                   t.name as template_name, 
                   u.display_name as user_name,
                   u.display_name as wp_user_name,
                   s.id as response_id
            FROM $surveys_table s
            LEFT JOIN $templates_table t ON s.template_id = t.id
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            $where_sql
            ORDER BY s.created_at DESC
        ";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $surveys = $wpdb->get_results($query, ARRAY_A);
        
        wp_send_json_success($surveys);
    }

    public function ajax_get_templates() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        $where_sql = '';
        $where_values = [];
        
        if (isset($_POST['show_only_active']) && $_POST['show_only_active'] !== 'all' && $_POST['show_only_active'] !== '') {
            $where_sql = 'WHERE is_active = %d';
            $where_values[] = intval($_POST['show_only_active']);
        }
        
        $query = "SELECT * FROM $templates_table $where_sql ORDER BY created_at DESC";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $templates = $wpdb->get_results($query);
        
        foreach ($templates as &$template) {
            $template->fields = json_decode($template->fields, true);
        }
        
        wp_send_json_success(['templates' => $templates]);
    }

    public function ajax_get_template() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $template_id = intval($_POST['template_id']);
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM $templates_table WHERE id = %d", $template_id));
        
        if ($template) {
            $template->fields = json_decode($template->fields, true);
            wp_send_json_success(['template' => $template]);
        } else {
            wp_send_json_error(['message' => 'Vorlage nicht gefunden']);
        }
    }

    public function ajax_get_statistics() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        $values_table = $wpdb->prefix . 'neo_umfrage_survey_values';
        
        $total_surveys = $wpdb->get_var("SELECT COUNT(*) FROM $surveys_table");
        $total_templates = $wpdb->get_var("SELECT COUNT(*) FROM $templates_table");
        $total_responses = $wpdb->get_var("SELECT COUNT(*) FROM $values_table");
        
        $recent_surveys = $wpdb->get_results("
            SELECT s.*, t.name as template_name, u.display_name as user_name
            FROM $surveys_table s
            LEFT JOIN $templates_table t ON s.template_id = t.id
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            ORDER BY s.created_at DESC
            LIMIT 10
        ");
        
        wp_send_json_success([
            'total_surveys' => $total_surveys,
            'total_templates' => $total_templates,
            'total_responses' => $total_responses,
            'recent_surveys' => $recent_surveys
        ]);
    }

    public function ajax_get_template_fields() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $template_id = intval($_POST['template_id']);
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        $template = $wpdb->get_row($wpdb->prepare("SELECT fields FROM $templates_table WHERE id = %d", $template_id));
        
        if ($template) {
            $fields = json_decode($template->fields, true);
            wp_send_json_success(['fields' => $fields]);
        } else {
            wp_send_json_error(['message' => 'Vorlage nicht gefunden']);
        }
    }

    public function ajax_get_field_statistics() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $template_id = intval($_POST['template_id']);
        $field_name = sanitize_text_field($_POST['field_name']);
        
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $values_table = $wpdb->prefix . 'neo_umfrage_survey_values';
        
        $query = $wpdb->prepare("
            SELECT sv.field_value, COUNT(*) as count
            FROM $values_table sv
            INNER JOIN $surveys_table s ON sv.survey_id = s.id
            WHERE s.template_id = %d AND sv.field_name = %s
            GROUP BY sv.field_value
            ORDER BY count DESC
        ", $template_id, $field_name);
        
        $statistics = $wpdb->get_results($query);
        
        wp_send_json_success(['statistics' => $statistics]);
    }

    public function ajax_update_template() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }

        global $wpdb;
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
            }
        }
        
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        $result = $wpdb->update(
            $templates_table,
            [
                'name' => $name,
                'description' => $description,
                'fields' => json_encode($fields),
                'is_active' => $is_active
            ],
            ['id' => $template_id],
            ['%s', '%s', '%s', '%d'],
            ['%d']
        );
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Vorlage erfolgreich aktualisiert']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Aktualisieren der Vorlage']);
        }
    }

    public function ajax_get_survey_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $survey_id = intval($_POST['survey_id']);
        
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $values_table = $wpdb->prefix . 'neo_umfrage_survey_values';
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        $survey = $wpdb->get_row($wpdb->prepare("
            SELECT s.*, t.name as template_name, t.fields as template_fields, u.display_name as user_name
            FROM $surveys_table s
            LEFT JOIN $templates_table t ON s.template_id = t.id
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            WHERE s.id = %d
        ", $survey_id));
        
        if (!$survey) {
            wp_send_json_error(['message' => 'Umfrage nicht gefunden']);
            return;
        }
        
        $values = $wpdb->get_results($wpdb->prepare("
            SELECT field_name, field_value
            FROM $values_table
            WHERE survey_id = %d
        ", $survey_id));
        
        $survey->template_fields = json_decode($survey->template_fields, true);
        
        $response_data_object = [];
        $response_data_array = [];
        
        foreach ($values as $value) {
            $response_data_object[$value->field_name] = $value->field_value;
            $response_data_array[] = [
                'label' => $value->field_name,
                'value' => $value->field_value
            ];
        }
        
        wp_send_json_success([
            'response' => [
                'id' => $survey->id,
                'template_id' => $survey->template_id,
                'user_id' => $survey->user_id,
                'created_at' => $survey->created_at,
                'submitted_at' => $survey->created_at,
                'user_display_name' => $survey->user_name
            ],
            'response_data' => $response_data_array,
            'response_data_object' => $response_data_object,
            'template_id' => $survey->template_id,
            'template_name' => $survey->template_name,
            'template_fields' => $survey->template_fields,
            'user_name' => $survey->user_name
        ]);
    }

    public function ajax_get_users() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        $users = get_users(['fields' => ['ID', 'display_name', 'user_email']]);
        wp_send_json_success(['users' => $users]);
    }

    public function ajax_toggle_template_status() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }

        global $wpdb;
        $template_id = intval($_POST['template_id']);
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        $current_status = $wpdb->get_var($wpdb->prepare("SELECT is_active FROM $templates_table WHERE id = %d", $template_id));
        $new_status = $current_status ? 0 : 1;
        
        $result = $wpdb->update(
            $templates_table,
            ['is_active' => $new_status],
            ['id' => $template_id],
            ['%d'],
            ['%d']
        );
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Status erfolgreich geändert', 'new_status' => $new_status]);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Ändern des Status']);
        }
    }

    public function ajax_deactivate_template() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }

        global $wpdb;
        $template_id = intval($_POST['template_id']);
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        
        $result = $wpdb->update(
            $templates_table,
            ['is_active' => 0],
            ['id' => $template_id],
            ['%d'],
            ['%d']
        );
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Vorlage deaktiviert']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Deaktivieren der Vorlage']);
        }
    }

    public function ajax_delete_template_with_surveys() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }

        global $wpdb;
        $template_id = intval($_POST['template_id']);
        
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $values_table = $wpdb->prefix . 'neo_umfrage_survey_values';
        
        $wpdb->query('START TRANSACTION');
        
        $survey_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM $surveys_table WHERE template_id = %d", $template_id));
        
        if (!empty($survey_ids)) {
            $survey_ids_str = implode(',', array_map('intval', $survey_ids));
            $wpdb->query("DELETE FROM $values_table WHERE survey_id IN ($survey_ids_str)");
        }
        
        $surveys_deleted = $wpdb->delete($surveys_table, ['template_id' => $template_id], ['%d']);
        $template_deleted = $wpdb->delete($templates_table, ['id' => $template_id], ['%d']);
        
        if ($template_deleted !== false) {
            $wpdb->query('COMMIT');
            wp_send_json_success(['message' => 'Vorlage und alle zugehörigen Umfragen wurden gelöscht']);
        } else {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => 'Fehler beim Löschen der Vorlage']);
        }
    }

    public function ajax_get_template_statistics() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_umfrage_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $template_id = intval($_POST['template_id']);
        
        $templates_table = $wpdb->prefix . 'neo_umfrage_templates';
        $surveys_table = $wpdb->prefix . 'neo_umfrage_surveys';
        $values_table = $wpdb->prefix . 'neo_umfrage_survey_values';
        
        $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM $templates_table WHERE id = %d", $template_id));
        
        if (!$template) {
            wp_send_json_error(['message' => 'Vorlage nicht gefunden']);
            return;
        }
        
        $template_fields = json_decode($template->fields, true);
        
        $total_responses = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $surveys_table WHERE template_id = %d",
            $template_id
        ));
        
        $fields_statistics = [];
        
        foreach ($template_fields as $field) {
            $field_label = $field['label'];
            $field_type = $field['type'];
            
            $field_stat = [
                'label' => $field_label,
                'type' => $field_type,
                'statistics' => []
            ];
            
            if ($field_type === 'text') {
                $results = $wpdb->get_results($wpdb->prepare("
                    SELECT sv.field_value as value, COUNT(*) as count
                    FROM $values_table sv
                    INNER JOIN $surveys_table s ON sv.survey_id = s.id
                    WHERE s.template_id = %d AND sv.field_name = %s AND sv.field_value != ''
                    GROUP BY sv.field_value
                    ORDER BY count DESC
                    LIMIT 5
                ", $template_id, $field_label));
                
                foreach ($results as $result) {
                    $field_stat['statistics'][] = [
                        'value' => $result->value,
                        'count' => (int)$result->count,
                        'percentage' => $total_responses > 0 ? round(($result->count / $total_responses) * 100, 1) : 0
                    ];
                }
                
            } elseif ($field_type === 'number') {
                $stats = $wpdb->get_row($wpdb->prepare("
                    SELECT 
                        MIN(CAST(sv.field_value AS DECIMAL(10,2))) as min_val,
                        MAX(CAST(sv.field_value AS DECIMAL(10,2))) as max_val,
                        AVG(CAST(sv.field_value AS DECIMAL(10,2))) as avg_val
                    FROM $values_table sv
                    INNER JOIN $surveys_table s ON sv.survey_id = s.id
                    WHERE s.template_id = %d AND sv.field_name = %s 
                          AND sv.field_value != '' 
                          AND CAST(sv.field_value AS DECIMAL(10,2)) > 0
                ", $template_id, $field_label));
                
                if ($stats) {
                    $field_stat['statistics'] = [
                        'min' => $stats->min_val ? round($stats->min_val, 2) : null,
                        'avg' => $stats->avg_val ? round($stats->avg_val, 2) : null,
                        'max' => $stats->max_val ? round($stats->max_val, 2) : null
                    ];
                }
                
            } elseif (in_array($field_type, ['radio', 'checkbox', 'select'])) {
                $results = $wpdb->get_results($wpdb->prepare("
                    SELECT sv.field_value as value, COUNT(*) as count
                    FROM $values_table sv
                    INNER JOIN $surveys_table s ON sv.survey_id = s.id
                    WHERE s.template_id = %d AND sv.field_name = %s AND sv.field_value != ''
                    GROUP BY sv.field_value
                    ORDER BY count DESC
                ", $template_id, $field_label));
                
                foreach ($results as $result) {
                    if ($field_type === 'checkbox' && strpos($result->value, ',') !== false) {
                        $values = array_map('trim', explode(',', $result->value));
                        foreach ($values as $value) {
                            $found = false;
                            foreach ($field_stat['statistics'] as &$existing) {
                                if ($existing['value'] === $value) {
                                    $existing['count'] += 1;
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $field_stat['statistics'][] = [
                                    'value' => $value,
                                    'count' => 1,
                                    'percentage' => 0
                                ];
                            }
                        }
                    } else {
                        $field_stat['statistics'][] = [
                            'value' => $result->value,
                            'count' => (int)$result->count,
                            'percentage' => 0
                        ];
                    }
                }
                
                foreach ($field_stat['statistics'] as &$stat) {
                    $stat['percentage'] = $total_responses > 0 ? round(($stat['count'] / $total_responses) * 100, 1) : 0;
                }
                
                usort($field_stat['statistics'], function($a, $b) {
                    return $b['count'] - $a['count'];
                });
            }
            
            $fields_statistics[] = $field_stat;
        }
        
        wp_send_json_success([
            'template_name' => $template->name,
            'total_responses' => (int)$total_responses,
            'fields' => $fields_statistics
        ]);
    }
}

new Neo_Umfrage();