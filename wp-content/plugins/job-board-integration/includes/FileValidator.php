<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

class FileValidator {
    
    const ALLOWED_TYPES = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'document' => ['pdf', 'doc', 'docx', 'txt'],
        'archive' => ['zip', 'rar', '7z']
    ];
    
    const MAX_FILE_SIZE = 5 * 1024 * 1024;
    
    public static function validate_upload($file, $allowed_types = null) {
        $errors = [];
        
        if (empty($file) || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'File not uploaded';
            return $errors;
        }
        
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $errors[] = 'File size exceeds the maximum allowed (' . self::format_file_size(self::MAX_FILE_SIZE) . ')';
        }
        
        $file_type = self::get_file_type($file);
        if (!$file_type) {
            $errors[] = 'Could not determine file type';
        }
        
        $allowed_types = $allowed_types ?? array_merge(
            self::ALLOWED_TYPES['image'],
            self::ALLOWED_TYPES['document'],
            self::ALLOWED_TYPES['archive']
        );
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_types)) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $allowed_types);
        }
        
        if (!self::validate_mime_type($file, $file_extension)) {
            $errors[] = 'MIME type of the file does not match the extension';
        }
        
        if (!self::scan_file_content($file['tmp_name'])) {
            $errors[] = 'File contains suspicious content';
        }
        
        return $errors;
    }
    
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
    
    private static function get_file_type($file) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        return $mime_type;
    }
    
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
    
    private static function scan_file_content($file_path) {
        $content = file_get_contents($file_path, false, null, 0, 1024);
        
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
    
    private static function format_file_size($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    public static function generate_safe_filename($original_name) {
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $name = pathinfo($original_name, PATHINFO_FILENAME);
        
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        
        $name = $name . '_' . time();
        
        return $name . '.' . $extension;
    }
}
