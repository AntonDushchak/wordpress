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
    
    /**
     * Получение всех шаблонов
     */
    public function get_templates() {
        try {
            $templates = SettingsCache::get_templates();
            return ['success' => true, 'data' => $templates];
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'get_templates');
        }
    }
    
    /**
     * Получение шаблона по ID
     */
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
    
    /**
     * Сохранение нового шаблона
     */
    public function save_template($data) {
        try {
            $sanitized_data = DataSanitizer::sanitize_template_data($data);
            
            // Валидация обязательных полей
            $required_fields = ['name'];
            $validation_errors = DataSanitizer::validate_required_fields($sanitized_data, $required_fields);
            
            if (!empty($validation_errors)) {
                ErrorHandler::handle_validation_error($validation_errors, 'save_template');
            }
            
            // Валидация полей шаблона
            if (empty($sanitized_data['fields']) || !is_array($sanitized_data['fields'])) {
                wp_send_json_error('Добавьте хотя бы одно поле');
            }
            
            // Конвертируем поля в JSON для сохранения в БД
            $sanitized_data['fields'] = wp_json_encode($sanitized_data['fields']);
            
            // Сохранение в БД
            $result = Templates::save_template($sanitized_data);
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }
            
            // Отправка в API
            $api_result = $this->api_client->send_template($sanitized_data, $result, false);
            
            // Очистка кеша
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
    
    /**
     * Обновление шаблона
     */
    public function update_template($template_id, $data) {
        try {
            $template_id = SecurityValidator::validate_id($template_id, 'template_id');
            
            // Проверяем существование шаблона
            SecurityValidator::verify_record_exists('neo_job_board_templates', $template_id);
            
            $sanitized_data = DataSanitizer::sanitize_template_data($data);
            
            // Валидация обязательных полей
            $required_fields = ['name'];
            $validation_errors = DataSanitizer::validate_required_fields($sanitized_data, $required_fields);
            
            if (!empty($validation_errors)) {
                ErrorHandler::handle_validation_error($validation_errors, 'update_template');
            }
            
            // Обновление в БД
            $result = Templates::update_template($template_id, $sanitized_data);
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }
            
            // Отправка в API
            $api_result = $this->api_client->send_template($sanitized_data, $template_id, true);
            
            // Очистка кеша
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
    
    /**
     * Переключение статуса шаблона
     */
    public function toggle_status($template_id, $status) {
        try {
            $template_id = SecurityValidator::validate_id($template_id, 'template_id');
            $status = (int) $status;
            
            // Проверяем существование шаблона
            SecurityValidator::verify_record_exists('neo_job_board_templates', $template_id);
            
            $result = Templates::toggle_status($template_id, $status);
            if (!$result) {
                wp_send_json_error('Ошибка изменения статуса');
            }
            
            // Очистка кеша
            SettingsCache::clear_templates_cache();
            
            return [
                'success' => true,
                'data' => ['message' => 'Статус изменен']
            ];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'toggle_status');
        }
    }
    
    /**
     * Удаление шаблона
     */
    public function delete_template($template_id) {
        try {
            $template_id = SecurityValidator::validate_id($template_id, 'template_id');
            
            // Проверяем существование шаблона
            SecurityValidator::verify_record_exists('neo_job_board_templates', $template_id);
            
            $result = Templates::delete_template($template_id);
            if (!$result) {
                wp_send_json_error('Ошибка удаления шаблона');
            }
            
            // Очистка кеша
            SettingsCache::clear_templates_cache();
            
            return [
                'success' => true,
                'data' => ['message' => 'Шаблон удален']
            ];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'delete_template');
        }
    }
    
    /**
     * Получение активных шаблонов
     */
    public function get_active_templates() {
        try {
            $templates = SettingsCache::get_active_templates();
            return ['success' => true, 'data' => $templates];
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'get_active_templates');
        }
    }
    
    /**
     * Получение полей шаблона
     */
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
    
    /**
     * Тестирование соединения с API
     */
    public function test_api_connection() {
        try {
            $result = $this->api_client->test_connection();
            
            if (is_wp_error($result)) {
                wp_send_json_error('Ошибка соединения: ' . $result->get_error_message());
            }
            
            return [
                'success' => true,
                'data' => ['message' => 'Соединение с API успешно']
            ];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'test_api_connection');
        }
    }
    
    /**
     * Синхронизация всех шаблонов
     */
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
