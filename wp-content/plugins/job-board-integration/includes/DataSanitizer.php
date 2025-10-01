<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

class DataSanitizer {
    
    /**
     * Рекурсивная очистка данных из $_POST
     */
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
    
    /**
     * Санитизация данных заявки
     */
    public static function sanitize_application_data($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $key = sanitize_key($key);
            
            if (is_array($value)) {
                $sanitized[$key] = self::sanitize_recursive($value);
            } elseif (is_string($value)) {
                // Для текстовых полей используем более мягкую очистку
                $sanitized[$key] = sanitize_textarea_field($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Санитизация данных шаблона
     */
    public static function sanitize_template_data($data) {
        $sanitized = [];
        
        // Обязательные поля
        if (isset($data['name'])) {
            $sanitized['name'] = sanitize_text_field($data['name']);
        }
        
        if (isset($data['description'])) {
            $sanitized['description'] = sanitize_textarea_field($data['description']);
        }
        
        if (isset($data['is_active'])) {
            $sanitized['is_active'] = (int) $data['is_active'];
        }
        
        // Поля шаблона
        if (isset($data['fields'])) {
            if (is_array($data['fields'])) {
                // Если уже массив, санитизируем его
                $sanitized['fields'] = self::sanitize_template_fields($data['fields']);
            } elseif (is_string($data['fields'])) {
                // Если JSON строка, декодируем и санитизируем
                $decoded_fields = json_decode($data['fields'], true);
                if (is_array($decoded_fields)) {
                    $sanitized['fields'] = self::sanitize_template_fields($decoded_fields);
                } else {
                    $sanitized['fields'] = $data['fields']; // Оставляем как есть, если не удалось декодировать
                }
            } else {
                $sanitized['fields'] = $data['fields'];
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Санитизация полей шаблона
     */
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
            
            // Добавляем name если есть
            if (isset($field['name'])) {
                $sanitized_field['name'] = sanitize_key($field['name']);
            }
            
            $sanitized_fields[] = $sanitized_field;
        }
        
        return $sanitized_fields;
    }
    
    /**
     * Валидация email адреса
     */
    public static function validate_email($email) {
        $email = sanitize_email($email);
        
        if (!is_email($email)) {
            return false;
        }
        
        return $email;
    }
    
    /**
     * Валидация телефона
     */
    public static function validate_phone($phone) {
        $phone = sanitize_text_field($phone);
        
        // Убираем все кроме цифр, +, -, (, ), пробелов
        $phone = preg_replace('/[^\d\+\-\(\)\s]/', '', $phone);
        
        if (empty($phone)) {
            return false;
        }
        
        return $phone;
    }
    
    /**
     * Валидация обязательных полей
     */
    public static function validate_required_fields($data, $required_fields) {
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $errors[] = "Поле '{$field}' обязательно для заполнения";
            }
        }
        
        return $errors;
    }
    
    /**
     * Экранирование данных для SQL запросов
     */
    public static function escape_sql($value) {
        global $wpdb;
        return $wpdb->prepare('%s', $value);
    }
    
    /**
     * Подготовка данных для JSON
     */
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
    
    /**
     * Нормализация немецких символов (для внутренней работы)
     */
    private static function normalize_german_chars($text) {
        $german_chars = [
            'ä' => 'ae', 'Ä' => 'Ae',
            'ö' => 'oe', 'Ö' => 'Oe', 
            'ü' => 'ue', 'Ü' => 'Ue',
            'ß' => 'ss'
        ];
        
        return str_replace(array_keys($german_chars), array_values($german_chars), $text);
    }
    
    /**
     * Денормализация немецких символов (для отображения пользователю)
     */
    public static function denormalize_german_chars($text) {
        $german_chars = [
            'ae' => 'ä', 'Ae' => 'Ä',
            'oe' => 'ö', 'Oe' => 'Ö',
            'ue' => 'ü', 'Ue' => 'Ü',
            'ss' => 'ß'
        ];
        
        return str_replace(array_keys($german_chars), array_values($german_chars), $text);
    }
    
    /**
     * Подготовка данных для отображения (денормализация)
     */
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
