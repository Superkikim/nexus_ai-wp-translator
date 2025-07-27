<?php
/**
 * Admin interface for Claude Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class Claude_Translator_Admin {
    
    private static $instance = null;
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
        $this->db = Claude_Translator_Database::get_instance();
        $this->api_handler = Claude_Translator_API_Handler::get_instance();
        $this->translation_manager = Claude_Translator_Manager::get_instance();
        
        $this->init_hooks();
    }
    
    /**
     * Initialize admin hooks
     */
    private function init_hooks() {
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
        add_action('wp_ajax_claude_test_api', array($this, 'ajax_test_api'));
        add_action('wp_ajax_claude_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_claude_get_stats', array($this, 'ajax_get_stats'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Claude Translator', 'claude-translator'),
            __('Claude Translator', 'claude-translator'),
            'manage_options',
            'claude-translator',
            array($this, 'admin_page_dashboard'),
            'dashicons-translation',
            30
        );
        
        add_submenu_page(
            'claude-translator',
            __('Dashboard', 'claude-translator'),
            __('Dashboard', 'claude-translator'),
            'manage_options',
            'claude-translator',
            array($this, 'admin_page_dashboard')
        );
        
        add_submenu_page(
            'claude-translator',
            __('Settings', 'claude-translator'),
            __('Settings', 'claude-translator'),
            'manage_options',
            'claude-translator-settings',
            array($this, 'admin_page_settings')
        );
        
        add_submenu_page(
            'claude-translator',
            __('Translation Logs', 'claude-translator'),
            __('Logs', 'claude-translator'),
            'manage_options',
            'claude-translator-logs',
            array($this, 'admin_page_logs')
        );
        
        add_submenu_page(
            'claude-translator',
            __('Post Relationships', 'claude-translator'),
            __('Relationships', 'claude-translator'),
            'manage_options',
            'claude-translator-relationships',
            array($this, 'admin_page_relationships')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('claude_translator_settings', 'claude_translator_api_key');
        register_setting('claude_translator_settings', 'claude_translator_source_language');
        register_setting('claude_translator_settings', 'claude_translator_target_languages');
        register_setting('claude_translator_settings', 'claude_translator_auto_translate');
        register_setting('claude_translator_settings', 'claude_translator_throttle_limit');
        register_setting('claude_translator_settings', 'claude_translator_throttle_period');
        register_setting('claude_translator_settings', 'claude_translator_retry_attempts');
        register_setting('claude_translator_settings', 'claude_translator_cache_translations');
        register_setting('claude_translator_settings', 'claude_translator_seo_friendly_urls');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'claude-translator') === false && $hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        wp_enqueue_script(
            'claude-translator-admin',
            CLAUDE_TRANSLATOR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            CLAUDE_TRANSLATOR_VERSION,
            true
        );
        
        wp_enqueue_style(
            'claude-translator-admin',
            CLAUDE_TRANSLATOR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CLAUDE_TRANSLATOR_VERSION
        );
        
        wp_localize_script('claude-translator-admin', 'claude_translator_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('claude_translator_nonce'),
            'strings' => array(
                'testing' => __('Testing API connection...', 'claude-translator'),
                'success' => __('Success!', 'claude-translator'),
                'error' => __('Error:', 'claude-translator'),
                'translating' => __('Translating...', 'claude-translator'),
                'confirm_unlink' => __('Are you sure you want to unlink this translation?', 'claude-translator')
            )
        ));
    }
    
    /**
     * Dashboard page
     */
    public function admin_page_dashboard() {
        $stats = $this->db->get_translation_stats();
        $recent_logs = $this->db->get_translation_logs(10);
        
        include CLAUDE_TRANSLATOR_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
    
    /**
     * Settings page
     */
    public function admin_page_settings() {
        $languages = $this->translation_manager->get_available_languages();
        $api_key = get_option('claude_translator_api_key', '');
        $source_language = get_option('claude_translator_source_language', 'en');
        $target_languages = get_option('claude_translator_target_languages', array('es', 'fr', 'de'));
        $auto_translate = get_option('claude_translator_auto_translate', true);
        $throttle_limit = get_option('claude_translator_throttle_limit', 10);
        $throttle_period = get_option('claude_translator_throttle_period', 3600);
        $retry_attempts = get_option('claude_translator_retry_attempts', 3);
        $cache_translations = get_option('claude_translator_cache_translations', true);
        $seo_friendly_urls = get_option('claude_translator_seo_friendly_urls', true);
        
        include CLAUDE_TRANSLATOR_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    
    /**
     * Logs page
     */
    public function admin_page_logs() {
        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $logs = $this->db->get_translation_logs($per_page, $offset);
        
        include CLAUDE_TRANSLATOR_PLUGIN_DIR . 'templates/admin-logs.php';
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
        
        include CLAUDE_TRANSLATOR_PLUGIN_DIR . 'templates/admin-relationships.php';
    }
    
    /**
     * Add translation meta box
     */
    public function add_translation_meta_box() {
        add_meta_box(
            'claude-translator-meta-box',
            __('Claude Translator', 'claude-translator'),
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
        $post_language = get_post_meta($post->ID, '_claude_translator_language', true);
        $source_post_id = get_post_meta($post->ID, '_claude_translator_source_post', true);
        $translations = $this->db->get_post_translations($post->ID);
        $languages = $this->translation_manager->get_available_languages();
        $target_languages = get_option('claude_translator_target_languages', array());
        
        include CLAUDE_TRANSLATOR_PLUGIN_DIR . 'templates/meta-box-translation.php';
    }
    
    /**
     * Add posts columns
     */
    public function add_posts_columns($columns) {
        $columns['claude_language'] = __('Language', 'claude-translator');
        $columns['claude_translations'] = __('Translations', 'claude-translator');
        return $columns;
    }
    
    /**
     * Display posts columns
     */
    public function display_posts_columns($column, $post_id) {
        switch ($column) {
            case 'claude_language':
                $language = get_post_meta($post_id, '_claude_translator_language', true);
                if ($language) {
                    $languages = $this->translation_manager->get_available_languages();
                    echo isset($languages[$language]) ? $languages[$language] : $language;
                } else {
                    echo __('Not set', 'claude-translator');
                }
                break;
                
            case 'claude_translations':
                $translations = $this->db->get_post_translations($post_id);
                if ($translations) {
                    $count = count($translations);
                    $completed = 0;
                    foreach ($translations as $translation) {
                        if ($translation->status === 'completed') {
                            $completed++;
                        }
                    }
                    echo sprintf(__('%d/%d completed', 'claude-translator'), $completed, $count);
                } else {
                    echo __('None', 'claude-translator');
                }
                break;
        }
    }
    
    /**
     * AJAX: Test API connection
     */
    public function ajax_test_api() {
        check_ajax_referer('claude_translator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'claude-translator'));
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        // Temporarily update the API key for testing
        $old_key = get_option('claude_translator_api_key');
        update_option('claude_translator_api_key', $api_key);
        
        $result = $this->api_handler->test_api_connection();
        
        // Restore old key
        update_option('claude_translator_api_key', $old_key);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('claude_translator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'claude-translator'));
        }
        
        $settings = array(
            'api_key' => sanitize_text_field($_POST['api_key']),
            'source_language' => sanitize_text_field($_POST['source_language']),
            'target_languages' => array_map('sanitize_text_field', (array) $_POST['target_languages']),
            'auto_translate' => isset($_POST['auto_translate']),
            'throttle_limit' => intval($_POST['throttle_limit']),
            'throttle_period' => intval($_POST['throttle_period']),
            'retry_attempts' => intval($_POST['retry_attempts']),
            'cache_translations' => isset($_POST['cache_translations']),
            'seo_friendly_urls' => isset($_POST['seo_friendly_urls'])
        );
        
        foreach ($settings as $key => $value) {
            update_option('claude_translator_' . $key, $value);
        }
        
        wp_send_json_success(__('Settings saved successfully', 'claude-translator'));
    }
    
    /**
     * AJAX: Get statistics
     */
    public function ajax_get_stats() {
        check_ajax_referer('claude_translator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'claude-translator'));
        }
        
        $period = sanitize_text_field($_POST['period']) ?: '7 days';
        $stats = $this->db->get_translation_stats($period);
        
        wp_send_json_success($stats);
    }
}