<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

class PersonalDataManager {
    
    public static function get_personal_fields($template_id = null) {
        $personal_fields = PersonalDataFields::STANDARD_FIELDS;
        
        if ($template_id) {
            $template = Templates::get_template($template_id);
            if ($template && !empty($template->fields)) {
                $fields = json_decode($template->fields, true);
                if (is_array($fields)) {
                    foreach ($fields as $field) {
                        if (!empty($field['is_personal']) && !empty($field['name'])) {
                            $personal_fields[] = $field['name'];
                        }
                    }
                }
            }
        }
        
        return array_unique($personal_fields);
    }
    
    public static function filter_personal_data($data, $template_id = null) {
        $personal_fields = self::get_personal_fields($template_id);
        $filtered_data = [];
        
        foreach ($data as $key => $value) {
            if (!in_array($key, $personal_fields)) {
                $filtered_data[$key] = $value;
            }
        }
        
        return $filtered_data;
    }
    
    public static function extract_personal_data($data, $template_id = null) {
        $personal_fields = self::get_personal_fields($template_id);
        $personal_data = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $personal_fields)) {
                $personal_data[$key] = $value;
            }
        }
        
        return $personal_data;
    }
    
    public static function anonymize_data($data) {
        $anonymized = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, PersonalDataFields::STANDARD_FIELDS)) {
                $anonymized[$key] = self::anonymize_field($key, $value);
            } else {
                $anonymized[$key] = $value;
            }
        }
        
        return $anonymized;
    }
    
    private static function anonymize_field($field_name, $value) {
        switch ($field_name) {
            case 'email':
                return 'anonymized@example.com';
            case 'phone':
                return '***-***-****';
            case 'full_name':
            case 'first_name':
            case 'last_name':
                return 'Anonymous User';
            case 'address':
                return 'Anonymized Address';
            default:
                return '***';
        }
    }
    
    public static function pseudonymize_data($data) {
        $pseudonymized = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, PersonalDataFields::STANDARD_FIELDS)) {
                $pseudonymized[$key] = self::pseudonymize_field($key, $value);
            } else {
                $pseudonymized[$key] = $value;
            }
        }
        
        return $pseudonymized;
    }
    
    private static function pseudonymize_field($field_name, $value) {
        switch ($field_name) {
            case 'email':
                return 'user' . wp_generate_password(8, false) . '@example.com';
            case 'phone':
                return '+1-' . wp_rand(100, 999) . '-' . wp_rand(100, 999) . '-' . wp_rand(1000, 9999);
            case 'full_name':
            case 'first_name':
            case 'last_name':
                return 'User' . wp_rand(1000, 9999);
            case 'address':
                return wp_rand(100, 999) . ' Anonymized Street';
            default:
                return 'Pseudonymized_' . wp_rand(1000, 9999);
        }
    }
    
    public static function log_personal_data_access($action, $data_id, $user_id = null, $details = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'neo_job_board_personal_data_log';
        
        $log_data = [
            'action' => $action,
            'data_id' => $data_id,
            'user_id' => $user_id ?: get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details,
            'created_at' => current_time('mysql')
        ];
        
        $wpdb->insert($table_name, $log_data);
    }
    
    public static function export_personal_data($user_identifier) {
        global $wpdb;
        
        $applications_table = $wpdb->prefix . DatabaseConstants::APPLICATIONS_TABLE;
        
        $applications = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$applications_table} 
            WHERE application_data LIKE %s 
            OR first_name LIKE %s 
            OR last_name LIKE %s
        ", 
            '%' . $user_identifier . '%',
            '%' . $user_identifier . '%',
            '%' . $user_identifier . '%'
        ));
        
        $export_data = [];
        
        foreach ($applications as $application) {
            $app_data = json_decode($application->application_data, true);
            $personal_data = self::extract_personal_data($app_data, $application->template_id);
            
            $export_data[] = [
                'application_id' => $application->id,
                'hash_id' => $application->hash_id,
                'created_at' => $application->created_at,
                'personal_data' => $personal_data
            ];
        }
        
        return $export_data;
    }
    
    public static function delete_personal_data($user_identifier) {
        global $wpdb;
        
        $applications_table = $wpdb->prefix . DatabaseConstants::APPLICATIONS_TABLE;
        
        $applications = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$applications_table} 
            WHERE application_data LIKE %s 
            OR first_name LIKE %s 
            OR last_name LIKE %s
        ", 
            '%' . $user_identifier . '%',
            '%' . $user_identifier . '%',
            '%' . $user_identifier . '%'
        ));
        
        $deleted_count = 0;
        
        foreach ($applications as $application) {
            $app_data = json_decode($application->application_data, true);
            $anonymized_data = self::anonymize_data($app_data);
            
            $wpdb->update(
                $applications_table,
                [
                    'application_data' => json_encode($anonymized_data),
                    'first_name' => 'Anonymous',
                    'last_name' => 'User'
                ],
                ['id' => $application->id]
            );
            
            $deleted_count++;
            
            self::log_personal_data_access('delete', $application->id, null, 'GDPR deletion request');
        }
        
        return $deleted_count;
    }
    
    public static function contains_personal_data($data) {
        $personal_fields = PersonalDataFields::STANDARD_FIELDS;
        
        foreach ($personal_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function create_personal_data_log_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'neo_job_board_personal_data_log';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            action varchar(50) NOT NULL,
            data_id int(11) NOT NULL,
            user_id int(11) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            details text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY action (action),
            KEY data_id (data_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
