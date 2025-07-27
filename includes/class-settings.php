<?php
/**
 * Settings management for Nexus AI WP Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_Settings {
    
    private static $instance = null;
    private $default_settings = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->default_settings = array(
            'api_key' => '',
            'source_language' => 'en',
            'target_languages' => array('es', 'fr', 'de'),
            'auto_translate' => true,
            'throttle_limit' => 10,
            'throttle_period' => 3600,
            'retry_attempts' => 3,
            'cache_translations' => true,
            'seo_friendly_urls' => true,
            'sync_post_status' => true,
            'translate_excerpts' => true,
            'translate_meta_fields' => array(),
            'exclude_post_types' => array(),
            'user_role_permissions' => array('administrator', 'editor')
        );
    }
    
    /**
     * Get setting value
     */
    public function get($key, $default = null) {
        if ($default === null && isset($this->default_settings[$key])) {
            $default = $this->default_settings[$key];
        }
        
        return get_option('nexus_ai_wp_translator_' . $key, $default);
    }
    
    /**
     * Update setting value
     */
    public function update($key, $value) {
        return update_option('nexus_ai_wp_translator_' . $key, $value);
    }
    
    /**
     * Get all settings
     */
    public function get_all() {
        $settings = array();
        
        foreach ($this->default_settings as $key => $default) {
            $settings[$key] = $this->get($key, $default);
        }
        
        return $settings;
    }
    
    /**
     * Update multiple settings
     */
    public function update_multiple($settings) {
        $updated = array();
        
        foreach ($settings as $key => $value) {
            if (array_key_exists($key, $this->default_settings)) {
                $this->update($key, $value);
                $updated[$key] = $value;
            }
        }
        
        return $updated;
    }
    
    /**
     * Reset to default settings
     */
    public function reset_to_defaults() {
        foreach ($this->default_settings as $key => $value) {
            $this->update($key, $value);
        }
    }
    
    /**
     * Validate settings
     */
    public function validate($key, $value) {
        switch ($key) {
            case 'api_key':
                return is_string($value) && strlen($value) > 10;
                
            case 'source_language':
            case 'target_languages':
                $valid_languages = array('en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'ja', 'ko', 'zh', 'ar', 'hi', 'nl', 'sv', 'da', 'no', 'fi', 'pl', 'cs', 'hu');
                
                if ($key === 'source_language') {
                    return in_array($value, $valid_languages);
                } else {
                    return is_array($value) && !empty(array_intersect($value, $valid_languages));
                }
                
            case 'throttle_limit':
            case 'retry_attempts':
                return is_numeric($value) && intval($value) > 0;
                
            case 'throttle_period':
                return is_numeric($value) && intval($value) >= 60; // Minimum 1 minute
                
            case 'auto_translate':
            case 'cache_translations':
            case 'seo_friendly_urls':
            case 'sync_post_status':
            case 'translate_excerpts':
                return is_bool($value);
                
            case 'translate_meta_fields':
            case 'exclude_post_types':
            case 'user_role_permissions':
                return is_array($value);
                
            default:
                return true;
        }
    }
    
    /**
     * Get default settings
     */
    public function get_defaults() {
        return $this->default_settings;
    }
}