<?php
/**
 * Gutenberg Block for Nexus AI WP Translator Language Switcher
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_Gutenberg_Block {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register_block'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
    }
    
    /**
     * Register the Gutenberg block
     */
    public function register_block() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [BLOCK] Starting block registration');
        }
        
        if (!function_exists('register_block_type')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [BLOCK] register_block_type function not available');
            }
            return;
        }
        
        // Check if scripts and styles are properly enqueued
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $js_file = NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'assets/js/block-editor.js';
            $css_file = NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'assets/css/block-editor.css';
            error_log('Nexus AI WP Translator: [BLOCK] JS file exists: ' . (file_exists($js_file) ? 'YES' : 'NO') . ' - ' . $js_file);
            error_log('Nexus AI WP Translator: [BLOCK] CSS file exists: ' . (file_exists($css_file) ? 'YES' : 'NO') . ' - ' . $css_file);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [BLOCK] About to register block type');
        }
        
        $block_registered = register_block_type('nexus-ai-wp-translator/language-switcher', array(
            'attributes' => array(
                'style' => array(
                    'type' => 'string',
                    'default' => 'dropdown'
                ),
                'showFlags' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'alignment' => array(
                    'type' => 'string',
                    'default' => 'left'
                )
            ),
            'render_callback' => array($this, 'render_block'),
            'editor_script' => 'nexus-ai-wp-translator-block-editor',
            'script' => 'nexus-ai-wp-translator-frontend',
            'editor_style' => 'nexus-ai-wp-translator-block-editor-style',
            'style' => 'nexus-ai-wp-translator-frontend'
        ));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [BLOCK] Block registered successfully');
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [BLOCK] Block registration result: ' . ($block_registered ? 'SUCCESS' : 'FAILED'));
            error_log('Nexus AI WP Translator: [BLOCK] Registered block type: nexus-ai-wp-translator/language-switcher');
        }
        
        // Also register a simpler language selector block for easier use
        register_block_type('nexus-ai-wp-translator/simple-language-selector', array(
            'attributes' => array(
                'showLabels' => array(
                    'type' => 'boolean',
                    'default' => true
                )
            ),
            'render_callback' => array($this, 'render_simple_selector_block'),
            'editor_script' => 'nexus-ai-wp-translator-block-editor',
            'style' => 'nexus-ai-wp-translator-frontend'
        ));
    }
    
    /**
     * Render simple language selector block
     */
    public function render_simple_selector_block($attributes) {
        if (!class_exists('Nexus_AI_WP_Translator_Frontend')) {
            return '<p>' . __('Language selector temporarily unavailable.', 'nexus-ai-wp-translator') . '</p>';
        }
        
        $frontend = Nexus_AI_WP_Translator_Frontend::get_instance();
        
        $args = array(
            'style' => 'list',
            'show_flags' => false,
            'show_current' => $attributes['showLabels'] ?? true,
            'container_class' => 'nexus-ai-wp-simple-language-selector'
        );
        
        return $frontend->render_language_switcher($args);
    }
    
    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [BLOCK] Starting to enqueue block editor assets');
            error_log('Nexus AI WP Translator: [BLOCK] Current screen: ' . (function_exists('get_current_screen') ? get_current_screen()->id ?? 'unknown' : 'no screen function'));
        }
        
        // Enqueue the block editor script
        $script_enqueued = wp_enqueue_script(
            'nexus-ai-wp-translator-block-editor',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/block-editor.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            NEXUS_AI_WP_TRANSLATOR_VERSION,
            true
        );
        
        $style_enqueued = wp_enqueue_style(
            'nexus-ai-wp-translator-block-editor-style',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/css/block-editor.css',
            array('wp-edit-blocks'),
            NEXUS_AI_WP_TRANSLATOR_VERSION
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [BLOCK] Script enqueued: ' . ($script_enqueued ? 'SUCCESS' : 'FAILED'));
            error_log('Nexus AI WP Translator: [BLOCK] Style enqueued: ' . ($style_enqueued ? 'SUCCESS' : 'FAILED'));
            error_log('Nexus AI WP Translator: [BLOCK] Script URL: ' . NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/block-editor.js');
            error_log('Nexus AI WP Translator: [BLOCK] Style URL: ' . NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/css/block-editor.css');
        }
        
        // Also enqueue frontend script for the block
        wp_enqueue_script(
            'nexus-ai-wp-translator-frontend',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            NEXUS_AI_WP_TRANSLATOR_VERSION,
            true
        );
        
        // Localize script for the block editor
        wp_localize_script('nexus-ai-wp-translator-block-editor', 'nexusAiWpTranslatorBlock', array(
            'title' => __('Language Switcher', 'nexus-ai-wp-translator'),
            'description' => __('Display a language switcher for Nexus AI WP Translator', 'nexus-ai-wp-translator'),
            'category' => 'widgets',
            'keywords' => array(
                __('language', 'nexus-ai-wp-translator'),
                __('translation', 'nexus-ai-wp-translator'),
                __('switcher', 'nexus-ai-wp-translator')
            )
        ));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [BLOCK] Block editor assets enqueued and localized');
        }
    }
    
    /**
     * Render the block on frontend
     */
    public function render_block($attributes) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [BLOCK] *** RENDER_BLOCK CALLED ***');
            error_log('Nexus AI WP Translator: [BLOCK] Attributes received: ' . print_r($attributes, true));
            error_log('Nexus AI WP Translator: [BLOCK] Is admin: ' . (is_admin() ? 'YES' : 'NO'));
            error_log('Nexus AI WP Translator: [BLOCK] Current URL: ' . $_SERVER['REQUEST_URI'] ?? 'unknown');
        }
        
        if (!class_exists('Nexus_AI_WP_Translator_Frontend')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [BLOCK] Frontend class not available');
            }
            return '<p>' . __('Language switcher temporarily unavailable.', 'nexus-ai-wp-translator') . '</p>';
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [BLOCK] Frontend class available, getting instance');
        }
        
        $frontend = Nexus_AI_WP_Translator_Frontend::get_instance();
        
        $args = array(
            'style' => isset($attributes['style']) ? $attributes['style'] : 'dropdown',
            'show_flags' => isset($attributes['showFlags']) ? $attributes['showFlags'] : false,
            'container_class' => 'nexus-ai-wp-block-language-switcher'
        );
        
        // Add alignment class if specified
        if (isset($attributes['alignment']) && $attributes['alignment']) {
            $args['container_class'] .= ' has-text-align-' . $attributes['alignment'];
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [BLOCK] Calling render_language_switcher with args: ' . print_r($args, true));
        }
        
        $switcher_html = $frontend->render_language_switcher($args);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [BLOCK] Switcher HTML generated, length: ' . strlen($switcher_html));
            if (strlen($switcher_html) > 0) {
                error_log('Nexus AI WP Translator: [BLOCK] Switcher HTML preview: ' . substr($switcher_html, 0, 300) . '...');
            } else {
                error_log('Nexus AI WP Translator: [BLOCK] *** WARNING: Empty HTML generated ***');
            }
        }
        
        if (empty($switcher_html)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [BLOCK] No switcher HTML generated');
            }
            return '<div class="nexus-ai-wp-block-language-switcher-error"><p style="color: red; border: 1px solid red; padding: 10px;">' . __('Language switcher: No languages available or error occurred.', 'nexus-ai-wp-translator') . '</p></div>';
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [BLOCK] *** RETURNING HTML SUCCESSFULLY ***');
        }
        
        return $switcher_html;
    }
}