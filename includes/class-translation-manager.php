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
        
        // Add admin scripts for post list and edit screens - MUST be in admin context
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_post_scripts'), 5);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: Registered enqueue_post_scripts hook in admin context');
            }
        }
    }
    
    /**
     * Enqueue scripts for post management
     */
    public function enqueue_post_scripts($hook) {
        // Debug: Always log when this function is called
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: enqueue_post_scripts called for hook: ' . $hook);
        }
        
        if (in_array($hook, array('edit.php', 'post.php', 'post-new.php'))) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: Loading posts.js for hook: ' . $hook);
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
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: posts.js enqueued successfully');
            }
            
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
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: posts.js NOT loaded - wrong hook: ' . $hook);
            }
        }
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
        
        if ($user_choice === 'unlink_only') {
            // Mark relationships as orphaned instead of deleting them
            $translations = $this->db->get_post_translations($post_id);
            foreach ($translations as $translation) {
                // Update the relationship status to indicate source was deleted
                global $wpdb;
                if ($translation->source_post_id == $post_id) {
                    // This post is the source, mark as source_deleted
                    $wpdb->update(
                        $this->db->translations_table,
                        array('status' => 'source_deleted'),
                        array('id' => $translation->id),
                        array('%s'),
                        array('%d')
                    );
                } else {
                    // This post is a translation, mark as translation_deleted
                    $wpdb->update(
                        $this->db->translations_table,
                        array('status' => 'translation_deleted'),
                        array('id' => $translation->id),
                        array('%s'),
                        array('%d')
                    );
                }
            }
            
            // Remove translation meta from related posts but keep relationships
            foreach ($translations as $translation) {
                $related_post_id = ($translation->source_post_id == $post_id) 
                    ? $translation->translated_post_id 
                    : $translation->source_post_id;
                
                delete_post_meta($related_post_id, '_nexus_ai_wp_translator_source_post');
                delete_post_meta($related_post_id, '_nexus_ai_wp_translator_translation_date');
            }
            
            // Now perform the action on the main post only
            if ($post_action === 'delete') {
                wp_delete_post($post_id, true);
            } else {
                wp_trash_post($post_id);
            }
            
            $this->db->log_translation_activity($post_id, $post_action . '_unlink_only', 'completed', 'Post processed with unlink only option');
            
        } else { // delete_all
            // Get all linked posts first
            $translations = $this->db->get_post_translations($post_id);
            $all_post_ids = array($post_id);
            
            foreach ($translations as $translation) {
                $related_post_id = ($translation->source_post_id == $post_id) 
                    ? $translation->translated_post_id 
                    : $translation->source_post_id;
                
                if (!in_array($related_post_id, $all_post_ids)) {
                    $all_post_ids[] = $related_post_id;
                }
            }
            
            // Delete relationships first
            $this->db->delete_translation_relationships($post_id);
            
            // Perform action on all posts
            foreach ($all_post_ids as $id) {
                if ($post_action === 'delete') {
                    wp_delete_post($id, true);
                } else {
                    wp_trash_post($id);
                }
            }
            
            $this->db->log_translation_activity($post_id, $post_action . '_all', 'completed', 'All linked posts processed: ' . implode(', ', $all_post_ids));
        }
        
        wp_send_json_success(array(
            'message' => __('Action completed successfully', 'nexus-ai-wp-translator'),
            'action' => $post_action,
            'choice' => $user_choice
        ));
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