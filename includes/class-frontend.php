<?php
/**
 * Frontend functionality for Nexus AI WP Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_Frontend {
    
    private static $instance = null;
    private $db;
    private $current_language;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = Nexus_AI_WP_Translator_Database::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize frontend hooks
     */
    private function init_hooks() {
        // Initialize language detection early
        add_action('init', array($this, 'init_language_detection'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // Add language selector to navigation - higher priority to ensure it runs
        add_filter('wp_nav_menu_items', array($this, 'add_language_selector_to_nav'), 10, 2);
        
        // Content filtering for language-specific display
        add_action('pre_get_posts', array($this, 'filter_posts_by_language'), 10);
        add_action('template_redirect', array($this, 'setup_language_switching'), 5);
        
        // URL rewriting for SEO-friendly URLs
        if (get_option('nexus_ai_wp_translator_seo_friendly_urls', true)) {
            add_action('init', array($this, 'add_rewrite_rules'));
            add_filter('query_vars', array($this, 'add_query_vars'));
            add_action('template_redirect', array($this, 'handle_language_redirect'), 5);
        }

        // Browser language detection and auto-redirect
        add_action('template_redirect', array($this, 'handle_browser_language_detection'), 1);
        
        // Shortcodes
        add_shortcode('nexus_ai_wp_language_switcher', array($this, 'language_switcher_shortcode'));
        
        // AJAX handlers for frontend
        add_action('wp_ajax_nexus_ai_wp_set_language_preference', array($this, 'ajax_set_language_preference'));
        add_action('wp_ajax_nopriv_nexus_ai_wp_set_language_preference', array($this, 'ajax_set_language_preference'));
        
        // Debug: Log when hooks are initialized
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: Frontend hooks initialized');
        }
    }
    
    /**
     * Handle browser language detection and auto-redirect
     */
    public function handle_browser_language_detection() {
        // Skip if already processed or if admin
        if (is_admin() || isset($_SESSION['nexus_ai_wp_translator_browser_checked'])) {
            return;
        }
        
        // Skip if language is already set in URL
        if (isset($_GET['lang'])) {
            return;
        }
        
        // Start session if not started
        if (!isset($_SESSION)) {
            session_start();
        }
        
        // Mark as checked to prevent loops
        $_SESSION['nexus_ai_wp_translator_browser_checked'] = true;
        
        $browser_lang = $this->detect_browser_language();
        $source_lang = get_option('nexus_ai_wp_translator_source_language', 'en');
        
        if ($browser_lang && $browser_lang !== $source_lang && $this->is_valid_language($browser_lang)) {
            // Redirect to browser language version
            $current_url = home_url($_SERVER['REQUEST_URI']);
            $redirect_url = add_query_arg('lang', $browser_lang, $current_url);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: Auto-redirecting to browser language: {$browser_lang}");
            }
            
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * Initialize language detection
     */
    public function init_language_detection() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [INIT] Starting language detection');
        }
        
        $this->current_language = $this->detect_current_language();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [INIT] Current language set to: ' . $this->current_language);
        }
        
        // Store language in session if not logged in
        if (!is_user_logged_in() && !isset($_SESSION)) {
            session_start();
        }
    }
    
    /**
     * Detect current user language
     */
    private function detect_current_language() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: Starting language detection');
        }
        
        // 1. Check URL parameter
        if (isset($_GET['lang'])) {
            $lang = sanitize_text_field($_GET['lang']);
            if ($this->is_valid_language($lang)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Nexus AI WP Translator: Language from URL parameter: {$lang}");
                }
                $this->store_language_preference($lang);
                return $lang;
            }
        }
        
        // 2. Check user preference (logged in users)
        if (is_user_logged_in()) {
            $user_pref = $this->db->get_user_preference(get_current_user_id());
            if ($user_pref && $this->is_valid_language($user_pref)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Nexus AI WP Translator: Language from user preference: {$user_pref}");
                }
                return $user_pref;
            }
        }
        
        // 3. Check session (non-logged in users)
        if (!is_user_logged_in()) {
            if (!isset($_SESSION)) {
                session_start();
            }
            if (isset($_SESSION['nexus_ai_wp_translator_language'])) {
                $lang = $_SESSION['nexus_ai_wp_translator_language'];
                if ($this->is_valid_language($lang)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Nexus AI WP Translator: Language from session: {$lang}");
                    }
                    return $lang;
                }
            }
        }
        
        // 4. Check cookie (fallback for session issues)
        if (isset($_COOKIE['nexus_ai_wp_translator_language'])) {
            $lang = sanitize_text_field($_COOKIE['nexus_ai_wp_translator_language']);
            if ($this->is_valid_language($lang)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Nexus AI WP Translator: Language from cookie: {$lang}");
                }
                return $lang;
            }
        }
        
        // 5. Check browser Accept-Language header
        $browser_lang = $this->detect_browser_language();
        if ($browser_lang && $this->is_valid_language($browser_lang)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: Language from browser: {$browser_lang}");
            }
            $this->store_language_preference($browser_lang);
            return $browser_lang;
        }
        
        // 6. Default to source language
        $default_lang = get_option('nexus_ai_wp_translator_source_language', 'en');
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Using default language: {$default_lang}");
        }
        return $default_lang;
    }
    
    /**
     * Detect browser language from Accept-Language header
     */
    private function detect_browser_language() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: No Accept-Language header found');
            }
            return false;
        }
        
        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: Accept-Language header: ' . $accept_language);
        }
        
        $languages = explode(',', $accept_language);
        
        foreach ($languages as $lang) {
            $lang = trim($lang);
            if (strpos($lang, ';') !== false) {
                $lang = substr($lang, 0, strpos($lang, ';'));
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: Testing browser language: ' . $lang);
            }
            
            // Try full language code first (e.g., en-US)
            if ($this->is_valid_language($lang)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Nexus AI WP Translator: Found valid full language: ' . $lang);
                }
                return $lang;
            }
            
            // Try just the language part (e.g., en from en-US)
            $lang_short = substr($lang, 0, 2);
            if ($this->is_valid_language($lang_short)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Nexus AI WP Translator: Found valid short language: ' . $lang_short);
                }
                return $lang_short;
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: No valid browser language found');
        }
        return false;
    }
    
    /**
     * Check if language is valid/supported
     */
    private function is_valid_language($lang) {
        $source_lang = get_option('nexus_ai_wp_translator_source_language', 'en');
        $target_langs = get_option('nexus_ai_wp_translator_target_languages', array());
        
        return in_array($lang, array_merge(array($source_lang), $target_langs));
    }
    
    /**
     * Store language preference
     */
    private function store_language_preference($language) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Storing language preference: {$language}");
        }
        
        if (is_user_logged_in()) {
            $this->db->store_user_preference(get_current_user_id(), $language);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: Stored in database for user: " . get_current_user_id());
            }
        } else {
            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION['nexus_ai_wp_translator_language'] = $language;
            
            // Also store in cookie as fallback
            setcookie('nexus_ai_wp_translator_language', $language, time() + (30 * DAY_IN_SECONDS), '/');
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: Stored in session and cookie");
            }
        }
    }
    
    /**
     * Get all available languages from content
     */
    public function get_available_content_languages() {
        global $wpdb;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [FRONTEND] Getting available content languages');
        }
        
        // Get source language
        $source_language = get_option('nexus_ai_wp_translator_source_language', 'en');
        $languages = array($source_language);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [FRONTEND] Source language: ' . $source_language);
        }
        
        // Get languages from translated content
        $translated_languages = $wpdb->get_col(
            "SELECT DISTINCT meta_value 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_nexus_ai_wp_translator_language' 
             AND meta_value != '' 
             AND meta_value != '{$source_language}'"
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [FRONTEND] Translated languages from meta: ' . print_r($translated_languages, true));
        }
        
        if ($translated_languages) {
            $languages = array_merge($languages, $translated_languages);
        }
        
        // Get target languages from translations table
        $target_languages = $wpdb->get_col(
            "SELECT DISTINCT target_language 
             FROM {$this->db->translations_table} 
             WHERE status = 'completed'"
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [FRONTEND] Target languages from translations table: ' . print_r($target_languages, true));
        }
        
        if ($target_languages) {
            $languages = array_merge($languages, $target_languages);
        }
        
        // Remove duplicates and sort
        $languages = array_unique($languages);
        sort($languages);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [FRONTEND] Final available languages: ' . print_r($languages, true));
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: Available content languages: ' . implode(', ', $languages));
        }
        
        return $languages;
    }
    
    /**
     * Setup language switching for posts
     */
    public function setup_language_switching() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: setup_language_switching called - is_singular: ' . (is_singular() ? 'yes' : 'no') . ', current_language: ' . ($this->current_language ?: 'EMPTY'));
        }
        
        if (is_singular()) {
            $this->handle_singular_content();
        } elseif (is_home() || is_archive()) {
            $this->handle_archive_content();
        }
    }
    
    /**
     * Handle singular content (posts, pages)
     */
    private function handle_singular_content() {
        global $post;
        if (!$post) return;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Handling singular content - Post ID: {$post->ID}, Title: {$post->post_title}");
        }
        
        $source_language = get_option('nexus_ai_wp_translator_source_language', 'en');
        $post_language = get_post_meta($post->ID, '_nexus_ai_wp_translator_language', true) ?: $source_language;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Post language: {$post_language}, Current language: {$this->current_language}");
        }
        
        // If user wants a different language than the current post
        if ($this->current_language !== $post_language) {
            $translated_post = null;
            
            // If current post is a translation, get the source first
            $source_post_id = get_post_meta($post->ID, '_nexus_ai_wp_translator_source_post', true);
            if ($source_post_id) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Nexus AI WP Translator: Current post is a translation, source ID: {$source_post_id}");
                }
                // This is a translation, use source as base
                $translated_post = $this->get_translated_post($source_post_id, $this->current_language);
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Nexus AI WP Translator: Current post is source, looking for translation");
                }
                // This is the source, find translation
                $translated_post = $this->get_translated_post($post->ID, $this->current_language);
            }
            
            if ($translated_post && $translated_post->ID !== $post->ID) {
                // Redirect to the translated version
                $translated_url = get_permalink($translated_post->ID);
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Nexus AI WP Translator: Redirecting to translated post: {$translated_post->ID}, URL: {$translated_url}");
                }
                if ($translated_url && $translated_url !== get_permalink($post->ID)) {
                    wp_redirect($translated_url);
                    exit;
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Nexus AI WP Translator: No translation found, staying on current post (fallback)");
                }
            }
        }
    }
    
    /**
     * Handle archive content
     */
    private function handle_archive_content() {
        // For archives, we'll modify the query to show content in the preferred language
        add_action('pre_get_posts', array($this, 'filter_posts_by_language'));
    }
    
    /**
     * Filter posts by language in archives
     */
    public function filter_posts_by_language($query) {
        if (!$query->is_main_query() || is_admin()) {
            return;
        }
        
        $source_language = get_option('nexus_ai_wp_translator_source_language', 'en');
        
        // Add meta query to filter by language
        $meta_query = $query->get('meta_query') ?: array();
        
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key' => '_nexus_ai_wp_translator_language',
                'value' => $this->current_language,
                'compare' => '='
            ),
            array(
                'key' => '_nexus_ai_wp_translator_language',
                'compare' => 'NOT EXISTS'
            )
        );
        
        $query->set('meta_query', $meta_query);
    }
    
    /**
     * Get translated post
     */
    private function get_translated_post($post_id, $target_language) {
        // Check if current post is already a translation
        $source_post_id = get_post_meta($post_id, '_nexus_ai_wp_translator_source_post', true);
        if ($source_post_id) {
            $post_id = $source_post_id; // Use source post ID to find other translations
        }
        
        $translation = $this->db->get_translated_post($post_id, $target_language);
        
        if ($translation && $translation->status === 'completed') {
            return get_post($translation->translated_post_id);
        }
        
        return null;
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_script(
            'nexus-ai-wp-translator-frontend',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            NEXUS_AI_WP_TRANSLATOR_VERSION,
            true
        );
        
        wp_enqueue_style(
            'nexus-ai-wp-translator-frontend',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            NEXUS_AI_WP_TRANSLATOR_VERSION
        );
        
        wp_localize_script('nexus-ai-wp-translator-frontend', 'nexus_ai_wp_translator', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nexus_ai_wp_translator_nonce'),
            'current_language' => $this->get_current_language(),
            'source_language' => get_option('nexus_ai_wp_translator_source_language', 'en'),
            'available_languages' => $this->get_available_content_languages()
        ));
    }
    
    /**
     * Add rewrite rules for SEO-friendly URLs
     */
    public function add_rewrite_rules() {
        $target_languages = get_option('nexus_ai_wp_translator_target_languages', array());
        
        foreach ($target_languages as $lang) {
            add_rewrite_rule(
                '^' . $lang . '/(.+)/?$',
                'index.php?nexus_ai_wp_lang=' . $lang . '&name=$matches[1]',
                'top'
            );
        }
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'nexus_ai_wp_lang';
        return $vars;
    }
    
    /**
     * Handle language redirect
     */
    public function handle_language_redirect() {
        $lang = get_query_var('nexus_ai_wp_lang');
        if ($lang && $this->is_valid_language($lang)) {
            $this->current_language = $lang;
            $this->store_language_preference($lang);
        }
    }


    
    /**
     * Language switcher shortcode
     */
    public function language_switcher_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'dropdown', // dropdown, list, flags
            'show_current' => 'yes',
            'show_flags' => 'no'
        ), $atts);
        
        return $this->render_language_switcher($atts);
    }
    
    /**
     * AJAX: Set language preference
     */
    public function ajax_set_language_preference() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
        
        $language = sanitize_text_field($_POST['language']);
        
        if (!$this->is_valid_language($language)) {
            wp_send_json_error(__('Invalid language', 'nexus-ai-wp-translator'));
        }
        
        $this->store_language_preference($language);
        $this->current_language = $language;
        
        wp_send_json_success(array(
            'message' => __('Language preference saved', 'nexus-ai-wp-translator'),
            'redirect_url' => add_query_arg('lang', $language, $_SERVER['HTTP_REFERER'])
        ));
    }
    
    /**
     * Add language selector to navigation
     */
    public function add_language_selector_to_nav($items, $args) {
        // Only add to primary navigation
        if ($args->theme_location !== 'primary' && $args->theme_location !== 'main') {
            return $items;
        }
        
        $switcher = $this->render_language_switcher(array(
            'style' => 'list',
            'container_class' => 'menu-item-language-switcher'
        ));
        
        if ($switcher) {
            $items .= '<li class="menu-item menu-item-language-switcher">' . $switcher . '</li>';
        }
        
        return $items;
    }
    
    /**
     * Render language switcher
     */
    public function render_language_switcher($args = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [FRONTEND] *** RENDER_LANGUAGE_SWITCHER CALLED ***');
            error_log('Nexus AI WP Translator: [FRONTEND] Args: ' . print_r($args, true));
            error_log('Nexus AI WP Translator: [FRONTEND] Current language: ' . ($this->current_language ?: 'EMPTY'));
        }
        
        $defaults = array(
            'style' => 'dropdown',
            'show_current' => true,
            'show_flags' => false,
            'container_class' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Get all available languages from actual content
        $available_languages = $this->get_available_content_languages();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [FRONTEND] Available languages count: ' . count($available_languages));
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [FRONTEND] Available languages: ' . print_r($available_languages, true));
        }
        
        $language_names = array(
            'en' => __('English', 'nexus-ai-wp-translator'),
            'es' => __('Español', 'nexus-ai-wp-translator'),
            'fr' => __('Français', 'nexus-ai-wp-translator'),
            'de' => __('Deutsch', 'nexus-ai-wp-translator'),
            'it' => __('Italiano', 'nexus-ai-wp-translator'),
            'pt' => __('Português', 'nexus-ai-wp-translator'),
            'ru' => __('Русский', 'nexus-ai-wp-translator'),
            'ja' => __('日本語', 'nexus-ai-wp-translator'),
            'ko' => __('한국어', 'nexus-ai-wp-translator'),
            'zh' => __('中文', 'nexus-ai-wp-translator'),
            'ar' => __('العربية', 'nexus-ai-wp-translator'),
            'hi' => __('हिन्दी', 'nexus-ai-wp-translator'),
            'nl' => __('Nederlands', 'nexus-ai-wp-translator'),
            'sv' => __('Svenska', 'nexus-ai-wp-translator'),
            'da' => __('Dansk', 'nexus-ai-wp-translator'),
            'no' => __('Norsk', 'nexus-ai-wp-translator'),
            'fi' => __('Suomi', 'nexus-ai-wp-translator'),
            'pl' => __('Polski', 'nexus-ai-wp-translator'),
            'cs' => __('Čeština', 'nexus-ai-wp-translator'),
            'hu' => __('Magyar', 'nexus-ai-wp-translator')
        );
        
        if (empty($available_languages)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [FRONTEND] No available languages found');
                error_log('Nexus AI WP Translator: [FRONTEND] Returning empty string');
            }
            return '<div class="nexus-ai-wp-language-switcher-debug" style="color: red; border: 1px solid red; padding: 5px;">DEBUG: No languages found</div>';
        }
        
        ob_start();
        
        $container_class = 'nexus-ai-wp-language-switcher nexus-ai-wp-' . $args['style'];
        if ($args['container_class']) {
            $container_class .= ' ' . $args['container_class'];
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [FRONTEND] Rendering ' . $args['style'] . ' style switcher');
            error_log('Nexus AI WP Translator: [FRONTEND] Container class: ' . $container_class);
            error_log('Nexus AI WP Translator: [FRONTEND] Current language for selection: ' . ($this->current_language ?: 'NONE'));
        }
        
        if ($args['style'] === 'dropdown') {
            echo '<div class="' . esc_attr($container_class) . '">';
            echo '<select id="nexus-ai-wp-language-select" class="nexus-ai-wp-language-select" data-current-url="' . esc_url(get_permalink()) . '">';
            
            foreach ($available_languages as $lang) {
                $selected = ($lang === $this->current_language) ? 'selected' : '';
                $name = isset($language_names[$lang]) ? $language_names[$lang] : $lang;
                
                if (defined('WP_DEBUG') && WP_DEBUG && $selected) {
                    error_log('Nexus AI WP Translator: [FRONTEND] Selected language in dropdown: ' . $lang);
                }
                
                // Build URL for language switch
                $url = add_query_arg('lang', $lang, home_url($_SERVER['REQUEST_URI']));
                
                echo '<option value="' . esc_attr($lang) . '" data-url="' . esc_url($url) . '" ' . $selected . '>' . esc_html($name) . '</option>';
            }
            
            echo '</select>';
            echo '</div>';
        } else {
            echo '<div class="' . esc_attr($container_class) . '">';
            echo '<ul class="nexus-ai-wp-language-list">';
            
            foreach ($available_languages as $lang) {
                $class = ($lang === $this->current_language) ? 'current' : '';
                $name = isset($language_names[$lang]) ? $language_names[$lang] : $lang;
                
                if (defined('WP_DEBUG') && WP_DEBUG && $class === 'current') {
                    error_log('Nexus AI WP Translator: [FRONTEND] Current language in list: ' . $lang);
                }
                
                // Build URL for language switch
                $url = add_query_arg('lang', $lang, home_url($_SERVER['REQUEST_URI']));
                
                echo '<li class="' . esc_attr($class) . '">';
                echo '<a href="' . esc_url($url) . '" data-lang="' . esc_attr($lang) . '">' . esc_html($name) . '</a>';
                echo '</li>';
            }
            
            echo '</ul>';
            echo '</div>';
        }
        
        $output = ob_get_clean();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [FRONTEND] *** OUTPUT GENERATED ***');
            error_log('Nexus AI WP Translator: [FRONTEND] Output length: ' . strlen($output));
            if (strlen($output) > 0) {
                error_log('Nexus AI WP Translator: [FRONTEND] Output preview: ' . substr($output, 0, 200) . '...');
            }
        }
        
        return $output;
    }
    
    /**
     * Get available languages for current post
     */
    public function get_post_languages($post_id) {
        $translations = $this->db->get_post_translations($post_id);
        $languages = array();
        
        // Add source language
        $source_lang = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');
        $languages[$source_lang] = $post_id;
        
        // Add translations
        foreach ($translations as $translation) {
            if ($translation->status === 'completed') {
                if ($translation->source_post_id == $post_id) {
                    $languages[$translation->target_language] = $translation->translated_post_id;
                } else {
                    $languages[$translation->source_language] = $translation->source_post_id;
                }
            }
        }
        
        return $languages;
    }
    
    /**
     * Get current language (public method)
     */
    public function get_current_language() {
        return $this->current_language ?: get_option('nexus_ai_wp_translator_source_language', 'en');
    }
}