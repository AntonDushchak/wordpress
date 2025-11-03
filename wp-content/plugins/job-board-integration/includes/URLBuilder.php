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
    
    private function load_settings() {
        $settings = Settings::get_settings();
        $this->base_url = rtrim($settings['api_url'] ?? '', '/');
        $this->api_key = $settings['api_key'] ?? '';
    }
    
    public function for_templates($template_id = null) {
        $this->load_settings();
        
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
    
    public function for_applications($application_hash = null) {
        $this->load_settings();
        
        $url = $this->get_base_api_url() . '/applications';
        
        if ($application_hash) {
            $url .= '/' . $application_hash;
        }
        
        return $url;
    }
    
    public function for_test() {
        $this->load_settings();
        return $this->get_base_api_url() . '/test';
    }
    
    public function for_sync() {
        $this->load_settings();
        return $this->get_base_api_url() . '/sync';
    }
    
    public function for_users($user_id = null) {
        $this->load_settings();
        $url = $this->get_base_api_url() . '/users';
        
        if ($user_id) {
            $url .= '/' . $user_id;
        }
        
        return $url;
    }
    
    public function get_base_api_url() {
        if (strpos($this->base_url, '/api/admin') !== false) {
            return preg_replace('/\/api\/admin\/.*$/', '/api/admin', $this->base_url);
        }
        
        if (strpos($this->base_url, '/api/') !== false) {
            return preg_replace('/\/api\/.*$/', '/api', $this->base_url);
        }
        
        if (strpos($this->base_url, '/api') === strlen($this->base_url) - 4) {
            return $this->base_url;
        }
        
        return $this->base_url . '/api';
    }
    
    public function build_url($endpoint, $params = []) {
        $this->load_settings();
        $url = $this->get_base_api_url() . '/' . ltrim($endpoint, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
    
    public function get_headers() {
        $this->load_settings();
        
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Neo Job Board/' . (defined('NEO_JOB_BOARD_VERSION') ? constant('NEO_JOB_BOARD_VERSION') : '1.0.0')
        ];
        
        if (!empty($this->api_key)) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        }
        
        return $headers;
    }
    
    public function is_valid_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    public function get_domain($url = null) {
        $url = $url ?: $this->base_url;
        $parsed = parse_url($url);
        return $parsed['host'] ?? '';
    }
    
    public function for_webhook($action) {
        return home_url('/wp-json/neo-job-board/v1/webhook/' . $action);
    }
    
    public function for_ajax($action) {
        return admin_url('admin-ajax.php?action=neo_job_board_' . $action);
    }
}
