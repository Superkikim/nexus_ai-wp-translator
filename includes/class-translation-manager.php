<?php
/**
 * Nexus AI WP Translator Manager - Core translation logic
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_Manager {
    
    private static $instance = null;
    private $db;
    private $api_handler;
    private $processing_posts = array(); // Prevent infinite loops
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = Nexus_AI_WP_Translator_Database::get_instance();
        $this->api_handler = Nexus_AI_WP_Translator_API_Handler::get_instance();
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Post publication hooks
        add_action('publish_post', array($this, 'handle_post_publish'), 10, 2);
        add_action('publish_page', array($this, 'handle_post_publish'), 10, 2);
        
        // Post status change hooks
        add_action('wp_trash_post', array($this, 'handle_post_trash'));
        add_action('before_delete_post', array($this, 'handle_post_delete'));
        add_action('untrash_post', array($this, 'handle_post_untrash'));
        
        // AJAX handlers
        add_action('wp_ajax_nexus_ai_wp_translate_post', array($this, 'ajax_translate_post'));
        add_action('wp_ajax_nexus_ai_wp_unlink_translation', array($this, 'ajax_unlink_translation'));
        add_action('wp_ajax_nexus_ai_wp_get_translation_status', array($this, 'ajax_get_translation_status'));
    }
    
    /**
     * Handle post publish
     */
    public function handle_post_publish($post_id, $post) {
        // Prevent processing the same post multiple times
        if (in_array($post_id, $this->processing_posts)) {
            return;
        }
        
        // Check if auto-translation is enabled
        if (!get_option('nexus_ai_wp_translator_auto_translate', true)) {
            return;
        }
        
        // Skip if this is already a translation
        if (get_post_meta($post_id, '_nexus_ai_wp_translator_source_post', true)) {
            return;
        }
        
        // Skip if API key is not configured
        if (empty(get_option('nexus_ai_wp_translator_api_key'))) {
            return;
        }
        
        $this->processing_posts[] = $post_id;
        
        // Start translation process
        $this->translate_post($post_id);
        
        // Remove from processing list
        $key = array_search($post_id, $this->processing_posts);
        if ($key !== false) {
            unset($this->processing_posts[$key]);
        }
    }
    
    /**
     * Translate a post to all target languages
     */
    public function translate_post($post_id, $target_languages = null) {
        $start_time = microtime(true);
        
        if (!$target_languages) {
            $target_languages = get_option('nexus_ai_wp_translator_target_languages', array('es', 'fr', 'de'));
        }
        
        if (!is_array($target_languages)) {
            $target_languages = array($target_languages);
        }
        
        $source_lang = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');
        $total_api_calls = 0;
        $success_count = 0;
        $errors = array();
        
        foreach ($target_languages as $target_lang) {
            // Skip if target language is same as source
            if ($target_lang === $source_lang) {
                continue;
            }
            
            // Check if translation already exists
            $existing_translation = $this->db->get_translated_post($post_id, $target_lang);
            if ($existing_translation && $existing_translation->status === 'completed') {
                continue;
            }
            
            // Log translation start
            $this->db->log_translation_activity($post_id, 'translate_start', 'processing', "Starting translation to {$target_lang}");
            
            // Perform translation
            $result = $this->create_translated_post($post_id, $target_lang);
            
            if ($result['success']) {
                $success_count++;
                $total_api_calls += isset($result['api_calls']) ? $result['api_calls'] : 0;
                
                // Log success
                $this->db->log_translation_activity(
                    $post_id, 
                    'translate', 
                    'success', 
                    "Translated to {$target_lang} successfully",
                    isset($result['api_calls']) ? $result['api_calls'] : 0,
                    isset($result['processing_time']) ? $result['processing_time'] : 0
                );
            } else {
                $errors[] = $target_lang . ': ' . $result['message'];
                
                // Log error
                $this->db->log_translation_activity(
                    $post_id, 
                    'translate', 
                    'error', 
                    "Translation to {$target_lang} failed: " . $result['message']
                );
            }
        }
        
        $total_time = microtime(true) - $start_time;
        
        // Log overall completion
        $status = empty($errors) ? 'completed' : 'partial';
        $message = sprintf(
            __('Translation completed. Success: %d, Errors: %d', 'nexus-ai-wp-translator'),
            $success_count,
            count($errors)
        );
        
        if (!empty($errors)) {
            $message .= ' | Errors: ' . implode(', ', $errors);
        }
        
        $this->db->log_translation_activity($post_id, 'translate_complete', $status, $message, $total_api_calls, $total_time);
        
        return array(
            'success' => $success_count > 0,
            'success_count' => $success_count,
            'error_count' => count($errors),
            'errors' => $errors,
            'total_api_calls' => $total_api_calls,
            'processing_time' => $total_time
        );
    }
    
    /**
     * Create translated post
     */
    private function create_translated_post($source_post_id, $target_lang) {
        $source_post = get_post($source_post_id);
        if (!$source_post) {
            return array('success' => false, 'message' => __('Source post not found', 'claude-translator'));
            return array('success' => false, 'message' => __('Source post not found', 'nexus-ai-wp-translator'));
        }
        
        // Get translation from API
        $translation_result = $this->api_handler->translate_post_content($source_post_id, $target_lang);
        
        if (!$translation_result['success']) {
            return $translation_result;
        }
        
        // Create new post
        $translated_post_data = array(
            'post_title' => $translation_result['title'],
            'post_content' => $translation_result['content'],
            'post_excerpt' => $translation_result['excerpt'],
            'post_status' => $source_post->post_status,
            'post_type' => $source_post->post_type,
            'post_author' => $source_post->post_author,
            'post_category' => wp_get_post_categories($source_post_id),
            'tags_input' => wp_get_post_tags($source_post_id, array('fields' => 'names')),
            'meta_input' => array(
                '_nexus_ai_wp_translator_language' => $target_lang,
                '_nexus_ai_wp_translator_source_post' => $source_post_id,
                '_nexus_ai_wp_translator_translation_date' => current_time('mysql')
            )
        );
        
        // Insert translated post
        $translated_post_id = wp_insert_post($translated_post_data);
        
        if (is_wp_error($translated_post_id)) {
            return array(
                'success' => false, 
                'message' => $translated_post_id->get_error_message()
            );
        }
        
        // Copy custom fields (except translator meta)
        $custom_fields = get_post_meta($source_post_id);
        foreach ($custom_fields as $key => $values) {
            if (strpos($key, '_nexus_ai_wp_translator_') !== 0) {
                foreach ($values as $value) {
                    add_post_meta($translated_post_id, $key, maybe_unserialize($value));
                }
            }
        }
        
        // Store translation relationship
        $source_lang = get_post_meta($source_post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');
        $this->db->store_translation_relationship($source_post_id, $translated_post_id, $source_lang, $target_lang, 'completed');
        
        // Set language meta for source post if not set
        if (!get_post_meta($source_post_id, '_nexus_ai_wp_translator_language', true)) {
            update_post_meta($source_post_id, '_nexus_ai_wp_translator_language', $source_lang);
        }
        
        $translation_result['translated_post_id'] = $translated_post_id;
        return $translation_result;
    }
    
    /**
     * Handle post trash
     */
    public function handle_post_trash($post_id) {
        $translations = $this->db->get_post_translations($post_id);
        
        foreach ($translations as $translation) {
            $related_post_id = ($translation->source_post_id == $post_id) 
                ? $translation->translated_post_id 
                : $translation->source_post_id;
            
            if ($related_post_id && get_post_status($related_post_id) !== 'trash') {
                wp_trash_post($related_post_id);
            }
        }
        
        $this->db->log_translation_activity($post_id, 'trash', 'completed', 'Post and translations trashed');
    }
    
    /**
     * Handle post delete
     */
    public function handle_post_delete($post_id) {
        $translations = $this->db->get_post_translations($post_id);
        
        foreach ($translations as $translation) {
            $related_post_id = ($translation->source_post_id == $post_id) 
                ? $translation->translated_post_id 
                : $translation->source_post_id;
            
            if ($related_post_id) {
                wp_delete_post($related_post_id, true);
            }
        }
        
        // Clean up database
        $this->db->delete_translation_relationships($post_id);
        $this->db->log_translation_activity($post_id, 'delete', 'completed', 'Post and translations deleted');
    }
    
    /**
     * Handle post untrash
     */
    public function handle_post_untrash($post_id) {
        $translations = $this->db->get_post_translations($post_id);
        
        foreach ($translations as $translation) {
            $related_post_id = ($translation->source_post_id == $post_id) 
                ? $translation->translated_post_id 
                : $translation->source_post_id;
            
            if ($related_post_id && get_post_status($related_post_id) === 'trash') {
                wp_untrash_post($related_post_id);
            }
        }
        
        $this->db->log_translation_activity($post_id, 'untrash', 'completed', 'Post and translations restored');
    }
    
    /**
     * AJAX: Manually translate post
     */
    public function ajax_translate_post() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }
        
        $post_id = intval($_POST['post_id']);
        $target_languages = isset($_POST['target_languages']) ? (array) $_POST['target_languages'] : null;
        
        $result = $this->translate_post($post_id, $target_languages);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX: Unlink translation
     */
    public function ajax_unlink_translation() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }
        
        $post_id = intval($_POST['post_id']);
        $related_post_id = intval($_POST['related_post_id']);
        
        // Remove translation relationship
        $result = $this->db->delete_translation_relationships($post_id);
        
        if ($result) {
            // Remove meta fields
            delete_post_meta($related_post_id, '_nexus_ai_wp_translator_source_post');
            delete_post_meta($related_post_id, '_nexus_ai_wp_translator_translation_date');
            
            $this->db->log_translation_activity($post_id, 'unlink', 'completed', "Unlinked from post {$related_post_id}");
            
            wp_send_json_success(__('Translation unlinked successfully', 'nexus-ai-wp-translator'));
        } else {
            wp_send_json_error(__('Failed to unlink translation', 'nexus-ai-wp-translator'));
        }
    }
    
    /**
     * AJAX: Get translation status
     */
    public function ajax_get_translation_status() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }
        
        $post_id = intval($_POST['post_id']);
        $translations = $this->db->get_post_translations($post_id);
        
        $status = array();
        foreach ($translations as $translation) {
            $status[] = array(
                'source_post_id' => $translation->source_post_id,
                'translated_post_id' => $translation->translated_post_id,
                'source_language' => $translation->source_language,
                'target_language' => $translation->target_language,
                'status' => $translation->status,
                'created_at' => $translation->created_at
            );
        }
        
        wp_send_json_success($status);
    }
    
    /**
     * Get available languages
     */
    public function get_available_languages() {
        return array(
            'en' => __('English', 'nexus-ai-wp-translator'),
            'es' => __('Spanish', 'nexus-ai-wp-translator'),
            'fr' => __('French', 'nexus-ai-wp-translator'),
            'de' => __('German', 'nexus-ai-wp-translator'),
            'it' => __('Italian', 'nexus-ai-wp-translator'),
            'pt' => __('Portuguese', 'nexus-ai-wp-translator'),
            'ru' => __('Russian', 'nexus-ai-wp-translator'),
            'ja' => __('Japanese', 'nexus-ai-wp-translator'),
            'ko' => __('Korean', 'nexus-ai-wp-translator'),
            'zh' => __('Chinese', 'nexus-ai-wp-translator'),
            'ar' => __('Arabic', 'nexus-ai-wp-translator'),
            'hi' => __('Hindi', 'nexus-ai-wp-translator'),
            'nl' => __('Dutch', 'nexus-ai-wp-translator'),
            'sv' => __('Swedish', 'nexus-ai-wp-translator'),
            'da' => __('Danish', 'nexus-ai-wp-translator'),
            'no' => __('Norwegian', 'nexus-ai-wp-translator'),
            'fi' => __('Finnish', 'nexus-ai-wp-translator'),
            'pl' => __('Polish', 'nexus-ai-wp-translator'),
            'cs' => __('Czech', 'nexus-ai-wp-translator'),
            'hu' => __('Hungarian', 'nexus-ai-wp-translator')
        );
    }
}