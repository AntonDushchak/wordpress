<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

class URLBuilder {
    
    private $base_url;
    private $api_key;
    
    public function __construct() {
        $this->load_settings();
    }
    
    /**
     * Загрузка настроек (вызывается каждый раз для получения актуальных данных)
     */
    private function load_settings() {
        $settings = Settings::get_settings();
        $this->base_url = rtrim($settings['api_url'] ?? '', '/');
        $this->api_key = $settings['api_key'] ?? '';
    }
    
    /**
     * Построение URL для шаблонов
     */
    public function for_templates($template_id = null) {
        $this->load_settings(); // Загружаем актуальные настройки
        
        // Если базовый URL уже содержит путь к шаблонам, используем его как есть
        if (strpos($this->base_url, '/templates') !== false) {
            $url = $this->base_url;
        } else {
            $url = $this->get_base_api_url() . '/templates';
        }
        
        if ($template_id) {
            $url .= '/' . $template_id;
        }
        
        return $url;
    }
    
    /**
     * Построение URL для заявок
     */
    public function for_applications($application_hash = null) {
        $this->load_settings(); // Загружаем актуальные настройки
        
        $url = $this->get_base_api_url() . '/applications';
        
        if ($application_hash) {
            $url .= '/' . $application_hash;
        }
        
        return $url;
    }
    
    /**
     * Построение URL для тестирования соединения
     */
    public function for_test() {
        $this->load_settings(); // Загружаем актуальные настройки
        return $this->get_base_api_url() . '/test';
    }
    
    /**
     * Построение URL для синхронизации
     */
    public function for_sync() {
        $this->load_settings(); // Загружаем актуальные настройки
        return $this->get_base_api_url() . '/sync';
    }
    
    /**
     * Построение URL для пользователей
     */
    public function for_users($user_id = null) {
        $this->load_settings(); // Загружаем актуальные настройки
        $url = $this->get_base_api_url() . '/users';
        
        if ($user_id) {
            $url .= '/' . $user_id;
        }
        
        return $url;
    }
    
    /**
     * Получение базового API URL
     */
    public function get_base_api_url() {
        // Если api_url уже содержит /api/admin, используем его как есть
        if (strpos($this->base_url, '/api/admin') !== false) {
            return preg_replace('/\/api\/admin\/.*$/', '/api/admin', $this->base_url);
        }
        
        // Если api_url уже содержит /api, заменяем на /api
        if (strpos($this->base_url, '/api/') !== false) {
            return preg_replace('/\/api\/.*$/', '/api', $this->base_url);
        }
        
        // Если api_url заканчивается на /api, используем как есть
        if (strpos($this->base_url, '/api') === strlen($this->base_url) - 4) {
            return $this->base_url;
        }
        
        return $this->base_url . '/api';
    }
    
    /**
     * Построение URL с параметрами
     */
    public function build_url($endpoint, $params = []) {
        $this->load_settings(); // Загружаем актуальные настройки
        $url = $this->get_base_api_url() . '/' . ltrim($endpoint, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
    
    /**
     * Получение заголовков для API запросов
     */
    public function get_headers() {
        $this->load_settings(); // Загружаем актуальные настройки
        
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Neo Job Board/' . (defined('NEO_JOB_BOARD_VERSION') ? NEO_JOB_BOARD_VERSION : '1.0.0')
        ];
        
        if (!empty($this->api_key)) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        }
        
        return $headers;
    }
    
    /**
     * Проверка валидности URL
     */
    public function is_valid_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Получение домена из URL
     */
    public function get_domain($url = null) {
        $url = $url ?: $this->base_url;
        $parsed = parse_url($url);
        return $parsed['host'] ?? '';
    }
    
    /**
     * Построение URL для webhook
     */
    public function for_webhook($action) {
        return home_url('/wp-json/neo-job-board/v1/webhook/' . $action);
    }
    
    /**
     * Построение URL для AJAX действий
     */
    public function for_ajax($action) {
        return admin_url('admin-ajax.php?action=neo_job_board_' . $action);
    }
}
