<?php
/**
 * Plugin Name: Neo Calendar
 * Description: Календарь на базе Neo Dashboard
 * Version: 1.0.0
 * Author: Ваше имя
 * Text Domain: neo-calendar
 */

declare(strict_types=1);

// Безопасность - предотвращаем прямой доступ
if (!defined('ABSPATH')) {
    exit;
}

// 1) Проверяем что Neo Dashboard Core активен
add_action('plugins_loaded', static function() {

    if (!class_exists(\NeoDashboard\Core\Router::class)) {
        // Показываем ошибку если Neo Dashboard не активен
        add_action('admin_notices', static function() {
            echo '<div class="notice notice-error"><p>';
            echo 'Neo Calendar требует "Neo Dashboard Core" для работы.';
            echo '</p></div>';
        });
        return;
    }

    // 2) Подключаем CSS и JS только для dashboard
    add_action('neo_dashboard_enqueue_assets', static function() {
        // CSS файл
        wp_enqueue_style(
            'neo-calendar-css',
            plugin_dir_url(__FILE__) . 'assets/css/neo-calendar.css',
            [],
            '1.0.0'
        );
        
        // Загружаем наш JavaScript файл
        wp_enqueue_script(
            'neo-calendar-js',
            plugin_dir_url(__FILE__) . 'assets/js/neo-calendar.js',
            ['jquery'],
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
    add_action('neo_dashboard_init', static function() {
        
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
            'settings' => [
                'label' => 'Settings',
                'icon'  => 'bi-gear',
                'pos'   => 27,
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
    add_action('wp_ajax_neo_calendar_action', function() {
        // Проверяем nonce для безопасности
        if (!wp_verify_nonce($_POST['nonce'], 'neo_calendar_nonce')) {
            wp_send_json_error('Security check failed');
        }

        // Ваша логика здесь
        $message = sanitize_text_field($_POST['message'] ?? '');
        
        if (empty($message)) {
            wp_send_json_error('Message is required');
        }

        // Отправляем успешный ответ
        wp_send_json_success([
            'message' => 'Success! Your message: ' . $message,
            'time'    => current_time('mysql'),
        ]);
    });
});

// 5) Функции для отображения секций
function neo_calendar_main_section_callback() {
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

// Функция для секции "Приветствие"
function neo_calendar_welcome_callback() {
    ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div id="neo-calendar">
                    <h2>Календарь сотрудников</h2>
                    
                    <!-- Форма рабочего времени (по умолчанию видна) -->
                    <div id="work-time-form" class="calendar-form mb-4">
                        <h4>Добавить рабочее время</h4>
                        <div class="row">
                            <div class="col-md-3">
                                <label for="work-date" class="form-label">Дата</label>
                                <input type="date" class="form-control" id="work-date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="work-time-from" class="form-label">Время с</label>
                                <input type="time" class="form-control" id="work-time-from" value="09:00">
                            </div>
                            <div class="col-md-3">
                                <label for="work-time-to" class="form-label">Время до</label>
                                <input type="time" class="form-control" id="work-time-to" value="18:00">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary me-2" id="add-work-time-btn">
                                    <i class="bi bi-plus-circle"></i> Добавить
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="show-vacation-form-btn">
                                    <i class="bi bi-calendar-x"></i> Urlaub
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Форма отпуска (скрыта по умолчанию) -->
                    <div id="vacation-form" class="calendar-form mb-4" style="display: none;">
                        <h4>Добавить отпуск</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="vacation-date-from" class="form-label">Дата с</label>
                                <input type="date" class="form-control" id="vacation-date-from" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="vacation-date-to" class="form-label">Дата до</label>
                                <input type="date" class="form-control" id="vacation-date-to" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" class="btn btn-success me-2" id="add-vacation-btn">
                                    <i class="bi bi-plus-circle"></i> Добавить
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="back-to-work-form-btn">
                                    <i class="bi bi-arrow-left"></i> Назад
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="calendar"></div>
                </div>
                
                <!-- Загружаем FullCalendar напрямую -->
                <script src="<?php echo plugin_dir_url(__FILE__); ?>assets/fullcalendar/dist/index.global.min.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        console.log('Direct FullCalendar script loaded');
                        console.log('FullCalendar object:', typeof FullCalendar);
                        
                        var calendarEl = document.getElementById('calendar');
                        console.log('Calendar element:', calendarEl);
                        
                        if (calendarEl && typeof FullCalendar !== 'undefined') {
                            try {
                                var calendar = new FullCalendar.Calendar(calendarEl, {
                                    initialView: 'dayGridMonth',
                                    events: [],
                                    height: 'auto',
                                    headerToolbar: {
                                        left: 'prev,next today',
                                        center: 'title',
                                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                                    },
                                    editable: true,
                                    selectable: true,
                                    selectMirror: true,
                                    dayMaxEvents: true,
                                    weekends: true
                                });
                                
                                // Рендерим календарь
                                calendar.render();
                                console.log('FullCalendar initialized successfully from direct script!');
                                
                                // Делаем календарь глобально доступным
                                window.neoCalendar = calendar;
                                
                                // Инициализируем обработчики форм
                                initCalendarForms();
                            } catch (error) {
                                console.error('Error initializing FullCalendar:', error);
                            }
                        } else {
                            console.error('Calendar element or FullCalendar not found');
                        }
                    });
                    
                    // Функция инициализации обработчиков форм
                    function initCalendarForms() {
                        // Обработчик кнопки "Urlaub" - показать форму отпуска
                        document.getElementById('show-vacation-form-btn').addEventListener('click', function() {
                            document.getElementById('work-time-form').style.display = 'none';
                            document.getElementById('vacation-form').style.display = 'block';
                        });
                        
                        // Обработчик кнопки "Назад" - вернуться к форме рабочего времени
                        document.getElementById('back-to-work-form-btn').addEventListener('click', function() {
                            document.getElementById('vacation-form').style.display = 'none';
                            document.getElementById('work-time-form').style.display = 'block';
                        });
                        
                        // Обработчик добавления рабочего времени
                        document.getElementById('add-work-time-btn').addEventListener('click', function() {
                            addWorkTime();
                        });
                        
                        // Обработчик добавления отпуска
                        document.getElementById('add-vacation-btn').addEventListener('click', function() {
                            addVacation();
                        });
                    }
                    
                    // Функция добавления рабочего времени
                    function addWorkTime() {
                        const date = document.getElementById('work-date').value;
                        const timeFrom = document.getElementById('work-time-from').value;
                        const timeTo = document.getElementById('work-time-to').value;
                        
                        if (!date || !timeFrom || !timeTo) {
                            alert('Пожалуйста, заполните все поля!');
                            return;
                        }
                        
                        if (timeFrom >= timeTo) {
                            alert('Время "с" должно быть меньше времени "до"!');
                            return;
                        }
                        
                        const startDateTime = date + 'T' + timeFrom + ':00';
                        const endDateTime = date + 'T' + timeTo + ':00';
                        
                        const event = {
                            title: 'Рабочее время',
                            start: startDateTime,
                            end: endDateTime,
                            color: '#3788d8',
                            backgroundColor: '#3788d8',
                            borderColor: '#3788d8',
                            textColor: '#ffffff',
                            allDay: false
                        };
                        
                        window.neoCalendar.addEvent(event);
                        
                        // Очищаем форму
                        document.getElementById('work-date').value = '<?php echo date('Y-m-d'); ?>';
                        document.getElementById('work-time-from').value = '09:00';
                        document.getElementById('work-time-to').value = '18:00';
                        
                        alert('Рабочее время добавлено!');
                    }
                    
                    // Функция добавления отпуска
                    function addVacation() {
                        const dateFrom = document.getElementById('vacation-date-from').value;
                        const dateTo = document.getElementById('vacation-date-to').value;
                        
                        if (!dateFrom || !dateTo) {
                            alert('Пожалуйста, заполните все поля!');
                            return;
                        }
                        
                        if (dateFrom > dateTo) {
                            alert('Дата "с" должна быть меньше или равна дате "до"!');
                            return;
                        }
                        
                        const event = {
                            title: 'Отпуск',
                            start: dateFrom,
                            end: dateTo,
                            color: '#ff6b6b',
                            backgroundColor: '#ff6b6b',
                            borderColor: '#ff6b6b',
                            textColor: '#ffffff',
                            allDay: true
                        };
                        
                        window.neoCalendar.addEvent(event);
                        
                        // Очищаем форму
                        document.getElementById('vacation-date-from').value = '<?php echo date('Y-m-d'); ?>';
                        document.getElementById('vacation-date-to').value = '<?php echo date('Y-m-d'); ?>';
                        
                        // Возвращаемся к форме рабочего времени
                        document.getElementById('vacation-form').style.display = 'none';
                        document.getElementById('work-time-form').style.display = 'block';
                        
                        alert('Отпуск добавлен!');
                    }
                </script>
            </div>
        </div>
    </div>
    <?php
}

// Функция для секции "Настройки"
function neo_calendar_settings_callback() {
    ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card settings-card">
                    <div class="card-header">
                        <h3><i class="bi bi-gear"></i> Настройки</h3>
                    </div>
                    <div class="card-body">
                        <div class="settings-intro">
                            <p>Настройте параметры вашего календаря. Все изменения сохраняются автоматически.</p>
                        </div>
                        
                        <form id="neo-calendar-settings-form" class="settings-form">
                            <div class="form-group">
                                <label for="plugin-name" class="form-label">Название плагина</label>
                                <input type="text" class="form-control" id="plugin-name" value="Neo Calendar" placeholder="Введите название плагина">
                                <small class="form-text text-muted">Это название будет отображаться в меню</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="plugin-description" class="form-label">Описание</label>
                                <textarea class="form-control" id="plugin-description" rows="3" placeholder="Опишите функционал плагина">Календарь на базе Neo Dashboard</textarea>
                                <small class="form-text text-muted">Краткое описание возможностей</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="plugin-version" class="form-label">Версия</label>
                                <input type="text" class="form-control" id="plugin-version" value="1.0.0" placeholder="1.0.0">
                                <small class="form-text text-muted">Текущая версия плагина</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Сохранить настройки
                            </button>
                        </form>
                        
                        <div class="settings-preview mt-4">
                            <h5>Предварительный просмотр:</h5>
                            <div class="preview-card">
                                <strong id="preview-name">Neo Calendar</strong>
                                <p id="preview-description">Календарь на базе Neo Dashboard</p>
                                <small id="preview-version">Версия: 1.0.0</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}


// 6) Функция для отображения виджета
function neo_calendar_widget_callback() {
    ?>
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-calendar-week"></i> Календарь</h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-6">
                    <div class="border-end">
                        <h4 class="text-primary">3</h4>
                        <small class="text-muted">События</small>
                    </div>
                </div>
                <div class="col-6">
                    <h4 class="text-primary">1</h4>
                    <small class="text-muted">Сегодня</small>
                </div>
            </div>
            <hr>
            <button class="btn btn-sm btn-outline-primary w-100 refresh-btn">
                <i class="bi bi-arrow-clockwise"></i> Обновить
            </button>
        </div>
    </div>
    <?php
}

// 7) Хуки активации/деактивации
register_activation_hook(__FILE__, function() {
    // Создаем опции при активации
    add_option('neo_calendar_settings', [
        'name' => 'Neo Calendar',
        'description' => 'Календарь на базе Neo Dashboard'
    ]);
    
    // Показываем сообщение об успешной активации
    add_action('admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible"><p>';
        echo '<strong>Neo Calendar</strong> успешно активирован! Перейдите в Neo Dashboard для просмотра.';
        echo '</p></div>';
    });
});

register_deactivation_hook(__FILE__, function() {
    // Очищаем при деактивации (опционально)
    // delete_option('neo_calendar_settings');
});
