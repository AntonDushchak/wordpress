<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

class SettingsCache {
    
    private static $cache = [];
    private static $cache_timeout = 3600;
    
    public static function get_settings() {
        if (isset(self::$cache['settings'])) {
            return self::$cache['settings'];
        }
        
        $settings = Settings::get_settings();
        self::$cache['settings'] = $settings;
        
        return $settings;
    }
    
    public static function get_templates($force_refresh = false) {
        $cache_key = 'templates';
        
        if (!$force_refresh && isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }
        
        $templates = Templates::get_templates();
        self::$cache[$cache_key] = $templates;
        
        return $templates;
    }
    
    public static function get_active_templates($force_refresh = false) {
        $cache_key = 'active_templates';
        
        if (!$force_refresh && isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }
        
        $templates = Templates::get_active_templates();
        self::$cache[$cache_key] = $templates;
        
        return $templates;
    }
    
    public static function get_applications($force_refresh = false) {
        $cache_key = 'applications';
        
        if (!$force_refresh && isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }
        
        $applications = Jobs::get_applications();
        self::$cache[$cache_key] = $applications;
        
        return $applications;
    }
    
    public static function get_template($template_id, $force_refresh = false) {
        $cache_key = "template_{$template_id}";
        
        if (!$force_refresh && isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }
        
        $template = Templates::get_template($template_id);
        self::$cache[$cache_key] = $template;
        
        return $template;
    }
    
    public static function clear_cache($key = null) {
        if ($key) {
            unset(self::$cache[$key]);
        } else {
            self::$cache = [];
        }
    }
    
    public static function clear_templates_cache() {
        self::clear_cache('templates');
        self::clear_cache('active_templates');
        
        foreach (self::$cache as $key => $value) {
            if (strpos($key, 'template_') === 0) {
                unset(self::$cache[$key]);
            }
        }
    }
    
    public static function clear_applications_cache() {
        self::clear_cache('applications');
    }
    
    public static function get_cached_data($key, $callback, $timeout = null) {
        $timeout = $timeout ?? self::$cache_timeout;
        
        if (isset(self::$cache[$key])) {
            $cached_data = self::$cache[$key];
            
            if (isset($cached_data['timestamp']) && 
                (time() - $cached_data['timestamp']) < $timeout) {
                return $cached_data['data'];
            }
        }
        
        $data = $callback();
        
        self::$cache[$key] = [
            'data' => $data,
            'timestamp' => time()
        ];
        
        return $data;
    }
    
    public static function get_cache_stats() {
        return [
            'cache_size' => count(self::$cache),
            'memory_usage' => memory_get_usage(true),
            'cached_keys' => array_keys(self::$cache)
        ];
    }
    
    public static function cleanup_expired_cache() {
        $current_time = time();
        
        foreach (self::$cache as $key => $data) {
            if (is_array($data) && isset($data['timestamp'])) {
                if (($current_time - $data['timestamp']) > self::$cache_timeout) {
                    unset(self::$cache[$key]);
                }
            }
        }
    }
    
    public static function get_api_settings() {
        $settings = self::get_settings();
        
        return [
            'api_url' => $settings['api_url'] ?? '',
            'api_key' => $settings['api_key'] ?? '',
            'user_id' => $settings['user_id'] ?? '',
            'webhook_secret' => $settings['webhook_secret'] ?? ''
        ];
    }
    
    public static function is_api_available($force_check = false) {
        $cache_key = 'api_available';
        
        if (!$force_check) {
            $cached = self::get_cached_data($cache_key, function() {
                return false;
            }, 300);
            
            if ($cached !== false) {
                return $cached;
            }
        }
        
        $api_client = new APIClientV2();
        $result = $api_client->test_connection();
        
        $is_available = !is_wp_error($result);
        
        self::$cache[$cache_key] = [
            'data' => $is_available,
            'timestamp' => time()
        ];
        
        return $is_available;
    }
}
