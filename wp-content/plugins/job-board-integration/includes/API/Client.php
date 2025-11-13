<?php

namespace NeoJobBoard\API;

if (!defined('ABSPATH')) {
    exit;
}

class Client {
    
    public static function send_template($template_id) {
        global $wpdb;
        $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_templates WHERE id = %d", $template_id));

        if (!$template) {
            self::log('template', $template_id, 'error', 'Template nicht gefunden');
            return ['success' => false, 'message' => 'Template nicht gefunden'];
        }

        $api_url = get_option('jbi_api_url');
        $api_key = get_option('jbi_api_key');

        if (empty($api_url) || empty($api_key)) {
            self::log('template', $template_id, 'error', 'API nicht konfiguriert');
            return ['success' => false, 'message' => 'API nicht konfiguriert'];
        }

        $fields = json_decode($template->fields, true);
        
        if (empty($fields) || !is_array($fields)) {
            self::log('template', $template_id, 'error', 'Keine Felder im Template');
            return ['success' => false, 'message' => 'Template hat keine Felder'];
        }
        
        $formatted_data = self::format_template_for_api($fields);
        
        $payload = [
            'template_id' => $template_id,
            'template_name' => $template->name,
            'fields' => $formatted_data['fields'],
            'filterable_fields' => $formatted_data['filterable_fields']
        ];

        error_log('JBI Template payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));
        
        $endpoint = rtrim($api_url, '/') . '/templates/receive';
        
        $response = wp_remote_post($endpoint, [
            'headers' => [
                'X-API-Key' => $api_key,
                'Content-Type' => 'application/json; charset=utf-8'
            ],
            'body' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            self::log('template', $template_id, 'error', $response->get_error_message());
            return ['success' => false, 'message' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($code === 200 || $code === 201) {
            self::log('template', $template_id, 'success', 'Erfolgreich gesendet', $body);
            return ['success' => true, 'message' => 'Template erfolgreich gesendet an ' . $endpoint];
        } else {
            self::log('template', $template_id, 'error', 'Fehler ' . $code, $body);
            return ['success' => false, 'message' => 'Fehler: ' . $code . ' - URL: ' . $endpoint . ' - Response: ' . substr($body, 0, 100)];
        }
    }

    public static function send_application($application_id, $action = 'create') {
        try {
            global $wpdb;
            $application = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_applications WHERE id = %d", $application_id));

            if (!$application) {
                self::log('application', $application_id, 'error', 'Bewerbung nicht gefunden');
                return ['success' => false, 'message' => 'Bewerbung nicht gefunden'];
            }

            $api_url = get_option('jbi_api_url');
            $api_key = get_option('jbi_api_key');

            if (empty($api_url) || empty($api_key)) {
                self::log('application', $application_id, 'error', 'API nicht konfiguriert');
                return ['success' => false, 'message' => 'API nicht konfiguriert'];
            }

            $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_templates WHERE id = %d", $application->template_id));
            if (!$template) {
                self::log('application', $application_id, 'error', 'Template nicht gefunden');
                return ['success' => false, 'message' => 'Template nicht gefunden'];
            }

            $template_fields = json_decode($template->fields, true);
            if (!is_array($template_fields)) {
                $template_fields = [];
            }
            
            $hash = substr($application->hash_id, 0, 8);
            $filled_data = self::prepare_application_data($application_id, $template_fields);

            $payload = [
                'template_id' => $application->template_id,
                'hash' => $hash,
                'filled_data' => $filled_data,
                'action' => $action,
                'is_active' => $application->is_active ?? 1
            ];

            if ($action === 'update_status') {
                $payload['is_active'] = $application->is_active;
            }

            error_log('JBI Application payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));

            $endpoint = rtrim($api_url, '/') . '/applications/receive';
            
            $response = wp_remote_post($endpoint, [
                'headers' => [
                    'X-API-Key' => $api_key,
                    'Content-Type' => 'application/json; charset=utf-8'
                ],
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'timeout' => 30,
                'sslverify' => false
            ]);

            if (is_wp_error($response)) {
                self::log('application', $application_id, 'error', $response->get_error_message());
                return ['success' => false, 'message' => $response->get_error_message()];
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($code === 200 || $code === 201) {
                self::log('application', $application_id, 'success', 'Erfolgreich gesendet', $body);
                return ['success' => true, 'message' => 'Bewerbung erfolgreich gesendet'];
            } else {
                self::log('application', $application_id, 'error', 'Fehler ' . $code, $body);
                return ['success' => false, 'message' => 'Fehler: ' . $code . ' - ' . substr($body, 0, 100)];
            }
        } catch (\Exception $e) {
            error_log('JBI Error in send_application: ' . $e->getMessage());
            error_log('JBI Error trace: ' . $e->getTraceAsString());
            self::log('application', $application_id, 'error', $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function send_application_update_preview($application_id, $new_fields_data) {
        try {
            global $wpdb;
            $application = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_applications WHERE id = %d", $application_id));

            if (!$application) {
                self::log('application', $application_id, 'error', 'Bewerbung nicht gefunden');
                return ['success' => false, 'message' => 'Bewerbung nicht gefunden'];
            }

            $api_url = get_option('jbi_api_url');
            $api_key = get_option('jbi_api_key');

            if (empty($api_url) || empty($api_key)) {
                self::log('application', $application_id, 'error', 'API nicht konfiguriert');
                return ['success' => false, 'message' => 'API nicht konfiguriert'];
            }

            $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_templates WHERE id = %d", $application->template_id));
            if (!$template) {
                self::log('application', $application_id, 'error', 'Template nicht gefunden');
                return ['success' => false, 'message' => 'Template nicht gefunden'];
            }

            $template_fields = json_decode($template->fields, true);
            if (!is_array($template_fields)) {
                $template_fields = [];
            }
            
            $hash = substr($application->hash_id, 0, 8);
            
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
            
            $filled_data = [];
            foreach ($new_fields_data as $field_name => $field_value) {
                $field_name_lower = strtolower(trim($field_name));
                
                $is_personal = false;
                if (isset($private_field_map[$field_name_lower])) {
                    $is_personal = $private_field_map[$field_name_lower];
                }
                
                if (!$is_personal) {
                    $filled_data[$field_name] = is_array($field_value) ? $field_value : $field_value;
                }
            }

            $payload = [
                'template_id' => $application->template_id,
                'hash' => $hash,
                'filled_data' => $filled_data,
                'action' => 'update',
                'is_active' => $application->is_active ?? 1
            ];

            error_log('JBI Application update preview payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));

            $endpoint = rtrim($api_url, '/') . '/applications/receive';
            
            $response = wp_remote_post($endpoint, [
                'headers' => [
                    'X-API-Key' => $api_key,
                    'Content-Type' => 'application/json; charset=utf-8'
                ],
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'timeout' => 30,
                'sslverify' => false
            ]);

            if (is_wp_error($response)) {
                self::log('application', $application_id, 'error', $response->get_error_message());
                return ['success' => false, 'message' => $response->get_error_message()];
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($code === 200 || $code === 201) {
                self::log('application', $application_id, 'success', 'Update erfolgreich gesendet', $body);
                return ['success' => true, 'message' => 'Bewerbung erfolgreich aktualisiert'];
            } else {
                self::log('application', $application_id, 'error', 'Fehler ' . $code, $body);
                return ['success' => false, 'message' => 'Fehler: ' . $code . ' - ' . substr($body, 0, 100)];
            }
        } catch (\Exception $e) {
            error_log('JBI Error in send_application_update_preview: ' . $e->getMessage());
            error_log('JBI Error trace: ' . $e->getTraceAsString());
            self::log('application', $application_id, 'error', $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function send_application_status_change($application_id, $new_is_active) {
        try {
            global $wpdb;
            $application = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_applications WHERE id = %d", $application_id));

            if (!$application) {
                self::log('application', $application_id, 'error', 'Bewerbung nicht gefunden');
                return ['success' => false, 'message' => 'Bewerbung nicht gefunden'];
            }

            $api_url = get_option('jbi_api_url');
            $api_key = get_option('jbi_api_key');

            if (empty($api_url) || empty($api_key)) {
                self::log('application', $application_id, 'error', 'API nicht konfiguriert');
                return ['success' => false, 'message' => 'API nicht konfiguriert'];
            }

            $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_templates WHERE id = %d", $application->template_id));
            if (!$template) {
                self::log('application', $application_id, 'error', 'Template nicht gefunden');
                return ['success' => false, 'message' => 'Template nicht gefunden'];
            }

            $template_fields = json_decode($template->fields, true);
            if (!is_array($template_fields)) {
                $template_fields = [];
            }
            
            $hash = substr($application->hash_id, 0, 8);
            $filled_data = self::prepare_application_data($application_id, $template_fields);

            $payload = [
                'template_id' => $application->template_id,
                'hash' => $hash,
                'filled_data' => $filled_data,
                'action' => 'update_status',
                'is_active' => $new_is_active
            ];

            error_log('JBI Application status change payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));

            $endpoint = rtrim($api_url, '/') . '/applications/receive';
            
            $response = wp_remote_post($endpoint, [
                'headers' => [
                    'X-API-Key' => $api_key,
                    'Content-Type' => 'application/json; charset=utf-8'
                ],
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'timeout' => 30,
                'sslverify' => false
            ]);

            if (is_wp_error($response)) {
                self::log('application', $application_id, 'error', $response->get_error_message());
                return ['success' => false, 'message' => $response->get_error_message()];
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($code === 200 || $code === 201) {
                self::log('application', $application_id, 'success', 'Statusänderung erfolgreich gesendet', $body);
                return ['success' => true, 'message' => 'Status erfolgreich geändert'];
            } else {
                self::log('application', $application_id, 'error', 'Fehler ' . $code, $body);
                return ['success' => false, 'message' => 'Fehler: ' . $code . ' - ' . substr($body, 0, 100)];
            }
        } catch (\Exception $e) {
            error_log('JBI Error in send_application_status_change: ' . $e->getMessage());
            error_log('JBI Error trace: ' . $e->getTraceAsString());
            self::log('application', $application_id, 'error', $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function send_application_delete($application_id, $hash_id) {
        try {
            $api_url = get_option('jbi_api_url');
            $api_key = get_option('jbi_api_key');

            if (empty($api_url) || empty($api_key)) {
                self::log('application', $application_id, 'error', 'API nicht konfiguriert');
                return ['success' => false, 'message' => 'API nicht konfiguriert'];
            }

            $hash = substr($hash_id, 0, 8);
            $payload = [
                'hash' => $hash,
                'action' => 'delete'
            ];

            error_log('JBI Application delete payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));

            $endpoint = rtrim($api_url, '/') . '/applications/receive';
            
            $response = wp_remote_post($endpoint, [
                'headers' => [
                    'X-API-Key' => $api_key,
                    'Content-Type' => 'application/json; charset=utf-8'
                ],
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'timeout' => 30,
                'sslverify' => false
            ]);

            if (is_wp_error($response)) {
                self::log('application', $application_id, 'error', $response->get_error_message());
                return ['success' => false, 'message' => $response->get_error_message()];
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($code === 200 || $code === 201 || $code === 204) {
                self::log('application', $application_id, 'success', 'Erfolgreich gelöscht', $body);
                return ['success' => true, 'message' => 'Bewerbung erfolgreich gelöscht'];
            } else {
                self::log('application', $application_id, 'error', 'Fehler ' . $code, $body);
                return ['success' => false, 'message' => 'Fehler: ' . $code . ' - ' . substr($body, 0, 100)];
            }
        } catch (\Exception $e) {
            error_log('JBI Error in send_application_delete: ' . $e->getMessage());
            self::log('application', $application_id, 'error', $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private static function format_template_for_api($fields) {
        $formatted_fields = [];
        $filterable_fields = [];
        
        foreach ($fields as $index => $field) {
            $field_name = $field['label'] ?? '';
            $field_type = $field['type'] ?? 'text';
            
            $formatted_fields[] = [
                'field_id' => 'field_' . ($index + 1),
                'name' => $field_name,
                'type' => $field_type,
                'label' => $field_name,
                'required' => $field['required'] ?? false,
                'is_personal_data' => $field['personal_data'] ?? false,
                'is_system_field' => false
            ];
            
            if (!empty($field['filterable'])) {
                $filterable_fields[] = $field_name;
            }
        }
        
        return [
            'fields' => $formatted_fields,
            'filterable_fields' => $filterable_fields
        ];
    }

    private static function prepare_application_data($application_id, $template_fields) {
        global $wpdb;
        
        $application_data = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}neo_job_board_application_data WHERE application_id = %d", $application_id),
            ARRAY_A
        );

        $data = [];
        foreach ($application_data as $row) {
            $field_name = $row['field_name'] ?? '';
            $field_value = $row['field_value'] ?? '';
            $is_personal = intval($row['is_personal'] ?? 0);
            
            if ($is_personal === 1) {
                continue;
            }
            
            $decoded = json_decode($field_value, true);
            if ($decoded !== null && is_array($decoded)) {
                $data[$field_name] = $decoded;
            } else {
                $data[$field_name] = $field_value;
            }
        }

        return $data;
    }

    private static function log($type, $entity_id, $status, $message, $response_data = null) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'neo_job_board_api_logs',
            [
                'template_id' => $entity_id,
                'action' => $type,
                'endpoint' => get_option('jbi_api_url'),
                'method' => 'POST',
                'request_data' => $message,
                'response_data' => $response_data,
                'status_code' => 0,
                'success' => $status === 'success' ? 1 : 0,
                'error_message' => $status === 'error' ? $message : null
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s']
        );
    }

    public static function test_connection($api_url, $api_key) {
        if (empty($api_url)) {
            return ['success' => false, 'message' => 'Bitte geben Sie die API URL ein'];
        }

        if (empty($api_key)) {
            return ['success' => false, 'message' => 'Bitte geben Sie den API Key ein'];
        }

        if (!filter_var($api_url, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => 'Ungültige URL. Format: https://site.com/wp-json/bewerberboerse/v1'];
        }

        $parsed_url = parse_url($api_url);
        if (isset($parsed_url['host']) && ($parsed_url['host'] === 'localhost' || $parsed_url['host'] === '127.0.0.1')) {
            $server_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $clean_host = explode(':', $server_host)[0];
            
            $new_host = $clean_host;
            if (isset($parsed_url['port'])) {
                $new_host .= ':' . $parsed_url['port'];
            }
            
            $api_url = ($parsed_url['scheme'] ?? 'http') . '://' . $new_host . ($parsed_url['path'] ?? '');
        }

        $test_url = rtrim($api_url, '/') . '/templates';
        
        $args = [
            'headers' => [
                'X-API-Key' => $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'timeout' => 30,
            'sslverify' => false,
            'httpversion' => '1.1',
            'blocking' => true
        ];

        $response = wp_remote_get($test_url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $debug_info = [
                'url' => $test_url,
                'error' => $error_message,
                'has_curl' => function_exists('curl_version') ? 'yes' : 'no'
            ];
            
            return [
                'success' => false,
                'message' => 'Verbindung fehlgeschlagen: ' . $error_message,
                'debug' => $debug_info
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($code === 200) {
            return [
                'success' => true,
                'message' => 'Verbindung erfolgreich! API antwortet (200)',
                'url' => $test_url
            ];
        } elseif ($code === 401) {
            return [
                'success' => false,
                'message' => 'API Key ungültig (401). Prüfen Sie Ihren API Key.',
                'url' => $test_url
            ];
        } elseif ($code === 404) {
            return [
                'success' => false,
                'message' => 'Endpoint nicht gefunden (404). URL: ' . $test_url,
                'url' => $test_url
            ];
        } elseif (empty($code)) {
            return [
                'success' => false,
                'message' => 'Keine Antwort vom Server. Prüfen Sie die URL.',
                'url' => $test_url
            ];
        } else {
            return [
                'success' => false,
                'message' => 'API Fehler: HTTP ' . $code,
                'url' => $test_url,
                'body' => substr($body, 0, 200)
            ];
        }
    }
}

