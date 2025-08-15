<?php
/**
 * Plugin Name: Nexus AI WP Translator
 * Plugin URI: https://your-domain.com/nexus-ai-wp-translator
 * Description: Automatically translate WordPress posts using Claude AI API with comprehensive management features
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: nexus-ai-wp-translator
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NEXUS_AI_WP_TRANSLATOR_VERSION', '1.0.0');
define('NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NEXUS_AI_WP_TRANSLATOR_PLUGIN_FILE', __FILE__);

/**
 * Main Nexus AI WP Translator Plugin Class
 */
class Nexus_AI_WP_Translator_Plugin {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Hook into WordPress
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Register uninstall hook
        register_uninstall_hook(__FILE__, 'nexus_ai_wp_translator_uninstall');
        
        // Initialize components
        add_action('init', array($this, 'init_components'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load base classes first
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-database.php';
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-settings.php';
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-api-handler.php';
        
        // Load manager classes that depend on base classes
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-translation-manager.php';
        
        // Load UI classes
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-admin.php';
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-frontend.php';
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-language-switcher.php';
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-gutenberg-block.php';
    }
    
    /**
     * Initialize plugin components
     */
    public function init_components() {
        // Prevent multiple initializations
        static $initialized = false;
        if ($initialized) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [INIT] Components already initialized, skipping (hook: ' . current_action() . ')');
            }
            return;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [INIT] Plugin components initialized (hook: ' . current_action() . ')');
        }
        
        try {
            // Initialize database
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [INIT] Initializing database');
            }
            Nexus_AI_WP_Translator_Database::get_instance();
            
            // Initialize admin interface
            if (is_admin()) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Nexus AI WP Translator: [INIT] Admin interface loaded');
                }
                Nexus_AI_WP_Translator_Admin::get_instance();
            }
            
            // Initialize frontend
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [INIT] Initializing frontend');
            }
            Nexus_AI_WP_Translator_Frontend::get_instance();
            
            // Initialize translation manager
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [INIT] Initializing translation manager');
            }
            Nexus_AI_WP_Translator_Manager::get_instance();
            
            // Initialize language switcher widget
            add_action('widgets_init', array($this, 'register_widgets'));
            
            // Initialize Gutenberg block
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [INIT] Initializing Gutenberg block');
            }
            Nexus_AI_WP_Translator_Gutenberg_Block::get_instance();
            
            // Mark as initialized only after successful completion
            $initialized = true;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [INIT] All components loaded successfully');
            }
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [INIT] Failed to initialize components: ' . $e->getMessage());
            }
            // Don't mark as initialized so it can be retried
        }
    }
    
    /**
     * Register widgets
     */
    public function register_widgets() {
        register_widget('Nexus_AI_WP_Translator_Language_Switcher_Widget');
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('nexus-ai-wp-translator', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        error_log('Nexus AI WP Translator: [ACTIVATION] Plugin activation started');
        
        // Create database tables
        Nexus_AI_WP_Translator_Database::create_tables();
        
        // Verify tables were created
        global $wpdb;
        $db_instance = Nexus_AI_WP_Translator_Database::get_instance();
        
        $tables_to_check = array(
            $db_instance->translations_table,
            $db_instance->logs_table,
            $db_instance->preferences_table
        );
        
        foreach ($tables_to_check as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            if ($table_exists) {
                error_log("Nexus AI WP Translator: [ACTIVATION] Table {$table} created successfully");
            } else {
                error_log("Nexus AI WP Translator: [ACTIVATION] ERROR: Table {$table} was not created");
            }
        }
        
        // Set default options
        $default_options = array(
            'api_key' => '',
            'model' => 'claude-3-5-sonnet-20241022',
            'source_language' => 'en',
            'target_languages' => array('es', 'fr', 'de'),
            'throttle_limit' => 100, // Increased default limit
            'throttle_period' => 3600, // 1 hour
            'retry_attempts' => 3,
            'cache_translations' => true,
            'seo_friendly_urls' => true
        );
        
        foreach ($default_options as $key => $value) {
            if (false === get_option('nexus_ai_wp_translator_' . $key)) {
                add_option('nexus_ai_wp_translator_' . $key, $value);
                error_log("Nexus AI WP Translator: [ACTIVATION] Set default option: {$key}");
            } else {
                error_log("Nexus AI WP Translator: [ACTIVATION] Option already exists: {$key}");
            }
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        error_log('Nexus AI WP Translator: [ACTIVATION] Plugin activation completed');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

/**
 * Plugin uninstall function
 */
function nexus_ai_wp_translator_uninstall() {
    // Remove all plugin options
    $options = array(
        'nexus_ai_wp_translator_api_key',
        'nexus_ai_wp_translator_model',
        'nexus_ai_wp_translator_source_language',
        'nexus_ai_wp_translator_target_languages',
        'nexus_ai_wp_translator_auto_translate',
        'nexus_ai_wp_translator_throttle_limit',
        'nexus_ai_wp_translator_throttle_period',
        'nexus_ai_wp_translator_retry_attempts',
        'nexus_ai_wp_translator_cache_translations',
        'nexus_ai_wp_translator_seo_friendly_urls'
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // Drop custom tables
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nexus_ai_wp_translations");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nexus_ai_wp_translation_logs");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nexus_ai_wp_user_preferences");
    
    // Clear any cached translations
    wp_cache_flush();
}

// Initialize plugin
Nexus_AI_WP_Translator_Plugin::get_instance();