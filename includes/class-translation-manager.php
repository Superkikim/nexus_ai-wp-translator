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
    private $trashing_posts = array(); // Prevent infinite loops in trash operations
    
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
        static $hooks_initialized = false;
        if ($hooks_initialized) {
            return;
        }
        $hooks_initialized = true;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: [MANAGER] Translation manager hooks initialized');
        }
        
        // Post publication hooks  
        add_action('publish_post', array($this, 'handle_post_publish'), 10, 2);
        add_action('publish_page', array($this, 'handle_post_publish'), 10, 2);
        
        // Post status change hooks - removed automatic handling
        // We'll handle these via AJAX after user confirmation
        
        // AJAX handlers
        add_action('wp_ajax_nexus_ai_wp_translate_post', array($this, 'ajax_translate_post'));
        add_action('wp_ajax_nexus_ai_wp_unlink_translation', array($this, 'ajax_unlink_translation'));
        add_action('wp_ajax_nexus_ai_wp_get_translation_status', array($this, 'ajax_get_translation_status'));
        add_action('wp_ajax_nexus_ai_wp_get_linked_posts', array($this, 'ajax_get_linked_posts'));
        add_action('wp_ajax_nexus_ai_wp_handle_post_action', array($this, 'ajax_handle_post_action'));
        add_action('wp_ajax_nexus_ai_wp_get_auto_translation_status', array($this, 'ajax_get_auto_translation_status'));
        add_action('wp_ajax_nexus_ai_wp_dismiss_auto_translation', array($this, 'ajax_dismiss_auto_translation'));
        
        // Add admin scripts for post list and edit screens - MUST be in admin context
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_post_scripts'), 5);
        }
    }
    
    /**
     * Enqueue scripts for post management
     */
    public function enqueue_post_scripts($hook) {
        static $post_scripts_enqueued = false;
        
        if (in_array($hook, array('edit.php', 'post.php', 'post-new.php')) || strpos($hook, 'nexus-ai-wp-translator') !== false) {
            if ($post_scripts_enqueued) {
                return;
            }
            $post_scripts_enqueued = true;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: [SCRIPTS] Loading posts.js for hook: ' . $hook);
            }
            
            wp_enqueue_script(
                'nexus-ai-wp-translator-posts',
                NEXUS_AI_WP_TRANSLATOR_PLUGIN_URL . 'assets/js/posts.js',
                array('jquery'),
                NEXUS_AI_WP_TRANSLATOR_VERSION,
                true
            );
            
            wp_localize_script('nexus-ai-wp-translator-posts', 'nexus_ai_wp_translator_posts', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('nexus_ai_wp_translator_nonce'),
                'strings' => array(
                    'confirm_title' => __('Linked Posts Found', 'nexus-ai-wp-translator'),
                    'confirm_message' => __('This post has linked translations. What would you like to do?', 'nexus-ai-wp-translator'),
                    'delete_all' => __('Delete all linked posts', 'nexus-ai-wp-translator'),
                    'unlink_only' => __('Unlink and delete only this post', 'nexus-ai-wp-translator'),
                    'cancel' => __('Cancel', 'nexus-ai-wp-translator'),
                    'loading' => __('Loading linked posts...', 'nexus-ai-wp-translator'),
                    'processing' => __('Processing...', 'nexus-ai-wp-translator')
                )
            ));
            
            // Add CSS for the popup
            wp_add_inline_style('wp-admin', '
                .nexus-ai-wp-delete-popup {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.7);
                    z-index: 999999;
                    display: none;
                }
                .nexus-ai-wp-delete-popup-content {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: white;
                    padding: 20px;
                    border-radius: 4px;
                    max-width: 500px;
                    width: 90%;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                }
                .nexus-ai-wp-linked-posts {
                    margin: 15px 0;
                    padding: 10px;
                    background: #f9f9f9;
                    border-left: 4px solid #0073aa;
                }
                .nexus-ai-wp-linked-post {
                    padding: 5px 0;
                    border-bottom: 1px solid #eee;
                }
                .nexus-ai-wp-linked-post:last-child {
                    border-bottom: none;
                }
                .nexus-ai-wp-popup-buttons {
                    text-align: right;
                    margin-top: 20px;
                }
                .nexus-ai-wp-popup-buttons .button {
                    margin-left: 10px;
                }
            ');
        }
    }
    
    /**
     * Handle post publish
     */
    public function handle_post_publish($post_id, $post) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: handle_post_publish called for post {$post_id}");
        }
        
        // Prevent processing the same post multiple times
        if (in_array($post_id, $this->processing_posts)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: Skipping post {$post_id} - already processing");
            }
            return;
        }
        
        // Check if auto-translation is enabled
        if (!get_option('nexus_ai_wp_translator_auto_translate', true)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: Auto-translation disabled for post {$post_id}");
            }
            return;
        }
        
        // Skip if this is already a translation
        if (get_post_meta($post_id, '_nexus_ai_wp_translator_source_post', true)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: Skipping post {$post_id} - already a translation");
            }
            return;
        }
        
        // Skip if API key is not configured
        if (empty(get_option('nexus_ai_wp_translator_api_key'))) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: No API key configured for post {$post_id}");
            }
            return;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Adding post {$post_id} to processing list");
        }
        $this->processing_posts[] = $post_id;
        
        // Store translation data in session for popup
        $target_languages = get_option('nexus_ai_wp_translator_target_languages', array('es', 'fr', 'de'));
        $source_lang = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');
        
        // Filter out source language from targets
        $target_languages = array_filter($target_languages, function($lang) use ($source_lang) {
            return $lang !== $source_lang;
        });
        
        if (!empty($target_languages)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: Setting transient for post {$post_id} with languages: " . implode(', ', $target_languages));
            }
            // Store in transient for popup display
            set_transient('nexus_ai_wp_auto_translation_' . $post_id, array(
                'post_id' => $post_id,
                'post_title' => $post->post_title,
                'target_languages' => $target_languages,
                'status' => 'starting'
            ), 300); // 5 minutes
        }
        
        // Start translation process
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Starting translation process for post {$post_id}");
        }
        $this->translate_post($post_id);
        
        // Remove from processing list
        $key = array_search($post_id, $this->processing_posts);
        if ($key !== false) {
            unset($this->processing_posts[$key]);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: Removed post {$post_id} from processing list");
            }
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
            return array('success' => false, 'message' => __('Source post not found', 'nexus-ai-wp-translator'));
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
        // Prevent infinite loops
        if (in_array($post_id, $this->trashing_posts)) {
            error_log("Nexus AI WP Translator: Skipping trash for post {$post_id} - already processing");
            return;
        }
        
        $this->trashing_posts[] = $post_id;
        error_log("Nexus AI WP Translator: Starting trash process for post {$post_id}");
        
        $translations = $this->db->get_post_translations($post_id);
        error_log("Nexus AI WP Translator: Found " . count($translations) . " translations for post {$post_id}");
        
        foreach ($translations as $translation) {
            $related_post_id = ($translation->source_post_id == $post_id) 
                ? $translation->translated_post_id 
                : $translation->source_post_id;
            
            if ($related_post_id && get_post_status($related_post_id) !== 'trash' && !in_array($related_post_id, $this->trashing_posts)) {
                error_log("Nexus AI WP Translator: Trashing related post {$related_post_id}");
                wp_trash_post($related_post_id);
            } else {
                error_log("Nexus AI WP Translator: Skipping related post {$related_post_id} - already trashed or processing");
            }
        }
        
        $this->db->log_translation_activity($post_id, 'trash', 'completed', 'Post and translations trashed');
        
        // Remove from processing list
        $key = array_search($post_id, $this->trashing_posts);
        if ($key !== false) {
            unset($this->trashing_posts[$key]);
        }
        
        error_log("Nexus AI WP Translator: Completed trash process for post {$post_id}");
    }
    
    /**
     * Handle post delete
     */
    public function handle_post_delete($post_id) {
        // Prevent infinite loops
        static $deleting_posts = array();
        
        if (in_array($post_id, $deleting_posts)) {
            error_log("Nexus AI WP Translator: Skipping delete for post {$post_id} - already processing");
            return;
        }
        
        $deleting_posts[] = $post_id;
        error_log("Nexus AI WP Translator: Starting delete process for post {$post_id}");
        
        $translations = $this->db->get_post_translations($post_id);
        error_log("Nexus AI WP Translator: Found " . count($translations) . " translations for post {$post_id}");
        
        foreach ($translations as $translation) {
            $related_post_id = ($translation->source_post_id == $post_id) 
                ? $translation->translated_post_id 
                : $translation->source_post_id;
            
            if ($related_post_id && !in_array($related_post_id, $deleting_posts)) {
                error_log("Nexus AI WP Translator: Deleting related post {$related_post_id}");
                wp_delete_post($related_post_id, true);
            } else {
                error_log("Nexus AI WP Translator: Skipping related post {$related_post_id} - already processing");
            }
        }
        
        // Clean up database
        $this->db->delete_translation_relationships($post_id);
        $this->db->log_translation_activity($post_id, 'delete', 'completed', 'Post and translations deleted');
        
        // Remove from processing list
        $key = array_search($post_id, $deleting_posts);
        if ($key !== false) {
            unset($deleting_posts[$key]);
        }
        
        error_log("Nexus AI WP Translator: Completed delete process for post {$post_id}");
    }
    
    /**
     * AJAX: Get auto translation status
     */
    public function ajax_get_auto_translation_status() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }
        
        $post_id = intval($_POST['post_id']);
        $translation_data = get_transient('nexus_ai_wp_auto_translation_' . $post_id);
        
        if (!$translation_data) {
            wp_send_json_error('No auto translation data found');
            return;
        }
        
        // Get current translation status
        $translations = $this->db->get_post_translations($post_id);
        $completed_languages = array();
        $failed_languages = array();
        
        foreach ($translations as $translation) {
            if ($translation->source_post_id == $post_id) {
                if ($translation->status === 'completed') {
                    $completed_languages[] = $translation->target_language;
                } else {
                    $failed_languages[] = $translation->target_language;
                }
            }
        }
        
        // Check if all translations are done
        $all_done = count($completed_languages) + count($failed_languages) >= count($translation_data['target_languages']);
        
        wp_send_json_success(array(
            'post_title' => $translation_data['post_title'],
            'target_languages' => $translation_data['target_languages'],
            'completed_languages' => $completed_languages,
            'failed_languages' => $failed_languages,
            'all_done' => $all_done
        ));
    }
    
    /**
     * AJAX: Dismiss auto translation popup
     */
    public function ajax_dismiss_auto_translation() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }
        
        $post_id = intval($_POST['post_id']);
        delete_transient('nexus_ai_wp_auto_translation_' . $post_id);
        
        wp_send_json_success('Auto translation popup dismissed');
    }
    
    /**
     * Handle post untrash
     */
    public function handle_post_untrash($post_id) {
        // Prevent infinite loops
        static $untrashing_posts = array();
        
        if (in_array($post_id, $untrashing_posts)) {
            error_log("Nexus AI WP Translator: Skipping untrash for post {$post_id} - already processing");
            return;
        }
        
        $untrashing_posts[] = $post_id;
        error_log("Nexus AI WP Translator: Starting untrash process for post {$post_id}");
        
        $translations = $this->db->get_post_translations($post_id);
        error_log("Nexus AI WP Translator: Found " . count($translations) . " translations for post {$post_id}");
        
        foreach ($translations as $translation) {
            $related_post_id = ($translation->source_post_id == $post_id) 
                ? $translation->translated_post_id 
                : $translation->source_post_id;
            
            if ($related_post_id && get_post_status($related_post_id) === 'trash' && !in_array($related_post_id, $untrashing_posts)) {
                error_log("Nexus AI WP Translator: Untrashing related post {$related_post_id}");
                wp_untrash_post($related_post_id);
            } else {
                error_log("Nexus AI WP Translator: Skipping related post {$related_post_id} - not trashed or already processing");
            }
        }
        
        $this->db->log_translation_activity($post_id, 'untrash', 'completed', 'Post and translations restored');
        
        // Remove from processing list
        $key = array_search($post_id, $untrashing_posts);
        if ($key !== false) {
            unset($untrashing_posts[$key]);
        }
        
        error_log("Nexus AI WP Translator: Completed untrash process for post {$post_id}");
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
     * AJAX: Get linked posts
     */
    public function ajax_get_linked_posts() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }
        
        $post_id = intval($_POST['post_id']);
        $translations = $this->db->get_post_translations($post_id);
        
        $linked_posts = array();
        foreach ($translations as $translation) {
            $related_post_id = ($translation->source_post_id == $post_id) 
                ? $translation->translated_post_id 
                : $translation->source_post_id;
            
            $related_post = get_post($related_post_id);
            if ($related_post) {
                $language = ($translation->source_post_id == $post_id) 
                    ? $translation->target_language 
                    : $translation->source_language;
                
                $linked_posts[] = array(
                    'id' => $related_post_id,
                    'title' => $related_post->post_title,
                    'status' => $related_post->post_status,
                    'language' => $language,
                    'edit_link' => get_edit_post_link($related_post_id)
                );
            }
        }
        
        wp_send_json_success($linked_posts);
    }
    
    /**
     * AJAX: Handle post action with user choice
     */
    public function ajax_handle_post_action() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }
        
        $post_id = intval($_POST['post_id']);
        $post_action = sanitize_text_field($_POST['post_action']); // 'delete' or 'trash'
        $user_choice = sanitize_text_field($_POST['user_choice']); // 'delete_all' or 'unlink_only'
        
        error_log("Nexus AI WP Translator: Handling post action - Post: {$post_id}, Action: {$post_action}, Choice: {$user_choice}");
        
        try {
        if ($user_choice === 'unlink_only') {
            // Mark relationships as orphaned instead of deleting them
            $translations = $this->db->get_post_translations($post_id);
            error_log("Nexus AI WP Translator: Found " . count($translations) . " translations to mark as orphaned");
            
            foreach ($translations as $translation) {
                // Update the relationship status to indicate source was deleted
                global $wpdb;
                if ($translation->source_post_id == $post_id) {
                    // This post is the source, mark as source_deleted
                    $update_result = $wpdb->update(
                        $this->db->translations_table,
                        array('status' => 'source_deleted'),
                        array('id' => $translation->id),
                        array('%s'),
                        array('%d')
                    );
                    error_log("Nexus AI WP Translator: Updated translation {$translation->id} to source_deleted, result: " . ($update_result !== false ? 'SUCCESS' : 'FAILED'));
                } else {
                    // This post is a translation, mark as translation_deleted
                    $update_result = $wpdb->update(
                        $this->db->translations_table,
                        array('status' => 'translation_deleted'),
                        array('id' => $translation->id),
                        array('%s'),
                        array('%d')
                    );
                    error_log("Nexus AI WP Translator: Updated translation {$translation->id} to translation_deleted, result: " . ($update_result !== false ? 'SUCCESS' : 'FAILED'));
                }
            }
            
            // Remove translation meta from related posts but keep relationships
            foreach ($translations as $translation) {
                $related_post_id = ($translation->source_post_id == $post_id) 
                    ? $translation->translated_post_id 
                    : $translation->source_post_id;
                
                delete_post_meta($related_post_id, '_nexus_ai_wp_translator_source_post');
                delete_post_meta($related_post_id, '_nexus_ai_wp_translator_translation_date');
                error_log("Nexus AI WP Translator: Removed meta from post {$related_post_id}");
            }
            
            // Now perform the action on the main post only
            if ($post_action === 'delete') {
                $delete_result = wp_delete_post($post_id, true);
                error_log("Nexus AI WP Translator: Delete post {$post_id} result: " . ($delete_result ? 'SUCCESS' : 'FAILED'));
            } else {
                $trash_result = wp_trash_post($post_id);
                error_log("Nexus AI WP Translator: Trash post {$post_id} result: " . ($trash_result ? 'SUCCESS' : 'FAILED'));
            }
            
            $this->db->log_translation_activity($post_id, $post_action . '_unlink_only', 'completed', 'Post processed with unlink only option');
            
        } else { // delete_all
            // Get all linked posts first
            $translations = $this->db->get_post_translations($post_id);
            $all_post_ids = array($post_id);
            error_log("Nexus AI WP Translator: Delete all - found " . count($translations) . " translations");
            
            foreach ($translations as $translation) {
                $related_post_id = ($translation->source_post_id == $post_id) 
                    ? $translation->translated_post_id 
                    : $translation->source_post_id;
                
                if (!in_array($related_post_id, $all_post_ids)) {
                    $all_post_ids[] = $related_post_id;
                }
            }
            
            error_log("Nexus AI WP Translator: Will process posts: " . implode(', ', $all_post_ids));
            
            // Delete relationships first
            $this->db->delete_translation_relationships($post_id);
            
            // Perform action on all posts
            foreach ($all_post_ids as $id) {
                if ($post_action === 'delete') {
                    $delete_result = wp_delete_post($id, true);
                    error_log("Nexus AI WP Translator: Delete post {$id} result: " . ($delete_result ? 'SUCCESS' : 'FAILED'));
                } else {
                    $trash_result = wp_trash_post($id);
                    error_log("Nexus AI WP Translator: Trash post {$id} result: " . ($trash_result ? 'SUCCESS' : 'FAILED'));
                }
            }
            
            $this->db->log_translation_activity($post_id, $post_action . '_all', 'completed', 'All linked posts processed: ' . implode(', ', $all_post_ids));
        }
        
        error_log("Nexus AI WP Translator: Post action completed successfully");
        wp_send_json_success(array(
            'message' => __('Action completed successfully', 'nexus-ai-wp-translator'),
            'action' => $post_action,
            'choice' => $user_choice
        ));
        
        } catch (Exception $e) {
            error_log("Nexus AI WP Translator: Exception in ajax_handle_post_action: " . $e->getMessage());
            error_log("Nexus AI WP Translator: Exception trace: " . $e->getTraceAsString());
            wp_send_json_error('Exception occurred: ' . $e->getMessage());
        } catch (Error $e) {
            error_log("Nexus AI WP Translator: Fatal error in ajax_handle_post_action: " . $e->getMessage());
            error_log("Nexus AI WP Translator: Error trace: " . $e->getTraceAsString());
            wp_send_json_error('Fatal error occurred: ' . $e->getMessage());
        }
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