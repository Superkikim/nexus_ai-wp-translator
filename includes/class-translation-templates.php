<?php
/**
 * Translation Templates Manager
 * 
 * Manages custom translation templates and presets for different content types
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_Templates {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init_templates'));
    }
    
    /**
     * Initialize templates system
     */
    public function init_templates() {
        // Create default templates if they don't exist
        $this->create_default_templates();
    }
    
    /**
     * Get all translation templates
     */
    public function get_templates() {
        $templates = get_option('nexus_ai_wp_translator_templates', array());
        
        // Ensure we have default templates
        if (empty($templates)) {
            $this->create_default_templates();
            $templates = get_option('nexus_ai_wp_translator_templates', array());
        }
        
        return $templates;
    }
    
    /**
     * Get template by ID
     */
    public function get_template($template_id) {
        $templates = $this->get_templates();
        return isset($templates[$template_id]) ? $templates[$template_id] : null;
    }
    
    /**
     * Save template
     */
    public function save_template($template_data) {
        $templates = $this->get_templates();
        
        // Generate ID if not provided
        if (empty($template_data['id'])) {
            $template_data['id'] = $this->generate_template_id($template_data['name']);
        }
        
        // Validate template data
        $validation = $this->validate_template($template_data);
        if (!$validation['valid']) {
            return array(
                'success' => false,
                'message' => $validation['message']
            );
        }
        
        // Add metadata
        $template_data['created_at'] = isset($template_data['created_at']) ? $template_data['created_at'] : time();
        $template_data['updated_at'] = time();
        
        // Save template
        $templates[$template_data['id']] = $template_data;
        update_option('nexus_ai_wp_translator_templates', $templates);
        
        return array(
            'success' => true,
            'template_id' => $template_data['id'],
            'message' => __('Template saved successfully', 'nexus-ai-wp-translator')
        );
    }
    
    /**
     * Delete template
     */
    public function delete_template($template_id) {
        $templates = $this->get_templates();
        
        if (!isset($templates[$template_id])) {
            return array(
                'success' => false,
                'message' => __('Template not found', 'nexus-ai-wp-translator')
            );
        }
        
        // Prevent deletion of default templates
        if (isset($templates[$template_id]['is_default']) && $templates[$template_id]['is_default']) {
            return array(
                'success' => false,
                'message' => __('Cannot delete default template', 'nexus-ai-wp-translator')
            );
        }
        
        unset($templates[$template_id]);
        update_option('nexus_ai_wp_translator_templates', $templates);
        
        return array(
            'success' => true,
            'message' => __('Template deleted successfully', 'nexus-ai-wp-translator')
        );
    }
    
    /**
     * Get template for content type
     */
    public function get_template_for_content_type($content_type, $post_id = null) {
        $templates = $this->get_templates();
        
        // Check for post-specific template setting
        if ($post_id) {
            $post_template = get_post_meta($post_id, '_nexus_ai_wp_translator_template', true);
            if ($post_template && isset($templates[$post_template])) {
                return $templates[$post_template];
            }
        }
        
        // Find template by content type
        foreach ($templates as $template) {
            if (in_array($content_type, $template['content_types'])) {
                return $template;
            }
        }
        
        // Return default template
        return $this->get_default_template();
    }
    
    /**
     * Get default template
     */
    public function get_default_template() {
        $templates = $this->get_templates();
        
        foreach ($templates as $template) {
            if (isset($template['is_default']) && $template['is_default']) {
                return $template;
            }
        }
        
        // Fallback to first template
        return !empty($templates) ? reset($templates) : $this->create_fallback_template();
    }
    
    /**
     * Apply template to translation
     */
    public function apply_template($template, $content, $source_lang, $target_lang, $context = array()) {
        // Replace placeholders in prompt
        $prompt = $template['prompt'];
        
        $placeholders = array(
            '{content}' => $content,
            '{source_lang}' => $source_lang,
            '{target_lang}' => $target_lang,
            '{content_type}' => isset($context['content_type']) ? $context['content_type'] : 'content',
            '{post_title}' => isset($context['post_title']) ? $context['post_title'] : '',
            '{post_type}' => isset($context['post_type']) ? $context['post_type'] : 'post',
            '{target_audience}' => isset($template['settings']['target_audience']) ? $template['settings']['target_audience'] : 'general',
            '{tone}' => isset($template['settings']['tone']) ? $template['settings']['tone'] : 'neutral',
            '{formality}' => isset($template['settings']['formality']) ? $template['settings']['formality'] : 'standard'
        );
        
        foreach ($placeholders as $placeholder => $value) {
            $prompt = str_replace($placeholder, $value, $prompt);
        }
        
        return $prompt;
    }
    
    /**
     * Create default templates
     */
    private function create_default_templates() {
        $existing_templates = get_option('nexus_ai_wp_translator_templates', array());
        
        // Don't recreate if templates already exist
        if (!empty($existing_templates)) {
            return;
        }
        
        $default_templates = array(
            'general' => array(
                'id' => 'general',
                'name' => __('General Content', 'nexus-ai-wp-translator'),
                'description' => __('Standard template for general content translation', 'nexus-ai-wp-translator'),
                'content_types' => array('post', 'page', 'general'),
                'prompt' => 'Translate the following {content_type} from {source_lang} to {target_lang}. Maintain the original tone, style, and formatting. OUTPUT ONLY THE TRANSLATION without any comments or explanations.

Content to translate:
{content}',
                'settings' => array(
                    'tone' => 'neutral',
                    'formality' => 'standard',
                    'target_audience' => 'general',
                    'preserve_formatting' => true,
                    'preserve_links' => true
                ),
                'is_default' => true,
                'created_at' => time(),
                'updated_at' => time()
            ),
            'blog_post' => array(
                'id' => 'blog_post',
                'name' => __('Blog Posts', 'nexus-ai-wp-translator'),
                'description' => __('Optimized for blog posts and articles', 'nexus-ai-wp-translator'),
                'content_types' => array('post', 'article'),
                'prompt' => 'Translate this blog post from {source_lang} to {target_lang}. Maintain an engaging, conversational tone appropriate for blog readers. Keep the original structure, headings, and any calls-to-action. OUTPUT ONLY THE TRANSLATION.

Blog post title: {post_title}

Content:
{content}',
                'settings' => array(
                    'tone' => 'conversational',
                    'formality' => 'informal',
                    'target_audience' => 'blog_readers',
                    'preserve_formatting' => true,
                    'preserve_links' => true,
                    'seo_friendly' => true
                ),
                'is_default' => false,
                'created_at' => time(),
                'updated_at' => time()
            ),
            'product_description' => array(
                'id' => 'product_description',
                'name' => __('Product Descriptions', 'nexus-ai-wp-translator'),
                'description' => __('For e-commerce product descriptions', 'nexus-ai-wp-translator'),
                'content_types' => array('product', 'woocommerce'),
                'prompt' => 'Translate this product description from {source_lang} to {target_lang}. Use persuasive, sales-oriented language that appeals to potential customers. Maintain all product features, benefits, and specifications. OUTPUT ONLY THE TRANSLATION.

Product description:
{content}',
                'settings' => array(
                    'tone' => 'persuasive',
                    'formality' => 'professional',
                    'target_audience' => 'customers',
                    'preserve_formatting' => true,
                    'preserve_links' => true,
                    'sales_focused' => true
                ),
                'is_default' => false,
                'created_at' => time(),
                'updated_at' => time()
            ),
            'news_article' => array(
                'id' => 'news_article',
                'name' => __('News Articles', 'nexus-ai-wp-translator'),
                'description' => __('For news and journalistic content', 'nexus-ai-wp-translator'),
                'content_types' => array('news', 'article'),
                'prompt' => 'Translate this news article from {source_lang} to {target_lang}. Maintain journalistic objectivity, factual accuracy, and formal tone. Preserve all quotes, dates, and proper names exactly. OUTPUT ONLY THE TRANSLATION.

News article:
{content}',
                'settings' => array(
                    'tone' => 'objective',
                    'formality' => 'formal',
                    'target_audience' => 'news_readers',
                    'preserve_formatting' => true,
                    'preserve_links' => true,
                    'factual_accuracy' => true
                ),
                'is_default' => false,
                'created_at' => time(),
                'updated_at' => time()
            ),
            'technical_documentation' => array(
                'id' => 'technical_documentation',
                'name' => __('Technical Documentation', 'nexus-ai-wp-translator'),
                'description' => __('For technical guides and documentation', 'nexus-ai-wp-translator'),
                'content_types' => array('documentation', 'guide', 'manual'),
                'prompt' => 'Translate this technical documentation from {source_lang} to {target_lang}. Maintain technical accuracy, precise terminology, and clear instructions. Preserve all code examples, commands, and technical terms. OUTPUT ONLY THE TRANSLATION.

Technical content:
{content}',
                'settings' => array(
                    'tone' => 'instructional',
                    'formality' => 'formal',
                    'target_audience' => 'technical_users',
                    'preserve_formatting' => true,
                    'preserve_links' => true,
                    'technical_accuracy' => true,
                    'preserve_code' => true
                ),
                'is_default' => false,
                'created_at' => time(),
                'updated_at' => time()
            ),
            'meta_description' => array(
                'id' => 'meta_description',
                'name' => __('Meta Descriptions', 'nexus-ai-wp-translator'),
                'description' => __('For SEO meta descriptions', 'nexus-ai-wp-translator'),
                'content_types' => array('meta_description'),
                'prompt' => 'Translate this SEO meta description from {source_lang} to {target_lang}. Keep it concise, compelling, and under 160 characters. Maintain the marketing appeal and key information. OUTPUT ONLY THE TRANSLATION.

Meta description:
{content}',
                'settings' => array(
                    'tone' => 'compelling',
                    'formality' => 'professional',
                    'target_audience' => 'search_users',
                    'character_limit' => 160,
                    'seo_optimized' => true
                ),
                'is_default' => false,
                'created_at' => time(),
                'updated_at' => time()
            )
        );
        
        update_option('nexus_ai_wp_translator_templates', $default_templates);
    }
    
    /**
     * Validate template data
     */
    private function validate_template($template_data) {
        $required_fields = array('name', 'prompt', 'content_types');
        
        foreach ($required_fields as $field) {
            if (empty($template_data[$field])) {
                return array(
                    'valid' => false,
                    'message' => sprintf(__('Field "%s" is required', 'nexus-ai-wp-translator'), $field)
                );
            }
        }
        
        // Validate content types
        if (!is_array($template_data['content_types']) || empty($template_data['content_types'])) {
            return array(
                'valid' => false,
                'message' => __('At least one content type must be specified', 'nexus-ai-wp-translator')
            );
        }
        
        // Validate prompt contains required placeholders
        if (strpos($template_data['prompt'], '{content}') === false) {
            return array(
                'valid' => false,
                'message' => __('Prompt must contain {content} placeholder', 'nexus-ai-wp-translator')
            );
        }
        
        return array('valid' => true);
    }
    
    /**
     * Generate template ID from name
     */
    private function generate_template_id($name) {
        $id = sanitize_title($name);
        $templates = $this->get_templates();
        
        // Ensure unique ID
        $counter = 1;
        $original_id = $id;
        while (isset($templates[$id])) {
            $id = $original_id . '_' . $counter;
            $counter++;
        }
        
        return $id;
    }
    
    /**
     * Create fallback template
     */
    private function create_fallback_template() {
        return array(
            'id' => 'fallback',
            'name' => 'Fallback Template',
            'prompt' => 'Translate from {source_lang} to {target_lang}: {content}',
            'content_types' => array('general'),
            'settings' => array(),
            'is_default' => true
        );
    }
}
