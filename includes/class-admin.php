<?php
/**
 * Admin interface for Nexus AI WP Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_Admin {
    
    private static $instance = null;
    private static $hooks_initialized = false;
    private static $script_enqueued = false;
    private $db;
    private $api_handler;
    private $translation_manager;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_dependencies();
        
        $this->init_hooks();
    }
    
    /**
     * Initialize dependencies with error checking
     */
    private function init_dependencies() {
        try {
            $this->db = Nexus_AI_WP_Translator_Database::get_instance();
            $this->api_handler = Nexus_AI_WP_Translator_API_Handler::get_instance();
            $this->translation_manager = Nexus_AI_WP_Translator_Manager::get_instance();
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: Failed to initialize admin dependencies: ' . $e->getMessage());
            }
            // Set fallback null values to prevent fatal errors
            $this->db = null;
            $this->api_handler = null;
            $this->translation_manager = null;
        }
    }
    /**
     * Initialize admin hooks
     */
    private function init_hooks() {
        if (self::$hooks_initialized) {
            return;
        }
        self::$hooks_initialized = true;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [ADMIN] Registering admin hooks and AJAX handlers');
        }
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add meta box to post edit screen
        add_action('add_meta_boxes', array($this, 'add_translation_meta_box'));
        
        // Add columns to posts list
        add_filter('manage_posts_columns', array($this, 'add_posts_columns'));
        add_filter('manage_pages_columns', array($this, 'add_posts_columns'));
        add_action('manage_posts_custom_column', array($this, 'display_posts_columns'), 10, 2);
        add_action('manage_pages_custom_column', array($this, 'display_posts_columns'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_nexus_ai_wp_test_api', array($this, 'ajax_test_api'));
        add_action('wp_ajax_nexus_ai_wp_get_models', array($this, 'ajax_get_models'));
        add_action('wp_ajax_nexus_ai_wp_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_nexus_ai_wp_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_nexus_ai_wp_cleanup_orphaned', array($this, 'ajax_cleanup_orphaned'));
        
        // Translation AJAX handlers (from translation manager)
        add_action('wp_ajax_nexus_ai_wp_translate_post', array($this->translation_manager, 'ajax_translate_post'));
        add_action('wp_ajax_nexus_ai_wp_unlink_translation', array($this->translation_manager, 'ajax_unlink_translation'));
        add_action('wp_ajax_nexus_ai_wp_get_translation_status', array($this->translation_manager, 'ajax_get_translation_status'));
        add_action('wp_ajax_nexus_ai_wp_get_auto_translation_status', array($this->translation_manager, 'ajax_get_auto_translation_status'));
        add_action('wp_ajax_nexus_ai_wp_dismiss_auto_translation', array($this->translation_manager, 'ajax_dismiss_auto_translation'));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [ADMIN] AJAX handlers registered: test_api, get_models, save_settings, translate_post, unlink_translation, get_translation_status, get_auto_translation_status, dismiss_auto_translation');
        }
        
        // Post meta box save
        add_action('save_post', array($this, 'save_translation_meta_box'));
    }
    
    /**
     * Save translation meta box data
     */
    public function save_translation_meta_box($post_id) {
        // Check if our nonce is set and verify it
        if (!isset($_POST['nexus_ai_wp_translator_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['nexus_ai_wp_translator_meta_box_nonce'], 'nexus_ai_wp_translator_meta_box')) {
            return;
        }
        
        // Check if user has permission to edit the post
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Don't save during autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Save post language
        if (isset($_POST['nexus_ai_wp_post_language'])) {
            $post_language = sanitize_text_field($_POST['nexus_ai_wp_post_language']);
            update_post_meta($post_id, '_nexus_ai_wp_translator_language', $post_language);
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Nexus AI WP Translator', 'nexus-ai-wp-translator'),
            __('Nexus AI WP Translator', 'nexus-ai-wp-translator'),
            'manage_options',
            'nexus-ai-wp-translator-dashboard',
            array($this, 'admin_page_dashboard'),
            'dashicons-translation',
            30
        );
        
        add_submenu_page(
            'nexus-ai-wp-translator-dashboard',
            __('Dashboard', 'nexus-ai-wp-translator'),
            __('Dashboard', 'nexus-ai-wp-translator'),
            'manage_options',
            'nexus-ai-wp-translator-dashboard',
            array($this, 'admin_page_dashboard')
        );
        
        add_submenu_page(
            'nexus-ai-wp-translator-dashboard',
            __('Settings', 'nexus-ai-wp-translator'),
            __('Settings', 'nexus-ai-wp-translator'),
            'manage_options',
            'nexus-ai-wp-translator-settings',
            array($this, 'admin_page_settings')
        );
        
        add_submenu_page(
            'nexus-ai-wp-translator-dashboard',
            __('Translation Logs', 'nexus-ai-wp-translator'),
            __('Logs', 'nexus-ai-wp-translator'),
            'manage_options',
            'nexus-ai-wp-translator-logs',
            array($this, 'admin_page_logs')
        );
        
        add_submenu_page(
            'nexus-ai-wp-translator-dashboard',
            __('Post Relationships', 'nexus-ai-wp-translator'),
            __('Relationships', 'nexus-ai-wp-translator'),
            'manage_options',
            'nexus-ai-wp-translator-relationships',
            array($this, 'admin_page_relationships')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_api_key');
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_model');
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_source_language');
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_target_languages');
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_auto_translate');
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_throttle_limit');
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_throttle_period');
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_retry_attempts');
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_cache_translations');
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_seo_friendly_urls');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Load on our admin pages AND post edit pages
        $load_on_hooks = array('post.php', 'post-new.php');
        $is_our_page = strpos($hook, 'nexus-ai-wp-translator') !== false;
        $is_post_page = in_array($hook, $load_on_hooks);
        
        if (!$is_our_page && !$is_post_page) {
            return;
        }
        
        // Prevent multiple enqueues
        if (self::$script_enqueued) {
            return;
        }
        self::$script_enqueued = true;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [SCRIPTS] Loading admin scripts for hook: ' . $hook . ' (our_page: ' . ($is_our_page ? 'Y' : 'N') . ', post_page: ' . ($is_post_page ? 'Y' : 'N') . ')');
        }
        
        // Enqueue jQuery first to ensure it's available
        wp_enqueue_script('jquery');
        
        // Force load admin script with high priority
        wp_enqueue_script(
            'nexus-ai-wp-translator-admin',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            NEXUS_AI_WP_TRANSLATOR_VERSION,
            false  // Load in header to ensure availability for inline scripts
        );
        
        wp_enqueue_style(
            'nexus-ai-wp-translator-admin',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            NEXUS_AI_WP_TRANSLATOR_VERSION
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $script_url = NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/admin.js';
            $file_exists = file_exists(NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'assets/js/admin.js') ? 'EXISTS' : 'MISSING';
            error_log('Nexus AI WP Translator: [SCRIPTS] admin.js enqueued - URL: ' . $script_url . ' - File: ' . $file_exists);
        }
        
        // Make AJAX variables available globally, not just for the external script
        $ajax_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nexus_ai_wp_translator_nonce'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'strings' => array(
                'testing' => __('Testing API connection...', 'nexus-ai-wp-translator'),
                'success' => __('Success!', 'nexus-ai-wp-translator'),
                'error' => __('Error:', 'nexus-ai-wp-translator'),
                'translating' => __('Translating...', 'nexus-ai-wp-translator'),
                'loading_models' => __('Loading models...', 'nexus-ai-wp-translator'),
                'confirm_unlink' => __('Are you sure you want to unlink this translation?', 'nexus-ai-wp-translator')
            )
        );
        
        wp_localize_script('nexus-ai-wp-translator-admin', 'nexus_ai_wp_translator_ajax', $ajax_data);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [SCRIPTS] AJAX variables localized - URL: ' . admin_url('admin-ajax.php'));
        }
    }
    
    /**
     * Dashboard page
     */
    public function admin_page_dashboard() {
        $stats = $this->db->get_translation_stats();
        $recent_logs = $this->db->get_translation_logs(10);
        
        include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
    
    /**
     * Settings page
     */
    public function admin_page_settings() {
        $languages = $this->translation_manager->get_available_languages();
        $api_key = get_option('nexus_ai_wp_translator_api_key', '');
        $selected_model = get_option('nexus_ai_wp_translator_model', 'claude-3-5-sonnet-20241022');
        $source_language = get_option('nexus_ai_wp_translator_source_language', 'en');
        $target_languages = get_option('nexus_ai_wp_translator_target_languages', array('es', 'fr', 'de'));
        $auto_translate = get_option('nexus_ai_wp_translator_auto_translate', true);
        $throttle_limit = get_option('nexus_ai_wp_translator_throttle_limit', 10);
        $throttle_period = get_option('nexus_ai_wp_translator_throttle_period', 3600);
        $retry_attempts = get_option('nexus_ai_wp_translator_retry_attempts', 3);
        $cache_translations = get_option('nexus_ai_wp_translator_cache_translations', true);
        $seo_friendly_urls = get_option('nexus_ai_wp_translator_seo_friendly_urls', true);
        
        include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    
    /**
     * Logs page
     */
    public function admin_page_logs() {
        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $logs = $this->db->get_translation_logs($per_page, $offset);
        
        include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/admin-logs.php';
    }
    
    /**
     * Relationships page
     */
    public function admin_page_relationships() {
        global $wpdb;
        
        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $relationships = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, 
                        sp.post_title as source_title, 
                        tp.post_title as translated_title,
                        sp.post_status as source_status,
                        tp.post_status as translated_status
                FROM {$this->db->translations_table} t
                LEFT JOIN {$wpdb->posts} sp ON t.source_post_id = sp.ID
                LEFT JOIN {$wpdb->posts} tp ON t.translated_post_id = tp.ID
                ORDER BY t.created_at DESC
                LIMIT %d OFFSET %d",
                $per_page, $offset
            )
        );
        
        include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/admin-relationships.php';
    }
    
    /**
     * Add translation meta box
     */
    public function add_translation_meta_box() {
        add_meta_box(
            'nexus-ai-wp-translator-meta-box',
            __('Nexus AI WP Translator', 'nexus-ai-wp-translator'),
            array($this, 'display_translation_meta_box'),
            array('post', 'page'),
            'side',
            'high'
        );
    }
    
    /**
     * Display translation meta box
     */
    public function display_translation_meta_box($post) {
        $post_language = get_post_meta($post->ID, '_nexus_ai_wp_translator_language', true);
        $source_post_id = get_post_meta($post->ID, '_nexus_ai_wp_translator_source_post', true);
        $translations = $this->db->get_post_translations($post->ID);
        $languages = $this->translation_manager->get_available_languages();
        
        // Get configured settings
        $source_language = get_option('nexus_ai_wp_translator_source_language', 'en');
        $target_languages = get_option('nexus_ai_wp_translator_target_languages', array('es', 'fr', 'de'));
        
        // If post language is not set, default to source language
        if (empty($post_language)) {
            $post_language = $source_language;
        }
        
        include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/meta-box-translation.php';
    }
    
    /**
     * Add posts columns
     */
    public function add_posts_columns($columns) {
        $columns['nexus_ai_wp_language'] = __('Language', 'nexus-ai-wp-translator');
        $columns['nexus_ai_wp_translations'] = __('Translations', 'nexus-ai-wp-translator');
        return $columns;
    }
    
    /**
     * Display posts columns
     */
    public function display_posts_columns($column, $post_id) {
        switch ($column) {
            case 'nexus_ai_wp_language':
                $language = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true);
                if ($language) {
                    $languages = $this->translation_manager->get_available_languages();
                    echo isset($languages[$language]) ? $languages[$language] : $language;
                } else {
                    echo __('Not set', 'nexus-ai-wp-translator');
                }
                break;
                
            case 'nexus_ai_wp_translations':
                $translations = $this->db->get_post_translations($post_id);
                if ($translations) {
                    $count = count($translations);
                    $completed = 0;
                    foreach ($translations as $translation) {
                        if ($translation->status === 'completed') {
                            $completed++;
                        }
                    }
                    echo sprintf(__('%d/%d completed', 'nexus-ai-wp-translator'), $completed, $count);
                } else {
                    echo __('None', 'nexus-ai-wp-translator');
                }
                break;
        }
    }
    
    /**
     * AJAX: Test API connection
     */
    public function ajax_test_api() {
        // First debug message to confirm function is called
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: *** ajax_test_api() FUNCTION CALLED ***');
            error_log('Nexus AI WP Translator: POST data: ' . print_r($_POST, true));
        }
        
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: Permission denied for user');
            }
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: API key received for test (length: ' . strlen($api_key) . ')');
        }
        
        // Temporarily update the API key for testing
        $old_key = get_option('nexus_ai_wp_translator_api_key');
        update_option('nexus_ai_wp_translator_api_key', $api_key);
        
        // Refresh API key in handler
        $this->api_handler->refresh_api_key();
        
        $result = $this->api_handler->test_api_connection();
        
        // Restore old key
        update_option('nexus_ai_wp_translator_api_key', $old_key);
        $this->api_handler->refresh_api_key();
        
        wp_send_json($result);
    }
    
    /**
     * AJAX: Get available models
     */
    public function ajax_get_models() {
        // First debug message to confirm function is called
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: *** ajax_get_models() FUNCTION CALLED ***');
            error_log('Nexus AI WP Translator: POST data: ' . print_r($_POST, true));
        }
        
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: Permission denied for get_models');
            }
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: API key for models (length: ' . strlen($api_key) . ')');
        }
        
        // Temporarily update the API key for testing
        $old_key = get_option('nexus_ai_wp_translator_api_key');
        update_option('nexus_ai_wp_translator_api_key', $api_key);
        
        // Refresh API key in handler
        $this->api_handler->refresh_api_key();
        
        $result = $this->api_handler->get_available_models();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: Get models result: ' . print_r($result, true));
        }
        
        // Restore old key
        update_option('nexus_ai_wp_translator_api_key', $old_key);
        $this->api_handler->refresh_api_key();
        
        wp_send_json($result);
    }
    
    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: Saving settings via AJAX');
        }
        
        // Validate and sanitize input data
        $api_key = isset($_POST['nexus_ai_wp_translator_api_key']) ? sanitize_text_field($_POST['nexus_ai_wp_translator_api_key']) : '';
        $model = isset($_POST['nexus_ai_wp_translator_model']) ? sanitize_text_field($_POST['nexus_ai_wp_translator_model']) : 'claude-3-5-sonnet-20241022';
        
        // Debug: Log what model we're trying to save
        error_log('Nexus AI WP Translator: Attempting to save model: "' . $model . '"');
        error_log('Nexus AI WP Translator: Model POST data: ' . print_r($_POST['nexus_ai_wp_translator_model'] ?? 'NOT SET', true));
        
        $source_language = isset($_POST['nexus_ai_wp_translator_source_language']) ? sanitize_text_field($_POST['nexus_ai_wp_translator_source_language']) : 'en';
        $target_languages = isset($_POST['nexus_ai_wp_translator_target_languages']) ? array_map('sanitize_text_field', (array) $_POST['nexus_ai_wp_translator_target_languages']) : array();
        $auto_translate = isset($_POST['nexus_ai_wp_translator_auto_translate']) ? true : false;
        $throttle_limit = isset($_POST['nexus_ai_wp_translator_throttle_limit']) ? intval($_POST['nexus_ai_wp_translator_throttle_limit']) : 10;
        $throttle_period = isset($_POST['nexus_ai_wp_translator_throttle_period']) ? intval($_POST['nexus_ai_wp_translator_throttle_period']) : 3600;
        $retry_attempts = isset($_POST['nexus_ai_wp_translator_retry_attempts']) ? intval($_POST['nexus_ai_wp_translator_retry_attempts']) : 3;
        $cache_translations = isset($_POST['nexus_ai_wp_translator_cache_translations']) ? true : false;
        $seo_friendly_urls = isset($_POST['nexus_ai_wp_translator_seo_friendly_urls']) ? true : false;
        
        $settings = array(
            'api_key' => $api_key,
            'model' => $model,
            'source_language' => $source_language,
            'target_languages' => $target_languages,
            'auto_translate' => $auto_translate,
            'throttle_limit' => $throttle_limit,
            'throttle_period' => $throttle_period,
            'retry_attempts' => $retry_attempts,
            'cache_translations' => $cache_translations,
            'seo_friendly_urls' => $seo_friendly_urls
        );
        
        // Validate settings
        if ($throttle_limit < 1) {
            wp_send_json_error(__('Throttle limit must be at least 1', 'nexus-ai-wp-translator'));
            return;
        }
        
        if ($throttle_period < 60) {
            wp_send_json_error(__('Throttle period must be at least 60 seconds', 'nexus-ai-wp-translator'));
            return;
        }
        
        if ($retry_attempts < 1 || $retry_attempts > 10) {
            wp_send_json_error(__('Retry attempts must be between 1 and 10', 'nexus-ai-wp-translator'));
            return;
        }
        
        foreach ($settings as $key => $value) {
            update_option('nexus_ai_wp_translator_' . $key, $value);
            
            // Verify the save worked, especially for model
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $saved_value = get_option('nexus_ai_wp_translator_' . $key);
                error_log("Nexus AI WP Translator: Updated setting {$key} = '" . print_r($value, true) . "', verified: '" . print_r($saved_value, true) . "'");
            }
        }
        
        wp_send_json_success(__('Settings saved successfully', 'nexus-ai-wp-translator'));
    }
    
    /**
     * AJAX: Get statistics
     */
    public function ajax_get_stats() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }
        
        $period = sanitize_text_field($_POST['period']) ?: '7 days';
        $stats = $this->db->get_translation_stats($period);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Clean up orphaned relationships
     */
    public function ajax_cleanup_orphaned() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }
        
        global $wpdb;
        
        // Delete relationships where source post doesn't exist
        $deleted_source = $wpdb->query("
            DELETE t FROM {$this->db->translations_table} t
            LEFT JOIN {$wpdb->posts} p ON t.source_post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        // Delete relationships where translated post doesn't exist
        $deleted_translated = $wpdb->query("
            DELETE t FROM {$this->db->translations_table} t
            LEFT JOIN {$wpdb->posts} p ON t.translated_post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        $total_deleted = $deleted_source + $deleted_translated;
        
        wp_send_json_success(sprintf(
            __('Cleaned up %d orphaned relationships', 'nexus-ai-wp-translator'),
            $total_deleted
        ));
    }
}