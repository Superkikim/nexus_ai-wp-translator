<?php
/**
 * Custom Fields Translator
 * 
 * Handles translation of custom fields, ACF fields, Yoast SEO meta, and other plugin metadata
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_Custom_Fields_Translator {
    
    private static $instance = null;
    private $api_handler;
    private $db;
    private $supported_fields;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->api_handler = Nexus_AI_WP_Translator_API_Handler::get_instance();
        $this->db = Nexus_AI_WP_Translator_Database::get_instance();
        $this->init_supported_fields();
        $this->init_hooks();
    }
    
    /**
     * Initialize supported fields configuration
     */
    private function init_supported_fields() {
        $this->supported_fields = array(
            // Yoast SEO
            'yoast' => array(
                '_yoast_wpseo_title' => array('type' => 'text', 'label' => 'SEO Title'),
                '_yoast_wpseo_metadesc' => array('type' => 'text', 'label' => 'Meta Description'),
                '_yoast_wpseo_opengraph-title' => array('type' => 'text', 'label' => 'Facebook Title'),
                '_yoast_wpseo_opengraph-description' => array('type' => 'text', 'label' => 'Facebook Description'),
                '_yoast_wpseo_twitter-title' => array('type' => 'text', 'label' => 'Twitter Title'),
                '_yoast_wpseo_twitter-description' => array('type' => 'text', 'label' => 'Twitter Description'),
                '_yoast_wpseo_focuskw' => array('type' => 'keywords', 'label' => 'Focus Keyword'),
            ),
            
            // All in One SEO
            'aioseo' => array(
                '_aioseop_title' => array('type' => 'text', 'label' => 'SEO Title'),
                '_aioseop_description' => array('type' => 'text', 'label' => 'Meta Description'),
                '_aioseop_keywords' => array('type' => 'keywords', 'label' => 'Keywords'),
                '_aioseop_opengraph_settings' => array('type' => 'json', 'label' => 'OpenGraph Settings'),
            ),
            
            // Advanced Custom Fields (ACF)
            'acf' => array(
                // Will be dynamically populated based on field types
            ),
            
            // WooCommerce
            'woocommerce' => array(
                '_product_short_description' => array('type' => 'html', 'label' => 'Short Description'),
                '_purchase_note' => array('type' => 'text', 'label' => 'Purchase Note'),
                '_wc_review_count' => array('type' => 'skip', 'label' => 'Review Count'),
                '_wc_average_rating' => array('type' => 'skip', 'label' => 'Average Rating'),
            ),
            
            // Genesis Framework
            'genesis' => array(
                '_genesis_title' => array('type' => 'text', 'label' => 'Genesis Title'),
                '_genesis_description' => array('type' => 'text', 'label' => 'Genesis Description'),
                '_genesis_canonical_uri' => array('type' => 'skip', 'label' => 'Canonical URI'),
            ),
            
            // Custom fields (general)
            'custom' => array(
                'subtitle' => array('type' => 'text', 'label' => 'Subtitle'),
                'excerpt_custom' => array('type' => 'text', 'label' => 'Custom Excerpt'),
                'description' => array('type' => 'html', 'label' => 'Description'),
                'summary' => array('type' => 'text', 'label' => 'Summary'),
            )
        );
        
        // Allow filtering of supported fields
        $this->supported_fields = apply_filters('nexus_ai_wp_translator_supported_fields', $this->supported_fields);
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Hook into translation process
        add_action('nexus_ai_wp_translator_after_post_created', array($this, 'translate_custom_fields'), 10, 3);
        
        // AJAX handlers
        add_action('wp_ajax_nexus_ai_wp_get_custom_fields', array($this, 'ajax_get_custom_fields'));
        add_action('wp_ajax_nexus_ai_wp_translate_custom_field', array($this, 'ajax_translate_custom_field'));
        add_action('wp_ajax_nexus_ai_wp_manage_field_settings', array($this, 'ajax_manage_field_settings'));
        
        // ACF integration
        if (function_exists('get_field_objects')) {
            add_action('init', array($this, 'init_acf_integration'));
        }
    }
    
    /**
     * Translate custom fields for a post
     */
    public function translate_custom_fields($translated_post_id, $source_post_id, $target_language) {
        $source_language = get_post_meta($source_post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');
        
        // Get all custom fields from source post
        $custom_fields = get_post_meta($source_post_id);
        
        if (empty($custom_fields)) {
            return;
        }
        
        $translated_fields = array();
        $translation_context = array(
            'content_type' => 'custom_field',
            'post_id' => $source_post_id,
            'target_post_id' => $translated_post_id
        );
        
        foreach ($custom_fields as $field_key => $field_values) {
            // Skip WordPress internal fields
            if ($this->should_skip_field($field_key)) {
                continue;
            }
            
            $field_config = $this->get_field_configuration($field_key);
            
            if ($field_config['type'] === 'skip') {
                // Copy without translation
                update_post_meta($translated_post_id, $field_key, $field_values[0]);
                continue;
            }
            
            foreach ($field_values as $field_value) {
                $translated_value = $this->translate_field_value(
                    $field_value,
                    $field_config,
                    $source_language,
                    $target_language,
                    $translation_context
                );
                
                if ($translated_value !== false) {
                    update_post_meta($translated_post_id, $field_key, $translated_value);
                    $translated_fields[$field_key] = $translated_value;
                }
            }
        }
        
        // Handle ACF fields specifically
        if (function_exists('get_field_objects')) {
            $this->translate_acf_fields($translated_post_id, $source_post_id, $target_language);
        }
        
        // Store translation metadata
        update_post_meta($translated_post_id, '_nexus_ai_wp_translator_custom_fields', $translated_fields);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Translated " . count($translated_fields) . " custom fields for post {$translated_post_id}");
        }
    }
    
    /**
     * Translate individual field value
     */
    private function translate_field_value($value, $field_config, $source_lang, $target_lang, $context) {
        if (empty($value) || !is_string($value)) {
            return $value;
        }
        
        switch ($field_config['type']) {
            case 'text':
                return $this->translate_text_field($value, $source_lang, $target_lang, $context);
                
            case 'html':
                return $this->translate_html_field($value, $source_lang, $target_lang, $context);
                
            case 'keywords':
                return $this->translate_keywords_field($value, $source_lang, $target_lang, $context);
                
            case 'json':
                return $this->translate_json_field($value, $source_lang, $target_lang, $context);
                
            case 'array':
                return $this->translate_array_field($value, $source_lang, $target_lang, $context);
                
            case 'skip':
                return $value;
                
            default:
                // Try to detect content type automatically
                return $this->translate_auto_detect($value, $source_lang, $target_lang, $context);
        }
    }
    
    /**
     * Translate text field
     */
    private function translate_text_field($value, $source_lang, $target_lang, $context) {
        $context['field_type'] = 'text';
        
        $result = $this->api_handler->translate_content($value, $source_lang, $target_lang, $context);
        
        return $result['success'] ? $result['translated_content'] : $value;
    }
    
    /**
     * Translate HTML field
     */
    private function translate_html_field($value, $source_lang, $target_lang, $context) {
        $context['field_type'] = 'html';
        
        $result = $this->api_handler->translate_content($value, $source_lang, $target_lang, $context);
        
        return $result['success'] ? $result['translated_content'] : $value;
    }
    
    /**
     * Translate keywords field
     */
    private function translate_keywords_field($value, $source_lang, $target_lang, $context) {
        $keywords = array_map('trim', explode(',', $value));
        $translated_keywords = array();
        
        foreach ($keywords as $keyword) {
            if (!empty($keyword)) {
                $context['field_type'] = 'keyword';
                $result = $this->api_handler->translate_content($keyword, $source_lang, $target_lang, $context);
                $translated_keywords[] = $result['success'] ? $result['translated_content'] : $keyword;
            }
        }
        
        return implode(', ', $translated_keywords);
    }
    
    /**
     * Translate JSON field
     */
    private function translate_json_field($value, $source_lang, $target_lang, $context) {
        $data = json_decode($value, true);
        
        if (!is_array($data)) {
            return $value;
        }
        
        $translated_data = $this->translate_array_recursive($data, $source_lang, $target_lang, $context);
        
        return wp_json_encode($translated_data);
    }
    
    /**
     * Translate array field
     */
    private function translate_array_field($value, $source_lang, $target_lang, $context) {
        if (is_serialized($value)) {
            $data = unserialize($value);
            $translated_data = $this->translate_array_recursive($data, $source_lang, $target_lang, $context);
            return serialize($translated_data);
        }
        
        return $value;
    }
    
    /**
     * Translate array recursively
     */
    private function translate_array_recursive($array, $source_lang, $target_lang, $context) {
        if (!is_array($array)) {
            if (is_string($array) && !empty($array)) {
                $result = $this->api_handler->translate_content($array, $source_lang, $target_lang, $context);
                return $result['success'] ? $result['translated_content'] : $array;
            }
            return $array;
        }
        
        $translated_array = array();
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $translated_array[$key] = $this->translate_array_recursive($value, $source_lang, $target_lang, $context);
            } elseif (is_string($value) && !empty($value) && $this->should_translate_array_value($key, $value)) {
                $result = $this->api_handler->translate_content($value, $source_lang, $target_lang, $context);
                $translated_array[$key] = $result['success'] ? $result['translated_content'] : $value;
            } else {
                $translated_array[$key] = $value;
            }
        }
        
        return $translated_array;
    }
    
    /**
     * Auto-detect field type and translate
     */
    private function translate_auto_detect($value, $source_lang, $target_lang, $context) {
        // Check if it's HTML
        if (strip_tags($value) !== $value) {
            return $this->translate_html_field($value, $source_lang, $target_lang, $context);
        }
        
        // Check if it's a URL
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value; // Don't translate URLs
        }
        
        // Check if it's a number
        if (is_numeric($value)) {
            return $value; // Don't translate numbers
        }
        
        // Check if it's JSON
        $json_data = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->translate_json_field($value, $source_lang, $target_lang, $context);
        }
        
        // Default to text translation
        return $this->translate_text_field($value, $source_lang, $target_lang, $context);
    }
    
    /**
     * Initialize ACF integration
     */
    public function init_acf_integration() {
        // Get all ACF field groups
        $field_groups = acf_get_field_groups();
        
        foreach ($field_groups as $field_group) {
            $fields = acf_get_fields($field_group['key']);
            
            foreach ($fields as $field) {
                $this->register_acf_field($field);
            }
        }
    }
    
    /**
     * Register ACF field for translation
     */
    private function register_acf_field($field) {
        $field_type = $this->map_acf_field_type($field['type']);
        
        $this->supported_fields['acf'][$field['name']] = array(
            'type' => $field_type,
            'label' => $field['label'],
            'acf_type' => $field['type']
        );
    }
    
    /**
     * Map ACF field type to translation type
     */
    private function map_acf_field_type($acf_type) {
        $type_mapping = array(
            'text' => 'text',
            'textarea' => 'text',
            'wysiwyg' => 'html',
            'email' => 'skip',
            'url' => 'skip',
            'password' => 'skip',
            'number' => 'skip',
            'range' => 'skip',
            'date_picker' => 'skip',
            'date_time_picker' => 'skip',
            'time_picker' => 'skip',
            'color_picker' => 'skip',
            'image' => 'skip',
            'file' => 'skip',
            'gallery' => 'skip',
            'select' => 'text',
            'checkbox' => 'array',
            'radio' => 'text',
            'button_group' => 'text',
            'true_false' => 'skip',
            'link' => 'skip',
            'post_object' => 'skip',
            'page_link' => 'skip',
            'relationship' => 'skip',
            'taxonomy' => 'skip',
            'user' => 'skip',
            'google_map' => 'skip',
            'repeater' => 'array',
            'flexible_content' => 'array',
            'clone' => 'array',
            'group' => 'array'
        );
        
        return isset($type_mapping[$acf_type]) ? $type_mapping[$acf_type] : 'text';
    }
    
    /**
     * Translate ACF fields specifically
     */
    private function translate_acf_fields($translated_post_id, $source_post_id, $target_language) {
        $field_objects = get_field_objects($source_post_id);
        
        if (!$field_objects) {
            return;
        }
        
        $source_language = get_post_meta($source_post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');
        
        foreach ($field_objects as $field_name => $field_object) {
            $field_config = $this->get_acf_field_configuration($field_object);
            
            if ($field_config['type'] === 'skip') {
                // Copy without translation
                $value = get_field($field_name, $source_post_id);
                update_field($field_name, $value, $translated_post_id);
                continue;
            }
            
            $value = get_field($field_name, $source_post_id);
            
            if (!empty($value)) {
                $context = array(
                    'content_type' => 'acf_field',
                    'field_name' => $field_name,
                    'field_type' => $field_object['type'],
                    'post_id' => $source_post_id
                );
                
                $translated_value = $this->translate_field_value($value, $field_config, $source_language, $target_language, $context);
                
                if ($translated_value !== false) {
                    update_field($field_name, $translated_value, $translated_post_id);
                }
            }
        }
    }
    
    /**
     * Get ACF field configuration
     */
    private function get_acf_field_configuration($field_object) {
        $field_type = $this->map_acf_field_type($field_object['type']);
        
        return array(
            'type' => $field_type,
            'label' => $field_object['label'],
            'acf_type' => $field_object['type']
        );
    }
    
    /**
     * Get field configuration
     */
    private function get_field_configuration($field_key) {
        // Check each plugin's fields
        foreach ($this->supported_fields as $plugin => $fields) {
            if (isset($fields[$field_key])) {
                return $fields[$field_key];
            }
        }
        
        // Check if it's an ACF field
        if (function_exists('get_field_object')) {
            $field_object = get_field_object($field_key);
            if ($field_object) {
                return $this->get_acf_field_configuration($field_object);
            }
        }
        
        // Default configuration
        return array(
            'type' => 'text',
            'label' => $field_key
        );
    }
    
    /**
     * Check if field should be skipped
     */
    private function should_skip_field($field_key) {
        // Skip WordPress internal fields
        $skip_patterns = array(
            '_edit_lock',
            '_edit_last',
            '_wp_',
            '_thumbnail_id',
            '_nexus_ai_wp_translator_',
            '_aioseop_',
            '_yoast_wpseo_linkdex',
            '_yoast_wpseo_content_score'
        );
        
        foreach ($skip_patterns as $pattern) {
            if (strpos($field_key, $pattern) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if array value should be translated
     */
    private function should_translate_array_value($key, $value) {
        // Skip certain keys that shouldn't be translated
        $skip_keys = array('id', 'url', 'link', 'href', 'src', 'class', 'type', 'format');
        
        if (in_array(strtolower($key), $skip_keys)) {
            return false;
        }
        
        // Skip if value looks like a URL
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Skip if value is numeric
        if (is_numeric($value)) {
            return false;
        }
        
        // Skip very short values
        if (strlen($value) < 3) {
            return false;
        }
        
        return true;
    }

    /**
     * AJAX: Get custom fields for a post
     */
    public function ajax_get_custom_fields() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $post_id = intval($_POST['post_id']);

        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'nexus-ai-wp-translator'));
        }

        $custom_fields = $this->get_translatable_custom_fields($post_id);

        wp_send_json_success($custom_fields);
    }

    /**
     * AJAX: Translate individual custom field
     */
    public function ajax_translate_custom_field() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $post_id = intval($_POST['post_id']);
        $field_key = sanitize_text_field($_POST['field_key']);
        $source_lang = sanitize_text_field($_POST['source_lang']);
        $target_lang = sanitize_text_field($_POST['target_lang']);

        if (!$post_id || !$field_key) {
            wp_send_json_error(__('Invalid parameters', 'nexus-ai-wp-translator'));
        }

        $field_value = get_post_meta($post_id, $field_key, true);
        $field_config = $this->get_field_configuration($field_key);

        $context = array(
            'content_type' => 'custom_field',
            'field_name' => $field_key,
            'post_id' => $post_id
        );

        $translated_value = $this->translate_field_value($field_value, $field_config, $source_lang, $target_lang, $context);

        if ($translated_value !== false) {
            wp_send_json_success(array(
                'original' => $field_value,
                'translated' => $translated_value,
                'field_config' => $field_config
            ));
        } else {
            wp_send_json_error(__('Translation failed', 'nexus-ai-wp-translator'));
        }
    }

    /**
     * AJAX: Manage field settings
     */
    public function ajax_manage_field_settings() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $action = sanitize_text_field($_POST['field_action']);

        switch ($action) {
            case 'get_settings':
                $settings = $this->get_field_translation_settings();
                wp_send_json_success($settings);
                break;

            case 'update_settings':
                $settings = array_map('sanitize_text_field', $_POST['settings']);
                $result = $this->update_field_translation_settings($settings);
                wp_send_json_success(array('updated' => $result));
                break;

            case 'reset_settings':
                $result = $this->reset_field_translation_settings();
                wp_send_json_success(array('reset' => $result));
                break;

            default:
                wp_send_json_error(__('Invalid action', 'nexus-ai-wp-translator'));
        }
    }

    /**
     * Get translatable custom fields for a post
     */
    public function get_translatable_custom_fields($post_id) {
        $custom_fields = get_post_meta($post_id);
        $translatable_fields = array();

        foreach ($custom_fields as $field_key => $field_values) {
            if ($this->should_skip_field($field_key)) {
                continue;
            }

            $field_config = $this->get_field_configuration($field_key);

            if ($field_config['type'] !== 'skip') {
                $translatable_fields[] = array(
                    'key' => $field_key,
                    'label' => $field_config['label'],
                    'type' => $field_config['type'],
                    'value' => $field_values[0],
                    'plugin' => $this->detect_field_plugin($field_key)
                );
            }
        }

        // Add ACF fields
        if (function_exists('get_field_objects')) {
            $acf_fields = get_field_objects($post_id);

            if ($acf_fields) {
                foreach ($acf_fields as $field_name => $field_object) {
                    $field_config = $this->get_acf_field_configuration($field_object);

                    if ($field_config['type'] !== 'skip') {
                        $translatable_fields[] = array(
                            'key' => $field_name,
                            'label' => $field_object['label'],
                            'type' => $field_config['type'],
                            'value' => $field_object['value'],
                            'plugin' => 'ACF',
                            'acf_type' => $field_object['type']
                        );
                    }
                }
            }
        }

        return $translatable_fields;
    }

    /**
     * Detect which plugin a field belongs to
     */
    private function detect_field_plugin($field_key) {
        if (strpos($field_key, '_yoast_wpseo_') === 0) {
            return 'Yoast SEO';
        } elseif (strpos($field_key, '_aioseop_') === 0) {
            return 'All in One SEO';
        } elseif (strpos($field_key, '_genesis_') === 0) {
            return 'Genesis';
        } elseif (strpos($field_key, '_product_') === 0 || strpos($field_key, '_wc_') === 0) {
            return 'WooCommerce';
        } else {
            return 'Custom';
        }
    }

    /**
     * Get field translation settings
     */
    public function get_field_translation_settings() {
        $default_settings = array(
            'translate_yoast_seo' => true,
            'translate_aioseo' => true,
            'translate_acf' => true,
            'translate_woocommerce' => true,
            'translate_genesis' => true,
            'translate_custom_fields' => true,
            'excluded_fields' => array(),
            'field_type_mapping' => array()
        );

        return get_option('nexus_ai_wp_translator_field_settings', $default_settings);
    }

    /**
     * Update field translation settings
     */
    public function update_field_translation_settings($settings) {
        return update_option('nexus_ai_wp_translator_field_settings', $settings);
    }

    /**
     * Reset field translation settings
     */
    public function reset_field_translation_settings() {
        return delete_option('nexus_ai_wp_translator_field_settings');
    }

    /**
     * Get field statistics
     */
    public function get_field_translation_statistics() {
        global $wpdb;

        $stats = array();

        // Count posts with custom fields
        $posts_with_fields = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
             WHERE meta_key NOT LIKE '_wp_%'
             AND meta_key NOT LIKE '_edit_%'"
        );

        $stats['posts_with_custom_fields'] = intval($posts_with_fields);

        // Count by plugin
        foreach ($this->supported_fields as $plugin => $fields) {
            $plugin_count = 0;

            foreach (array_keys($fields) as $field_key) {
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                    $field_key
                ));
                $plugin_count += intval($count);
            }

            $stats['by_plugin'][$plugin] = $plugin_count;
        }

        // Most common custom fields
        $stats['most_common_fields'] = $wpdb->get_results(
            "SELECT meta_key, COUNT(*) as count
             FROM {$wpdb->postmeta}
             WHERE meta_key NOT LIKE '_wp_%'
             AND meta_key NOT LIKE '_edit_%'
             AND meta_key NOT LIKE '_nexus_ai_%'
             GROUP BY meta_key
             ORDER BY count DESC
             LIMIT 20"
        );

        return $stats;
    }

    /**
     * Bulk translate custom fields for multiple posts
     */
    public function bulk_translate_custom_fields($post_ids, $target_languages) {
        $results = array(
            'success' => 0,
            'failed' => 0,
            'details' => array()
        );

        foreach ($post_ids as $post_id) {
            foreach ($target_languages as $target_lang) {
                try {
                    // Check if translated post exists
                    $translated_posts = $this->db->get_translated_posts($post_id);
                    $translated_post_id = null;

                    foreach ($translated_posts as $translated_post) {
                        $post_lang = get_post_meta($translated_post['translated_post_id'], '_nexus_ai_wp_translator_language', true);
                        if ($post_lang === $target_lang) {
                            $translated_post_id = $translated_post['translated_post_id'];
                            break;
                        }
                    }

                    if ($translated_post_id) {
                        $this->translate_custom_fields($translated_post_id, $post_id, $target_lang);
                        $results['success']++;
                        $results['details'][] = array(
                            'post_id' => $post_id,
                            'target_lang' => $target_lang,
                            'status' => 'success'
                        );
                    } else {
                        $results['failed']++;
                        $results['details'][] = array(
                            'post_id' => $post_id,
                            'target_lang' => $target_lang,
                            'status' => 'no_translation',
                            'message' => 'No translated post found'
                        );
                    }
                } catch (Exception $e) {
                    $results['failed']++;
                    $results['details'][] = array(
                        'post_id' => $post_id,
                        'target_lang' => $target_lang,
                        'status' => 'error',
                        'message' => $e->getMessage()
                    );
                }
            }
        }

        return $results;
    }
}
