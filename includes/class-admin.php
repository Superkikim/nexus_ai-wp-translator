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
        add_action('wp_ajax_nexus_ai_wp_bulk_set_language', array($this, 'ajax_bulk_set_language'));
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
        add_action('wp_ajax_nexus_ai_wp_clear_translation_cache', array($this, 'ajax_clear_translation_cache'));
        add_action('wp_ajax_nexus_ai_wp_bulk_link_posts', array($this, 'ajax_bulk_link_posts'));
        add_action('wp_ajax_nexus_ai_wp_bulk_unlink_posts', array($this, 'ajax_bulk_unlink_posts'));
        add_action('wp_ajax_nexus_ai_wp_bulk_delete_posts', array($this, 'ajax_bulk_delete_posts'));
        add_action('wp_ajax_nexus_ai_wp_bulk_clear_cache_posts', array($this, 'ajax_bulk_clear_cache_posts'));
        add_action('wp_ajax_nexus_ai_wp_resume_translation', array($this, 'ajax_resume_translation'));
        add_action('wp_ajax_nexus_ai_wp_get_progress', array($this, 'ajax_get_progress'));
        add_action('wp_ajax_nexus_ai_wp_get_quality_details', array($this, 'ajax_get_quality_details'));
        
        // Translation AJAX handlers (from translation manager)
        if ($this->translation_manager) {
            add_action('wp_ajax_nexus_ai_wp_translate_post', array($this->translation_manager, 'ajax_translate_post'));
            add_action('wp_ajax_nexus_ai_wp_unlink_translation', array($this->translation_manager, 'ajax_unlink_translation'));
            add_action('wp_ajax_nexus_ai_wp_get_translation_status', array($this->translation_manager, 'ajax_get_translation_status'));

        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [ADMIN] AJAX handlers registered: test_api, get_models, save_settings, translate_post, unlink_translation, get_translation_status');
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
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_auto_redirect');
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_save_as_draft');
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

        // Add bulk actions interface
        $output = '<div class="nexus-ai-wp-bulk-actions-container">';
        $output .= '<div class="nexus-ai-wp-bulk-actions">';
        $output .= '<label for="nexus-ai-wp-bulk-action-' . $post_type . '">' . __('Actions for selected items:', 'nexus-ai-wp-translator') . '</label>';
        $output .= '<select id="nexus-ai-wp-bulk-action-' . $post_type . '" class="nexus-ai-wp-bulk-action-select" data-post-type="' . $post_type . '">';
        $output .= '<option value="">' . __('Select Action', 'nexus-ai-wp-translator') . '</option>';
        $output .= '<option value="translate">' . __('Translate', 'nexus-ai-wp-translator') . '</option>';
        $output .= '<option value="set_language">' . __('Set Language', 'nexus-ai-wp-translator') . '</option>';
        $output .= '<option value="link">' . __('Link', 'nexus-ai-wp-translator') . '</option>';
        $output .= '<option value="unlink">' . __('Unlink', 'nexus-ai-wp-translator') . '</option>';
        $output .= '<option value="delete">' . __('Delete', 'nexus-ai-wp-translator') . '</option>';
        $output .= '<option value="clear_cache">' . __('Clear Cache', 'nexus-ai-wp-translator') . '</option>';
        $output .= '</select>';
        $output .= '<button type="button" class="button nexus-ai-wp-bulk-action-apply" data-post-type="' . $post_type . '" disabled>';
        $output .= __('Apply', 'nexus-ai-wp-translator');
        $output .= '</button>';
        $output .= '<span class="nexus-ai-wp-bulk-selection-count">0 ' . __('items selected', 'nexus-ai-wp-translator') . '</span>';
        $output .= '</div>';
        $output .= '</div>';

        $output .= '<table class="wp-list-table widefat fixed striped">';
        $output .= '<thead>';
        $output .= '<tr>';
        $output .= '<th><input type="checkbox" id="select-all-' . $post_type . '" class="select-all-checkbox"></th>';
        $output .= '<th>' . __('Title', 'nexus-ai-wp-translator') . '</th>';
        $output .= '<th>' . __('Language', 'nexus-ai-wp-translator') . '</th>';
        $output .= '<th>' . __('Translations', 'nexus-ai-wp-translator') . '</th>';
        $output .= '<th>' . __('Quality', 'nexus-ai-wp-translator') . '</th>';
        $output .= '<th>' . __('Actions', 'nexus-ai-wp-translator') . '</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody>';
        
        foreach ($posts as $post) {
            $post_language = get_post_meta($post->ID, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');
            $translations = $this->db->get_post_translations($post->ID);
            $translation_count = count($translations);
            
            $output .= '<tr>';
            $output .= '<td><input type="checkbox" class="select-post-checkbox" data-post-id="' . $post->ID . '" data-language="' . esc_attr($post_language) . '"></td>';
            $output .= '<td>';
            $output .= '<strong><a href="' . get_edit_post_link($post->ID) . '">' . esc_html($post->post_title) . '</a></strong>';
            $output .= '<br><small>ID: ' . $post->ID . ' | ' . get_the_date('Y-m-d H:i', $post->ID) . '</small>';
            $output .= '</td>';
            $output .= '<td><code>' . esc_html($post_language) . '</code></td>';
            $output .= '<td>';
            if ($translation_count > 0) {
                $completed = 0;
                foreach ($translations as $translation) {
                    if ($translation->status === 'completed') {
                        $completed++;
                    }
                }
                $output .= sprintf(__('%d/%d completed', 'nexus-ai-wp-translator'), $completed, $translation_count);
            } else {
                $output .= __('None', 'nexus-ai-wp-translator');
            }
            $output .= '</td>';

            // Quality assessment column
            $output .= '<td>';
            $quality_assessment = get_post_meta($post->ID, '_nexus_ai_wp_translator_quality_assessment', true);
            if ($quality_assessment && is_array($quality_assessment)) {
                $grade = $quality_assessment['grade'];
                $score = $quality_assessment['overall_score'];
                $grade_class = $this->get_quality_grade_class($grade);

                $output .= '<div class="nexus-ai-wp-quality-badge ' . $grade_class . '" title="Quality Score: ' . $score . '%">';
                $output .= '<span class="grade">' . esc_html($grade) . '</span>';
                $output .= '<span class="score">' . $score . '%</span>';
                $output .= '</div>';

                if (!empty($quality_assessment['issues'])) {
                    $output .= '<button type="button" class="button button-small nexus-ai-wp-quality-details" ';
                    $output .= 'data-post-id="' . $post->ID . '" ';
                    $output .= 'title="' . __('View quality details', 'nexus-ai-wp-translator') . '">';
                    $output .= __('Details', 'nexus-ai-wp-translator');
                    $output .= '</button>';
                }
            } else {
                $output .= '<span class="nexus-ai-wp-no-quality">' . __('N/A', 'nexus-ai-wp-translator') . '</span>';
            }
            $output .= '</td>';

            $output .= '<td>';

            // Check for resumable translations
            $resumable_languages = $this->get_resumable_translations($post->ID);

            if (!empty($resumable_languages)) {
                $output .= '<button type="button" class="button button-secondary resume-translation-btn" ';
                $output .= 'data-post-id="' . $post->ID . '" ';
                $output .= 'data-post-title="' . esc_attr($post->post_title) . '" ';
                $output .= 'data-resumable-languages="' . esc_attr(implode(',', $resumable_languages)) . '" ';
                $output .= 'title="' . sprintf(__('Resume failed translations to: %s', 'nexus-ai-wp-translator'), implode(', ', $resumable_languages)) . '">';
                $output .= __('Resume', 'nexus-ai-wp-translator');
                $output .= '</button> ';
            }

            $output .= '<button type="button" class="button button-primary translate-post-btn" ';
            $output .= 'data-post-id="' . $post->ID . '" ';
            $output .= 'data-post-title="' . esc_attr($post->post_title) . '">';
            $output .= __('Translate', 'nexus-ai-wp-translator');
            $output .= '</button> ';

            $output .= '<button type="button" class="button add-to-queue-btn" ';
            $output .= 'data-post-id="' . $post->ID . '" ';
            $output .= 'data-post-title="' . esc_attr($post->post_title) . '" ';
            $output .= 'title="' . __('Add to translation queue', 'nexus-ai-wp-translator') . '">';
            $output .= __('Queue', 'nexus-ai-wp-translator');
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
        $languages = $this->translation_manager->get_available_languages();
        $api_key = get_option('nexus_ai_wp_translator_api_key', '');
        $selected_model = get_option('nexus_ai_wp_translator_model', 'claude-3-5-sonnet-20241022');
        $source_language = get_option('nexus_ai_wp_translator_source_language', 'en');
        $target_languages_raw = get_option('nexus_ai_wp_translator_target_languages', array('es', 'fr', 'de'));
        
        // Ensure target_languages is an array (fix for string conversion issue)
        if (is_string($target_languages_raw)) {
            // Handle serialized string or comma-separated values
            if (is_serialized($target_languages_raw)) {
                $target_languages = maybe_unserialize($target_languages_raw);
            } else {
                // Fallback: split by comma if it's a comma-separated string
                $target_languages = array_map('trim', explode(',', $target_languages_raw));
            }
        } else {
            $target_languages = $target_languages_raw;
        }

        // Ensure we have an array
        if (!is_array($target_languages)) {
            $target_languages = array('es', 'fr', 'de'); // fallback to default
        }
        $auto_redirect = get_option('nexus_ai_wp_translator_auto_redirect', true);
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
        $auto_redirect = isset($_POST['nexus_ai_wp_translator_auto_redirect']) ? true : false;
        $save_as_draft = isset($_POST['nexus_ai_wp_translator_save_as_draft']) ? true : false;
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
            'auto_redirect' => $auto_redirect,
            'save_as_draft' => $save_as_draft,
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

    /**
     * AJAX: Clear translation cache
     */
    public function ajax_clear_translation_cache() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        // Clear all translation cache by deleting all transients with our prefix
        global $wpdb;
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_nexus_ai_wp_translation_%'
             OR option_name LIKE '_transient_timeout_nexus_ai_wp_translation_%'"
        );

        wp_send_json_success(sprintf(
            __('Cleared %d cached translations', 'nexus-ai-wp-translator'),
            $deleted / 2 // Divide by 2 because each transient has a timeout entry
        ));
    }

    /**
     * AJAX: Bulk link posts
     */
    public function ajax_bulk_link_posts() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $source_post_id = intval($_POST['source_post_id']);
        $post_ids = array_map('intval', $_POST['post_ids']);

        if (!$source_post_id || empty($post_ids)) {
            wp_send_json_error(__('Invalid post IDs provided', 'nexus-ai-wp-translator'));
        }

        $linked_count = 0;
        $errors = array();

        foreach ($post_ids as $post_id) {
            if ($post_id === $source_post_id) {
                continue; // Skip source post
            }

            $target_lang = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true);
            if (!$target_lang) {
                $errors[] = sprintf(__('Post %d has no language set', 'nexus-ai-wp-translator'), $post_id);
                continue;
            }

            // Create translation relationship
            $result = $this->db->save_translation_relationship($source_post_id, $post_id, $target_lang, 'completed');

            if ($result) {
                // Update post meta
                update_post_meta($post_id, '_nexus_ai_wp_translator_source_post', $source_post_id);
                update_post_meta($post_id, '_nexus_ai_wp_translator_translation_date', current_time('mysql'));
                $linked_count++;
            } else {
                $errors[] = sprintf(__('Failed to link post %d', 'nexus-ai-wp-translator'), $post_id);
            }
        }

        $message = sprintf(__('Successfully linked %d posts', 'nexus-ai-wp-translator'), $linked_count);
        if (!empty($errors)) {
            $message .= '. ' . __('Errors: ', 'nexus-ai-wp-translator') . implode(', ', $errors);
        }

        wp_send_json_success($message);
    }

    /**
     * AJAX: Bulk unlink posts
     */
    public function ajax_bulk_unlink_posts() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $post_ids = array_map('intval', $_POST['post_ids']);

        if (empty($post_ids)) {
            wp_send_json_error(__('No post IDs provided', 'nexus-ai-wp-translator'));
        }

        $unlinked_count = 0;

        foreach ($post_ids as $post_id) {
            // Remove all translation relationships for this post
            $result = $this->db->delete_translation_relationships($post_id);

            if ($result) {
                // Remove meta fields
                delete_post_meta($post_id, '_nexus_ai_wp_translator_source_post');
                delete_post_meta($post_id, '_nexus_ai_wp_translator_translation_date');
                $unlinked_count++;
            }
        }

        wp_send_json_success(sprintf(
            __('Successfully unlinked %d posts', 'nexus-ai-wp-translator'),
            $unlinked_count
        ));
    }

    /**
     * AJAX: Bulk delete posts
     */
    public function ajax_bulk_delete_posts() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('delete_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $post_ids = array_map('intval', $_POST['post_ids']);

        if (empty($post_ids)) {
            wp_send_json_error(__('No post IDs provided', 'nexus-ai-wp-translator'));
        }

        $deleted_count = 0;

        foreach ($post_ids as $post_id) {
            // First unlink the post
            $this->db->delete_translation_relationships($post_id);

            // Then delete the post
            $result = wp_delete_post($post_id, true);

            if ($result) {
                $deleted_count++;
            }
        }

        wp_send_json_success(sprintf(
            __('Successfully deleted %d posts', 'nexus-ai-wp-translator'),
            $deleted_count
        ));
    }

    /**
     * AJAX: Bulk clear cache for posts
     */
    public function ajax_bulk_clear_cache_posts() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $post_ids = array_map('intval', $_POST['post_ids']);

        if (empty($post_ids)) {
            wp_send_json_error(__('No post IDs provided', 'nexus-ai-wp-translator'));
        }

        $cleared_count = 0;

        foreach ($post_ids as $post_id) {
            // Clear translation cache for this post
            $this->api_handler->clear_post_translation_cache($post_id);
            $cleared_count++;
        }

        wp_send_json_success(sprintf(
            __('Successfully cleared cache for %d posts', 'nexus-ai-wp-translator'),
            $cleared_count
        ));
    }

    /**
     * Get resumable translations for a post
     */
    private function get_resumable_translations($post_id) {
        $resumable_languages = array();
        $target_languages = get_option('nexus_ai_wp_translator_target_languages', array('es', 'fr', 'de'));

        // Ensure target_languages is an array (fix for string conversion issue)
        if (is_string($target_languages)) {
            // Handle serialized string or comma-separated values
            if (is_serialized($target_languages)) {
                $target_languages = maybe_unserialize($target_languages);
            } else {
                // Fallback: split by comma if it's a comma-separated string
                $target_languages = array_map('trim', explode(',', $target_languages));
            }
        }

        // Ensure we have an array
        if (!is_array($target_languages)) {
            $target_languages = array('es', 'fr', 'de'); // fallback to default
        }

        foreach ($target_languages as $lang) {
            if ($this->api_handler && $this->api_handler->has_partial_translation_cache($post_id, $lang)) {
                $resumable_languages[] = $lang;
            }
        }

        return $resumable_languages;
    }

    /**
     * AJAX: Resume translation
     */
    public function ajax_resume_translation() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $post_id = intval($_POST['post_id']);
        $target_languages = array_map('sanitize_text_field', $_POST['target_languages']);

        if (!$post_id || empty($target_languages)) {
            wp_send_json_error(__('Invalid parameters', 'nexus-ai-wp-translator'));
        }

        $results = array();
        $success_count = 0;
        $error_count = 0;

        foreach ($target_languages as $target_lang) {
            // Resume translation for this language
            $result = $this->api_handler->translate_post_content($post_id, $target_lang);

            if ($result['success']) {
                // Create the translated post
                $translation_result = $this->translation_manager->create_translated_post($post_id, $target_lang);

                if ($translation_result['success']) {
                    $success_count++;
                    $results[] = array(
                        'language' => $target_lang,
                        'status' => 'success',
                        'post_id' => $translation_result['translated_post_id']
                    );
                } else {
                    $error_count++;
                    $results[] = array(
                        'language' => $target_lang,
                        'status' => 'error',
                        'message' => $translation_result['message']
                    );
                }
            } else {
                $error_count++;
                $results[] = array(
                    'language' => $target_lang,
                    'status' => 'error',
                    'message' => $result['message']
                );
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Resume completed: %d successful, %d failed', 'nexus-ai-wp-translator'), $success_count, $error_count),
            'success_count' => $success_count,
            'error_count' => $error_count,
            'results' => $results
        ));
    }

    /**
     * AJAX: Get translation progress
     */
    public function ajax_get_progress() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $progress_id = sanitize_text_field($_POST['progress_id']);

        if (!$progress_id) {
            wp_send_json_error(__('Invalid progress ID', 'nexus-ai-wp-translator'));
        }

        $progress_data = $this->api_handler->get_progress($progress_id);

        if (!$progress_data) {
            wp_send_json_error(__('Progress data not found', 'nexus-ai-wp-translator'));
        }

        wp_send_json_success($progress_data);
    }

    /**
     * Get CSS class for quality grade
     */
    private function get_quality_grade_class($grade) {
        $grade_classes = array(
            'A+' => 'grade-a-plus',
            'A' => 'grade-a',
            'A-' => 'grade-a-minus',
            'B+' => 'grade-b-plus',
            'B' => 'grade-b',
            'B-' => 'grade-b-minus',
            'C+' => 'grade-c-plus',
            'C' => 'grade-c',
            'C-' => 'grade-c-minus',
            'D' => 'grade-d',
            'F' => 'grade-f'
        );

        return isset($grade_classes[$grade]) ? $grade_classes[$grade] : 'grade-unknown';
    }

    /**
     * AJAX: Get quality assessment details
     */
    public function ajax_get_quality_details() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $post_id = intval($_POST['post_id']);

        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'nexus-ai-wp-translator'));
        }

        $quality_assessment = get_post_meta($post_id, '_nexus_ai_wp_translator_quality_assessment', true);

        if (!$quality_assessment || !is_array($quality_assessment)) {
            wp_send_json_error(__('No quality assessment found', 'nexus-ai-wp-translator'));
        }

        wp_send_json_success($quality_assessment);
    }

    /**
     * AJAX: Bulk set language
     */
    public function ajax_bulk_set_language() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $post_ids = array_map('intval', $_POST['post_ids']);
        $language = sanitize_text_field($_POST['language']);

        if (empty($post_ids) || empty($language)) {
            wp_send_json_error(__('Invalid parameters', 'nexus-ai-wp-translator'));
        }

        $updated_count = 0;
        $errors = array();

        foreach ($post_ids as $post_id) {
            // Verify user can edit this post
            if (!current_user_can('edit_post', $post_id)) {
                $errors[] = sprintf(__('Permission denied for post ID %d', 'nexus-ai-wp-translator'), $post_id);
                continue;
            }

            // Set the language meta
            $result = update_post_meta($post_id, '_nexus_ai_wp_translator_language', $language);

            if ($result !== false) {
                $updated_count++;

                // Log the language change
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $post_title = get_the_title($post_id);
                    error_log("Nexus AI WP Translator: Set language '{$language}' for post '{$post_title}' (ID: {$post_id})");
                }
            } else {
                $errors[] = sprintf(__('Failed to update post ID %d', 'nexus-ai-wp-translator'), $post_id);
            }
        }

        if ($updated_count > 0) {
            $message = sprintf(
                _n(
                    'Language set successfully for %d post.',
                    'Language set successfully for %d posts.',
                    $updated_count,
                    'nexus-ai-wp-translator'
                ),
                $updated_count
            );

            if (!empty($errors)) {
                $message .= ' ' . sprintf(__('However, %d errors occurred.', 'nexus-ai-wp-translator'), count($errors));
            }

            wp_send_json_success(array(
                'message' => $message,
                'updated_count' => $updated_count,
                'errors' => $errors
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('No posts were updated.', 'nexus-ai-wp-translator'),
                'errors' => $errors
            ));
        }
    }
}
