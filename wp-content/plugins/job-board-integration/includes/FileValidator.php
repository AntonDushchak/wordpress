<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

class FileValidator {
    
    /**
     * Разрешенные типы файлов
     */
    const ALLOWED_TYPES = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'document' => ['pdf', 'doc', 'docx', 'txt'],
        'archive' => ['zip', 'rar', '7z']
    ];
    
    /**
     * Максимальный размер файла (в байтах)
     */
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    
    /**
     * Валидация загружаемого файла
     */
    public static function validate_upload($file, $allowed_types = null) {
        $errors = [];
        
        // Проверяем наличие файла
        if (empty($file) || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Файл не был загружен';
            return $errors;
        }
        
        // Проверяем размер файла
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $errors[] = 'Размер файла превышает максимально допустимый (' . self::format_file_size(self::MAX_FILE_SIZE) . ')';
        }
        
        // Проверяем тип файла
        $file_type = self::get_file_type($file);
        if (!$file_type) {
            $errors[] = 'Не удалось определить тип файла';
        }
        
        // Проверяем расширение
        $allowed_types = $allowed_types ?? array_merge(
            self::ALLOWED_TYPES['image'],
            self::ALLOWED_TYPES['document'],
            self::ALLOWED_TYPES['archive']
        );
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_types)) {
            $errors[] = 'Тип файла не разрешен. Разрешенные типы: ' . implode(', ', $allowed_types);
        }
        
        // Проверяем MIME тип
        if (!self::validate_mime_type($file, $file_extension)) {
            $errors[] = 'MIME тип файла не соответствует расширению';
        }
        
        // Проверяем на вирусы (базовая проверка)
        if (!self::scan_file_content($file['tmp_name'])) {
            $errors[] = 'Файл содержит подозрительное содержимое';
        }
        
        return $errors;
    }
    
    /**
     * Безопасная загрузка файла через WordPress
     */
    public static function handle_upload($file, $upload_dir = 'neo-job-board') {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $upload_overrides = [
            'test_form' => false,
            'test_size' => true,
            'test_type' => true,
            'mimes' => self::get_allowed_mimes()
        ];
        
        $movefile = wp_handle_upload($file, $upload_overrides);
        
        if (isset($movefile['error'])) {
            return new \WP_Error('upload_error', $movefile['error']);
        }
        
        return $movefile;
    }
    
    /**
     * Получение типа файла
     */
    private static function get_file_type($file) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        return $mime_type;
    }
    
    /**
     * Валидация MIME типа
     */
    private static function validate_mime_type($file, $extension) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $expected_mimes = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'txt' => ['text/plain'],
            'zip' => ['application/zip'],
            'rar' => ['application/x-rar-compressed'],
            '7z' => ['application/x-7z-compressed']
        ];
        
        $expected = $expected_mimes[$extension] ?? [];
        return in_array($mime_type, $expected);
    }
    
    /**
     * Сканирование содержимого файла на подозрительные паттерны
     */
    private static function scan_file_content($file_path) {
        $content = file_get_contents($file_path, false, null, 0, 1024); // Читаем первые 1KB
        
        // Проверяем на подозрительные паттерны
        $suspicious_patterns = [
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/eval\(/i',
            '/base64_decode/i'
        ];
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Получение разрешенных MIME типов для WordPress
     */
    private static function get_allowed_mimes() {
        return [
            'jpg|jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            '7z' => 'application/x-7z-compressed'
        ];
    }
    
    /**
     * Форматирование размера файла
     */
    private static function format_file_size($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Генерация безопасного имени файла
     */
    public static function generate_safe_filename($original_name) {
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $name = pathinfo($original_name, PATHINFO_FILENAME);
        
        // Убираем небезопасные символы
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        
        // Добавляем timestamp для уникальности
        $name = $name . '_' . time();
        
        return $name . '.' . $extension;
    }
}
