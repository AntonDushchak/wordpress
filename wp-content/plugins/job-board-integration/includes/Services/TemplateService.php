<?php

namespace NeoJobBoard\Services;

if (!defined('ABSPATH')) {
    exit;
}

use NeoJobBoard\Templates;
use NeoJobBoard\APIClientV2;
use NeoJobBoard\DataSanitizer;
use NeoJobBoard\SecurityValidator;
use NeoJobBoard\ErrorHandler;
use NeoJobBoard\SettingsCache;

class TemplateService {
    
    private $api_client;
    
    public function __construct() {
        $this->api_client = new APIClientV2();
    }
    
    public function get_templates() {
        try {
            $templates = SettingsCache::get_templates();
            return ['success' => true, 'data' => $templates];
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'get_templates');
        }
    }
    
    public function get_template($template_id) {
        try {
            $template_id = SecurityValidator::validate_id($template_id, 'template_id');
            
            $template = SettingsCache::get_template($template_id);
            if (!$template) {
                wp_send_json_error('Шаблон не найден');
            }
            
            return ['success' => true, 'data' => $template];
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'get_template');
        }
    }
    
    public function save_template($data) {
        try {
            $sanitized_data = DataSanitizer::sanitize_template_data($data);
            
            $required_fields = ['name'];
            $validation_errors = DataSanitizer::validate_required_fields($sanitized_data, $required_fields);
            
            if (!empty($validation_errors)) {
                ErrorHandler::handle_validation_error($validation_errors, 'save_template');
            }
            
            if (empty($sanitized_data['fields']) || !is_array($sanitized_data['fields'])) {
                wp_send_json_error('Добавьте хотя бы одно поле');
            }
            
            $sanitized_data['fields'] = DataSanitizer::ensure_required_name_field($sanitized_data['fields']);
            
            $sanitized_data['fields'] = wp_json_encode($sanitized_data['fields']);
                
            $result = Templates::save_template($sanitized_data);
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }
            
            $api_result = $this->api_client->send_template($sanitized_data, $result, false);
            
            SettingsCache::clear_templates_cache();
            
            return [
                'success' => true,
                'data' => [
                    'template_id' => $result,
                    'api_sent' => $api_result['success'] ?? false,
                    'message' => 'Шаблон успешно создан' . ($api_result['success'] ? ' и отправлен на сервер' : ' (сервер недоступен)')
                ]
            ];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'save_template');
        }
    }
    
    public function update_template($template_id, $data) {
        try {
            $template_id = SecurityValidator::validate_id($template_id, 'template_id');
            
            SecurityValidator::verify_record_exists('neo_job_board_templates', $template_id);
            
            $sanitized_data = DataSanitizer::sanitize_template_data($data);
            
            $required_fields = ['name'];
            $validation_errors = DataSanitizer::validate_required_fields($sanitized_data, $required_fields);
            
            if (!empty($validation_errors)) {
                ErrorHandler::handle_validation_error($validation_errors, 'update_template');
            }
            
            if (empty($sanitized_data['fields']) || !is_array($sanitized_data['fields'])) {
                wp_send_json_error('Добавьте хотя бы одно поле');
            }
            
            $sanitized_data['fields'] = DataSanitizer::ensure_required_name_field($sanitized_data['fields']);
            
            $sanitized_data['fields'] = wp_json_encode($sanitized_data['fields']);
            
            $result = Templates::update_template($template_id, $sanitized_data);
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }
            
            $api_result = $this->api_client->send_template($sanitized_data, $template_id, true);
            
            SettingsCache::clear_templates_cache();
            
            return [
                'success' => true,
                'data' => [
                    'template_id' => $template_id,
                    'api_sent' => $api_result['success'] ?? false,
                    'message' => 'Шаблон успешно обновлен' . ($api_result['success'] ? ' и отправлен на сервер' : ' (сервер недоступен)')
                ]
            ];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'update_template');
        }
    }
    
    public function toggle_status($template_id, $status) {
        try {
            $template_id = SecurityValidator::validate_id($template_id, 'template_id');
            $status = (int) $status;
            
            SecurityValidator::verify_record_exists('neo_job_board_templates', $template_id);
            
            $result = Templates::toggle_status($template_id, $status);
            if (!$result) {
                wp_send_json_error('Ошибка изменения статуса');
            }
            
            SettingsCache::clear_templates_cache();
            
            return [
                'success' => true,
                'data' => ['message' => 'Статус изменен']
            ];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'toggle_status');
        }
    }
    
    public function delete_template($template_id) {
        try {
            $template_id = SecurityValidator::validate_id($template_id, 'template_id');
            
            SecurityValidator::verify_record_exists('neo_job_board_templates', $template_id);
            
            $result = Templates::delete_template($template_id);
            if (!$result) {
                wp_send_json_error('Ошибка удаления шаблона');
            }
            
            SettingsCache::clear_templates_cache();
            
            return [
                'success' => true,
                'data' => ['message' => 'Шаблон удален']
            ];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'delete_template');
        }
    }
    
    public function get_active_templates() {
        try {
            $templates = SettingsCache::get_active_templates();
            return ['success' => true, 'data' => $templates];
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'get_active_templates');
        }
    }
    
    public function get_template_fields($template_id) {
        try {
            $template_id = SecurityValidator::validate_id($template_id, 'template_id');
            
            $template = SettingsCache::get_template($template_id);
            if (!$template) {
                wp_send_json_error('Шаблон не найден');
            }
            
            $fields = json_decode($template->fields, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error('Ошибка структуры полей');
            }
            
            return ['success' => true, 'data' => $fields];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'get_template_fields');
        }
    }
    
    public function test_api_connection() {
        try {
            $result = $this->api_client->test_connection();
            
            if (!$result['success']) {
                wp_send_json_error('Ошибка соединения: ' . ($result['error'] ?? 'Неизвестная ошибка'));
            }
            
            return [
                'success' => true,
                'data' => ['message' => $result['message'] ?? 'Соединение с API успешно']
            ];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'test_api_connection');
        }
    }
    
    public function sync_all_templates() {
        try {
            $result = $this->api_client->sync_all_templates();
            
            $message = "Синхронизировано: {$result['synced']} из {$result['total']} шаблонов";
            if (!empty($result['errors'])) {
                $message .= ". Ошибки: " . implode(', ', $result['errors']);
            }
            
            return [
                'success' => true,
                'data' => [
                    'message' => $message,
                    'synced' => $result['synced'],
                    'total' => $result['total'],
                    'errors' => $result['errors']
                ]
            ];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'sync_all_templates');
        }
    }
}
