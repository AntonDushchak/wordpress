<?php
/**
 * Plugin Name: Neo Calendar
 * Description: Kalender basierend auf Neo Dashboard
 * Version: 1.0.0
 * Author: Anton Dushchak
 * Text Domain: neo-calendar
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Neo_Calendar {

    public function __construct() {
        add_action('plugins_loaded', [$this, 'check_dependencies']);
        add_action('neo_dashboard_init', [$this, 'register_dashboard_components']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_calendar_scripts']);
        
        add_action('wp_ajax_neo_calendar_save_event', [$this, 'ajax_save_event']);
        add_action('wp_ajax_neo_calendar_get_events', [$this, 'ajax_get_events']);
        add_action('wp_ajax_neo_calendar_update_event', [$this, 'ajax_update_event']);
        add_action('wp_ajax_neo_calendar_delete_event', [$this, 'ajax_delete_event']);
        add_action('wp_ajax_neo_calendar_get_users', [$this, 'ajax_get_users']);
        
        add_action('init', [$this, 'add_capabilities']);
        
        register_activation_hook(__FILE__, [$this, 'create_database_table']);
    }

    public function check_dependencies() {
        if (!class_exists(\NeoDashboard\Core\Router::class)) {
            deactivate_plugins(plugin_basename(__FILE__));
            add_action('admin_notices', static function () {
                echo '<div class="notice notice-error"><p>';
                esc_html_e(
                    'Neo Calendar wurde deaktiviert, weil "Neo Dashboard Core" nicht aktiv ist.',
                    'neo-calendar'
                );
                echo '</p></div>';
            });
            return;
        }
    }

    public function register_dashboard_components() {
        do_action('neo_dashboard_register_section', [
            'slug' => 'neo-calendar',
            'label' => 'Neo Calendar',
            'callback' => [$this, 'render_calendar_page'],
        ]);

        do_action('neo_dashboard_register_sidebar_item', [
            'slug' => 'neo-calendar',
            'label' => 'Calendar',
            'icon' => 'bi-calendar-event',
            'url' => '/neo-dashboard/neo-calendar',
            'position' => 25,
        ]);

        do_action('neo_dashboard_register_widget', [
            'id' => 'neo-calendar-widget',
            'title' => 'Calendar',
            'callback' => [$this, 'render_widget'],
            'priority' => 10,
        ]);

        error_log('Neo Calendar: registering plugin assets');
        error_log('Neo Calendar: widget CSS path = ' . plugin_dir_url(__FILE__) . 'assets/css/neo-calendar-widget.css');
        
        do_action('neo_dashboard_register_plugin_assets', 'neo-calendar', [
            'css' => [
                'flatpickr-css' => [
                    'src' => 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
                    'deps' => [],
                    'contexts' => ['*']
                ],
                'neo-calendar-core' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/css/neo-calendar-core.css',
                    'deps' => ['neo-dashboard-core', 'flatpickr-css'],
                    'contexts' => ['neo-calendar']
                ],
                'neo-calendar-widget' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/css/neo-calendar-widget.css',
                    'deps' => [],
                    'contexts' => ['dashboard-home']
                ]
            ],
            'js' => [
                'fullcalendar-js' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/fullcalendar/dist/index.global.min.js',
                    'deps' => [],
                    'contexts' => ['neo-calendar']
                ],
                'fullcalendar-locale-de' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/fullcalendar/packages/core/locales/de.global.min.js',
                    'deps' => ['fullcalendar-js'],
                    'contexts' => ['neo-calendar']
                ],
                'flatpickr-js' => [
                    'src' => 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js',
                    'deps' => [],
                    'contexts' => ['*']
                ],
                'flatpickr-locale-de' => [
                    'src' => 'https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/de.js',
                    'deps' => ['flatpickr-js'],
                    'contexts' => ['*']
                ],
                'neo-calendar-common' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/js/neo-calendar-common.js',
                    'deps' => ['jquery', 'flatpickr-js', 'flatpickr-locale-de'],
                    'contexts' => ['*'],
                    'localize' => [
                        'object_name' => 'neoCalendarAjax',
                        'data' => [
                            'ajaxurl' => admin_url('admin-ajax.php'),
                            'nonce' => wp_create_nonce('neo_calendar_nonce'),
                            'current_user_id' => get_current_user_id(),
                        ]
                    ]
                ],
                'neo-calendar-core' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/js/neo-calendar-core.js',
                    'deps' => ['jquery', 'fullcalendar-js', 'neo-calendar-common'],
                    'contexts' => ['neo-calendar'],
                    'localize' => [
                        'object_name' => 'neoCalendarAjax',
                        'data' => [
                            'ajaxurl' => admin_url('admin-ajax.php'),
                            'nonce' => wp_create_nonce('neo_calendar_nonce'),
                            'current_user_id' => get_current_user_id(),
                            'strings' => [
                                'save_success' => 'Ereignis gespeichert',
                                'save_error' => 'Fehler beim Speichern',
                                'delete_success' => 'Ereignis gelöscht',
                                'delete_error' => 'Fehler beim Löschen',
                                'confirm_delete' => 'Ereignis wirklich löschen?'
                            ]
                        ]
                    ]
                ],
                'neo-calendar-widget' => [
                    'src' => plugin_dir_url(__FILE__) . 'assets/js/neo-calendar-widget.js',
                    'deps' => ['jquery', 'neo-calendar-common'],
                    'contexts' => ['dashboard-home']
                ]
            ]
        ]);
    }

    public function localize_calendar_scripts() {
        wp_localize_script('neo-calendar-core', 'neoCalendarAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('neo_calendar_nonce'),
            'current_user_id' => get_current_user_id(),
        ]);
    }

    public function localize_widget_scripts() {
        wp_localize_script('neo-calendar-widget', 'neoCalendarAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('neo_calendar_nonce'),
        ]);
    }

    public function enqueue_calendar_scripts() {

    }

    public function ajax_save_event() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_calendar_nonce')) {
            wp_send_json_error('Security check failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_calendar_events';

        $user_id = get_current_user_id();
        $current_user = wp_get_current_user();
        $user_name = $current_user->display_name;
        $first_name = $current_user->first_name ?: $current_user->display_name;
        $last_name = $current_user->last_name ?: '';
        $type = sanitize_text_field($_POST['type'] ?? 'event');
        $title = sanitize_text_field($_POST['title'] ?? '');
        $start = sanitize_text_field($_POST['start'] ?? '');
        $end = sanitize_text_field($_POST['end'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');

        if (empty($start)) {
            wp_send_json_error('Start date is required');
        }

        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            wp_send_json_error('Database table does not exist. Please deactivate and reactivate the plugin.');
        }

        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'user_name' => $user_name,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'type' => $type,
                'title' => $title,
                'start' => $start,
                'end' => $end,
                'description' => $description,
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($result === false) {
            wp_send_json_error('Failed to save event: ' . $wpdb->last_error);
        }
        wp_send_json_success([
            'message' => 'Event saved successfully',
            'id' => $wpdb->insert_id
        ]);
    }

    public function ajax_get_events() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_calendar_nonce')) {
            wp_send_json_error('Security check failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_calendar_events';

        $user_id = get_current_user_id();
        $can_manage = current_user_can('manage_calendar');
        $start_date = sanitize_text_field($_POST['start'] ?? '');
        $end_date = sanitize_text_field($_POST['end'] ?? '');

        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            wp_send_json_error('Database table does not exist. Please deactivate and reactivate the plugin.');
        }

        $where_clause = [];
        $where_values = [];

        if (!empty($start_date)) {
            $where_clause[] = "start >= %s";
            $where_values[] = $start_date;
        }

        if (!empty($end_date)) {
            $where_clause[] = "end <= %s";
            $where_values[] = $end_date;
        }

        $sql_where = '';
        if (!empty($where_clause)) {
            $sql_where = 'WHERE ' . implode(' AND ', $where_clause);
        }

        $sql = $wpdb->prepare(
            "SELECT id, type, title, start, end, description, user_name, first_name, last_name, user_id 
            FROM $table_name 
            $sql_where
            ORDER BY start ASC",
            $where_values
        );

        $events = $wpdb->get_results($sql);
        $formatted_events = [];

        foreach ($events as $event) {
            $display_name = '';
            if (!empty($event->first_name) && !empty($event->last_name)) {
                $display_name = trim($event->first_name . ' ' . $event->last_name);
            } elseif (!empty($event->first_name)) {
                $display_name = $event->first_name;
            } else {
                $display_name = $event->user_name ?: 'Unbekannter Benutzer';
            }

            switch ($event->type) {
                case 'arbeitsstunde':
                    $start_time = new DateTime($event->start);
                    $end_time = new DateTime($event->end);
                    $interval = $start_time->diff($end_time);
                    $hours = $interval->h + ($interval->i / 60);
                    $title = $display_name . ' (' . number_format($hours, 1) . 'h)';
                    $color = '#3788d8';
                    $allDay = false;
                    break;
                case 'urlaub':
                    $title = 'Urlaub ' . $display_name;
                    $color = '#ff6b6b';
                    $allDay = true;
                    break;
                case 'veranstaltung':
                    $title = $event->title;
                    $color = '#6c757d';
                    $allDay = false;
                    break;
                default:
                    $title = $event->title;
                    $color = '#6c757d';
                    $allDay = false;
            }

            $formatted_events[] = [
                'id' => $event->id,
                'title' => $title,
                'start' => $event->start,
                'end' => $event->end,
                'type' => $event->type,
                'color' => $color,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'allDay' => $allDay,
                'user_id' => $event->user_id,
                'is_owner' => ($event->user_id == $user_id),
                'can_manage' => $can_manage,
                'description' => $event->description ?? ''
            ];
        }

        wp_send_json_success($formatted_events);
    }

    public function ajax_update_event() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_calendar_nonce')) {
            wp_send_json_error('Security check failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_calendar_events';

        $user_id = get_current_user_id();
        $event_id = intval($_POST['event_id'] ?? 0);
        $type = sanitize_text_field($_POST['type'] ?? '');
        $title = sanitize_text_field($_POST['title'] ?? '');
        $start = sanitize_text_field($_POST['start'] ?? '');
        $end = sanitize_text_field($_POST['end'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $employee_id = intval($_POST['employee_id'] ?? 0);

        if ($event_id <= 0) {
            wp_send_json_error('Invalid event ID');
        }

        if (empty($start)) {
            wp_send_json_error('Start date is required');
        }

        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT id, user_id FROM $table_name WHERE id = %d",
            $event_id
        ));

        if (!$event) {
            wp_send_json_error('Event not found');
        }

        if ($event->user_id != $user_id && !current_user_can('manage_calendar')) {
            wp_send_json_error('Access denied');
        }

        $update_user_id = $user_id;
        $update_user_name = '';
        $update_first_name = '';
        $update_last_name = '';

        if ($employee_id > 0 && current_user_can('manage_calendar')) {
            $update_user_id = $employee_id;
            $selected_user = get_user_by('ID', $employee_id);
            if ($selected_user) {
                $update_user_name = $selected_user->display_name;
                $update_first_name = $selected_user->first_name ?: $selected_user->display_name;
                $update_last_name = $selected_user->last_name ?: '';
            }

            $current_user = wp_get_current_user();
            $admin_name = $current_user->first_name && $current_user->last_name ?
                $current_user->first_name . ' ' . $current_user->last_name :
                $current_user->display_name;

            $change_comment = "\n\nMitarbeiter geändert von " . $admin_name . " am " . date('d.m.Y H:i');
            $description .= $change_comment;
        } else {
            $current_user = wp_get_current_user();
            $update_user_name = $current_user->display_name;
            $update_first_name = $current_user->first_name ?: $current_user->display_name;
            $update_last_name = $current_user->last_name ?: '';
        }

        $result = $wpdb->update(
            $table_name,
            [
                'user_id' => $update_user_id,
                'user_name' => $update_user_name,
                'first_name' => $update_first_name,
                'last_name' => $update_last_name,
                'type' => $type,
                'title' => $title,
                'start' => $start,
                'end' => $end,
                'description' => $description
            ],
            ['id' => $event_id],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error('Failed to update event: ' . $wpdb->last_error);
        }

        wp_send_json_success([
            'message' => 'Event updated successfully',
            'id' => $event_id
        ]);
    }

    public function ajax_delete_event() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_calendar_nonce')) {
            wp_send_json_error('Security check failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_calendar_events';

        $user_id = get_current_user_id();
        $event_id = intval($_POST['event_id'] ?? 0);

        if ($event_id <= 0) {
            wp_send_json_error('Invalid event ID');
        }

        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            wp_send_json_error('Database table does not exist. Please deactivate and reactivate the plugin.');
        }

        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT id, user_id FROM $table_name WHERE id = %d",
            $event_id
        ));

        if (!$event) {
            wp_send_json_error('Event not found');
        }

        if ($event->user_id != $user_id && !current_user_can('manage_calendar')) {
            wp_send_json_error('Access denied');
        }

        $result = $wpdb->delete(
            $table_name,
            ['id' => $event_id],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error('Failed to delete event: ' . $wpdb->last_error);
        }
        wp_send_json_success(['message' => 'Event deleted successfully']);
    }

    public function ajax_get_users() {
        if (!wp_verify_nonce($_POST['nonce'], 'neo_calendar_nonce')) {
            wp_send_json_error('Security check failed');
        }

        if (!current_user_can('manage_calendar')) {
            wp_send_json_error('Access denied');
        }

        $users = get_users(['role__in' => ['administrator', 'neo_editor', 'neo_mitarbeiter']]);
        $users_data = [];

        foreach ($users as $user) {
            $users_data[] = [
                'id' => $user->ID,
                'name' => $user->display_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->roles[0] ?? 'neo_mitarbeiter'
            ];
        }

        wp_send_json_success($users_data);
    }

    public function add_capabilities() {
        $editor = get_role('neo_editor');
        if ($editor && !$editor->has_cap('manage_calendar')) {
            $editor->add_cap('manage_calendar');
        }

        $admin = get_role('administrator');
        if ($admin && !$admin->has_cap('manage_calendar')) {
            $admin->add_cap('manage_calendar');
        }
    }

    public function render_calendar_page() {
        $nonce = wp_create_nonce('neo_calendar_nonce');
        ?>
        <script type="text/javascript">
            window.neoCalendarAjax = {
                ajaxurl: "<?php echo admin_url('admin-ajax.php'); ?>",
                nonce: "<?php echo $nonce; ?>",
                current_user_id: <?php echo get_current_user_id(); ?>,
                strings: {
                    save_success: "Ereignis gespeichert",
                    save_error: "Fehler beim Speichern",
                    delete_success: "Ereignis gelöscht",
                    delete_error: "Fehler beim Löschen",
                    confirm_delete: "Ereignis wirklich löschen?"
                }
            };
        </script>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div id="neo-calendar">
                        <h2>Mitarbeiterkalender</h2>

                        <!-- Arbeitszeitformular (Standardmäßig sichtbar) -->
                        <div id="work-time-form" class="calendar-form mb-4">
                            <h4>Arbeitszeit hinzufügen</h4>
                            <div class="row">
                                <div class="col-md-2">
                                    <label for="work-date" class="form-label">Datum</label>
                                    <input type="text" class="form-control date-picker" id="work-date" value="<?php echo date('d-m-Y'); ?>" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label for="work-time-from" class="form-label">Zeit von</label>
                                    <input type="text" class="form-control" id="work-time-from" value="09:00">
                                </div>
                                <div class="col-md-2">
                                    <label for="work-time-to" class="form-label">Zeit bis</label>
                                    <input type="text" class="form-control" id="work-time-to" value="18:00">
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button type="button" class="btn btn-primary me-2" id="add-work-time-btn">
                                        <i class="bi bi-plus-circle"></i> Hinzufügen
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-toggle-form" id="show-vacation-form-btn">
                                        <i class="bi bi-calendar-x"></i> Urlaub
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-toggle-form" id="show-event-form-btn">
                                        <i class="bi bi-calendar-event"></i> Veranstaltung
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Urlaubformular (Standardmäßig ausgeblendet) -->
                        <div id="vacation-form" class="calendar-form mb-4" style="display: none;">
                            <h4>Urlaub hinzufügen</h4>
                            <div class="row">
                                <div class="col-md-8">
                                    <label for="vacation-date-range" class="form-label">Urlaubsbereich</label>
                                    <input type="text" class="form-control date-range-picker" id="vacation-date-range" placeholder="Wählen Sie einen Datumsbereich" readonly>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="button" class="btn btn-primary me-2" id="add-vacation-btn">
                                        <i class="bi bi-plus-circle"></i> Hinzufügen
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-toggle-form" id="back-to-work-form-btn">
                                        <i class="bi bi-arrow-left"></i> Zurück
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Veranstaltungsformular (Standardmäßig ausgeblendet) -->
                        <div id="event-form" class="calendar-form mb-4" style="display: none;">
                            <h4>Veranstaltung hinzufügen</h4>
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="event-date" class="form-label">Datum</label>
                                    <input type="text" class="form-control date-picker" id="event-date" value="<?php echo date('d-m-Y'); ?>" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label for="event-time" class="form-label">Zeit</label>
                                    <input type="text" class="form-control" id="event-time" value="10:00">
                                </div>
                                <div class="col-md-3">
                                    <label for="event-title" class="form-label">Titel</label>
                                    <input type="text" class="form-control" id="event-title" placeholder="Titel eingeben">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="button" class="btn btn-primary me-2" id="add-event-btn">
                                        <i class="bi bi-plus-circle"></i> Hinzufügen
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-toggle-form" id="back-to-work-from-event-btn">
                                        <i class="bi bi-arrow-left"></i> Zurück
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="calendar"></div>

                        <!-- Информационное сообщение, если нет событий -->
                        <div id="no-events-message" class="alert alert-info mt-3" style="display: none;">
                            <i class="bi bi-info-circle"></i>
                            <strong>Keine Ereignisse vorhanden</strong><br>
                            Der Kalender ist leer. Fügen Sie Arbeitszeiten oder Urlaub hinzu, um den Kalender zu füllen.
                        </div>

                        <!-- Modal для редактирования событий -->
                        <div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editEventModalLabel">Ereignis bearbeiten</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="edit-event-form">
                                            <input type="hidden" id="edit-event-id">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="edit-event-type" class="form-label">Typ</label>
                                                    <select class="form-control" id="edit-event-type" required>
                                                        <option value="arbeitsstunde">Arbeitszeit</option>
                                                        <option value="urlaub">Urlaub</option>
                                                        <option value="veranstaltung">Veranstaltung</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6" id="edit-event-title-container">
                                                    <label for="edit-event-title" class="form-label">Name</label>
                                                    <input type="text" class="form-control" id="edit-event-title" placeholder="Name eingeben">
                                                </div>
                                                <div class="col-md-6" id="edit-event-employee-container" style="display: none;">
                                                    <label for="edit-event-employee" class="form-label">Mitarbeiter</label>
                                                    <select class="form-control" id="edit-event-employee">
                                                        <!-- Опции добавляются через JavaScript -->
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-md-3">
                                                    <label for="edit-event-start-date" class="form-label">Start Datum</label>
                                                    <input type="text" class="form-control date-picker" id="edit-event-start-date" readonly required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="edit-event-start-time" class="form-label">Start Zeit</label>
                                                    <input type="text" class="form-control" id="edit-event-start-time">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="edit-event-end-date" class="form-label">Ende Datum</label>
                                                    <input type="text" class="form-control date-picker" id="edit-event-end-date" readonly>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="edit-event-end-time" class="form-label">Ende Zeit</label>
                                                    <input type="text" class="form-control" id="edit-event-end-time">
                                                </div>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <label for="edit-event-description" class="form-label">Beschreibung</label>
                                                    <textarea class="form-control" id="edit-event-description" rows="3" placeholder="Beschreibung eingeben"></textarea>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger me-auto" id="delete-event-btn">
                                            <i class="bi bi-trash"></i> Löschen
                                        </button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                                        <button type="button" class="btn btn-primary" id="save-event-changes-btn">
                                            <i class="bi bi-check-circle"></i> Speichern
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_widget() {
        ?>
        <div class="card widget-card" style="border-color: transparent;">
            <!-- Arbeitszeitformular (Standardmäßig sichtbar) -->
            <div class="row" id="widget-work-form">
                <div class="col-md-4">
                    <label for="widget-work-date" class="form-label">Datum</label>
                    <input type="text" class="form-control date-picker" id="widget-work-date" value="<?php echo date('d-m-Y'); ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label for="widget-work-time-from" class="form-label">Zeit von</label>
                    <input type="time" class="form-control" id="widget-work-time-from" value="09:00">
                </div>
                <div class="col-md-4">
                    <label for="widget-work-time-to" class="form-label">Zeit bis</label>
                    <input type="time" class="form-control" id="widget-work-time-to" value="18:00">
                </div>
            </div>

            <!-- Urlaubformular (Standardmäßig ausgeblendet) -->
            <div class="row" id="widget-vacation-form" style="display: none;">
                <div class="col-12">
                    <label for="widget-vacation-date-range" class="form-label">Urlaubsbereich</label>
                    <input type="text" class="form-control date-range-picker" id="widget-vacation-date-range" placeholder="Wählen Sie einen Datumsbereich" readonly>
                </div>
            </div>

            <hr>
            <div class="row">
                <div class="col-12">
                    <button type="button" class="btn btn-primary me-2" id="widget-add-work-time-btn">
                        <i class="bi bi-plus-circle"></i> Hinzufügen
                    </button>
                    <button type="button" class="btn btn-success me-2" id="widget-add-vacation-btn" style="display: none;">
                        <i class="bi bi-plus-circle"></i> Urlaub hinzufügen
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-toggle-form" id="widget-show-vacation-form-btn">
                        <i class="bi bi-calendar-x"></i> Urlaub
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    public function create_database_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'neo_calendar_events';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            type VARCHAR(20) NOT NULL COMMENT 'veranstaltung, urlaub, arbeitsstunde',
            title VARCHAR(255) NOT NULL,
            start DATETIME NOT NULL,
            end DATETIME DEFAULT NULL,
            description TEXT DEFAULT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_action('admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo '<strong>Neo Calendar</strong> erfolgreich aktiviert! Tabelle mit Feldern für Vorname und Nachname aktualisiert. Wechseln Sie zu Neo Dashboard, um sie anzuzeigen.';
            echo '</p></div>';
        });
    }
}

// Инициализация плагина
new Neo_Calendar();
