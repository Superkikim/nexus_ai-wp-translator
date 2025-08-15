<?php
/**
 * Admin interface for Nexus AI WP Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_Admin {
    
    private static $instance = null;
    private $db;
    private $api_handler;
    private $translation_manager;
    private $hooks_initialized = false;
    private $script_enqueued = false;
    
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
        $this->db = Nexus_AI_WP_Translator_Database::get_instance();
        $this->api_handler = Nexus_AI_WP_Translator_API_Handler::get_instance();
        $this->translation_manager = Nexus_AI_WP_Translator_Manager::get_instance();
    }
    /**
     * Initialize admin hooks
     */
    private function init_hooks() {
        if ($this->hooks_initialized) {
            return;
        }
        $this->hooks_initialized = true;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [ADMIN] Registering admin hooks and AJAX handlers');
        }
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Meta box for post language
        add_action('add_meta_boxes', array($this, 'add_language_meta_box'));
        add_action('save_post', array($this, 'save_language_meta_box'));

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
        add_action('wp_ajax_nexus_ai_wp_reset_translation_data', array($this, 'ajax_reset_translation_data'));



        // Translation AJAX handlers (from translation manager)
        if ($this->translation_manager) {
            add_action('wp_ajax_nexus_ai_wp_translate_post', array($this->translation_manager, 'ajax_translate_post'));
            add_action('wp_ajax_nexus_ai_wp_unlink_translation', array($this->translation_manager, 'ajax_unlink_translation'));
            add_action('wp_ajax_nexus_ai_wp_get_translation_status', array($this->translation_manager, 'ajax_get_translation_status'));

        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [ADMIN] AJAX handlers registered: test_api, get_models, save_settings, translate_post, unlink_translation, get_translation_status');
        }
        
        // Meta box save removed - no longer needed
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

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [SCRIPTS] Hook received: ' . $hook . ' (our_page: ' . ($is_our_page ? 'Y' : 'N') . ', post_page: ' . ($is_post_page ? 'Y' : 'N') . ')');
        }

        if (!$is_our_page && !$is_post_page) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [SCRIPTS] Skipping script load for hook: ' . $hook);
            }
            return;
        }
        
        // Prevent multiple enqueues
        if ($this->script_enqueued) {
            return;
        }
        $this->script_enqueued = true;
        
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
        if (!$this->db) {
            echo '<div class="notice notice-error"><p>' . __('Database connection error', 'nexus-ai-wp-translator') . '</p></div>';
            return;
        }

        $stats = $this->db->get_translation_stats();
        $recent_logs = $this->db->get_translation_logs(10);
        $languages = $this->translation_manager ? $this->translation_manager->get_available_languages() : array();
        $source_language = get_option('nexus_ai_wp_translator_source_language', 'en');

        include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
    
    /**
     * Render posts list for a specific post type
     */
    public function render_posts_list($post_type) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'numberposts' => 20,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($posts)) {
            return '<p>' . sprintf(__('No %s found.', 'nexus-ai-wp-translator'), $post_type) . '</p>';
        }
        
        $output = '<table class="wp-list-table widefat fixed striped">';
        $output .= '<thead>';
        $output .= '<tr>';
        $output .= '<th>' . __('Title', 'nexus-ai-wp-translator') . '</th>';
        $output .= '<th>' . __('Language', 'nexus-ai-wp-translator') . '</th>';
        $output .= '<th>' . __('Translations', 'nexus-ai-wp-translator') . '</th>';
        $output .= '<th>' . __('Actions', 'nexus-ai-wp-translator') . '</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody>';
        
        foreach ($posts as $post) {
            $post_language = get_post_meta($post->ID, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');
            $translations = $this->db->get_post_translations($post->ID);

            // Skip posts that are translations (not source posts)
            $is_translation = false;
            foreach ($translations as $translation) {
                if ($translation->translated_post_id == $post->ID) {
                    $is_translation = true;
                    break;
                }
            }

            if ($is_translation) {
                continue; // Skip translated posts, only show source posts
            }

            // Filter out translations where the translated post doesn't exist
            $valid_translations = array();
            foreach ($translations as $translation) {
                if ($translation->source_post_id == $post->ID) {
                    // This post is the source - check if translated post exists
                    $translated_post = get_post($translation->translated_post_id);
                    if ($translated_post && $translated_post->post_status === 'publish') {
                        $valid_translations[] = $translation;
                    }
                } elseif ($translation->translated_post_id == $post->ID) {
                    // This post is a translation - check if source post exists
                    $source_post = get_post($translation->source_post_id);
                    if ($source_post && $source_post->post_status === 'publish') {
                        $valid_translations[] = $translation;
                    }
                }
            }

            $translation_count = count($valid_translations);
            
            $output .= '<tr>';
            $output .= '<td>';
            $output .= '<strong><a href="' . get_edit_post_link($post->ID) . '">' . esc_html($post->post_title) . '</a></strong>';
            $output .= '<br><small>ID: ' . $post->ID . ' | ' . get_the_date('Y-m-d H:i', $post->ID) . '</small>';
            $output .= '</td>';
            $output .= '<td><code>' . esc_html($post_language) . '</code></td>';
            $output .= '<td>';
            if ($translation_count > 0) {
                $completed = 0;
                foreach ($valid_translations as $translation) {
                    if ($translation->status === 'completed') {
                        $completed++;
                    }
                }
                $output .= sprintf(__('%d/%d completed', 'nexus-ai-wp-translator'), $completed, $translation_count);
            } else {
                $output .= __('None', 'nexus-ai-wp-translator');
            }
            $output .= '</td>';
            $output .= '<td>';
            $output .= '<button type="button" class="button button-primary translate-post-btn" ';
            $output .= 'data-post-id="' . $post->ID . '" ';
            $output .= 'data-post-title="' . esc_attr($post->post_title) . '">';
            $output .= __('Translate', 'nexus-ai-wp-translator');
            $output .= '</button> ';
            $output .= '<button type="button" class="button button-secondary reset-translation-btn" ';
            $output .= 'data-post-id="' . $post->ID . '" ';
            $output .= 'data-post-title="' . esc_attr($post->post_title) . '" ';
            $output .= 'title="' . esc_attr(__('Reset all translation data for this post', 'nexus-ai-wp-translator')) . '">';
            $output .= __('Reset', 'nexus-ai-wp-translator');
            $output .= '</button> ';
            $output .= '<a href="' . get_edit_post_link($post->ID) . '" class="button">' . __('Edit', 'nexus-ai-wp-translator') . '</a>';
            $output .= '</td>';
            $output .= '</tr>';
        }
        
        $output .= '</tbody>';
        $output .= '</table>';
        
        return $output;
    }
    
    /**
     * Settings page
     */
    public function admin_page_settings() {
        if (!$this->translation_manager) {
            echo '<div class="notice notice-error"><p>' . __('Translation manager error', 'nexus-ai-wp-translator') . '</p></div>';
            return;
        }

        $message = '';

        // Handle form submission
        if (isset($_POST['submit']) && check_admin_referer('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_nonce')) {
            // Save settings
            $api_key = sanitize_text_field($_POST['nexus_ai_wp_translator_api_key']);
            $model = sanitize_text_field($_POST['nexus_ai_wp_translator_model']);
            $source_language = sanitize_text_field($_POST['nexus_ai_wp_translator_source_language']);
            $target_languages = isset($_POST['nexus_ai_wp_translator_target_languages']) ? array_map('sanitize_text_field', $_POST['nexus_ai_wp_translator_target_languages']) : array();
            $throttle_limit = intval($_POST['nexus_ai_wp_translator_throttle_limit']);
            $throttle_period = intval($_POST['nexus_ai_wp_translator_throttle_period']);
            $retry_attempts = intval($_POST['nexus_ai_wp_translator_retry_attempts']);
            $cache_translations = isset($_POST['nexus_ai_wp_translator_cache_translations']);
            $seo_friendly_urls = isset($_POST['nexus_ai_wp_translator_seo_friendly_urls']);

            // Update options
            update_option('nexus_ai_wp_translator_api_key', $api_key);
            update_option('nexus_ai_wp_translator_model', $model);
            update_option('nexus_ai_wp_translator_source_language', $source_language);
            update_option('nexus_ai_wp_translator_target_languages', $target_languages);
            update_option('nexus_ai_wp_translator_throttle_limit', $throttle_limit);
            update_option('nexus_ai_wp_translator_throttle_period', $throttle_period);
            update_option('nexus_ai_wp_translator_retry_attempts', $retry_attempts);
            update_option('nexus_ai_wp_translator_cache_translations', $cache_translations);
            update_option('nexus_ai_wp_translator_seo_friendly_urls', $seo_friendly_urls);

            $message = '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'nexus-ai-wp-translator') . '</p></div>';
        }

        $languages = $this->translation_manager->get_available_languages();
        $api_key = get_option('nexus_ai_wp_translator_api_key', '');
        $selected_model = get_option('nexus_ai_wp_translator_model', 'claude-3-5-sonnet-20241022');
        $source_language = get_option('nexus_ai_wp_translator_source_language', 'en');
        $target_languages = get_option('nexus_ai_wp_translator_target_languages', array('es', 'fr', 'de'));

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
                $default_language = get_option('nexus_ai_wp_translator_source_language', 'en');
                $languages = $this->translation_manager ? $this->translation_manager->get_available_languages() : array();

                if ($language) {
                    $language_name = isset($languages[$language]) ? $languages[$language] : $language;
                    if ($language === $default_language) {
                        echo '<span style="color: #0073aa; font-weight: 600;">' . esc_html($language_name) . '</span>';
                        echo ' <span style="color: #666; font-size: 11px;">(default)</span>';
                    } else {
                        echo '<span style="color: #d63638; font-weight: 600;">' . esc_html($language_name) . '</span>';
                        echo ' <span style="color: #666; font-size: 11px;">(custom)</span>';
                    }
                } else {
                    $default_name = isset($languages[$default_language]) ? $languages[$default_language] : $default_language;
                    echo '<span style="color: #999; font-style: italic;">' . esc_html($default_name) . '</span>';
                    echo ' <span style="color: #666; font-size: 11px;">(auto)</span>';
                }
                break;
                
            case 'nexus_ai_wp_translations':
                if ($this->db) {
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
                } else {
                    echo __('N/A', 'nexus-ai-wp-translator');
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
        
        if (!$this->api_handler) {
            wp_send_json_error(__('API handler not available', 'nexus-ai-wp-translator'));
            return;
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

        // Get all orphaned relationships before deleting them
        $orphaned_source = $wpdb->get_results("
            SELECT t.* FROM {$this->db->translations_table} t
            LEFT JOIN {$wpdb->posts} p ON t.source_post_id = p.ID
            WHERE p.ID IS NULL
        ");

        $orphaned_translated = $wpdb->get_results("
            SELECT t.* FROM {$this->db->translations_table} t
            LEFT JOIN {$wpdb->posts} p ON t.translated_post_id = p.ID
            WHERE p.ID IS NULL
        ");

        // Clean up metadata for remaining posts that had orphaned relationships
        $posts_to_clean = array();
        foreach ($orphaned_source as $rel) {
            if ($rel->translated_post_id && get_post($rel->translated_post_id)) {
                $posts_to_clean[] = $rel->translated_post_id;
            }
        }
        foreach ($orphaned_translated as $rel) {
            if ($rel->source_post_id && get_post($rel->source_post_id)) {
                $posts_to_clean[] = $rel->source_post_id;
            }
        }

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

        // Clean up metadata for posts that no longer have any relationships
        $cleaned_metadata = 0;
        foreach (array_unique($posts_to_clean) as $post_id) {
            $remaining_relations = $this->db->get_post_translations($post_id);
            if (empty($remaining_relations)) {
                // Remove translation metadata if no relationships remain
                delete_post_meta($post_id, '_nexus_ai_wp_translator_language');
                delete_post_meta($post_id, '_nexus_ai_wp_translator_source_post');
                $cleaned_metadata++;
            }
        }

        // Also clean up any posts that have translation metadata but no relationships
        $posts_with_meta = $wpdb->get_results("
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key IN ('_nexus_ai_wp_translator_language', '_nexus_ai_wp_translator_source_post')
        ");

        foreach ($posts_with_meta as $meta) {
            $post_id = $meta->post_id;
            if (get_post($post_id)) {
                $relations = $this->db->get_post_translations($post_id);
                if (empty($relations)) {
                    delete_post_meta($post_id, '_nexus_ai_wp_translator_language');
                    delete_post_meta($post_id, '_nexus_ai_wp_translator_source_post');
                    $cleaned_metadata++;
                }
            }
        }

        $total_deleted = $deleted_source + $deleted_translated;

        wp_send_json_success(sprintf(
            __('Cleaned up %d orphaned relationships and %d metadata entries', 'nexus-ai-wp-translator'),
            $total_deleted,
            $cleaned_metadata
        ));
    }

    /**
     * AJAX: Reset translation data for a specific post
     */
    public function ajax_reset_translation_data() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $post_id = intval($_POST['post_id']);

        if (!$post_id || !get_post($post_id)) {
            wp_send_json_error(__('Invalid post ID', 'nexus-ai-wp-translator'));
            return;
        }

        global $wpdb;

        // Get all relationships for this post (as source or translated)
        $relationships = $this->db->get_post_translations($post_id);
        $deleted_relationships = 0;
        $deleted_posts = 0;

        foreach ($relationships as $relationship) {
            // If this post is the source, delete the translated posts
            if ($relationship->source_post_id == $post_id) {
                $translated_post_id = $relationship->translated_post_id;

                // Delete the translated post if it exists
                if (get_post($translated_post_id)) {
                    wp_delete_post($translated_post_id, true); // Force delete
                    $deleted_posts++;
                }

                // Delete the relationship
                $wpdb->delete(
                    $this->db->translations_table,
                    array('id' => $relationship->id),
                    array('%d')
                );
                $deleted_relationships++;
            }
            // If this post is a translation, delete the relationship and reset source metadata
            elseif ($relationship->translated_post_id == $post_id) {
                // Delete the relationship
                $wpdb->delete(
                    $this->db->translations_table,
                    array('id' => $relationship->id),
                    array('%d')
                );
                $deleted_relationships++;

                // Reset source post metadata if no other translations exist
                $source_post_id = $relationship->source_post_id;
                $remaining_translations = $this->db->get_post_translations($source_post_id);
                if (empty($remaining_translations)) {
                    delete_post_meta($source_post_id, '_nexus_ai_wp_translator_language');
                    delete_post_meta($source_post_id, '_nexus_ai_wp_translator_source_post');
                }
            }
        }

        // Clean up metadata for the current post
        delete_post_meta($post_id, '_nexus_ai_wp_translator_language');
        delete_post_meta($post_id, '_nexus_ai_wp_translator_source_post');

        // Clean up any orphaned logs for this post
        $wpdb->delete(
            $this->db->logs_table,
            array('post_id' => $post_id),
            array('%d')
        );

        wp_send_json_success(sprintf(
            __('Reset complete: Deleted %d relationships, %d translated posts, and cleaned metadata', 'nexus-ai-wp-translator'),
            $deleted_relationships,
            $deleted_posts
        ));
    }

    /**
     * Add language meta box to post edit screen
     */
    public function add_language_meta_box() {
        $post_types = array('post', 'page');

        foreach ($post_types as $post_type) {
            add_meta_box(
                'nexus-ai-wp-translator-language',
                __('Post Language', 'nexus-ai-wp-translator'),
                array($this, 'display_language_meta_box'),
                $post_type,
                'side',
                'high'
            );
        }
    }

    /**
     * Display language meta box content
     */
    public function display_language_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('nexus_ai_wp_translator_language_meta_box', 'nexus_ai_wp_translator_language_nonce');

        // Get current language
        $current_language = get_post_meta($post->ID, '_nexus_ai_wp_translator_language', true);
        $default_language = get_option('nexus_ai_wp_translator_source_language', 'en');

        if (empty($current_language)) {
            $current_language = $default_language;
        }

        // Get available languages
        $languages = $this->translation_manager ? $this->translation_manager->get_available_languages() : array();

        echo '<div class="nexus-ai-wp-language-meta-box">';
        echo '<p><strong>' . __('Select the language of this content:', 'nexus-ai-wp-translator') . '</strong></p>';

        echo '<select name="nexus_ai_wp_translator_post_language" id="nexus_ai_wp_translator_post_language" style="width: 100%;">';

        foreach ($languages as $code => $name) {
            $selected = selected($current_language, $code, false);
            echo '<option value="' . esc_attr($code) . '" ' . $selected . '>' . esc_html($name) . '</option>';
        }

        echo '</select>';

        echo '<p class="description">' . __('This determines the source language for translations. Make sure to select the correct language of your content.', 'nexus-ai-wp-translator') . '</p>';

        // Show current status
        if ($current_language !== $default_language) {
            echo '<div class="notice notice-info inline" style="margin: 10px 0; padding: 8px 12px;">';
            echo '<p style="margin: 0;"><strong>' . __('Note:', 'nexus-ai-wp-translator') . '</strong> ' .
                 sprintf(__('This post is marked as %s, different from the default site language (%s).', 'nexus-ai-wp-translator'),
                         '<code>' . esc_html($languages[$current_language] ?? $current_language) . '</code>',
                         '<code>' . esc_html($languages[$default_language] ?? $default_language) . '</code>') . '</p>';
            echo '</div>';
        }

        // Show translation status
        $translations = $this->get_post_translations($post->ID);
        if (!empty($translations)) {
            echo '<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">';
            echo '<p><strong>' . __('Existing translations:', 'nexus-ai-wp-translator') . '</strong></p>';
            echo '<ul style="margin: 5px 0 0 20px;">';
            foreach ($translations as $lang => $translation_id) {
                $translation_post = get_post($translation_id);
                if ($translation_post) {
                    $edit_url = get_edit_post_link($translation_id);
                    echo '<li>' . esc_html($languages[$lang] ?? $lang) . ' - ';
                    echo '<a href="' . esc_url($edit_url) . '">' . esc_html($translation_post->post_title) . '</a>';
                    echo '</li>';
                }
            }
            echo '</ul>';
            echo '</div>';
        }

        echo '</div>';

        // Add some CSS
        echo '<style>
        .nexus-ai-wp-language-meta-box .notice.inline {
            display: block;
            margin: 10px 0;
        }
        </style>';
    }

    /**
     * Save language meta box data
     */
    public function save_language_meta_box($post_id) {
        // Check if nonce is valid
        if (!isset($_POST['nexus_ai_wp_translator_language_nonce']) ||
            !wp_verify_nonce($_POST['nexus_ai_wp_translator_language_nonce'], 'nexus_ai_wp_translator_language_meta_box')) {
            return;
        }

        // Check if user has permission to edit the post
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Don't save on autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Save the language
        if (isset($_POST['nexus_ai_wp_translator_post_language'])) {
            $language = sanitize_text_field($_POST['nexus_ai_wp_translator_post_language']);

            // Validate language code
            $available_languages = $this->translation_manager ? $this->translation_manager->get_available_languages() : array();
            if (array_key_exists($language, $available_languages)) {
                update_post_meta($post_id, '_nexus_ai_wp_translator_language', $language);

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Nexus AI WP Translator: Updated post {$post_id} language to {$language}");
                }
            }
        }
    }

    /**
     * Get translations for a post
     */
    private function get_post_translations($post_id) {
        if (!$this->db) {
            return array();
        }

        // Use the database instance directly
        $results = $this->db->get_post_translations($post_id);

        $translations = array();
        foreach ($results as $result) {
            // Check if this post is the source
            if ($result->source_post_id == $post_id) {
                $translations[$result->target_language] = $result->translated_post_id;
            } else {
                // This post is a translation, so the other post is the source
                $translations[$result->source_language] = $result->source_post_id;
            }
        }

        return $translations;
    }


}
