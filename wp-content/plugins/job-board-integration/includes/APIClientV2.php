<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

use NeoJobBoard\URLBuilder;
use NeoJobBoard\Settings;
use NeoJobBoard\ErrorHandler;
use NeoJobBoard\APIConstants;
use NeoJobBoard\HTTPStatus;
use NeoJobBoard\DataSanitizer;

class APIClientV2 {
    
    private $url_builder;
    private $settings;
    
    public function __construct() {
        $this->url_builder = new URLBuilder();
        $this->settings = Settings::get_settings();
    }
    
    private function make_request($endpoint, $method = 'POST', $data = [], $template_id = null) {
        try {
            $url = $this->url_builder->build_url($endpoint);
            $headers = $this->url_builder->get_headers();
            
            $args = [
                'method' => $method,
                'headers' => $headers,
                'timeout' => APIConstants::TIMEOUT,
                'body' => !empty($data) ? wp_json_encode($data) : null
            ];
            
            $this->log_request($template_id, $endpoint, $method, $data);
            
            $response = wp_remote_request($url, $args);
            
            if (is_wp_error($response)) {
                return ErrorHandler::handle_wp_error($response, "API Request to {$endpoint}");
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $decoded_body = json_decode($response_body, true);
            
            $this->log_response($template_id, $endpoint, $method, $data, $decoded_body, $response_code);
            
            if ($response_code >= 200 && $response_code < 300) {
                return [
                    'success' => true,
                    'data' => $decoded_body,
                    'code' => $response_code
                ];
            } else {
                $error_message = $decoded_body['message'] ?? $response_body;
                throw new \Exception("API Error {$response_code}: {$error_message}");
            }
            
        } catch (\Exception $e) {
            ErrorHandler::handle_ajax_error($e, "API Request to {$endpoint}");
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => HTTPStatus::SERVICE_UNAVAILABLE
            ];
        }
    }
    
    public function send_template($template_data, $template_id, $is_update = false) {
        if ($is_update) {
            $endpoint = "templates/{$template_id}";
            $method = 'PUT';
            $payload = $this->build_template_update_payload($template_data, $template_id);
        } else {
            $endpoint = 'templates';
            $method = 'POST';
            $payload = $this->build_template_create_payload($template_data, $template_id);
        }
        
        return $this->make_request($endpoint, $method, $payload, $template_id);
    }
    
    public function send_application($application_data) {
        $payload = $this->build_application_payload($application_data);
        
        return $this->make_request('applications', 'POST', $payload);
    }
    
    public function update_application($application_hash, $data) {
        $payload = [
            'filledData' => DataSanitizer::prepare_for_json($data),
            'hash' => $application_hash
        ];
        
        return $this->make_request("applications/{$application_hash}", 'PUT', $payload);
    }
    
    public function delete_application($application_hash) {
        return $this->make_request("applications/{$application_hash}", 'DELETE');
    }
    
    public function update_application_status($application_hash, $is_active) {
        $payload = [
            'isActive' => (bool) $is_active
        ];
        
        return $this->make_request("applications/{$application_hash}", 'PATCH', $payload);
    }
    
    
    public function sync_all_templates() {
        $templates = Templates::get_templates();
        $synced = 0;
        $errors = [];
        
        foreach ($templates as $template) {
            $template_data = [
                'name' => $template->name,
                'description' => $template->description,
                'fields' => $template->fields,
                'is_active' => $template->is_active
            ];
            
            $result = $this->send_template($template_data, $template->id, false);
            
            if ($result['success']) {
                $synced++;
            } else {
                $errors[] = "Шаблон ID {$template->id}: {$template->name} - {$result['error']}";
            }
        }
        
        return [
            'synced' => $synced,
            'total' => count($templates),
            'errors' => $errors
        ];
    }
    
    public function check_template_exists($template_id) {
        $result = $this->make_request("templates/{$template_id}", 'GET', [], $template_id);
        
        if (is_wp_error($result)) {
            return false;
        }
        
        return $result['success'] ?? false;
    }
    
    private function get_user_api_id($wp_user_id = null) {
        if (!$wp_user_id) {
            $current_user = wp_get_current_user();
            $wp_user_id = $current_user->ID;
        }

        return (string) $wp_user_id;
    }

    private function build_template_create_payload($template_data, $template_id) {
        $settings = Settings::get_settings();
        
        $fields = json_decode($template_data['fields'], true);
        $formatted_fields = [];
        
        if (is_array($fields)) {
            foreach ($fields as $field) {
                $formatted_fields[] = [
                    'field_id' => 'field_' . uniqid(),
                    'name' => self::normalize_german_chars($field['label'] ?? ''),
                    'type' => $field['type'] ?? 'text',
                    'label' => self::normalize_german_chars($field['label'] ?? ''),
                    'required' => (bool) ($field['required'] ?? false),
                    'is_personal_data' => (bool) ($field['is_personal'] ?? false),
                    'is_system_field' => false
                ];
            }
        }
        
        $user_api_id = $this->get_user_api_id();

        $payload = [
            'template_id' => $template_id,
            'template_name' => $template_data['name'] ?? '',
            'user_id' => $user_api_id,
            'api_key' => $settings['api_key'] ?? '',
            'fields' => $formatted_fields
        ];

        return $payload;
    }
    
    private function build_template_update_payload($template_data, $template_id) {
        $settings = Settings::get_settings();
        
        $fields = json_decode($template_data['fields'], true);
        $formatted_fields = [];
        
        if (is_array($fields)) {
            foreach ($fields as $field) {
                $formatted_fields[] = [
                    'field_id' => 'field_' . uniqid(),
                    'name' => self::normalize_german_chars($field['label'] ?? ''),
                    'type' => $field['type'] ?? 'text',
                    'label' => self::normalize_german_chars($field['label'] ?? ''),
                    'required' => (bool) ($field['required'] ?? false),
                    'is_personal_data' => (bool) ($field['is_personal'] ?? false),
                    'is_system_field' => false
                ];
            }
        }
        
        return [
            'templateName' => $template_data['name'] ?? '',
            'fields' => $formatted_fields,
            'isActive' => (bool) ($template_data['is_active'] ?? true)
        ];
    }
    
    private function build_application_payload($application_data) {
        $user_api_id = $this->get_user_api_id();
        
        return [
            'templateId' => $application_data['template_id'],
            'wordpressApplicationId' => $application_data['wordpress_application_id'],
            'hash' => $application_data['hash'],
            'userId' => $user_api_id,
            'filledData' => DataSanitizer::prepare_for_json($application_data['filled_data'])
        ];
    }
    
    private static function normalize_german_chars($text) {
        $german_chars = [
            'ä' => 'ae', 'Ä' => 'Ae',
            'ö' => 'oe', 'Ö' => 'Oe', 
            'ü' => 'ue', 'Ü' => 'Ue',
            'ß' => 'ss'
        ];
        
        return str_replace(array_keys($german_chars), array_values($german_chars), $text);
    }
    
    private function log_request($template_id, $endpoint, $method, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . DatabaseConstants::API_LOGS_TABLE;
        
        $log_data = [
            'template_id' => $template_id,
            'action' => $method . '_' . $endpoint,
            'endpoint' => $endpoint,
            'method' => $method,
            'request_data' => wp_json_encode($data),
            'response_data' => null,
            'status_code' => 0,
            'success' => 0,
            'error_message' => null,
            'created_at' => current_time('mysql')
        ];
        
        $wpdb->insert($table_name, $log_data);
    }
    
    private function log_response($template_id, $endpoint, $method, $request_data, $response_data, $status_code) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . DatabaseConstants::API_LOGS_TABLE;
        
        $success = $status_code >= 200 && $status_code < 300;
        
        $wpdb->update(
            $table_name,
            [
                'response_data' => wp_json_encode($response_data),
                'status_code' => $status_code,
                'success' => $success ? 1 : 0,
                'error_message' => $success ? null : ($response_data['message'] ?? 'Unknown error')
            ],
            [
                'template_id' => $template_id,
                'endpoint' => $endpoint,
                'method' => $method
            ],
            ['%s', '%d', '%d', '%s'],
            ['%d', '%s', '%s']
        );
    }
    
    public function delete_template($template_id) {
        $endpoint = "templates/{$template_id}";
        
        return $this->make_request($endpoint, 'DELETE', [], $template_id);
    }
    
    public function sync_users($users_data) {
        $results = [];
        $success_count = 0;
        $error_count = 0;

        foreach ($users_data as $user_data) {
            try {
                $result = $this->register_user($user_data);

                if ($result['success']) {
                    $success_count++;
                    $result['user_id'] = $user_data['id'];
                    $result['api_user_id'] = (string) $user_data['id'];
                } else {
                    $error_count++;
                    $result['user_id'] = $user_data['id'];
                }

                $results[] = $result;

            } catch (\Exception $e) {
                $error_count++;
                $results[] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'user' => $user_data['email']
                ];
            }
        }

        return [
            'success' => $error_count === 0,
            'message' => "Синхронизация завершена: {$success_count} успешно, {$error_count} ошибок",
            'results' => $results,
            'success_count' => $success_count,
            'error_count' => $error_count
        ];
    }
    
    private function register_user($user_data) {
        $settings = Settings::get_settings();
        
        $payload = [
            'wordpressUserId' => (int) $user_data['id'],
            'email' => $user_data['email'],
            'name' => $user_data['display_name'],
            'role' => $this->map_wordpress_role($user_data['role']),
            'apiKey' => $settings['api_key'] ?? ''
        ];

        $url = $this->url_builder->get_base_api_url() . '/auth/register';
        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => wp_json_encode($payload),
            'timeout' => APIConstants::TIMEOUT
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message()
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (wp_remote_retrieve_response_code($response) === 200 && $data['success']) {
            return [
                'success' => true,
                'data' => $data['user'],
                'message' => $data['message'] ?? 'User registered successfully'
            ];
        } else {
            return [
                'success' => false,
                'error' => $data['message'] ?? 'Registration failed',
                'data' => $data
            ];
        }
    }

    private function map_wordpress_role($wp_role) {
        $role_mapping = [
            'administrator' => 'ADMINISTRATOR',
            'neo_editor' => 'NEO_EDITOR',
            'neo_mitarbeiter' => 'NEO_MITARBEITER'
        ];

        return $role_mapping[$wp_role] ?? 'NEO_MITARBEITER';
    }
    
    private function get_user_by_email($email) {
        $url = $this->url_builder->for_users();
        $response = $this->make_request('users', 'GET');
        
        if ($response['success']) {
            $users = $response['data'] ?? [];
            foreach ($users as $user) {
                if ($user['email'] === $email) {
                    return $user;
                }
            }
        }
        
        return null;
    }
    
    private function create_user($user_data) {
        $payload = [
            'email' => $user_data['email'],
            'name' => $user_data['display_name'],
            'role' => $this->map_wp_role_to_api_role($user_data['role']),
            'isActive' => true
        ];
        
        return $this->make_request('users', 'POST', $payload);
    }
    
    private function update_user($user_id, $user_data) {
        $payload = [
            'name' => $user_data['display_name'],
            'role' => $this->map_wp_role_to_api_role($user_data['role']),
            'isActive' => true
        ];
        
        return $this->make_request("users/{$user_id}", 'PUT', $payload);
    }
    
    private function map_wp_role_to_api_role($wp_role) {
        $role_mapping = [
            'administrator' => 'ADMINISTRATOR',
            'editor' => 'NEO_EDITOR',
            'author' => 'NEO_MITARBEITER',
            'contributor' => 'NEO_MITARBEITER',
            'subscriber' => 'NEO_MITARBEITER'
        ];
        
        return $role_mapping[$wp_role] ?? 'NEO_MITARBEITER';
    }

    public function test_connection() {
        try {
            $start_time = microtime(true);
            $response = $this->make_request('test', 'POST', ['action' => 'test_connection']);
            $end_time = microtime(true);
            
            $response_time = round(($end_time - $start_time) * 1000, 2) . 'ms';
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Соединение с API успешно установлено',
                    'response_time' => $response_time,
                    'data' => $response['data'] ?? []
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Неизвестная ошибка',
                    'response_time' => $response_time
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
