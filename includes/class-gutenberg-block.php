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
            error_log('Nexus AI WP Translator: [BLOCK] Registering Gutenberg block');
        }
        
        if (!function_exists('register_block_type')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [BLOCK] register_block_type function not available');
            }
            return;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [BLOCK] About to register block type');
        }
        
        register_block_type('nexus-ai-wp-translator/language-switcher', array(
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
            'editor_style' => 'nexus-ai-wp-translator-block-editor-style',
            'style' => 'nexus-ai-wp-translator-frontend'
        ));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [BLOCK] Block registered successfully');
        }
    }
    
    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [BLOCK] Enqueuing block editor assets');
        }
        
        wp_enqueue_script(
            'nexus-ai-wp-translator-block-editor',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/block-editor.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            NEXUS_AI_WP_TRANSLATOR_VERSION,
            true
        );
        
        wp_enqueue_style(
            'nexus-ai-wp-translator-block-editor-style',
            NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/css/block-editor.css',
            array('wp-edit-blocks'),
            NEXUS_AI_WP_TRANSLATOR_VERSION
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $js_file = NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'assets/js/block-editor.js';
            $css_file = NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'assets/css/block-editor.css';
            error_log('Nexus AI WP Translator: [BLOCK] JS file exists: ' . (file_exists($js_file) ? 'YES' : 'NO'));
            error_log('Nexus AI WP Translator: [BLOCK] CSS file exists: ' . (file_exists($css_file) ? 'YES' : 'NO'));
            error_log('Nexus AI WP Translator: [BLOCK] Plugin URL: ' . NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL);
        }
        
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
            error_log('Nexus AI WP Translator: [BLOCK] Rendering block with attributes: ' . print_r($attributes, true));
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
            error_log('Nexus AI WP Translator: [BLOCK] Switcher HTML length: ' . strlen($switcher_html));
            error_log('Nexus AI WP Translator: [BLOCK] Switcher HTML: ' . substr($switcher_html, 0, 200) . '...');
        }
        
        if (empty($switcher_html)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [BLOCK] No switcher HTML generated');
            }
            return '<p>' . __('No languages available.', 'nexus-ai-wp-translator') . '</p>';
        }
        
        return $switcher_html;
    }
}