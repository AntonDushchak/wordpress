<?php
/**
 * Plugin Name: Job Board Integration
 * Description: Integration mit bewerberboerse für Vorlagen und Bewerbungen
 * Version: 1.0.0
 * Author: Neo
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
require_once plugin_dir_path(__FILE__) . 'includes/Core/Database.php';
require_once plugin_dir_path(__FILE__) . 'includes/Core/Sync.php';
require_once plugin_dir_path(__FILE__) . 'includes/API/Client.php';

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
        add_action('wp_ajax_jbi_check_notifications', [$this, 'ajax_check_notifications']);
        add_action('wp_ajax_jbi_manual_sync', [$this, 'ajax_manual_sync']);
        add_action('wp_ajax_jbi_get_contact_request', [$this, 'ajax_get_contact_request']);
        add_action('wp_ajax_jbi_mark_notification_viewed', [$this, 'ajax_mark_notification_viewed']);
        
        add_action('wp_ajax_nopriv_jbi_get_templates', [$this, 'ajax_get_templates']);
        add_action('wp_ajax_nopriv_jbi_get_template', [$this, 'ajax_get_template']);
        add_action('wp_ajax_nopriv_jbi_get_applications', [$this, 'ajax_get_applications']);
        
        add_action('init', [$this, 'init']);
        
        add_action('jbi_sync_contact_requests', function() {
            error_log('JBI: Action jbi_sync_contact_requests triggered at ' . date('Y-m-d H:i:s'));
            \NeoJobBoard\Core\Sync::sync_contact_requests();
        });
        
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
        }

        do_action('neo_dashboard_register_widget', [
            'id' => 'job-board-integration-widget',
            'title' => 'Job Board Integration',
            'callback' => [$this, 'render_widget'],
            'priority' => 10,
        ]);

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
                    'deps' => ['jquery'],
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
        $contact_requests_table = $wpdb->prefix . 'neo_job_board_contact_requests';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            \NeoJobBoard\Core\Database::create_tables();
        } else {
            \NeoJobBoard\Core\Database::migrate_remove_columns();
            \NeoJobBoard\Core\Database::migrate_add_is_called_column();
        }
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$contact_requests_table'") !== $contact_requests_table) {
            \NeoJobBoard\Core\Database::create_tables();
        } else {
            \NeoJobBoard\Core\Database::migrate_add_notification_viewed_column();
        }
        
        $next_scheduled = wp_next_scheduled('jbi_sync_contact_requests');
        if (!$next_scheduled) {
            $scheduled = wp_schedule_event(time(), 'jbi_custom_sync_interval', 'jbi_sync_contact_requests');
            error_log('JBI: Scheduled sync event in init, result: ' . ($scheduled === false ? 'failed' : 'success'));
        } else {
            $current_time = time();
            if ($next_scheduled <= $current_time) {
                error_log('JBI: Sync event is overdue (scheduled: ' . date('Y-m-d H:i:s', $next_scheduled) . ', current: ' . date('Y-m-d H:i:s', $current_time) . '), executing now...');
                wp_unschedule_event($next_scheduled, 'jbi_sync_contact_requests');
                do_action('jbi_sync_contact_requests');
                $new_scheduled = wp_schedule_event($current_time, 'jbi_custom_sync_interval', 'jbi_sync_contact_requests');
                error_log('JBI: Executed overdue sync and rescheduled for: ' . date('Y-m-d H:i:s', wp_next_scheduled('jbi_sync_contact_requests')));
            } else {
                error_log('JBI: Sync event already scheduled in init for: ' . date('Y-m-d H:i:s', $next_scheduled));
            }
        }
    }

    public function activate_plugin() {
        \NeoJobBoard\Core\Database::create_tables();
        
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
        $sync_interval = (int) get_option('jbi_sync_interval', 600);
        $next_scheduled = wp_next_scheduled('jbi_sync_contact_requests');

        if ($next_scheduled) {
            $offset = (float) get_option('gmt_offset', 0) * HOUR_IN_SECONDS;
            $last_timestamp = (int) round($next_scheduled - $sync_interval + $offset);
            $next_timestamp = (int) round($next_scheduled + $offset);
            $last_sync_formatted = date('d.m.Y H:i:s', $last_timestamp);
            $next_sync_formatted = date('d.m.Y H:i:s', $next_timestamp);
        } else {
            $last_sync_formatted = 'Nie';
            $next_sync_formatted = 'Nicht geplant';
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
                                <option value="60" <?php selected($sync_interval, 60); ?>>1 Minute</option>
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
                            <div class="alert alert-info">
                                <strong>Status der Synchronisierung:</strong><br>
                                <small>Letzte Synchronisierung: <span id="last-sync-time"><?php echo esc_html($last_sync_formatted); ?></span></small><br>
                                <small>Nächste Synchronisierung: <span id="next-sync-time"><?php echo esc_html($next_sync_formatted); ?></span></small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <button type="button" class="btn btn-secondary" id="test-connection-btn">Verbindung testen</button>
                            <button type="button" class="btn btn-primary" id="save-settings-btn">Einstellungen speichern</button>
                            <button type="button" class="btn btn-info" id="manual-sync-btn">Manuelle Synchronisierung</button>
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
        $applications_url = home_url('/neo-dashboard/job-board-integration/applications');
        ?>
        <script type="text/javascript">
            window.jbiAjax = {
                ajaxurl: "<?php echo admin_url('admin-ajax.php'); ?>",
                nonce: "<?php echo $nonce; ?>",
                applicationsUrl: "<?php echo esc_js($applications_url); ?>"
            };
        </script>
        <div class="jbi-widget">
            <div class="jbi-widget-body">
                <div id="widget-status"></div>
                <div class="jbi-widget-actions mt-3">
                    <button type="button" class="btn btn-primary btn-sm w-100 mb-2" id="jbi-widget-add-candidate">
                        <i class="bi bi-person-plus me-1"></i> Kandidat hinzufügen
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm w-100" id="jbi-widget-view-notification" disabled>
                        <i class="bi bi-bell me-1"></i> Benachrichtigung ansehen
                    </button>
                </div>
            </div>
        </div>
        <script type="text/javascript">
        (function($) {
            'use strict';
            
            function checkNotifications() {
                $.post(jbiAjax.ajaxurl, {
                    action: 'jbi_check_notifications',
                    nonce: jbiAjax.nonce
                }, function(response) {
                    if (response.success && response.data && response.data.has_notification) {
                        const $btn = $('#jbi-widget-view-notification');
                        $btn.prop('disabled', false).removeClass('btn-secondary').addClass('btn-success');
                        if (response.data.count > 0) {
                            $btn.html('<i class="bi bi-bell-fill me-1"></i> Benachrichtigung ansehen (' + response.data.count + ')');
                        }
                    }
                });
            }
            
            $('#jbi-widget-add-candidate').on('click', function() {
                window.location.href = jbiAjax.applicationsUrl;
            });
            
            $('#jbi-widget-view-notification').on('click', function() {
                if (!$(this).prop('disabled')) {
                    window.location.href = jbiAjax.applicationsUrl;
                }
            });
            
            checkNotifications();
            setInterval(checkNotifications, 30000);
        })(jQuery);
        </script>
        <?php
    }

    public function ajax_test_connection() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        $api_url = get_option('jbi_api_url');
        $api_key = get_option('jbi_api_key');

        $result = \NeoJobBoard\API\Client::test_connection($api_url, $api_key);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
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
        
        $sync_interval_raw = isset($_POST['sync_interval']) ? $_POST['sync_interval'] : 600;
        $sync_interval = absint($sync_interval_raw);

        $valid_intervals = [60, 300, 600, 900, 1800, 3600, 7200, 14400, 86400];
        if (!in_array($sync_interval, $valid_intervals)) {
            error_log('JBI: Invalid sync_interval received: ' . var_export($sync_interval_raw, true) . ' (type: ' . gettype($sync_interval_raw) . ', converted to: ' . $sync_interval . ')');
            $sync_interval = 600;
        }
        
        error_log('JBI: Saving sync_interval: ' . $sync_interval . ' (raw: ' . var_export($sync_interval_raw, true) . ')');

        $old_interval = get_option('jbi_sync_interval', 600);
        
        update_option('jbi_api_url', $api_url);
        update_option('jbi_api_key', $api_key);
        update_option('jbi_auto_send', $auto_send);
        $saved = update_option('jbi_sync_interval', $sync_interval);
        error_log('JBI: update_option result for sync_interval: ' . ($saved ? 'true' : 'false') . ', value: ' . $sync_interval);

        if ($old_interval != $sync_interval) {
            error_log('JBI: Interval changed from ' . $old_interval . ' to ' . $sync_interval . ', rescheduling...');
            
            $timestamp = wp_next_scheduled('jbi_sync_contact_requests');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'jbi_sync_contact_requests');
                error_log('JBI: Unscheduled old event at ' . date('Y-m-d H:i:s', $timestamp));
            }
            
            add_filter('cron_schedules', [$this, 'add_custom_sync_schedule']);
            
            $new_timestamp = wp_schedule_event(time(), 'jbi_custom_sync_interval', 'jbi_sync_contact_requests');
            error_log('JBI: Scheduled new event, result: ' . ($new_timestamp === false ? 'failed' : 'success') . ', next run: ' . date('Y-m-d H:i:s', wp_next_scheduled('jbi_sync_contact_requests')));
        }

        wp_send_json_success([
            'message' => 'Einstellungen erfolgreich gespeichert',
            'sync_interval' => $sync_interval
        ]);
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

            $hash_id = \NeoJobBoard\Core\Database::generate_unique_hash();
            
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
                $send_result = \NeoJobBoard\API\Client::send_application($application_id, 'create');
                if (!$send_result['success']) {
                    $wpdb->update(
                        $wpdb->prefix . 'neo_job_board_applications',
                        ['is_active' => 0, 'updated_at' => current_time('mysql')],
                        ['id' => $application_id],
                        ['%d', '%s'],
                        ['%d']
                    );
                } else {
                    $wpdb->update(
                        $wpdb->prefix . 'neo_job_board_applications',
                        ['updated_at' => current_time('mysql')],
                        ['id' => $application_id],
                        ['%s'],
                        ['%d']
                    );
                }
            } else {
                $wpdb->update(
                    $wpdb->prefix . 'neo_job_board_applications',
                    ['is_active' => 0, 'updated_at' => current_time('mysql')],
                    ['id' => $application_id],
                    ['%d', '%s'],
                    ['%d']
                );
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
        
        $requested_user_id = isset($_POST['user_id']) && $_POST['user_id'] !== '' ? intval($_POST['user_id']) : 0;
        
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
            
            $created_at = strtotime($application['created_at'] ?? '');
            $updated_at = strtotime($application['updated_at'] ?? '');
            $time_diff = abs($updated_at - $created_at);
            
            if ($application['is_active'] == 1) {
                $application['sync_status'] = 'active';
            } else {
                if ($time_diff < 60) {
                    $application['sync_status'] = 'unsynced';
                } else {
                    $application['sync_status'] = 'inactive';
                }
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

        $active_template = $wpdb->get_row(
            "SELECT id, name, fields FROM {$wpdb->prefix}neo_job_board_templates WHERE is_active = 1 LIMIT 1",
            ARRAY_A
        );

        if (!$active_template) {
            wp_send_json_error(['message' => 'Aktives Template nicht gefunden']);
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
        $application['template_fields'] = json_decode($active_template['fields'] ?? '[]', true);
        $application['active_template_id'] = $active_template['id'];
        $application['original_template_id'] = $application['template_id'];

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

        $send_result = \NeoJobBoard\API\Client::send_application_update_preview($application_id, $fields_data);
        
        if (!$send_result['success']) {
            wp_send_json_error(['message' => 'Fehler beim Senden an API: ' . $send_result['message']]);
            return;
        }

        $responsible_employee = isset($_POST['responsible_employee']) ? intval($_POST['responsible_employee']) : null;
        
        $existing_application_data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}neo_job_board_application_data WHERE application_id = %d",
            $application_id
        ), ARRAY_A);

        $existing_fields_data = [];
        foreach ($existing_application_data as $row) {
            $decoded = json_decode($row['field_value'], true);
            $existing_fields_data[$row['field_name']] = $decoded !== null ? $decoded : $row['field_value'];
        }

        $merged_fields_data = array_merge($existing_fields_data, $fields_data);

        $update_data = [
            'application_data' => json_encode($merged_fields_data, JSON_UNESCAPED_UNICODE),
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

        $active_template = $wpdb->get_row(
            "SELECT fields FROM {$wpdb->prefix}neo_job_board_templates WHERE is_active = 1 LIMIT 1"
        );

        if ($active_template) {
            $template_fields = json_decode($active_template->fields ?? '[]', true);
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

            foreach ($merged_fields_data as $field_name => $field_value) {
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

        $send_result = \NeoJobBoard\API\Client::send_application_status_change($application_id, $is_active);
        
        if (!$send_result['success']) {
            wp_send_json_error(['message' => 'Fehler beim Senden an API: ' . $send_result['message']]);
            return;
        }

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
        $send_result = \NeoJobBoard\API\Client::send_application_delete($application_id, $hash_id);
        
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

        $send_result = \NeoJobBoard\API\Client::send_application($application_id, 'create');

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

    public function ajax_check_notifications() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $current_user_id = get_current_user_id();
        
        if (!$current_user_id) {
            wp_send_json_success(['has_notification' => false, 'count' => 0]);
            return;
        }

        // Проверяем наличие контактных запросов для резюме, где текущий пользователь является ответственным
        $contact_requests_table = $wpdb->prefix . 'neo_job_board_contact_requests';
        $applications_table = $wpdb->prefix . 'neo_job_board_applications';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT cr.id)
             FROM {$contact_requests_table} cr
             INNER JOIN {$applications_table} a ON cr.application_id = a.id
             WHERE a.responsible_employee = %d
             AND cr.notification_viewed = 0
             AND cr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            $current_user_id
        ));

        $has_notification = $count > 0;
        
        wp_send_json_success([
            'has_notification' => $has_notification,
            'count' => intval($count)
        ]);
    }

    public function ajax_manual_sync() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }

        try {
            \NeoJobBoard\Core\Sync::sync_contact_requests();
            wp_send_json_success(['message' => 'Synchronisierung erfolgreich abgeschlossen']);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Fehler bei der Synchronisierung: ' . $e->getMessage()]);
        }
    }

    public function ajax_get_contact_request() {
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

        $current_user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT responsible_employee FROM {$wpdb->prefix}neo_job_board_applications WHERE id = %d",
            $application_id
        ));
        
        if (!$application) {
            wp_send_json_error(['message' => 'Bewerbung nicht gefunden']);
            return;
        }
        
        if (!in_array('administrator', $user->roles) && !in_array('neo_editor', $user->roles)) {
            if ($application->responsible_employee != $current_user_id) {
                wp_send_json_error(['message' => 'Sie haben keine Berechtigung, auf diese Bewerbung zuzugreifen.']);
                return;
            }
        }

        $contact_requests = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}neo_job_board_contact_requests 
             WHERE application_id = %d 
             ORDER BY created_at DESC",
            $application_id
        ), ARRAY_A);

        if (empty($contact_requests)) {
            wp_send_json_error(['message' => 'Kontaktanfragen nicht gefunden']);
            return;
        }

        wp_send_json_success(['contact_requests' => $contact_requests]);
    }

    public function ajax_mark_notification_viewed() {
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

        $current_user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT responsible_employee FROM {$wpdb->prefix}neo_job_board_applications WHERE id = %d",
            $application_id
        ));
        
        if (!$application) {
            wp_send_json_error(['message' => 'Bewerbung nicht gefunden']);
            return;
        }
        
        if (!in_array('administrator', $user->roles) && !in_array('neo_editor', $user->roles)) {
            if ($application->responsible_employee != $current_user_id) {
                wp_send_json_error(['message' => 'Sie haben keine Berechtigung, auf diese Bewerbung zuzugreifen.']);
                return;
            }
        }

        $wpdb->query('START TRANSACTION');

        $result1 = $wpdb->update(
            $wpdb->prefix . 'neo_job_board_contact_requests',
            ['notification_viewed' => 1],
            ['application_id' => $application_id],
            ['%d'],
            ['%d']
        );

        $result2 = $wpdb->update(
            $wpdb->prefix . 'neo_job_board_applications',
            ['is_called' => 0],
            ['id' => $application_id],
            ['%d'],
            ['%d']
        );

        if ($result1 === false || $result2 === false) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => 'Fehler beim Aktualisieren']);
            return;
        }

        $wpdb->query('COMMIT');

        wp_send_json_success(['message' => 'Benachrichtigung als gelesen markiert']);
    }

    public function ajax_send_template() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'jbi_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        global $wpdb;
        $template_id = intval($_POST['template_id'] ?? 0);
        
        $result = \NeoJobBoard\API\Client::send_template($template_id);
        
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
            $send_result = \NeoJobBoard\API\Client::send_application($application_id);
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

}

new Job_Board_Integration();
