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

add_action('plugins_loaded', static function () {
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

    add_action('neo_dashboard_enqueue_neo-calendar_assets_css', function () {
        wp_enqueue_style(
            'neo-calendar-css',
            plugin_dir_url(__FILE__) . 'assets/css/neo-calendar.css',
            [],
            '1.0.0'
        );

        wp_enqueue_style(
            'flatpickr-css',
            'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
            [],
            '4.6.13'
        );
    });

    add_action('neo_dashboard_enqueue_widget_assets_css', function () {
        wp_enqueue_style(
            'flatpickr-css',
            'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
            [],
            '4.6.13'
        );
    });

    // FullCalendar JS
    add_action('neo_dashboard_enqueue_neo-calendar_assets_js', function () {
        wp_enqueue_script(
            'fullcalendar-js',
            plugin_dir_url(__FILE__) . 'assets/fullcalendar/dist/index.global.min.js',
            [],
            '6.1.19',
            true
        );

        wp_enqueue_script(
            'fullcalendar-locale-de-js',
            plugin_dir_url(__FILE__) . 'assets/fullcalendar/packages/core/locales/de.global.min.js',
            ['fullcalendar-js'],
            '6.1.19',
            true
        );

        wp_enqueue_script(
            'neo-calendar-js',
            plugin_dir_url(__FILE__) . 'assets/js/neo-calendar.js',
            ['jquery', 'fullcalendar-js'],
            '1.0.0',
            true
        );

        wp_enqueue_script(
            'flatpickr-js',
            'https://cdn.jsdelivr.net/npm/flatpickr',
            [],
            '4.6.13',
            true
        );

        wp_enqueue_script(
            'neo-calendar-common-js',
            plugin_dir_url(__FILE__) . 'assets/js/neo-calendar-common.js',
            ['jquery', 'flatpickr-js'],
            '1.0.0',
            true
        );

        // Sende AJAX Daten an JavaScript
        wp_localize_script('neo-calendar-js', 'neoCalendarAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('neo_calendar_nonce'),
            'current_user_id' => get_current_user_id(),
        ]);
    });

    add_action('neo_dashboard_enqueue_widget_assets_js', function () {
        wp_enqueue_script(
            'flatpickr-js',
            'https://cdn.jsdelivr.net/npm/flatpickr',
            [],
            '4.6.13',
            true
        );

        wp_enqueue_script(
            'neo-calendar-common-js',
            plugin_dir_url(__FILE__) . 'assets/js/neo-calendar-common.js',
            ['jquery', 'flatpickr-js'],
            '1.0.0',
            true
        );

        wp_enqueue_script(
            'widget-neo-calendar-js',
            plugin_dir_url(__FILE__) . 'assets/js/widget-neo-calendar.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Sende AJAX Daten an JavaScript
        wp_localize_script('widget-neo-calendar-js', 'neoCalendarAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('neo_calendar_nonce'),
        ]);
    });

    // Registriere Dashboard Elemente
    add_action('neo_dashboard_init', static function () {

        // Erstelle Gruppe in Sidebar
        do_action('neo_dashboard_register_sidebar_item', [
            'slug'     => 'neo-calendar-group',
            'label'    => 'Neo Calendar',
            'icon'     => 'bi-calendar-event',
            'url'      => '/neo-dashboard/neo-calendar',
            'position' => 25,
            'is_group' => true, // Dies ist eine Gruppe
        ]);

        // Erstelle Untersektionen
        $sections = [
            'welcome' => [
                'label' => 'Calendar',
                'icon'  => 'bi-calendar',
                'pos'   => 26,
            ],
        ];

        foreach ($sections as $slug => $data) {
            $full_slug = 'neo-calendar/' . $slug;

            // Registriere Sidebar Element
            do_action('neo_dashboard_register_sidebar_item', [
                'slug'     => $full_slug,
                'label'    => $data['label'],
                'icon'     => $data['icon'],
                'url'      => '/neo-dashboard/' . $full_slug,
                'parent'   => 'neo-calendar-group', // Binde an Gruppe
                'position' => $data['pos'],
            ]);

            // Registriere Sektion mit eindeutigem Callback
            do_action('neo_dashboard_register_section', [
                'slug'     => $full_slug,
                'label'    => $data['label'],
                'callback' => 'neo_calendar_' . $slug . '_callback',
            ]);
        }

        // Registriere Hauptsektion
        do_action('neo_dashboard_register_section', [
            'slug'     => 'neo-calendar',
            'label'    => 'Neo Calendar',
            'callback' => 'neo_calendar_main_section_callback',
        ]);

        // Registriere Widget
        do_action('neo_dashboard_register_widget', [
            'id'       => 'neo-calendar-widget',
            'label'    => 'Calendar',
            'icon'     => 'bi-calendar-week',
            'priority' => 10,
            'callback' => 'neo_calendar_widget_callback',
        ]);
    });

    // 4) AJAX обработчики
    add_action('wp_ajax_neo_calendar_save_event', function () {
        // Проверяем nonce für Sicherheit
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

        // Prüfe auf Existenz der Tabelle
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
    });

    add_action('wp_ajax_neo_calendar_get_events', function () {
        // Проверяем nonce für Sicherheit
        if (!wp_verify_nonce($_POST['nonce'], 'neo_calendar_nonce')) {
            wp_send_json_error('Security check failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_calendar_events';



        $user_id = get_current_user_id();
        $can_manage = current_user_can('manage_calendar');
        $start_date = sanitize_text_field($_POST['start'] ?? '');
        $end_date = sanitize_text_field($_POST['end'] ?? '');

        // Prüfe auf Existenz der Tabelle
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

        // Formatiere Ereignisse für FullCalendar
        $formatted_events = [];
        foreach ($events as $event) {
            // Erstelle angezeigten Namen: Vorname + Nachname oder Nickname als Fallback
            $display_name = '';
            if (!empty($event->first_name) && !empty($event->last_name)) {
                $display_name = trim($event->first_name . ' ' . $event->last_name);
            } elseif (!empty($event->first_name)) {
                $display_name = $event->first_name;
            } else {
                $display_name = $event->user_name ?: 'Unbekannter Benutzer';
            }

            // Erstelle Titel und füge Farben basierend auf Ereignistyp hinzu
            switch ($event->type) {
                case 'arbeitsstunde':
                    // Berechne Anzahl der Stunden
                    $start_time = new DateTime($event->start);
                    $end_time = new DateTime($event->end);
                    $interval = $start_time->diff($end_time);
                    $hours = $interval->h + ($interval->i / 60);
                    // Ignoriere Titel aus der Datenbank, erstelle neu
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

            $formatted_event = [
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

            $formatted_events[] = $formatted_event;
        }

        wp_send_json_success($formatted_events);
    });

    add_action('wp_ajax_neo_calendar_delete_event', function () {
        // Проверяем nonce für Sicherheit
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

        // Prüfe auf Existenz der Tabelle
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            wp_send_json_error('Database table does not exist. Please deactivate and reactivate the plugin.');
        }

        // Prüfe auf Existenz des Ereignisses
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT id, user_id FROM $table_name WHERE id = %d",
            $event_id
        ));

        if (!$event) {
            wp_send_json_error('Event not found');
        }

        // Prüfe Rechte: Besitzer oder Administrator
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
    });

    // AJAX обработчик für Aktualisierung von Ereignissen
    add_action('wp_ajax_neo_calendar_update_event', function () {
        // Проверяем nonce für Sicherheit
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

        // Prüfe, ob das Ereignis dem aktuellen Benutzer gehört oder ob der Benutzer Berechtigungen hat
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT id, user_id FROM $table_name WHERE id = %d",
            $event_id
        ));

        if (!$event) {
            wp_send_json_error('Event not found');
        }

        // Prüfe Rechte: Besitzer oder Administrator
        if ($event->user_id != $user_id && !current_user_can('manage_calendar')) {
            wp_send_json_error('Access denied');
        }

        // Bestimme user_id für Aktualisierung und hole Benutzerdaten
        $update_user_id = $user_id;
        $update_user_name = '';
        $update_first_name = '';
        $update_last_name = '';

        if ($employee_id > 0 && current_user_can('manage_calendar')) {
            $update_user_id = $employee_id;

            // Hole Benutzerdaten des ausgewählten Benutzers
            $selected_user = get_user_by('ID', $employee_id);
            if ($selected_user) {
                $update_user_name = $selected_user->display_name;
                $update_first_name = $selected_user->first_name ?: $selected_user->display_name;
                $update_last_name = $selected_user->last_name ?: '';
            }

            // Füge Kommentar zur Änderung des Mitarbeiters hinzu
            $current_user = wp_get_current_user();
            $admin_name = $current_user->first_name && $current_user->last_name ?
                $current_user->first_name . ' ' . $current_user->last_name :
                $current_user->display_name;

            $change_comment = "\n\nMitarbeiter geändert von " . $admin_name . " am " . date('d.m.Y H:i');
            $description .= $change_comment;
        } else {
            // Verwende Daten des aktuellen Benutzers
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
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error('Failed to update event: ' . $wpdb->last_error);
        }

        wp_send_json_success([
            'message' => 'Event updated successfully',
            'id' => $event_id
        ]);
    });

    // AJAX обработчик für Abrufen von Ereignisdaten zum Bearbeiten
    add_action('wp_ajax_neo_calendar_get_event', function () {
        // Проверяем nonce für Sicherheit
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

        // Hole Ereignis
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $event_id
        ));

        if (!$event) {
            wp_send_json_error('Event not found');
        }

        // Prüfe Rechte: Besitzer oder Administrator
        if ($event->user_id != $user_id && !current_user_can('manage_calendar')) {
            wp_send_json_error('Access denied');
        }

        wp_send_json_success($event);
    });

    // AJAX обработчик für Aktualisierung von vorhandenen Einträgen mit neuen Benutzerdaten
    add_action('wp_ajax_neo_calendar_update_user_data', function () {
        // Проверяем nonce für Sicherheit
        if (!wp_verify_nonce($_POST['nonce'], 'neo_calendar_nonce')) {
            wp_send_json_error('Security check failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_calendar_events';

        $user_id = get_current_user_id();
        $current_user = wp_get_current_user();
        $first_name = $current_user->first_name ?: $current_user->display_name;
        $last_name = $current_user->last_name ?: '';

        // Aktualisiere alle Einträge des aktuellen Benutzers
        $result = $wpdb->update(
            $table_name,
            [
                'first_name' => $first_name,
                'last_name' => $last_name
            ],
            ['user_id' => $user_id],
            ['%s', '%s'],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error('Failed to update user data: ' . $wpdb->last_error);
        }

        wp_send_json_success([
            'message' => 'User data updated successfully',
            'updated_records' => $result
        ]);
    });

    // AJAX обработчик für Abrufen der Benutzerliste
    add_action('wp_ajax_neo_calendar_get_users', function () {
        // Проверяем nonce für Sicherheit
        if (!wp_verify_nonce($_POST['nonce'], 'neo_calendar_nonce')) {
            wp_send_json_error('Security check failed');
        }

        // Prüfe Zugriffsrechte
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
    });

    add_action('init', function () {
        $editor = get_role('neo_editor');
        if ($editor && !$editor->has_cap('manage_calendar')) {
            $editor->add_cap('manage_calendar');
        }

        $admin = get_role('administrator');
        if ($admin && !$admin->has_cap('manage_calendar')) {
            $admin->add_cap('manage_calendar');
        }
    });
});

// 5) Funktionen für die Anzeige von Sektionen
function neo_calendar_main_section_callback()
{
?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="bi bi-calendar-event"></i> Neo Calendar</h2>
                    </div>
                    <div class="card-body">
                        <p>Willkommen bei Neo Calendar auf Basis von Neo Dashboard!</p>
                        <p>Dies ist die Hauptseite des Kalenders. Hier können Sie Ereignisse verwalten und das Stundenplan.</p>

                        <div class="alert alert-info">
                            <strong>Tipp:</strong> Verwenden Sie die Navigation links, um zwischen Abschnitten zu wechseln.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}

// Funktion für die Sektion "Kalender"
function neo_calendar_welcome_callback()
{
?>
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
                                 <input type="date" class="form-control" id="work-date" value="<?php echo date('Y-m-d'); ?>">
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
                            <div class="col-md-4">
                                <label for="vacation-date-from" class="form-label">Datum von</label>
                                <input type="date" class="form-control" id="vacation-date-from" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="vacation-date-to" class="form-label">Datum bis</label>
                                <input type="date" class="form-control" id="vacation-date-to" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" class="btn btn-success me-2" id="add-vacation-btn">
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
                                <input type="date" class="form-control" id="event-date" value="<?php echo date('Y-m-d'); ?>">
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
                                <button type="button" class="btn btn-info me-2" id="add-event-btn">
                                    <i class="bi bi-plus-circle"></i> Hinzufügen
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-toggle-form" id="back-to-work-from-event-btn">
                                    <i class="bi bi-arrow-left"></i> Zurück
                                </button>
                            </div>
                        </div>
                    </div>

                     

                    <div id="calendar"></div>
                    <!-- Informationsnachricht, wenn keine Ereignisse vorhanden sind -->
                    <div id="no-events-message" class="alert alert-info mt-3" style="display: none;">
                        <i class="bi bi-info-circle"></i>
                        <strong>Keine Ereignisse vorhanden</strong><br>
                        Der Kalender ist leer. Fügen Sie Arbeitszeiten oder Urlaub hinzu, um den Kalender zu füllen.
                    </div>

                    <!-- Modal zum Bearbeiten von Ereignissen -->
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
                                                    <!-- Optionen werden über JavaScript hinzugefügt -->
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-3">
                                                <label for="edit-event-start-date" class="form-label">Start Datum</label>
                                                <input type="date" class="form-control" id="edit-event-start-date" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="edit-event-start-time" class="form-label">Start Zeit</label>
                                                <input type="text" class="form-control" id="edit-event-start-time">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="edit-event-end-date" class="form-label">Ende Datum</label>
                                                <input type="date" class="form-control" id="edit-event-end-date">
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




// 6) Funktion für die Anzeige des Widgets
function neo_calendar_widget_callback()
{
?>
    <div class="card widget-card" style="border-color: transparent;">
        <!-- Arbeitszeitformular (Standardmäßig sichtbar) -->
        <div class="row" id="widget-work-form">
            <div class="col-md-4">
                <label for="widget-work-date" class="form-label">Datum</label>
                <input type="date" class="form-control" id="widget-work-date" value="<?php echo date('Y-m-d'); ?>">
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
            <div class="col-md-6">
                <label for="widget-vacation-date-from" class="form-label">Datum von</label>
                <input type="date" class="form-control" id="widget-vacation-date-from" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-6">
                <label for="widget-vacation-date-to" class="form-label">Datum bis</label>
                <input type="date" class="form-control" id="widget-vacation-date-to" value="<?php echo date('Y-m-d'); ?>">
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

// 7) Aktivierungs-/Deaktivierungs-Hooks
register_activation_hook(__FILE__, function () {
    global $wpdb;



    // Erstelle Tabelle in der Hauptdatenbank von WordPress
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
});

register_deactivation_hook(__FILE__, function () {
    // Leere bei Deaktivierung (optional)
});
