<?php

namespace NeoJobBoard\Services;

if (!defined('ABSPATH')) {
    exit;
}

use NeoJobBoard\Jobs;
use NeoJobBoard\Templates;
use NeoJobBoard\APIClientV2;
use NeoJobBoard\DataSanitizer;
use NeoJobBoard\SecurityValidator;
use NeoJobBoard\ErrorHandler;
use NeoJobBoard\PersonalDataManager;
use NeoJobBoard\SettingsCache;
use NeoJobBoard\ApplicationConstants;
use NeoJobBoard\DatabaseConstants;
use NeoJobBoard\DatabaseException;

class ApplicationService {
    
    private $api_client;
    
    public function __construct() {
        $this->api_client = new APIClientV2();
    }
    
    public function get_applications() {
        try {
            $applications = SettingsCache::get_applications();
            return ['success' => true, 'data' => $applications];
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'get_applications');
        }
    }
    
    public function get_application_details($application_id, $hash_id) {
        try {
            $application_id = SecurityValidator::validate_id($application_id, 'application_id');
            $hash_id = SecurityValidator::validate_hash($hash_id, 'hash_id');
            
            $details = Jobs::get_application_details($application_id, $hash_id);
            if (!$details) {
                wp_send_json_error('Заявка не найдена');
            }
            
            return ['success' => true, 'data' => $details];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'get_application_details');
        }
    }
    
    public function delete_application($application_id, $hash_id) {
        try {
            $application_id = SecurityValidator::validate_id($application_id, 'application_id');
            $hash_id = SecurityValidator::validate_hash($hash_id, 'hash_id');
            
            $result = Jobs::delete_application($application_id, $hash_id);
            if (!$result) {
                wp_send_json_error('Ошибка удаления заявки');
            }
            
            SettingsCache::clear_applications_cache();
            
            return [
                'success' => true,
                'data' => ['message' => 'Заявка удалена']
            ];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'delete_application');
        }
    }
    
    public function update_responsible($application_id, $responsible) {
        try {
            $application_id = SecurityValidator::validate_id($application_id, 'application_id');
            $responsible = sanitize_text_field($responsible);
            
            $result = Jobs::update_responsible_employee($application_id, $responsible);
            if (!$result) {
                wp_send_json_error('Ошибка обновления ответственного сотрудника');
            }
            
            return [
                'success' => true,
                'data' => ['message' => 'Ответственный сотрудник обновлен']
            ];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'update_responsible');
        }
    }
    
    public function update_application($application_id, $hash_id, $data) {
        try {
            $application_id = SecurityValidator::validate_id($application_id, 'application_id');
            $hash_id = SecurityValidator::validate_hash($hash_id, 'hash_id');
            
            $application = Jobs::get_application_by_id($application_id);
            if (!$application) {
                wp_send_json_error('Заявка не найдена');
            }
            
            $sanitized_data = DataSanitizer::sanitize_application_data($data);
            
            $result = Jobs::update_application($application_id, $sanitized_data);
            if (!$result) {
                wp_send_json_error('Ошибка обновления заявки');
            }
            
            $personal_fields = PersonalDataManager::get_personal_fields($application->template_id);
            $api_data = PersonalDataManager::filter_personal_data($sanitized_data, $application->template_id);
            
            $api_result = $this->api_client->update_application($hash_id, $api_data);
            
            if (!$api_result['success']) {
                ErrorHandler::log_error(new \Exception($api_result['error']), 'API update error');
            }
            
            SettingsCache::clear_applications_cache();
            
            return [
                'success' => true,
                'data' => ['message' => 'Заявка обновлена']
            ];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'update_application');
        }
    }
    
    public function toggle_application_status($application_id, $hash_id) {
        try {
            $application_id = SecurityValidator::validate_id($application_id, 'application_id');
            $hash_id = SecurityValidator::validate_hash($hash_id, 'hash_id');
            
            $application = Jobs::get_application_by_id($application_id);
            if (!$application) {
                wp_send_json_error('Заявка не найдена');
            }
            
            $new_status = $application->status === ApplicationConstants::STATUS_ACTIVE 
                ? ApplicationConstants::STATUS_INACTIVE 
                : ApplicationConstants::STATUS_ACTIVE;
            
            $result = Jobs::update_application_status($application_id, $new_status);
            if (!$result) {
                wp_send_json_error('Ошибка изменения статуса заявки');
            }
            
            $api_result = $this->api_client->update_application_status($hash_id, $new_status === ApplicationConstants::STATUS_ACTIVE);
            
            if (!$api_result['success']) {
                ErrorHandler::log_error(new \Exception($api_result['error']), 'API status update error');
            }
            
            SettingsCache::clear_applications_cache();
            
            return [
                'success' => true,
                'data' => [
                    'message' => 'Статус заявки изменен',
                    'new_status' => $new_status
                ]
            ];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'toggle_application_status');
        }
    }
    
    public function delete_application_with_api($application_id, $hash_id) {
        try {
            $application_id = SecurityValidator::validate_id($application_id, 'application_id');
            $hash_id = SecurityValidator::validate_hash($hash_id, 'hash_id');
            
            $application = Jobs::get_application_by_id($application_id);
            if (!$application) {
                wp_send_json_error('Заявка не найдена');
            }
            
            $api_result = $this->api_client->delete_application($hash_id);
            
            if (!$api_result['success']) {
                ErrorHandler::log_error(new \Exception($api_result['error']), 'API delete error');
            }
            
            $result = Jobs::delete_application($application_id, $hash_id);
            if (!$result) {
                wp_send_json_error('Ошибка удаления заявки');
            }
            
            SettingsCache::clear_applications_cache();
            
            return [
                'success' => true,
                'data' => ['message' => 'Заявка удалена']
            ];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'delete_application_with_api');
        }
    }
    
    public function submit_application($template_id, $data) {
        try {
            $template_id = SecurityValidator::validate_id($template_id, 'template_id');
            
            $template = SettingsCache::get_template($template_id);
            if (!$template) {
                wp_send_json_error('Шаблон не найден');
            }
            
            $sanitized_data = DataSanitizer::sanitize_application_data($data);
            
            $required_fields = ['full_name'];
            $validation_errors = DataSanitizer::validate_required_fields($sanitized_data, $required_fields);
            
            if (!empty($validation_errors)) {
                ErrorHandler::handle_validation_error($validation_errors, 'submit_application');
            }
            
            $sanitized_data = $this->process_list_data($sanitized_data, $template_id);
            
            $save_result = $this->save_application($template_id, $sanitized_data);
            if (!$save_result || !isset($save_result['id'])) {
                wp_send_json_error('Ошибка сохранения заявки в базу данных');
            }
            
            $wordpress_app_id = $save_result['id'];
            $hash_id = $save_result['hash_id'];
            
            $personal_fields = PersonalDataManager::get_personal_fields($template_id);
            $api_data = PersonalDataManager::filter_personal_data($sanitized_data, $template_id);
            
            $api_payload = [
                'template_id' => $template_id,
                'wordpress_application_id' => $wordpress_app_id,
                'hash' => $hash_id,
                'filled_data' => $api_data
            ];
            
            $api_result = $this->api_client->send_application($api_payload);
            
            $this->send_admin_notification($sanitized_data, $wordpress_app_id);
            
            SettingsCache::clear_applications_cache();
            
            return [
                'success' => true,
                'data' => [
                    'message' => 'Заявка успешно отправлена' . ($api_result['success'] ? ' и передана в API' : ' (API недоступен)'),
                    'application_id' => $wordpress_app_id,
                    'api_sent' => $api_result['success'] ?? false
                ]
            ];
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, 'submit_application');
        }
    }
    
    private function save_application($template_id, $all_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . DatabaseConstants::APPLICATIONS_TABLE;
        
        $name_parts = explode(' ', trim($all_data['full_name']), 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
        
        $hash_id = $this->generate_short_hash();
        
        $template_creator = $this->get_template_creator($template_id);
        
        $insert_data = [
            'hash_id' => $hash_id,
            'template_id' => (int) $template_id,
            'first_name' => sanitize_text_field($first_name),
            'last_name' => sanitize_text_field($last_name),
            'responsible_employee' => $template_creator,
            'status' => ApplicationConstants::STATUS_NEW,
            'is_active' => 1,
            'application_data' => wp_json_encode($all_data),
            'created_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert($table_name, $insert_data);
        
        if ($result === false) {
            throw new DatabaseException('Ошибка сохранения заявки в БД: ' . $wpdb->last_error);
        }
        
        return [
            'id' => $wpdb->insert_id,
            'hash_id' => $hash_id
        ];
    }

    private function generate_short_hash() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . DatabaseConstants::APPLICATIONS_TABLE;
        
        for ($attempt = 0; $attempt < ApplicationConstants::HASH_MAX_ATTEMPTS; $attempt++) {
            $hash = '';
            for ($i = 0; $i < ApplicationConstants::HASH_LENGTH; $i++) {
                $hash .= ApplicationConstants::HASH_CHARACTERS[random_int(0, strlen(ApplicationConstants::HASH_CHARACTERS) - 1)];
            }
            
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE hash_id = %s",
                $hash
            ));
            
            if (!$exists) {
                return $hash;
            }
        }
        
        return strtoupper(substr(md5(uniqid((string)time(), true)), 0, ApplicationConstants::HASH_LENGTH));
    }
    
    private function get_template_creator($template_id) {
        global $wpdb;
        
        $templates_table = $wpdb->prefix . DatabaseConstants::TEMPLATES_TABLE;
        
        $template = $wpdb->get_row($wpdb->prepare("
            SELECT t.*, u.display_name, u.user_login 
            FROM {$templates_table} t
            LEFT JOIN {$wpdb->users} u ON t.created_by = u.ID
            WHERE t.id = %d
        ", $template_id));
        
        if (!$template) {
            return 'Неизвестный';
        }
        
        if ($template->created_by && $template->display_name) {
            return $template->display_name;
        }
        
        if ($template->created_by && $template->user_login) {
            return $template->user_login;
        }
        
        return $template->name;
    }
    
    private function process_list_data($data, $template_id) {
        $template = SettingsCache::get_template($template_id);
        if (!$template || empty($template->fields)) {
            return $data;
        }
        
        $fields = json_decode($template->fields, true);
        if (!is_array($fields)) {
            return $data;
        }
        
        foreach ($fields as $field) {
            if ($field['type'] === 'liste') {
                $field_label = $field['label'] ?? '';
                if ($field_label) {
                    $normalized_label = $this->normalize_german_chars($field_label);
                    
                    $field_name = null;
                    foreach ($data as $key => $value) {
                        if ($key === $field_label || $key === $normalized_label || 
                            strtolower($key) === strtolower($field_label) ||
                            strtolower($key) === strtolower($normalized_label)) {
                            $field_name = $key;
                            break;
                        }
                    }
                    
                    if ($field_name && isset($data[$field_name]) && is_string($data[$field_name])) {
                        $lines = array_filter(array_map('trim', explode("\n", $data[$field_name])));
                        $data[$field_name] = $lines;
                    }
                }
            }
        }
        
        return $data;
    }
    
    private function normalize_german_chars($text) {
        $german_chars = [
            'ä' => 'ae', 'Ä' => 'Ae',
            'ö' => 'oe', 'Ö' => 'Oe', 
            'ü' => 'ue', 'Ü' => 'Ue',
            'ß' => 'ss'
        ];
        
        return str_replace(array_keys($german_chars), array_values($german_chars), $text);
    }
    
    private function send_admin_notification($data, $app_id) {
        $admin_email = get_option('admin_email');
        $subject = 'Новая заявка #' . $app_id;
        $message = "Получена новая заявка с ID: $app_id\n\n";
        $message .= "Данные заявки:\n";
        $message .= print_r($data, true);
        
        wp_mail($admin_email, $subject, $message);
    }
}
