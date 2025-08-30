<?php

/**
 * Plugin Name: Neo Calendar
 * Description: Calendar on the basis of Neo Dashboard
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

    add_action('neo_dashboard_enqueue_plugin_assets_css', function () {
        wp_enqueue_style(
            'neo-calendar-css',
            plugin_dir_url(__FILE__) . 'assets/css/neo-calendar.css',
            [],
            '1.0.0'
        );
    });

    // FullCalendar JS
    add_action('neo_dashboard_enqueue_plugin_assets_js', function () {
        wp_enqueue_script(
            'fullcalendar-js',
            plugin_dir_url(__FILE__) . 'assets/fullcalendar/dist/index.global.min.js',
            [],
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
        // Передаем AJAX данные в JavaScript
        wp_localize_script('neo-calendar-js', 'neoCalendarAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('neo_calendar_nonce'),
        ]);
    });

    // 3) Регистрируем элементы dashboard
    add_action('neo_dashboard_init', static function () {

        // Создаем группу в sidebar
        do_action('neo_dashboard_register_sidebar_item', [
            'slug'     => 'neo-calendar-group',
            'label'    => 'Neo Calendar',
            'icon'     => 'bi-calendar-event',
            'url'      => '/neo-dashboard/neo-calendar',
            'position' => 25,
            'is_group' => true, // Это группа
        ]);

        // Создаем подсекции
        $sections = [
            'welcome' => [
                'label' => 'Calendar',
                'icon'  => 'bi-calendar',
                'pos'   => 26,
            ],
        ];

        foreach ($sections as $slug => $data) {
            $full_slug = 'neo-calendar/' . $slug;

            // Регистрируем sidebar элемент
            do_action('neo_dashboard_register_sidebar_item', [
                'slug'     => $full_slug,
                'label'    => $data['label'],
                'icon'     => $data['icon'],
                'url'      => '/neo-dashboard/' . $full_slug,
                'parent'   => 'neo-calendar-group', // Привязываем к группе
                'position' => $data['pos'],
            ]);

            // Регистрируем секцию с уникальным callback
            do_action('neo_dashboard_register_section', [
                'slug'     => $full_slug,
                'label'    => $data['label'],
                'callback' => 'neo_calendar_' . $slug . '_callback',
            ]);
        }

        // Регистрируем главную секцию
        do_action('neo_dashboard_register_section', [
            'slug'     => 'neo-calendar',
            'label'    => 'Neo Calendar',
            'callback' => 'neo_calendar_main_section_callback',
        ]);

        // Регистрируем виджет
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
        // Проверяем nonce для безопасности
        if (!wp_verify_nonce($_POST['nonce'], 'neo_calendar_nonce')) {
            wp_send_json_error('Security check failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_calendar_events';



        $user_id = get_current_user_id();
        $user_name = wp_get_current_user()->display_name;
        $type = sanitize_text_field($_POST['type'] ?? 'event');
        $title = sanitize_text_field($_POST['title'] ?? '');
        $start = sanitize_text_field($_POST['start'] ?? '');
        $end = sanitize_text_field($_POST['end'] ?? '');
        $meta = sanitize_text_field($_POST['meta'] ?? '');

        if (empty($start)) {
            wp_send_json_error('Start date is required');
        }

        // Проверяем существование таблицы
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            wp_send_json_error('Database table does not exist. Please deactivate and reactivate the plugin.');
        }

        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'user_name' => $user_name,
                'type' => $type,
                'title' => $title,
                'start' => $start,
                'end' => $end,
                'meta' => $meta
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
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
        // Проверяем nonce для безопасности
        if (!wp_verify_nonce($_POST['nonce'], 'neo_calendar_nonce')) {
            wp_send_json_error('Security check failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_calendar_events';



        $user_id = get_current_user_id();
        $start_date = sanitize_text_field($_POST['start'] ?? '');
        $end_date = sanitize_text_field($_POST['end'] ?? '');

        // Проверяем существование таблицы
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            wp_send_json_error('Database table does not exist. Please deactivate and reactivate the plugin.');
        }

        $where_clause = "WHERE user_id = %d";
        $where_values = [$user_id];

        if (!empty($start_date)) {
            $where_clause .= " AND start >= %s";
            $where_values[] = $start_date;
        }

        if (!empty($end_date)) {
            $where_clause .= " AND end <= %s";
            $where_values[] = $end_date;
        }

        $sql = $wpdb->prepare(
            "SELECT id, type, title, start, end, meta, user_name 
              FROM $table_name 
              $where_clause 
              ORDER BY start ASC",
            $where_values
        );

        $events = $wpdb->get_results($sql);

        // Форматируем события для FullCalendar
        $formatted_events = [];
        foreach ($events as $event) {
            $user_name = $event->user_name ?: 'Unbekannter Benutzer';

            // Формируем заголовок и добавляем цвета в зависимости от типа события
            switch ($event->type) {
                case 'arbeitsstunde':
                    // Вычисляем количество часов
                    $start_time = new DateTime($event->start);
                    $end_time = new DateTime($event->end);
                    $interval = $start_time->diff($end_time);
                    $hours = $interval->h + ($interval->i / 60);
                    // Игнорируем title из базы данных, формируем заново
                    $title = $user_name . ' (' . number_format($hours, 1) . 'h)';
                    $color = '#3788d8';
                    $allDay = false;
                    break;
                case 'urlaub':
                    $title = 'Urlaub ' . $user_name;
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
                'allDay' => $allDay
            ];

            $formatted_events[] = $formatted_event;
        }

        wp_send_json_success($formatted_events);
    });

    add_action('wp_ajax_neo_calendar_delete_event', function () {
        // Проверяем nonce для безопасности
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

        // Проверяем существование таблицы
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            wp_send_json_error('Database table does not exist. Please deactivate and reactivate the plugin.');
        }

        // Проверяем, что событие принадлежит текущему пользователю
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id = %d AND user_id = %d",
            $event_id,
            $user_id
        ));

        if (!$event) {
            wp_send_json_error('Event not found or access denied');
        }

        $result = $wpdb->delete(
            $table_name,
            ['id' => $event_id, 'user_id' => $user_id],
            ['%d', '%d']
        );

        if ($result === false) {
            wp_send_json_error('Failed to delete event: ' . $wpdb->last_error);
        }
        wp_send_json_success(['message' => 'Event deleted successfully']);
    });
});

// 5) Функции для отображения секций
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
                        <p>Добро пожаловать в Neo Calendar на базе Neo Dashboard!</p>
                        <p>Это главная страница календаря. Здесь вы можете управлять событиями и расписанием.</p>

                        <div class="alert alert-info">
                            <strong>Подсказка:</strong> Используйте навигацию слева для перехода между разделами.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}

// Функция для секции "Календарь"
function neo_calendar_welcome_callback()
{
?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div id="neo-calendar">
                    <h2>Mitarbeiterkalender</h2>

                    <!-- Форма рабочего времени (по умолчанию видна) -->
                    <div id="work-time-form" class="calendar-form mb-4">
                        <h4>Arbeitszeit hinzufügen</h4>
                        <div class="row">
                            <div class="col-md-3">
                                <label for="work-date" class="form-label">Datum</label>
                                <input type="date" class="form-control" id="work-date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="work-time-from" class="form-label">Zeit von</label>
                                <input type="time" class="form-control" id="work-time-from" value="09:00">
                            </div>
                            <div class="col-md-3">
                                <label for="work-time-to" class="form-label">Zeit bis</label>
                                <input type="time" class="form-control" id="work-time-to" value="18:00">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary me-2" id="add-work-time-btn">
                                    <i class="bi bi-plus-circle"></i> Hinzufügen
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="show-vacation-form-btn">
                                    <i class="bi bi-calendar-x"></i> Urlaub
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Форма отпуска (скрыта по умолчанию) -->
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
                                <button type="button" class="btn btn-outline-secondary" id="back-to-work-form-btn">
                                    <i class="bi bi-arrow-left"></i> Zurück
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="calendar"></div>
                    <!-- Информационное сообщение когда нет событий -->
                    <div id="no-events-message" class="alert alert-info mt-3" style="display: none;">
                        <i class="bi bi-info-circle"></i>
                        <strong>Keine Ereignisse vorhanden</strong><br>
                        Der Kalender ist leer. Fügen Sie Arbeitszeiten oder Urlaub hinzu, um den Kalender zu füllen.
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}




// 6) Функция для отображения виджета
function neo_calendar_widget_callback()
{
?>
    <div class="card">
        <div class="row">
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
        <hr>
        <div class="row">
            <div class="col-12">
                <button type="button" class="btn btn-primary me-2" id="widget-add-work-time-btn">
                    <i class="bi bi-plus-circle"></i> Hinzufügen
                </button>
                <button type="button" class="btn btn-outline-secondary" id="widget-show-vacation-form-btn">
                    <i class="bi bi-calendar-x"></i> Urlaub
                </button>
            </div>
        </div>
    </div>
<?php
}

// 7) Хуки активации/деактивации
register_activation_hook(__FILE__, function () {
    global $wpdb;



    // Создаем таблицу в основной базе данных WordPress
    $table_name = $wpdb->prefix . 'neo_calendar_events';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
          id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          user_id BIGINT(20) UNSIGNED NOT NULL,
          user_name VARCHAR(100) NOT NULL,
          type VARCHAR(20) NOT NULL COMMENT 'veranstaltung, urlaub, arbeitsstunde',
          title VARCHAR(255) NOT NULL,
          start DATETIME NOT NULL,
          end DATETIME DEFAULT NULL,
          meta TEXT DEFAULT NULL,
          PRIMARY KEY  (id)
      ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Показываем сообщение об успешной активации
    add_action('admin_notices', function () {
        echo '<div class="notice notice-success is-dismissible"><p>';
        echo '<strong>Neo Calendar</strong> успешно активирован! Таблица создана в основной базе данных WordPress. Перейдите в Neo Dashboard для просмотра.';
        echo '</p></div>';
    });
});

register_deactivation_hook(__FILE__, function () {
    // Очищаем при деактивации (опционально)
});
