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
        // Translation manager will be loaded lazily when needed
        $this->translation_manager = null;
    }

    /**
     * Get translation manager instance (restored for settings page)
     */
    private function get_translation_manager() {
        if ($this->translation_manager === null) {
            $this->translation_manager = Nexus_AI_WP_Translator_Manager::get_instance();
        }
        return $this->translation_manager;
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
        add_action('wp_ajax_nexus_ai_wp_bulk_detect_language', array($this, 'ajax_bulk_detect_language'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Meta box and post list modifications removed per user request

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
        add_action('wp_ajax_nexus_ai_wp_get_logs', array($this, 'ajax_get_logs'));
        add_action('wp_ajax_nexus_ai_wp_reassess_quality', array($this, 'ajax_reassess_quality'));
        add_action('wp_ajax_nexus_ai_wp_detailed_assessment', array($this, 'ajax_detailed_assessment'));

        // Anthropic status AJAX
        add_action('wp_ajax_nexus_ai_wp_get_anthropic_status', array($this, 'ajax_get_anthropic_status'));
        // Posts list (dashboard tabs)
        add_action('wp_ajax_nexus_ai_wp_get_posts_list', array($this, 'ajax_get_posts_list'));
        // Load Anthropic status helper
        if (!class_exists('NexusAIWPAnthropicStatus')) {
            require_once plugin_dir_path(__FILE__) . 'class-anthropic-status.php';
        }

        // Translation AJAX handlers (using lazy loading)
        add_action('wp_ajax_nexus_ai_wp_translate_post', array($this, 'ajax_translate_post_proxy'));
        add_action('wp_ajax_nexus_ai_wp_unlink_translation', array($this, 'ajax_unlink_translation_proxy'));
        add_action('wp_ajax_nexus_ai_wp_get_translation_status', array($this, 'ajax_get_translation_status_proxy'));

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [ADMIN] Translation manager AJAX proxy handlers registered');
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
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_target_languages');
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_use_llm_quality_assessment');
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_save_as_draft');
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_throttle_limit');
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_throttle_period');
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_retry_attempts');
        register_setting('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_cache_translations');
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

        // Load modular admin scripts
        // Core utilities first
        wp_enqueue_script(
            'nexus-ai-wp-translator-admin-core',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/admin/core/admin-core.js',
            array('jquery'),
            NEXUS_AI_WP_TRANSLATOR_VERSION,
            false
        );

        // AJAX handler
        wp_enqueue_script(
            'nexus-ai-wp-translator-ajax-handler',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/admin/core/ajax-handler.js',
            array('jquery', 'nexus-ai-wp-translator-admin-core'),
            NEXUS_AI_WP_TRANSLATOR_VERSION,
            false
        );

        // Components
        wp_enqueue_script(
            'nexus-ai-wp-translator-settings-tabs',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/admin/components/settings-tabs.js',
            array('jquery', 'nexus-ai-wp-translator-admin-core', 'nexus-ai-wp-translator-ajax-handler'),
            NEXUS_AI_WP_TRANSLATOR_VERSION,
            false
        );

        wp_enqueue_script(
            'nexus-ai-wp-translator-progress-dialog',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/admin/components/progress-dialog.js',
            array('jquery', 'nexus-ai-wp-translator-admin-core'),
            NEXUS_AI_WP_TRANSLATOR_VERSION,
            false
        );

        wp_enqueue_script(
            'nexus-ai-wp-translator-translation-manager',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/admin/components/translation-manager.js',
            array('jquery', 'nexus-ai-wp-translator-admin-core', 'nexus-ai-wp-translator-ajax-handler', 'nexus-ai-wp-translator-progress-dialog'),
            NEXUS_AI_WP_TRANSLATOR_VERSION,
            false
        );

        wp_enqueue_script(
            'nexus-ai-wp-translator-bulk-actions',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/admin/components/bulk-actions.js',
            array('jquery', 'nexus-ai-wp-translator-admin-core', 'nexus-ai-wp-translator-ajax-handler'),
            NEXUS_AI_WP_TRANSLATOR_VERSION,
            false
        );

        wp_enqueue_script(
            'nexus-ai-wp-translator-quality-assessor',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/admin/components/quality-assessor.js',
            array('jquery', 'nexus-ai-wp-translator-admin-core'),
            NEXUS_AI_WP_TRANSLATOR_VERSION,
            false
        );

        // Dashboard UI Component
        wp_enqueue_script(
            'nexus-ai-wp-translator-dashboard-ui',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/components/dashboard-ui.js',
            array('jquery', 'nexus-ai-wp-translator-admin-core'),
            NEXUS_AI_WP_TRANSLATOR_VERSION,
            false
        );

        // Meta box script removed per user request

        // Page-specific scripts
        if (strpos($hook, 'nexus-ai-wp-translator-dashboard') !== false) {
            wp_enqueue_script(
                'nexus-ai-wp-translator-dashboard-page',
                NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/pages/dashboard.js',
                array('jquery', 'nexus-ai-wp-translator-admin-core', 'nexus-ai-wp-translator-dashboard-ui'),
                NEXUS_AI_WP_TRANSLATOR_VERSION,
                false
            );

            // Tab-specific scripts for dashboard
            wp_enqueue_script(
                'nexus-ai-wp-translator-articles-tab',
                NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/tabs/articles-tab.js',
                array('jquery', 'nexus-ai-wp-translator-admin-core'),
                NEXUS_AI_WP_TRANSLATOR_VERSION,
                false
            );
        }

        if (strpos($hook, 'nexus-ai-wp-translator-settings') !== false) {
            wp_enqueue_script(
                'nexus-ai-wp-translator-settings-page',
                NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/pages/settings.js',
                array('jquery', 'nexus-ai-wp-translator-admin-core', 'nexus-ai-wp-translator-ajax-handler'),
                NEXUS_AI_WP_TRANSLATOR_VERSION,
                false
            );

            // Language settings tab script
            wp_enqueue_script(
                'nexus-ai-wp-translator-settings-languages',
                NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/tabs/settings-languages.js',
                array('jquery', 'nexus-ai-wp-translator-admin-core'),
                NEXUS_AI_WP_TRANSLATOR_VERSION,
                false
            );
        }

        // Modules
        wp_enqueue_script(
            'nexus-ai-wp-translator-dashboard',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/admin/modules/dashboard.js',
            array('jquery', 'nexus-ai-wp-translator-admin-core', 'nexus-ai-wp-translator-ajax-handler'),
            NEXUS_AI_WP_TRANSLATOR_VERSION,
            false
        );

        wp_enqueue_script(
            'nexus-ai-wp-translator-queue-manager',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/admin/modules/queue-manager.js',
            array('jquery', 'nexus-ai-wp-translator-admin-core', 'nexus-ai-wp-translator-ajax-handler'),
            NEXUS_AI_WP_TRANSLATOR_VERSION,
            false
        );

        // Main coordinator (loads last)
        $main_dependencies = array(
            'jquery',
            'nexus-ai-wp-translator-admin-core',
            'nexus-ai-wp-translator-ajax-handler',
            'nexus-ai-wp-translator-settings-tabs',
            'nexus-ai-wp-translator-progress-dialog',
            'nexus-ai-wp-translator-translation-manager',
            'nexus-ai-wp-translator-bulk-actions',
            'nexus-ai-wp-translator-quality-assessor',
            'nexus-ai-wp-translator-dashboard-ui',
            'nexus-ai-wp-translator-dashboard',
            'nexus-ai-wp-translator-queue-manager'
        );

        // Add page-specific dependencies
        if (strpos($hook, 'nexus-ai-wp-translator-dashboard') !== false) {
            $main_dependencies[] = 'nexus-ai-wp-translator-dashboard-page';
            $main_dependencies[] = 'nexus-ai-wp-translator-articles-tab';
        }

        if (strpos($hook, 'nexus-ai-wp-translator-settings') !== false) {
            $main_dependencies[] = 'nexus-ai-wp-translator-settings-page';
            $main_dependencies[] = 'nexus-ai-wp-translator-settings-languages';
        }

        wp_enqueue_script(
            'nexus-ai-wp-translator-admin-main',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/admin/admin-main.js',
            $main_dependencies,
            NEXUS_AI_WP_TRANSLATOR_VERSION,
            false
        );

        wp_enqueue_style(
            'nexus-ai-wp-translator-admin',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            NEXUS_AI_WP_TRANSLATOR_VERSION
        );

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $main_script_url = NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/admin/admin-main.js';
            $file_exists = file_exists(NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'assets/js/admin/admin-main.js') ? 'EXISTS' : 'MISSING';
            error_log('Nexus AI WP Translator: [SCRIPTS] Modular admin scripts enqueued - Main: ' . $main_script_url . ' - File: ' . $file_exists);
        }

        // Make AJAX variables available globally, not just for the external script
        // SECURITY: API key removed from JavaScript to prevent exposure in browser console
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

        // Localize to the core script so it's available to all modules
        wp_localize_script('nexus-ai-wp-translator-admin-core', 'nexus_ai_wp_translator_ajax', $ajax_data);

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
        $output .= '<option value="detect_language">' . __('Detect Language', 'nexus-ai-wp-translator') . '</option>';
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
            $post_language = get_post_meta($post->ID, '_nexus_ai_wp_translator_language', true) ?: '';
            $translations = $this->db->get_post_translations($post->ID);
            $translation_count = count($translations);

            $output .= '<tr>';
            $output .= '<td><input type="checkbox" class="select-post-checkbox" data-post-id="' . $post->ID . '" data-language="' . esc_attr($post_language) . '"></td>';
            $output .= '<td>';
            $output .= '<strong><a href="' . get_edit_post_link($post->ID) . '">' . esc_html($post->post_title) . '</a></strong>';
            $output .= '<br><small>ID: ' . $post->ID . ' | ' . get_the_date('Y-m-d H:i', $post->ID) . '</small>';
            $output .= '</td>';
            $output .= '<td>';
            if (!empty($post_language)) {
                $output .= '<code>' . esc_html($post_language) . '</code>';
            } else {
                $output .= '<span class="nexus-ai-wp-no-language">' . __('Not set', 'nexus-ai-wp-translator') . '</span>';
            }
            $output .= '</td>';
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
            if ($quality_assessment && is_array($quality_assessment) && isset($quality_assessment['overall_score'])) {
                $score = intval($quality_assessment['overall_score']);
                $grade = isset($quality_assessment['grade']) ? $quality_assessment['grade'] : $this->calculate_grade_from_score($score);
                $grade_class = $this->get_quality_grade_class($grade);

                // Determine quality level based on score
                $quality_level = '';
                if ($score >= 90) {
                    $quality_level = 'excellent';
                } elseif ($score >= 80) {
                    $quality_level = 'good';
                } elseif ($score >= 70) {
                    $quality_level = 'fair';
                } elseif ($score >= 60) {
                    $quality_level = 'poor';
                } else {
                    $quality_level = 'very-poor';
                }

                $output .= '<div class="nexus-ai-wp-quality-display nexus-ai-wp-quality-' . $quality_level . '">';
                $output .= '<span class="nexus-ai-wp-quality-grade-letter">' . esc_html($grade) . '</span>';
                $output .= '<span class="nexus-ai-wp-quality-score" data-post-id="' . $post->ID . '">' . $score . '%</span>';
                $output .= '<button type="button" class="nexus-ai-wp-quality-details button-link" data-post-id="' . $post->ID . '" title="' . esc_attr__('View detailed quality assessment', 'nexus-ai-wp-translator') . '">';
                $output .= '<span class="dashicons dashicons-chart-bar"></span>';
                $output .= '</button>';
                $output .= '</div>';
            } else {
                $output .= '<span class="nexus-ai-wp-quality-none" title="' . esc_attr__('No quality assessment available', 'nexus-ai-wp-translator') . '">—</span>';
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
        // Ensure translation manager is initialized
        $translation_manager = $this->get_translation_manager();
        if (!$translation_manager) {
            echo '<div class="notice notice-error"><p>' . __('Translation manager error', 'nexus-ai-wp-translator') . '</p></div>';
            return;
        }
        $languages = $translation_manager->get_available_languages();
        // Sort languages alphabetically by name
        asort($languages);

        $api_key = get_option('nexus_ai_wp_translator_api_key', '');
        $selected_model = get_option('nexus_ai_wp_translator_model', '');
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
        $throttle_limit = get_option('nexus_ai_wp_translator_throttle_limit', 10);
        $throttle_period = get_option('nexus_ai_wp_translator_throttle_period', 3600);
        $retry_attempts = get_option('nexus_ai_wp_translator_retry_attempts', 3);
        $cache_translations = get_option('nexus_ai_wp_translator_cache_translations', true);

        include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/admin-settings.php';
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

    // Meta box methods removed per user request

    // Column methods removed per user request

    // Display posts columns method removed per user request

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

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: API key received for test (length: ' . strlen($api_key) . ')');
        }

        // Only update stored key if a non-empty key is provided to avoid wiping existing valid keys
        if (!empty($api_key)) {
            update_option('nexus_ai_wp_translator_api_key', $api_key);
        }

        // Refresh API key in handler (will pick up stored key if none provided)
        $this->api_handler->refresh_api_key();

        // Check if we have any API key to test (either provided or stored)
        $stored_key = get_option('nexus_ai_wp_translator_api_key', '');
        if (empty($api_key) && empty($stored_key)) {
            wp_send_json_error(__('No API key configured. Please configure your API key in settings.', 'nexus-ai-wp-translator'));
            return;
        }

        $result = $this->api_handler->test_api_connection();

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

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: API key for models (length: ' . strlen($api_key) . ')');
        }

        // Only update stored key if a non-empty key is provided
        if (!empty($api_key)) {
            update_option('nexus_ai_wp_translator_api_key', $api_key);
        }

        // Refresh API key in handler (uses stored key if none provided)
        $this->api_handler->refresh_api_key();

        $result = $this->api_handler->get_available_models();

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: Get models result: ' . print_r($result, true));
        }

        wp_send_json($result);
    }

    /**
     * AJAX: Save settings (idempotent, non-destructive)
     * Only updates keys explicitly provided in POST.
     */
    public function ajax_save_settings() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: Saving settings via AJAX (non-destructive)');
        }

        $updated_any = false;
        $errors = array();

        // Map of POST field => [option_key, sanitizer, validator]
        $fields = array(
            'nexus_ai_wp_translator_api_key' => array('api_key', 'sanitize_text_field', null),
            'nexus_ai_wp_translator_model' => array('model', 'sanitize_text_field', null),

            'nexus_ai_wp_translator_target_languages' => array('target_languages', null, function($v){
                return is_array($v) && $this->translation_manager->validate_language_codes($v);
            }),
            'nexus_ai_wp_translator_use_llm_quality_assessment' => array('use_llm_quality_assessment', function($v){ return (bool)$v; }, null),
            'nexus_ai_wp_translator_save_as_draft' => array('save_as_draft', function($v){ return (bool)$v; }, null),
            'nexus_ai_wp_translator_throttle_limit' => array('throttle_limit', 'intval', function($v){ return $v >= 1; }),
            'nexus_ai_wp_translator_throttle_period' => array('throttle_period', 'intval', function($v){ return $v >= 60; }),
            'nexus_ai_wp_translator_retry_attempts' => array('retry_attempts', 'intval', function($v){ return $v >= 1 && $v <= 10; }),
            'nexus_ai_wp_translator_cache_translations' => array('cache_translations', function($v){ return (bool)$v; }, null),
        );

        foreach ($fields as $post_key => $meta) {
            if (!isset($_POST[$post_key])) {
                continue; // Do not overwrite existing option when field not sent
            }
            list($option_key, $sanitizer, $validator) = $meta;
            $raw = $_POST[$post_key];

            // Normalize arrays (e.g., target_languages)
            if ($post_key === 'nexus_ai_wp_translator_target_languages') {
                $value = array_map('sanitize_text_field', (array)$raw);
            } else {
                $value = $sanitizer ? call_user_func($sanitizer, $raw) : $raw;
            }

            if (is_callable($validator) && !$validator($value)) {
                $errors[] = $option_key;
                continue;
            }

            update_option('nexus_ai_wp_translator_' . $option_key, $value);
            $updated_any = true;

            if (defined('WP_DEBUG') && WP_DEBUG) {
                $saved_value = get_option('nexus_ai_wp_translator_' . $option_key);
                error_log("Nexus AI WP Translator: Updated setting {$option_key} = '" . print_r($value, true) . "', verified: '" . print_r($saved_value, true) . "'");
            }
        }

        if (!empty($errors)) {
            wp_send_json_error(array('message' => __('Some fields failed validation', 'nexus-ai-wp-translator'), 'fields' => $errors));
        }

        // If nothing to update, still return success (idempotent)
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

        // TODO: Implement resume logic (placeholder)
        wp_send_json_success(__('Resume translation not implemented yet', 'nexus-ai-wp-translator'));
    }

    /**
     * AJAX: Get Anthropic service status from Statuspage API (server-side to avoid CORS)
     */
    public function ajax_get_anthropic_status() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $result = NexusAIWPAnthropicStatus::get_api_status();
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success($result);
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
     * Calculate grade letter from score
     */
    private function calculate_grade_from_score($score) {
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 75) return 'B+';
        if ($score >= 70) return 'B';
        if ($score >= 65) return 'B-';
        if ($score >= 60) return 'C+';
        if ($score >= 55) return 'C';
        if ($score >= 50) return 'C-';
        if ($score >= 40) return 'D';
        return 'F';
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

        // Check if quality assessment is enabled
        $use_llm_quality = get_option('nexus_ai_wp_translator_use_llm_quality_assessment', true);

        if (!$use_llm_quality) {
            wp_send_json_error(__('Quality assessment is disabled in settings', 'nexus-ai-wp-translator'));
        }

        $quality_assessment = get_post_meta($post_id, '_nexus_ai_wp_translator_quality_assessment', true);

        if (!$quality_assessment || !is_array($quality_assessment)) {
            wp_send_json_error(__('No quality assessment found for this post', 'nexus-ai-wp-translator'));
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

        // Validate language code
        if (!$this->translation_manager->is_valid_language($language)) {
            wp_send_json_error(sprintf(__('Language code "%s" is not supported', 'nexus-ai-wp-translator'), $language));
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

    /**
     * AJAX: Bulk detect language
     */
    public function ajax_bulk_detect_language() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $post_ids = array_map('intval', $_POST['post_ids']);

        if (empty($post_ids)) {
            wp_send_json_error(__('No post IDs provided', 'nexus-ai-wp-translator'));
        }

        $detected_count = 0;
        $errors = array();

        foreach ($post_ids as $post_id) {
            // Verify user can edit this post
            if (!current_user_can('edit_post', $post_id)) {
                $errors[] = sprintf(__('Permission denied for post ID %d', 'nexus-ai-wp-translator'), $post_id);
                continue;
            }

            // Get post content for detection
            $post = get_post($post_id);
            if (!$post) {
                $errors[] = sprintf(__('Post ID %d not found', 'nexus-ai-wp-translator'), $post_id);
                continue;
            }

            // Skip if language is already set
            $existing_language = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true);
            if (!empty($existing_language)) {
                continue; // Skip posts that already have a language set
            }

            // Detect language
            $detection_result = $this->api_handler->detect_language($post->post_content . ' ' . $post->post_title);

            if ($detection_result['success']) {
                // Set the detected language
                $result = update_post_meta($post_id, '_nexus_ai_wp_translator_language', $detection_result['language']);

                if ($result !== false) {
                    $detected_count++;

                    // Log the language detection
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $post_title = get_the_title($post_id);
                        error_log("Nexus AI WP Translator: Detected language '{$detection_result['language']}' for post '{$post_title}' (ID: {$post_id})");
                    }
                } else {
                    $errors[] = sprintf(__('Failed to update post ID %d', 'nexus-ai-wp-translator'), $post_id);
                }
            } else {
                $errors[] = sprintf(__('Failed to detect language for post ID %d: %s', 'nexus-ai-wp-translator'), $post_id, $detection_result['message']);
            }
        }

        if ($detected_count > 0) {
            $message = sprintf(
                _n(
                    'Language detected successfully for %d post.',
                    'Language detected successfully for %d posts.',
                    $detected_count,
                    'nexus-ai-wp-translator'
                ),
                $detected_count
            );

            if (!empty($errors)) {
                $message .= ' ' . sprintf(__('However, %d errors occurred.', 'nexus-ai-wp-translator'), count($errors));
            }

            wp_send_json_success(array(
                'message' => $message,
                'detected_count' => $detected_count,
                'errors' => $errors
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('No languages were detected. Posts may already have languages set or detection failed.', 'nexus-ai-wp-translator'),
                'errors' => $errors
            ));
        }
    }

    /**
     * AJAX: Reassess quality for a translated post
     */
    public function ajax_reassess_quality() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $post_id = intval($_POST['post_id']);

        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'nexus-ai-wp-translator'));
        }

        // Check if quality assessment is enabled
        $use_llm_quality = get_option('nexus_ai_wp_translator_use_llm_quality_assessment', true);

        if (!$use_llm_quality) {
            wp_send_json_error(__('Quality assessment is disabled in settings', 'nexus-ai-wp-translator'));
        }

        // Find the source post for this translation
        $source_post_id = get_post_meta($post_id, '_nexus_ai_wp_translator_source_post_id', true);
        $source_lang = get_post_meta($post_id, '_nexus_ai_wp_translator_source_language', true);
        $target_lang = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true);

        if (!$source_post_id || !$source_lang || !$target_lang) {
            wp_send_json_error(__('Could not find translation relationship data', 'nexus-ai-wp-translator'));
        }

        // Perform PHP-only quality assessment for reassessment
        if (!class_exists('Nexus_AI_WP_Translator_Quality_Assessor')) {
            require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-quality-assessor.php';
        }

        $source_post = get_post($source_post_id);
        $translated_post = get_post($post_id);

        if (!$source_post || !$translated_post) {
            wp_send_json_error(__('Could not load post content', 'nexus-ai-wp-translator'));
        }

        $php_assessor = new Nexus_AI_WP_Translator_Quality_Assessor();
        $quality_assessment = $php_assessor->assess_translation_quality(
            $source_post->post_content,
            $translated_post->post_content,
            $source_lang,
            $target_lang
        );

        // Add reassessment metadata
        $quality_assessment['assessment_type'] = 'php_reassessment';
        $quality_assessment['assessment_date'] = current_time('mysql');
        $quality_assessment['post_id'] = $post_id;
        $quality_assessment['source_post_id'] = $source_post_id;

        // Update the quality assessment
        update_post_meta($post_id, '_nexus_ai_wp_translator_quality_assessment', $quality_assessment);

        wp_send_json_success(array(
            'message' => __('Quality assessment updated successfully', 'nexus-ai-wp-translator'),
            'quality_data' => $quality_assessment
        ));
    }

    /**
     * AJAX: Perform detailed quality assessment
     */
    public function ajax_detailed_assessment() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $post_id = intval($_POST['post_id']);

        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'nexus-ai-wp-translator'));
        }

        // Check if quality assessment is enabled
        $use_quality_assessment = get_option('nexus_ai_wp_translator_use_llm_quality_assessment', true);

        if (!$use_quality_assessment) {
            wp_send_json_error(__('Quality assessment is disabled in settings', 'nexus-ai-wp-translator'));
        }

        // Find the source post for this translation
        $source_post_id = get_post_meta($post_id, '_nexus_ai_wp_translator_source_post_id', true);
        $source_lang = get_post_meta($post_id, '_nexus_ai_wp_translator_source_language', true);
        $target_lang = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true);

        if (!$source_post_id || !$source_lang || !$target_lang) {
            wp_send_json_error(__('Could not find translation relationship data', 'nexus-ai-wp-translator'));
        }

        // Get the translation manager to perform detailed assessment
        if (!$this->translation_manager) {
            wp_send_json_error(__('Translation manager not available', 'nexus-ai-wp-translator'));
        }

        // Perform detailed quality assessment
        $result = $this->translation_manager->perform_detailed_quality_assessment(
            $source_post_id,
            $post_id,
            $source_lang,
            $target_lang
        );

        if (!$result['success']) {
            wp_send_json_error($result['message']);
        }

        wp_send_json_success(array(
            'message' => __('Detailed quality assessment completed', 'nexus-ai-wp-translator'),
            'quality_data' => $result['data'],
            'cost' => $result['data']['api_cost'] ?? 0.05
        ));
    }

    /**
     * AJAX: Get logs for dashboard
     */
    public function ajax_get_logs() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $action = isset($_POST['action_filter']) ? sanitize_text_field($_POST['action_filter']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        $offset = ($page - 1) * $per_page;

        if (!$this->db) {
            wp_send_json_error(__('Database connection error', 'nexus-ai-wp-translator'));
            return;
        }

        // Current DB method supports limit+offset only
        $logs = $this->db->get_translation_logs($per_page, $offset);

        // Format logs for JSON response
        $formatted_logs = array();
        foreach ($logs as $log) {
            $formatted_logs[] = array(
                'id' => $log->id,
                'post_id' => $log->post_id,
                'post_title' => $log->post_title,
                'action' => $log->action,
                'status' => $log->status,
                'message' => $log->message,
                'api_calls_count' => intval($log->api_calls_count),
                'processing_time' => floatval($log->processing_time),
                'created_at' => date('Y-m-d H:i:s', strtotime($log->created_at))
            );
        }

        wp_send_json_success(array(
            'logs' => $formatted_logs,
            'total' => count($formatted_logs),
            'page' => $page,
            'per_page' => $per_page
        ));
    }

    /**
     * AJAX: Get posts list for dashboard tabs
     */
    public function ajax_get_posts_list() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $post_type = isset($_POST['post_type']) ? sanitize_key($_POST['post_type']) : 'post';
        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;

        // Basic list for now; TODO: apply filter/search/pagination when supported
        $html = $this->render_posts_list($post_type);

        wp_send_json_success(array(
            'html' => $html,
            'pagination' => ''
        ));
    }

    /**
     * AJAX proxy methods for translation manager
     */
    public function ajax_translate_post_proxy() {
        $translation_manager = $this->get_translation_manager();
        if ($translation_manager && method_exists($translation_manager, 'ajax_translate_post')) {
            return $translation_manager->ajax_translate_post();
        }
        wp_send_json_error(array('message' => __('Translation manager not available', 'nexus-ai-wp-translator')));
    }

    public function ajax_unlink_translation_proxy() {
        $translation_manager = $this->get_translation_manager();
        if ($translation_manager && method_exists($translation_manager, 'ajax_unlink_translation')) {
            return $translation_manager->ajax_unlink_translation();
        }
        wp_send_json_error(array('message' => __('Translation manager not available', 'nexus-ai-wp-translator')));
    }

    public function ajax_get_translation_status_proxy() {
        $translation_manager = $this->get_translation_manager();
        if ($translation_manager && method_exists($translation_manager, 'ajax_get_translation_status')) {
            return $translation_manager->ajax_get_translation_status();
        }
        wp_send_json_error(array('message' => __('Translation manager not available', 'nexus-ai-wp-translator')));
    }
}
