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
        if (!function_exists('register_block_type')) {
            return;
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
    }
    
    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
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
    }
    
    /**
     * Render the block on frontend
     */
    public function render_block($attributes) {
        if (!class_exists('Nexus_AI_WP_Translator_Frontend')) {
            return '<p>' . __('Language switcher temporarily unavailable.', 'nexus-ai-wp-translator') . '</p>';
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
        
        $switcher_html = $frontend->render_language_switcher($args);
        
        if (empty($switcher_html)) {
            return '<p>' . __('No languages available.', 'nexus-ai-wp-translator') . '</p>';
        }
        
        return $switcher_html;
    }
}