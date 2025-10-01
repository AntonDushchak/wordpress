<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

class SecurityValidator {
    
    /**
     * Проверка прав доступа для административных действий
     */
    public static function verify_admin_access($capability = 'manage_options') {
        if (!current_user_can($capability)) {
            wp_die('Недостаточно прав для выполнения этого действия.');
        }
    }
    
    /**
     * Проверка nonce для безопасности
     */
    public static function verify_nonce($nonce = null, $action = 'neo_job_board_nonce') {
        $nonce = $nonce ?? ($_POST['nonce'] ?? '');
        
        if (!wp_verify_nonce($nonce, $action)) {
            wp_die('Неверная nonce. Доступ запрещен.');
        }
    }
    
    /**
     * Комплексная проверка безопасности для AJAX запросов
     */
    public static function verify_ajax_security($require_admin = true, $capability = 'manage_options') {
        // Проверяем nonce
        self::verify_nonce();
        
        // Проверяем права доступа если требуется
        if ($require_admin) {
            self::verify_admin_access($capability);
        }
        
        // Проверяем, что это AJAX запрос
        if (!wp_doing_ajax()) {
            wp_die('Этот запрос может быть выполнен только через AJAX.');
        }
    }
    
    /**
     * Проверка прав доступа для конкретных операций
     */
    public static function verify_operation_access($operation) {
        $capabilities = [
            'manage_templates' => 'manage_options',
            'manage_applications' => 'manage_options', 
            'view_applications' => 'edit_posts',
            'delete_applications' => 'manage_options',
            'submit_application' => 'read' // Публичное действие
        ];
        
        $required_capability = $capabilities[$operation] ?? 'manage_options';
        
        if ($required_capability !== 'read' && !current_user_can($required_capability)) {
            wp_die('Недостаточно прав для выполнения операции: ' . $operation);
        }
    }
    
    /**
     * Валидация ID параметров
     */
    public static function validate_id($id, $param_name = 'ID') {
        $id = (int) $id;
        
        if ($id <= 0) {
            wp_send_json_error("Неверный {$param_name}");
        }
        
        return $id;
    }
    
    /**
     * Валидация hash ID
     */
    public static function validate_hash($hash, $param_name = 'hash_id') {
        $hash = sanitize_text_field($hash);
        
        if (empty($hash) || !preg_match('/^[A-Z0-9]{8}$/', $hash)) {
            wp_send_json_error("Неверный формат {$param_name}");
        }
        
        return $hash;
    }
    
    /**
     * Проверка существования записи в БД
     */
    public static function verify_record_exists($table, $id, $id_field = 'id') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $table;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE {$id_field} = %d",
            $id
        ));
        
        if (!$exists) {
            wp_send_json_error('Запись не найдена');
        }
        
        return true;
    }
    
    /**
     * Проверка лимитов запросов (защита от спама)
     */
    public static function check_rate_limit($action, $limit = 10, $window = 300) {
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "rate_limit_{$action}_{$user_ip}";
        
        $attempts = get_transient($key) ?: 0;
        
        if ($attempts >= $limit) {
            wp_send_json_error('Слишком много запросов. Попробуйте позже.');
        }
        
        set_transient($key, $attempts + 1, $window);
    }
}
