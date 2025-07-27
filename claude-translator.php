<?php
<?php
/**
 * Plugin Name: Claude AI Translator
 * Plugin URI: https://your-domain.com/claude-translator
 * Description: Automatically translate WordPress posts using Claude AI API with comprehensive management features
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: claude-translator
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CLAUDE_TRANSLATOR_VERSION', '1.0.0');
define('CLAUDE_TRANSLATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CLAUDE_TRANSLATOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CLAUDE_TRANSLATOR_PLUGIN_FILE', __FILE__);

/**
 * Main Claude Translator Plugin Class
 */
class Claude_Translator_Plugin {
    
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
        
        // Initialize components
        add_action('init', array($this, 'init_components'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once CLAUDE_TRANSLATOR_PLUGIN_DIR . 'includes/class-database.php';
        require_once CLAUDE_TRANSLATOR_PLUGIN_DIR . 'includes/class-api-handler.php';
        require_once CLAUDE_TRANSLATOR_PLUGIN_DIR . 'includes/class-translation-manager.php';
        require_once CLAUDE_TRANSLATOR_PLUGIN_DIR . 'includes/class-admin.php';
        require_once CLAUDE_TRANSLATOR_PLUGIN_DIR . 'includes/class-frontend.php';
        require_once CLAUDE_TRANSLATOR_PLUGIN_DIR . 'includes/class-language-switcher.php';
        require_once CLAUDE_TRANSLATOR_PLUGIN_DIR . 'includes/class-settings.php';
    }
    
    /**
     * Initialize plugin components
     */
    public function init_components() {
        // Initialize database
        Claude_Translator_Database::get_instance();
        
        // Initialize admin interface
        if (is_admin()) {
            Claude_Translator_Admin::get_instance();
        }
        
        // Initialize frontend
        Claude_Translator_Frontend::get_instance();
        
        // Initialize translation manager
        Claude_Translator_Manager::get_instance();
        
        // Initialize language switcher widget
        add_action('widgets_init', array($this, 'register_widgets'));
    }
    
    /**
     * Register widgets
     */
    public function register_widgets() {
        register_widget('Claude_Translator_Language_Switcher_Widget');
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('claude-translator', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        Claude_Translator_Database::create_tables();
        
        // Set default options
        $default_options = array(
            'api_key' => '',
            'source_language' => 'en',
            'target_languages' => array('es', 'fr', 'de'),
            'auto_translate' => true,
            'throttle_limit' => 10,
            'throttle_period' => 3600, // 1 hour
            'retry_attempts' => 3,
            'cache_translations' => true,
            'seo_friendly_urls' => true
        );
        
        foreach ($default_options as $key => $value) {
            if (false === get_option('claude_translator_' . $key)) {
                add_option('claude_translator_' . $key, $value);
            }
        }
        
        // Flush rewrite rules
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

// Initialize plugin
Claude_Translator_Plugin::get_instance();