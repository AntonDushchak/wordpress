<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

class ErrorHandler {
    
    /**
     * Обработка ошибок AJAX запросов
     */
    public static function handle_ajax_error(\Exception $e, $context = '') {
        self::log_error($e, $context);
        
        if ($e instanceof ValidationException) {
            wp_send_json_error($e->getMessage(), HTTPStatus::BAD_REQUEST);
        } elseif ($e instanceof SecurityException) {
            wp_send_json_error('Ошибка безопасности: ' . $e->getMessage(), HTTPStatus::FORBIDDEN);
        } elseif ($e instanceof ApiException) {
            wp_send_json_error('API недоступен: ' . $e->getMessage(), HTTPStatus::SERVICE_UNAVAILABLE);
        } elseif ($e instanceof DatabaseException) {
            wp_send_json_error('Ошибка базы данных: ' . $e->getMessage(), HTTPStatus::INTERNAL_SERVER_ERROR);
        } else {
            wp_send_json_error('Системная ошибка', HTTPStatus::INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Обработка ошибок API
     */
    public static function handle_api_error($error, $context = '') {
        $message = is_wp_error($error) ? $error->get_error_message() : $error;
        
        self::log_error(new \Exception($message), "API: {$context}");
        
        return [
            'success' => false,
            'error' => $message,
            'code' => HTTPStatus::SERVICE_UNAVAILABLE
        ];
    }
    
    /**
     * Обработка ошибок валидации
     */
    public static function handle_validation_error($errors, $context = '') {
        if (empty($errors)) {
            return;
        }
        
        $message = is_array($errors) ? implode(', ', $errors) : $errors;
        
        self::log_error(new ValidationException($message), "Validation: {$context}");
        
        wp_send_json_error($message, HTTPStatus::BAD_REQUEST);
    }
    
    /**
     * Обработка ошибок безопасности
     */
    public static function handle_security_error($message, $context = '') {
        self::log_error(new SecurityException($message), "Security: {$context}");
        
        wp_send_json_error('Ошибка безопасности: ' . $message, HTTPStatus::FORBIDDEN);
    }
    
    /**
     * Логирование ошибок
     */
    public static function log_error(\Exception $e, $context = '') {
        $log_message = sprintf(
            '[%s] %s: %s in %s:%d',
            current_time('mysql'),
            $context ?: 'Error',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
        
        // Логируем в WordPress error log
        error_log($log_message);
        
        // Дополнительно логируем в файл плагина
        self::log_to_file($log_message, $e->getTraceAsString());
    }
    
    /**
     * Логирование в файл плагина
     */
    private static function log_to_file($message, $trace = '') {
        $log_file = WP_CONTENT_DIR . '/uploads/neo-dashboard.log';
        
        $log_entry = $message . "\n";
        if ($trace) {
            $log_entry .= "Stack trace:\n" . $trace . "\n";
        }
        $log_entry .= str_repeat('-', 80) . "\n";
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Обработка критических ошибок
     */
    public static function handle_critical_error($message, $context = '') {
        self::log_error(new \Exception($message), "CRITICAL: {$context}");
        
        // Отправляем уведомление администратору
        self::notify_admin_critical_error($message, $context);
        
        wp_send_json_error('Критическая ошибка системы', HTTPStatus::INTERNAL_SERVER_ERROR);
    }
    
    /**
     * Уведомление администратора о критических ошибках
     */
    private static function notify_admin_critical_error($message, $context) {
        $admin_email = get_option('admin_email');
        $subject = 'Критическая ошибка в Neo Job Board';
        $body = "Произошла критическая ошибка в плагине Neo Job Board:\n\n";
        $body .= "Контекст: {$context}\n";
        $body .= "Сообщение: {$message}\n";
        $body .= "Время: " . current_time('mysql') . "\n";
        $body .= "URL: " . home_url() . "\n";
        
        wp_mail($admin_email, $subject, $body);
    }
    
    /**
     * Проверка и обработка ошибок WordPress
     */
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

/**
 * Исключения для различных типов ошибок
 */
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
