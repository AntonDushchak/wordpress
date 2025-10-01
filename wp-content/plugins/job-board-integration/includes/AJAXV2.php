<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

require_once 'Settings.php';
require_once 'Templates.php';
require_once 'Jobs.php';
require_once 'APIClientV2.php';
require_once 'SecurityValidator.php';
require_once 'DataSanitizer.php';
require_once 'ErrorHandler.php';
require_once 'Constants.php';
require_once 'Services/TemplateService.php';
require_once 'Services/ApplicationService.php';

use NeoJobBoard\Services\TemplateService;
use NeoJobBoard\Services\ApplicationService;
use NeoJobBoard\SecurityValidator;
use NeoJobBoard\ErrorHandler;
use NeoJobBoard\APIClientV2;

class AJAXV2 {

    private $template_service;
    private $application_service;
    private $api_client;

    public function __construct() {
        $this->template_service = new TemplateService();
        $this->application_service = new ApplicationService();
        $this->api_client = new APIClientV2();
    }

    public static function init() {
        $instance = new self();
        
        // Templates AJAX (только для авторизованных пользователей)
        $template_actions = [
            'get_templates' => 'get_templates',
            'get_template' => 'get_template',
            'save_template' => 'save_template',
            'update_template' => 'update_template',
            'toggle_template_status' => 'toggle_template_status',
            'delete_template' => 'delete_template',
            'get_active_templates' => 'get_active_templates',
            'get_template_fields' => 'get_template_fields',
            'test_api_connection' => 'test_api_connection',
            'sync_all_templates' => 'sync_all_templates'
        ];

        foreach ($template_actions as $hook => $method) {
            add_action("wp_ajax_neo_job_board_{$hook}", [$instance, $method]);
        }

        // Jobs AJAX (только для авторизованных пользователей)
        $job_actions = [
            'get_applications' => 'get_applications',
            'get_application_details' => 'get_application_details',
            'delete_application' => 'delete_application',
            'update_responsible' => 'update_responsible',
            'update_application' => 'update_application',
            'toggle_application_status' => 'toggle_application_status',
            'delete_application_with_api' => 'delete_application_with_api',
            'check_templates_sync' => 'check_templates_sync'
        ];

        foreach ($job_actions as $hook => $method) {
            add_action("wp_ajax_neo_job_board_{$hook}", [$instance, $method]);
        }

        // Public AJAX (доступно для всех пользователей)
        add_action('wp_ajax_nopriv_neo_job_board_submit_application', [$instance, 'submit_application']);
        add_action('wp_ajax_neo_job_board_submit_application', [$instance, 'submit_application']);
        
        // Settings AJAX (только для администраторов)
        add_action('wp_ajax_neo_job_board_sync_users', [$instance, 'sync_users']);
        add_action('wp_ajax_neo_job_board_test_api_connection', [$instance, 'test_api_connection']);
        add_action('wp_ajax_neo_job_board_auto_register_admins', [$instance, 'auto_register_admins']);
        add_action('wp_ajax_neo_job_board_get_users_data', [$instance, 'get_users_data']);
        
        // Applications AJAX
        add_action('wp_ajax_neo_job_board_get_applications', [$instance, 'get_applications']);
        add_action('wp_ajax_neo_job_board_delete_application', [$instance, 'delete_application']);
        add_action('wp_ajax_neo_job_board_toggle_application_status', [$instance, 'toggle_application_status']);
        add_action('wp_ajax_neo_job_board_get_application_details', [$instance, 'get_application_details']);
    }

    /* ----------------- Templates ----------------- */
    
    public function get_templates() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');
            $result = $this->template_service->get_templates();
            wp_send_json_success($result['data']);
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'get_templates');
        }
    }

    public function get_template() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');
            $template_id = (int) ($_POST['template_id'] ?? 0);
            $result = $this->template_service->get_template($template_id);
            wp_send_json_success($result['data']);
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'get_template');
        }
    }

    public function save_template() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');
            
            $data = $this->prepare_template_data($_POST);
            
            $result = $this->template_service->save_template($data);
            error_log('Neo Job Board: Template saved successfully - ID: ' . ($result['data']['template_id'] ?? 'unknown'));
            wp_send_json_success($result['data']);
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'save_template');
        }
    }

    public function update_template() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');
            
            $template_id = (int) ($_POST['template_id'] ?? 0);
            $data = $this->prepare_template_data($_POST);
            $result = $this->template_service->update_template($template_id, $data);
            wp_send_json_success($result['data']);
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'update_template');
        }
    }

    public function toggle_template_status() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');
            
            $template_id = (int) ($_POST['template_id'] ?? 0);
            $status = (int) ($_POST['status'] ?? 0);
            $result = $this->template_service->toggle_status($template_id, $status);
            wp_send_json_success($result['data']);
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'toggle_template_status');
        }
    }

    public function delete_template() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');
            
            $template_id = (int) ($_POST['template_id'] ?? 0);
            $result = $this->template_service->delete_template($template_id);
            wp_send_json_success($result['data']);
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'delete_template');
        }
    }

    public function get_active_templates() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');
            $result = $this->template_service->get_active_templates();
            wp_send_json_success($result['data']);
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'get_active_templates');
        }
    }

    public function get_template_fields() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');
            
            $template_id = (int) ($_POST['template_id'] ?? 0);
            $result = $this->template_service->get_template_fields($template_id);
            wp_send_json_success($result['data']);
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'get_template_fields');
        }
    }


    public function sync_all_templates() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');
            $result = $this->template_service->sync_all_templates();
            wp_send_json_success($result['data']);
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'sync_all_templates');
        }
    }

    /* ----------------- Applications ----------------- */

    public function submit_application() {
        try {
            // Для публичного действия проверяем только nonce и rate limit
            SecurityValidator::verify_nonce();
            SecurityValidator::check_rate_limit('submit_application', 5, 300); // 5 запросов в 5 минут
            
            $template_id = (int) ($_POST['template_id'] ?? 0);
            $data = DataSanitizer::sanitize_recursive($_POST);
            
            // Убираем служебные поля
            unset($data['nonce'], $data['template_id']);
            
            $result = $this->application_service->submit_application($template_id, $data);
            error_log('Neo Job Board: Application submitted successfully - ID: ' . ($result['data']['application_id'] ?? 'unknown'));
            wp_send_json_success($result['data']);
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'submit_application');
        }
    }

    public function check_templates_sync() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');
            
            $templates = Templates::get_templates();
            $sync_status = [];
            
            foreach ($templates as $template) {
                $exists_on_site = $this->api_client->check_template_exists($template->id);
                
                $sync_status[$template->id] = [
                    'id' => $template->id,
                    'name' => $template->name,
                    'exists_on_site' => $exists_on_site,
                    'status' => $exists_on_site ? 'synchronized' : 'not_synchronized'
                ];
            }
            
            wp_send_json_success($sync_status);
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'check_templates_sync');
        }
    }

    /**
     * Подготовка данных шаблона из $_POST
     */
    private function prepare_template_data($post_data) {
        $data = [
            'name' => sanitize_text_field($post_data['name'] ?? ''),
            'description' => sanitize_textarea_field($post_data['description'] ?? ''),
            'is_active' => (int) ($post_data['is_active'] ?? 1)
        ];

        // Обрабатываем поля - ищем все поля с паттерном field_*_*
        $fields_array = [];
        
        // Сначала пробуем получить количество полей из fields_count
        $fields_count = (int) ($post_data['fields_count'] ?? 0);
        
        if ($fields_count > 0) {
            // Если есть fields_count, используем его
            for ($i = 0; $i < $fields_count; $i++) {
                $field = $this->extract_field_data($post_data, $i);
                if ($field && !empty($field['label'])) {
                    $fields_array[] = $field;
                }
            }
        } else {
            // Если нет fields_count, ищем все поля вручную
            foreach ($post_data as $key => $value) {
                if (preg_match('/^field_(\d+)_type$/', $key, $matches)) {
                    $field_index = (int) $matches[1];
                    $field = $this->extract_field_data($post_data, $field_index);
                    if ($field && !empty($field['label'])) {
                        $fields_array[] = $field;
                    }
                }
            }
        }
        
        if (empty($fields_array)) {
            wp_send_json_error('Добавьте хотя бы одно поле');
        }
        
        $data['fields'] = wp_json_encode($fields_array);
        
        return $data;
    }
    
    /**
     * Извлечение данных поля по индексу
     */
    private function extract_field_data($post_data, $index) {
        $field = [
            'type' => sanitize_text_field($post_data["field_{$index}_type"] ?? ''),
            'label' => sanitize_text_field($post_data["field_{$index}_label"] ?? ''),
            'required' => !empty($post_data["field_{$index}_required"]),
            'is_personal' => !empty($post_data["field_{$index}_personal_data"]),
            'options' => sanitize_textarea_field($post_data["field_{$index}_options"] ?? '')
        ];
        
        return $field;
    }
    
    /* ----------------- Settings ----------------- */
    
    /**
     * Синхронизация пользователей с API
     */
    public function sync_users() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');
            
            $settings = Settings::get_settings();
            $allowed_users = $settings['allowed_users'] ?? [];
            
            if (empty($allowed_users)) {
                wp_send_json_error('Нет выбранных пользователей для синхронизации');
            }
            
            // Получаем данные пользователей
            $users_data = [];
            foreach ($allowed_users as $user_id) {
                $user = get_user_by('id', $user_id);
                if ($user) {
                    $users_data[] = [
                        'id' => $user->ID,
                        'username' => $user->user_login,
                        'email' => $user->user_email,
                        'display_name' => $user->display_name,
                        'role' => $user->roles[0] ?? 'subscriber'
                    ];
                }
            }
            
            if (empty($users_data)) {
                wp_send_json_error('Нет валидных администраторов для синхронизации');
            }
            
            // Отправляем данные в API
            $api_response = $this->api_client->sync_users($users_data);
            
            if ($api_response['success']) {
                // API ID теперь совпадает с WordPress ID, поэтому не нужно сохранять отдельно
                
                // Обновляем статус синхронизации
                $settings['user_access_synced'] = true;
                update_option('neo_job_board_settings', $settings);

                wp_send_json_success([
                    'message' => $api_response['message'],
                    'users_count' => $api_response['success_count'],
                    'error_count' => $api_response['error_count'],
                    'results' => $api_response['results']
                ]);
            } else {
                wp_send_json_error([
                    'message' => $api_response['message'],
                    'error_count' => $api_response['error_count'],
                    'results' => $api_response['results']
                ]);
            }
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'sync_users');
        }
    }

    /**
     * Тестирование соединения с API
     */
    public function test_api_connection() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');

            $settings = Settings::get_settings();
            $api_url = $settings['api_url'] ?? '';
            $api_key = $settings['api_key'] ?? '';

            if (empty($api_url)) {
                wp_send_json_error('API URL не настроен');
            }

            // Тестируем соединение через APIClientV2
            $response = $this->api_client->test_connection();

            if ($response['success']) {
                wp_send_json_success([
                    'message' => 'Соединение с API успешно установлено',
                    'api_url' => $api_url,
                    'response_time' => $response['response_time'] ?? 'N/A'
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Ошибка соединения с API: ' . ($response['error'] ?? 'Неизвестная ошибка'),
                    'api_url' => $api_url
                ]);
            }

        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'test_api_connection');
        }
    }

    /**
     * Автоматическая регистрация всех администраторов при установке нового URL
     */
    public function auto_register_admins() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');

            // Получаем всех администраторов
            $admins = get_users(['role' => 'administrator']);
            
            if (empty($admins)) {
                wp_send_json_error('Нет администраторов для регистрации');
            }

            // Подготавливаем данные администраторов
            $users_data = [];
            foreach ($admins as $admin) {
                $users_data[] = [
                    'id' => $admin->ID,
                    'username' => $admin->user_login,
                    'email' => $admin->user_email,
                    'display_name' => $admin->display_name,
                    'role' => 'administrator'
                ];
            }

            // Отправляем данные в API
            $api_response = $this->api_client->sync_users($users_data);

            if ($api_response['success']) {
                // Обновляем настройки - добавляем всех администраторов в allowed_users
                $settings = Settings::get_settings();
                $admin_ids = array_column($users_data, 'id');
                $settings['allowed_users'] = $admin_ids;
                $settings['user_access_synced'] = true;
                update_option('neo_job_board_settings', $settings);

                wp_send_json_success([
                    'message' => 'Все администраторы успешно зарегистрированы в API',
                    'users_count' => $api_response['success_count'],
                    'error_count' => $api_response['error_count'],
                    'admin_ids' => $admin_ids
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Ошибка регистрации администраторов: ' . $api_response['message'],
                    'error_count' => $api_response['error_count'],
                    'results' => $api_response['results']
                ]);
            }

        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'auto_register_admins');
        }
    }

    /**
     * Получение данных пользователей по ID
     */
    public function get_users_data() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');

            $user_ids = array_map('intval', explode(',', $_POST['user_ids'] ?? ''));
            
            if (empty($user_ids)) {
                wp_send_json_error('Нет ID пользователей');
            }

            $users_data = [];
            foreach ($user_ids as $user_id) {
                $user = get_user_by('id', $user_id);
                if ($user) {
                    $users_data[] = [
                        'ID' => $user->ID,
                        'user_login' => $user->user_login,
                        'user_email' => $user->user_email,
                        'display_name' => $user->display_name,
                        'roles' => $user->roles
                    ];
                }
            }

            wp_send_json_success($users_data);

        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'get_users_data');
        }
    }
    
    /* ----------------- Applications ----------------- */
    
    /**
     * Получение списка заявок
     */
    public function get_applications() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');
            
            global $wpdb;
            $applications_table = $wpdb->prefix . 'neo_job_board_applications';
            $templates_table = $wpdb->prefix . 'neo_job_board_templates';
            
            $applications = $wpdb->get_results("
                SELECT a.*, t.name as template_name 
                FROM $applications_table a 
                LEFT JOIN $templates_table t ON a.template_id = t.id 
                ORDER BY a.created_at DESC
            ");
            
            wp_send_json_success($applications);
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'get_applications');
        }
    }
    
    /**
     * Удаление заявки
     */
    public function delete_application() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');
            
            $application_id = intval($_POST['application_id'] ?? 0);
            $hash_id = sanitize_text_field($_POST['hash_id'] ?? '');
            
            if (!$application_id || !$hash_id) {
                wp_send_json_error('Ungültige Bewerbungsparameter');
            }
            
            // Удаляем из базы данных
            global $wpdb;
            $applications_table = $wpdb->prefix . 'neo_job_board_applications';
            
            $deleted = $wpdb->delete(
                $applications_table,
                ['id' => $application_id, 'hash_id' => $hash_id],
                ['%d', '%s']
            );
            
            if ($deleted) {
                // Удаляем из API
                $this->api_client->delete_application($hash_id);
                wp_send_json_success('Bewerbung erfolgreich gelöscht');
            } else {
                wp_send_json_error('Fehler beim Löschen der Bewerbung');
            }
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'delete_application');
        }
    }
    
    /**
     * Переключение статуса заявки
     */
    public function toggle_application_status() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');
            
            $application_id = intval($_POST['application_id'] ?? 0);
            $hash_id = sanitize_text_field($_POST['hash_id'] ?? '');
            $is_active = $_POST['is_active'] === 'true';
            
            if (!$application_id || !$hash_id) {
                wp_send_json_error('Ungültige Bewerbungsparameter');
            }
            
            // Обновляем в базе данных
            global $wpdb;
            $applications_table = $wpdb->prefix . 'neo_job_board_applications';
            
            $updated = $wpdb->update(
                $applications_table,
                ['is_active' => $is_active ? 1 : 0],
                ['id' => $application_id, 'hash_id' => $hash_id],
                ['%d'],
                ['%d', '%s']
            );
            
            if ($updated !== false) {
                // Обновляем в API
                $this->api_client->update_application_status($hash_id, $is_active);
                wp_send_json_success('Status der Bewerbung aktualisiert');
            } else {
                wp_send_json_error('Fehler beim Aktualisieren des Status');
            }
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'toggle_application_status');
        }
    }
    
    /**
     * Получение деталей заявки
     */
    public function get_application_details() {
        try {
            SecurityValidator::verify_ajax_security(true, 'manage_options');
            
            $application_id = intval($_POST['application_id'] ?? 0);
            $hash_id = sanitize_text_field($_POST['hash_id'] ?? '');
            
            if (!$application_id || !$hash_id) {
                wp_send_json_error('Ungültige Bewerbungsparameter');
            }
            
            $details = Jobs::get_application_details($application_id, $hash_id);
            
            if ($details) {
                wp_send_json_success($details);
            } else {
                wp_send_json_error('Bewerbung nicht gefunden');
            }
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'get_application_details');
        }
    }
}

AJAXV2::init();
