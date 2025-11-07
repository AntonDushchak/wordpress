<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

class DataSanitizer {
    
    public static function sanitize_recursive($data) {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $sanitized[sanitize_key($key)] = self::sanitize_recursive($value);
            }
            return $sanitized;
        }
        
        if (is_string($data)) {
            return sanitize_text_field($data);
        }
        
        return $data;
    }
    
    public static function sanitize_application_data($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $key = sanitize_key($key);
            
            if (is_array($value)) {
                $sanitized[$key] = self::sanitize_recursive($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = sanitize_textarea_field($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    public static function sanitize_template_data($data) {
        $sanitized = [];
        
        if (isset($data['name'])) {
            $sanitized['name'] = sanitize_text_field($data['name']);
        }
        
        if (isset($data['description'])) {
            $sanitized['description'] = sanitize_textarea_field($data['description']);
        }
        
        if (isset($data['is_active'])) {
            $sanitized['is_active'] = (int) $data['is_active'];
        }
        
        if (isset($data['fields'])) {
            if (is_array($data['fields'])) {
                $sanitized['fields'] = self::sanitize_template_fields($data['fields']);
            } elseif (is_string($data['fields'])) {
                $decoded_fields = json_decode($data['fields'], true);
                if (is_array($decoded_fields)) {
                    $sanitized['fields'] = self::sanitize_template_fields($decoded_fields);
                } else {
                    $sanitized['fields'] = $data['fields'];
                }
            } else {
                $sanitized['fields'] = $data['fields'];
            }
        }
        
        return $sanitized;
    }
    
    public static function sanitize_template_fields($fields) {
        $sanitized_fields = [];
        
        foreach ($fields as $field) {
            if (!is_array($field)) continue;
            
            $sanitized_field = [
                'type' => sanitize_text_field($field['type'] ?? 'text'),
                'label' => sanitize_text_field($field['label'] ?? ''),
                'required' => !empty($field['required']),
                'is_personal' => !empty($field['is_personal']),
                'options' => sanitize_textarea_field($field['options'] ?? '')
            ];
            
            if (isset($field['name'])) {
                $sanitized_field['name'] = sanitize_key($field['name']);
            }
            
            $sanitized_fields[] = $sanitized_field;
        }
        
        return $sanitized_fields;
    }
    
    public static function ensure_required_name_field($fields) {
        if (!is_array($fields)) {
            $fields = [];
        }
        
        $name_field_exists = false;
        $name_field_indicators = [
            'name',
            'Name',
            'Vorname',
            'Nachname',
            'Name (Vor- und Nachname)'
        ];
        
        foreach ($fields as $field) {
            if (!is_array($field)) continue;
            
            $field_name = sanitize_key($field['name'] ?? $field['field_name'] ?? '');
            $field_label = strtolower($field['label'] ?? '');
            
            if (in_array($field_name, $name_field_indicators, true)) {
                $name_field_exists = true;
                break;
            }
            
            foreach ($name_field_indicators as $indicator) {
                if (stripos($field_label, strtolower($indicator)) !== false || 
                    stripos($field_label, 'name') !== false) {
                    $name_field_exists = true;
                    break 2;
                }
            }
        }
        
        if (!$name_field_exists) {
            $name_field = [
                'type' => 'text',
                'label' => 'Name (Vor- und Nachname)',
                'required' => true,
                'is_personal' => true,
                'name' => 'name',
                'field_name' => 'name',
                'personal_data' => true,
                'options' => ''
            ];
            
            array_unshift($fields, $name_field);
        }
        
        return $fields;
    }
    
    public static function validate_email($email) {
        $email = sanitize_email($email);
        
        if (!is_email($email)) {
            return false;
        }
        
        return $email;
    }
    
    public static function validate_phone($phone) {
        $phone = sanitize_text_field($phone);
        
        $phone = preg_replace('/[^\d\+\-\(\)\s]/', '', $phone);
        
        if (empty($phone)) {
            return false;
        }
        
        return $phone;
    }
    
    public static function validate_required_fields($data, $required_fields) {
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $errors[] = "Das Feld '{$field}' ist erforderlich";
            }
        }
        
        return $errors;
    }
    
    public static function escape_sql($value) {
        global $wpdb;
        return $wpdb->prepare('%s', $value);
    }
    
    public static function prepare_for_json($data) {
        if (is_array($data)) {
            $normalized = [];
            foreach ($data as $key => $value) {
                $normalized_key = self::normalize_german_chars($key);
                $normalized[$normalized_key] = self::prepare_for_json($value);
            }
            return $normalized;
        }
        
        if (is_string($data)) {
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            return self::normalize_german_chars($data);
        }
        
        return $data;
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
    
    public static function denormalize_german_chars($text) {
        $german_chars = [
            'ae' => 'ä', 'Ae' => 'Ä',
            'oe' => 'ö', 'Oe' => 'Ö',
            'ue' => 'ü', 'Ue' => 'Ü',
            'ss' => 'ß'
        ];
        
        return str_replace(array_keys($german_chars), array_values($german_chars), $text);
    }
    
    public static function prepare_for_display($data) {
        if (is_array($data)) {
            $denormalized = [];
            foreach ($data as $key => $value) {
                $denormalized_key = self::denormalize_german_chars($key);
                $denormalized[$denormalized_key] = self::prepare_for_display($value);
            }
            return $denormalized;
        }
        
        if (is_string($data)) {
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            return self::denormalize_german_chars($data);
        }
        
        return $data;
    }
}
