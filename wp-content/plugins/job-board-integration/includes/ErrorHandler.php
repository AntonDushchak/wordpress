<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

class ErrorHandler {
    
    public static function handle_ajax_error(\Exception $e, $context = '') {
        self::log_error($e, $context);
        
        if ($e instanceof ValidationException) {
            wp_send_json_error($e->getMessage(), HTTPStatus::BAD_REQUEST);
        } elseif ($e instanceof SecurityException) {
            wp_send_json_error('Security error: ' . $e->getMessage(), HTTPStatus::FORBIDDEN);
        } elseif ($e instanceof ApiException) {
            wp_send_json_error('API not available: ' . $e->getMessage(), HTTPStatus::SERVICE_UNAVAILABLE);
        } elseif ($e instanceof DatabaseException) {
            wp_send_json_error('Database error: ' . $e->getMessage(), HTTPStatus::INTERNAL_SERVER_ERROR);
        } else {
            wp_send_json_error('System error', HTTPStatus::INTERNAL_SERVER_ERROR);
        }
    }
    
    public static function handle_api_error($error, $context = '') {
        $message = is_wp_error($error) ? $error->get_error_message() : $error;
        
        self::log_error(new \Exception($message), "API: {$context}");
        
        return [
            'success' => false,
            'error' => $message,
            'code' => HTTPStatus::SERVICE_UNAVAILABLE
        ];
    }
    
    public static function handle_validation_error($errors, $context = '') {
        if (empty($errors)) {
            return;
        }
        
        $message = is_array($errors) ? implode(', ', $errors) : $errors;
        
        self::log_error(new ValidationException($message), "Validation: {$context}");
        
        wp_send_json_error($message, HTTPStatus::BAD_REQUEST);
    }
    
    public static function handle_security_error($message, $context = '') {
        self::log_error(new SecurityException($message), "Security: {$context}");
        
        wp_send_json_error('Security error: ' . $message, HTTPStatus::FORBIDDEN);
    }
    
    public static function log_error(\Exception $e, $context = '') {
        $log_message = sprintf(
            '[%s] %s: %s in %s:%d',
            current_time('mysql'),
            $context ?: 'Error',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
        
        error_log($log_message);
        
        self::log_to_file($log_message, $e->getTraceAsString());
    }
    
    private static function log_to_file($message, $trace = '') {
        $log_file = WP_CONTENT_DIR . '/uploads/neo-dashboard.log';
        
        $log_entry = $message . "\n";
        if ($trace) {
            $log_entry .= "Stack trace:\n" . $trace . "\n";
        }
        $log_entry .= str_repeat('-', 80) . "\n";
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    public static function handle_critical_error($message, $context = '') {
        self::log_error(new \Exception($message), "CRITICAL: {$context}");
        
        self::notify_admin_critical_error($message, $context);
        
        wp_send_json_error('Critical system error', HTTPStatus::INTERNAL_SERVER_ERROR);
    }
    
    private static function notify_admin_critical_error($message, $context) {
        $admin_email = get_option('admin_email');
        $subject = 'Critical error in Neo Job Board';
        $body = "Critical error in Neo Job Board plugin:\n\n";
        $body .= "Context: {$context}\n";
        $body .= "Message: {$message}\n";
        $body .= "Time: " . current_time('mysql') . "\n";
        $body .= "URL: " . home_url() . "\n";
        
        wp_mail($admin_email, $subject, $body);
    }
    
    public static function handle_wp_error($wp_error, $context = '') {
        if (!is_wp_error($wp_error)) {
            return;
        }
        
        $message = $wp_error->get_error_message();
        $code = $wp_error->get_error_code();
        
        self::log_error(new \Exception("WP Error [{$code}]: {$message}"), $context);
        
        return [
            'success' => false,
            'error' => $message,
            'code' => $code
        ];
    }
}

class ValidationException extends \Exception {
    public function __construct($message = "", $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

class SecurityException extends \Exception {
    public function __construct($message = "", $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

class ApiException extends \Exception {
    public function __construct($message = "", $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

class DatabaseException extends \Exception {
    public function __construct($message = "", $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
