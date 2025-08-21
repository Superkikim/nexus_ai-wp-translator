<?php
/**
 * Plugin Name: Nexus AI WP Translator
 * Plugin URI: https://your-domain.com/nexus-ai-wp-translator
 * Description: Automatically translate WordPress posts using Claude AI API with comprehensive management features
 * Version: 0.2.0-beta
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
define('NEXUS_AI_WP_TRANSLATOR_VERSION', '0.2.0-beta');
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

        // Check for version updates on every load
        add_action('plugins_loaded', array($this, 'check_version_update'));

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
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-quality-assessor.php';
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-translation-templates.php';
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-seo-optimizer.php';

        // Load manager classes that depend on base classes
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-translation-manager.php';

        // Load advanced feature classes that depend on manager classes
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-translation-scheduler.php';
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-analytics.php';
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-translation-memory.php';
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-custom-fields-translator.php';
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-workflow-manager.php';
        require_once NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'includes/class-error-handler.php';
        
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

            // Initialize translation templates
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [INIT] Initializing translation templates');
            }
            Nexus_AI_WP_Translator_Templates::get_instance();

            // Initialize SEO optimizer
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [INIT] Initializing SEO optimizer');
            }
            Nexus_AI_WP_Translator_SEO_Optimizer::get_instance();

            // Initialize translation scheduler
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [INIT] Initializing translation scheduler');
            }
            Nexus_AI_WP_Translator_Scheduler::get_instance();

            // Initialize analytics
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [INIT] Initializing analytics');
            }
            Nexus_AI_WP_Translator_Analytics::get_instance();

            // Initialize translation memory
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [INIT] Initializing translation memory');
            }
            Nexus_AI_WP_Translator_Translation_Memory::get_instance();

            // Initialize custom fields translator
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [INIT] Initializing custom fields translator');
            }
            Nexus_AI_WP_Translator_Custom_Fields_Translator::get_instance();

            // Initialize workflow manager
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [INIT] Initializing workflow manager');
            }
            Nexus_AI_WP_Translator_Workflow_Manager::get_instance();

            // Initialize error handler
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [INIT] Initializing error handler');
            }
            Nexus_AI_WP_Translator_Error_Handler::get_instance();
            
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
     * Check for version updates and handle them gracefully
     */
    public function check_version_update() {
        $installed_version = get_option('nexus_ai_wp_translator_version', '');
        $current_version = NEXUS_AI_WP_TRANSLATOR_VERSION;

        // Only run update check if versions differ
        if (!empty($installed_version) && version_compare($installed_version, $current_version, '<')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: Version update detected - From: '{$installed_version}' To: '{$current_version}'");
            }

            // Run upgrade routine without resetting existing settings
            $this->handle_version_upgrade($installed_version, $current_version);
        }
    }

    /**
     * Handle version upgrades without losing settings
     */
    private function handle_version_upgrade($from_version, $to_version) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Handling upgrade from {$from_version} to {$to_version}");
        }

        // Update database tables if needed
        Nexus_AI_WP_Translator_Database::create_tables();

        // Add any new options that might be missing (without overwriting existing ones)
        $new_options = array(
            'model' => '',
            'auto_translate' => false,
            'sync_post_status' => true,
            'translate_excerpts' => true,
            'translate_meta_fields' => array(),
            'exclude_post_types' => array(),
            'user_role_permissions' => array('administrator', 'editor')
        );

        foreach ($new_options as $key => $default_value) {
            if (false === get_option('nexus_ai_wp_translator_' . $key)) {
                add_option('nexus_ai_wp_translator_' . $key, $default_value);
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Nexus AI WP Translator: Added new option during upgrade: {$key}");
                }
            }
        }

        // Update version number
        update_option('nexus_ai_wp_translator_version', $to_version);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Upgrade completed to version {$to_version}");
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Check if this is a fresh install or an upgrade
        $installed_version = get_option('nexus_ai_wp_translator_version', '');
        $current_version = NEXUS_AI_WP_TRANSLATOR_VERSION;

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Activation - Installed: '{$installed_version}', Current: '{$current_version}'");
        }

        // Only run full activation on fresh install or major version changes
        if (empty($installed_version) || version_compare($installed_version, $current_version, '<')) {

            // Create database tables
            Nexus_AI_WP_Translator_Database::create_tables();

            // Set default options (ONLY if they don't exist - preserve existing settings)
            $default_options = array(
                'api_key' => '',
                'model' => '', // Fix: Add missing model option
                'target_languages' => array('es', 'fr', 'de'),
                'auto_translate' => false, // Fix: Add missing auto_translate option
                'throttle_limit' => 100,
                'throttle_period' => 3600,
                'retry_attempts' => 3,
                'cache_translations' => true,
                'seo_friendly_urls' => true,
                'auto_redirect' => true,
                'save_as_draft' => false,
                'sync_post_status' => true, // Fix: Add missing option
                'translate_excerpts' => true, // Fix: Add missing option
                'translate_meta_fields' => array(), // Fix: Add missing option
                'exclude_post_types' => array(), // Fix: Add missing option
                'user_role_permissions' => array('administrator', 'editor') // Fix: Add missing option
            );

            foreach ($default_options as $key => $value) {
                // Only add if option doesn't exist (preserves existing settings)
                if (false === get_option('nexus_ai_wp_translator_' . $key)) {
                    add_option('nexus_ai_wp_translator_' . $key, $value);
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Nexus AI WP Translator: Added default option: {$key}");
                    }
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Nexus AI WP Translator: Preserved existing option: {$key}");
                    }
                }
            }

            // Update version number
            update_option('nexus_ai_wp_translator_version', $current_version);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: Activation completed for version {$current_version}");
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: Skipping activation - same or newer version already installed");
            }
        }

        // Always flush rewrite rules on activation
        flush_rewrite_rules();
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
        'nexus_ai_wp_translator_target_languages',
        'nexus_ai_wp_translator_auto_translate',
        'nexus_ai_wp_translator_throttle_limit',
        'nexus_ai_wp_translator_throttle_period',
        'nexus_ai_wp_translator_retry_attempts',
        'nexus_ai_wp_translator_cache_translations',
        'nexus_ai_wp_translator_seo_friendly_urls',
        'nexus_ai_wp_translator_auto_redirect',
        'nexus_ai_wp_translator_save_as_draft',
        'nexus_ai_wp_translator_sync_post_status',
        'nexus_ai_wp_translator_translate_excerpts',
        'nexus_ai_wp_translator_translate_meta_fields',
        'nexus_ai_wp_translator_exclude_post_types',
        'nexus_ai_wp_translator_user_role_permissions',
        'nexus_ai_wp_translator_field_settings',
        'nexus_ai_wp_translator_version' // Include version tracking
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
