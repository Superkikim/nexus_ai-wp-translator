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
        
        // Automatic translation completely removed for safety
        // All translations are now manual via admin interface
        
        // AJAX handlers
        add_action('wp_ajax_nexus_ai_wp_translate_post', array($this, 'ajax_translate_post'));
        add_action('wp_ajax_nexus_ai_wp_unlink_translation', array($this, 'ajax_unlink_translation'));
        add_action('wp_ajax_nexus_ai_wp_get_translation_status', array($this, 'ajax_get_translation_status'));
        add_action('wp_ajax_nexus_ai_wp_get_linked_posts', array($this, 'ajax_get_linked_posts'));
        add_action('wp_ajax_nexus_ai_wp_handle_post_action', array($this, 'ajax_handle_post_action'));

        
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
     * Translate a post to all target languages
     */
    public function translate_post($post_id, $target_languages = null) {
        $start_time = microtime(true);
        
        error_log("Nexus AI WP Translator: [TRANSLATE] Starting translation for post {$post_id}");

        // Validate post exists
        $post = get_post($post_id);
        if (!$post) {
            error_log("Nexus AI WP Translator: [TRANSLATE] Post {$post_id} not found");
            return array(
                'success' => false,
                'message' => __('Post not found.', 'nexus-ai-wp-translator'),
                'success_count' => 0,
                'error_count' => 1
            );
        }

        if (!$target_languages) {
            $target_languages = get_option('nexus_ai_wp_translator_target_languages', array('es', 'fr', 'de'));
            error_log("Nexus AI WP Translator: [TRANSLATE] Using default target languages: " . implode(', ', $target_languages));
        } else {
            error_log("Nexus AI WP Translator: [TRANSLATE] Using provided target languages: " . implode(', ', $target_languages));
        }

        if (!is_array($target_languages)) {
            $target_languages = array($target_languages);
        }

        // Validate we have target languages
        if (empty($target_languages)) {
            error_log("Nexus AI WP Translator: [TRANSLATE] No target languages specified");
            return array(
                'success' => false,
                'message' => __('No target languages specified. Please configure target languages in Settings.', 'nexus-ai-wp-translator'),
                'success_count' => 0,
                'error_count' => 1
            );
        }

        $source_lang = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');
        error_log("Nexus AI WP Translator: [TRANSLATE] Source language: {$source_lang}");
        
        $total_api_calls = 0;
        $success_count = 0;
        $errors = array();
        $skipped = array();
        $success_details = array();

        // Log start of translation session
        $languages_list = implode(', ', $target_languages);
        $this->db->log_translation_activity($post_id, 'batch_translate_start', 'processing',
            sprintf(__('Starting translation session: %s → [%s]', 'nexus-ai-wp-translator'), $source_lang, $languages_list));

        foreach ($target_languages as $target_lang) {
            error_log("Nexus AI WP Translator: [TRANSLATE] Processing language: {$target_lang}");
            
            // Skip if target language is same as source
            if ($target_lang === $source_lang) {
                error_log("Nexus AI WP Translator: [TRANSLATE] Skipping {$target_lang} - same as source");
                $skipped[] = $target_lang . ': ' . __('Same as source language', 'nexus-ai-wp-translator');
                continue;
            }

            // Check if translation already exists and the translated post still exists
            $existing_translation = $this->db->get_translated_post($post_id, $target_lang);
            if ($existing_translation && $existing_translation->status === 'completed') {
                // Verify the translated post actually exists
                $translated_post = get_post($existing_translation->translated_post_id);
                if ($translated_post && $translated_post->post_status === 'publish') {
                    error_log("Nexus AI WP Translator: [TRANSLATE] Skipping {$target_lang} - translation already exists");
                    $skipped[] = $target_lang . ': ' . __('Translation already exists', 'nexus-ai-wp-translator');
                    continue;
                } else {
                    // Translation record exists but post is missing - clean it up
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Nexus AI WP Translator: Cleaning up orphaned translation record for post {$post_id} -> {$target_lang}");
                    }
                    global $wpdb;
                    $wpdb->delete(
                        $this->db->translations_table,
                        array('id' => $existing_translation->id),
                        array('%d')
                    );
                }
            }

            // Perform translation (no individual logging to keep logs clean)
            error_log("Nexus AI WP Translator: [TRANSLATE] Creating translation for {$target_lang}");
            $result = $this->create_translated_post($post_id, $target_lang);
            error_log("Nexus AI WP Translator: [TRANSLATE] Translation result for {$target_lang}: " . ($result['success'] ? 'SUCCESS' : 'FAILED'));

            if ($result['success']) {
                $success_count++;
                $total_api_calls += isset($result['api_calls']) ? $result['api_calls'] : 0;

                // Store success details for final summary
                $success_details[] = sprintf(__('%s (%d API calls, %.1fs)', 'nexus-ai-wp-translator'),
                    $target_lang,
                    isset($result['api_calls']) ? $result['api_calls'] : 0,
                    isset($result['processing_time']) ? $result['processing_time'] : 0
                );
            } else {
                // Check if this is a throttling error
                $is_throttle_error = strpos($result['message'], 'API call limit reached') !== false;
                $is_timeout_error = strpos($result['message'], 'timeout') !== false || strpos($result['message'], 'timed out') !== false;

                if ($is_throttle_error) {
                    $errors[] = $target_lang . ': ' . __('THROTTLE LIMIT REACHED', 'nexus-ai-wp-translator') . ' - ' . $result['message'];
                    $log_message = "Translation to {$target_lang} BLOCKED by throttle limits: " . $result['message'];
                } elseif ($is_timeout_error) {
                    $errors[] = $target_lang . ': ' . __('TIMEOUT ERROR', 'nexus-ai-wp-translator') . ' - ' . $result['message'];
                    $log_message = "Translation to {$target_lang} TIMEOUT: " . $result['message'];
                } else {
                    $errors[] = $target_lang . ': ' . $result['message'];
                    $log_message = "Translation to {$target_lang} failed: " . $result['message'];
                }

                // Log error with clear categorization
                $this->db->log_translation_activity(
                    $post_id,
                    'translate',
                    'error',
                    $log_message
                );

                // If it's a throttle error, stop trying other languages to avoid wasting attempts
                if (strpos($result['message'], 'API call limit reached') !== false) {
                    $errors[] = __('Stopping further translations due to API limit. Please increase throttle limit in Settings or try again later.', 'nexus-ai-wp-translator');
                    break;
                }
            }
        }
        
        $total_time = microtime(true) - $start_time;
        error_log("Nexus AI WP Translator: [TRANSLATE] Translation session completed - Success: {$success_count}, Errors: " . count($errors) . ", Time: {$total_time}s");
        
        // Analyze error types
        $throttle_errors = 0;
        $timeout_errors = 0;
        $other_errors = 0;

        foreach ($errors as $error) {
            if (strpos($error, 'THROTTLE LIMIT REACHED') !== false) {
                $throttle_errors++;
            } elseif (strpos($error, 'TIMEOUT ERROR') !== false) {
                $timeout_errors++;
            } else {
                $other_errors++;
            }
        }

        // Build detailed message
        $message_parts = array();

        if ($success_count > 0) {
            $message_parts[] = sprintf(__('Successfully translated to %d languages', 'nexus-ai-wp-translator'), $success_count);
        }

        if (!empty($skipped)) {
            $message_parts[] = sprintf(__('Skipped %d languages', 'nexus-ai-wp-translator'), count($skipped));
        }

        if (!empty($errors)) {
            $error_details = array();
            if ($throttle_errors > 0) {
                $error_details[] = sprintf(__('%d throttle limit', 'nexus-ai-wp-translator'), $throttle_errors);
            }
            if ($timeout_errors > 0) {
                $error_details[] = sprintf(__('%d timeout', 'nexus-ai-wp-translator'), $timeout_errors);
            }
            if ($other_errors > 0) {
                $error_details[] = sprintf(__('%d other', 'nexus-ai-wp-translator'), $other_errors);
            }

            $message_parts[] = sprintf(__('Failed %d languages (%s)', 'nexus-ai-wp-translator'), count($errors), implode(', ', $error_details));
        }

        $message = implode(', ', $message_parts);

        // Add details for successes, errors and skipped
        $details = array();
        if (!empty($success_details)) {
            $details[] = 'Completed: ' . implode(', ', $success_details);
        }
        if (!empty($skipped)) {
            $details[] = 'Skipped: ' . implode(', ', $skipped);
        }
        if (!empty($errors)) {
            $details[] = 'Errors: ' . implode(', ', $errors);
        }

        if (!empty($details)) {
            $message .= ' | ' . implode(' | ', $details);
        }

        // Add throttle warning if applicable
        if ($throttle_errors > 0) {
            $throttle_limit = get_option('nexus_ai_wp_translator_throttle_limit', 100);
            $throttle_period = get_option('nexus_ai_wp_translator_throttle_period', 3600) / 3600; // Convert to hours
            $message .= sprintf(__(' | ⚠️ THROTTLE LIMIT: %d/%d API calls used in %s hours. Increase limit in Settings → Performance & Rate Limiting or wait.', 'nexus-ai-wp-translator'),
                $throttle_limit, $throttle_limit, round($throttle_period, 1));
        }

        // Log overall completion with clear action name
        $status = empty($errors) ? 'completed' : ($success_count > 0 ? 'partial' : 'failed');
        $action = 'batch_translate'; // Use a different action name to distinguish from individual translations
        $this->db->log_translation_activity($post_id, $action, $status, $message, $total_api_calls, $total_time);

        $final_result = array(
            'success' => $success_count > 0,
            'success_count' => $success_count,
            'error_count' => count($errors),
            'skipped_count' => count($skipped),
            'message' => $message,
            'errors' => $errors,
            'skipped' => $skipped,
            'total_api_calls' => $total_api_calls,
            'processing_time' => $total_time
        );
        
        error_log("Nexus AI WP Translator: [TRANSLATE] Final result: " . print_r($final_result, true));
        
        return $final_result;
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
        // Add comprehensive error logging
        error_log('Nexus AI WP Translator: [AJAX] translate_post called');
        error_log('Nexus AI WP Translator: [AJAX] POST data: ' . print_r($_POST, true));
        
        try {
            check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
            
            if (!current_user_can('edit_posts')) {
                error_log('Nexus AI WP Translator: [AJAX] Permission denied for user');
                wp_send_json_error(__('Permission denied', 'nexus-ai-wp-translator'));
                return;
            }
            
            $post_id = intval($_POST['post_id']);
            $target_languages = isset($_POST['target_languages']) ? (array) $_POST['target_languages'] : null;
            
            error_log('Nexus AI WP Translator: [AJAX] Processing post_id: ' . $post_id);
            error_log('Nexus AI WP Translator: [AJAX] Target languages: ' . print_r($target_languages, true));
            
            if (!$post_id) {
                error_log('Nexus AI WP Translator: [AJAX] Invalid post ID');
                wp_send_json_error(__('Invalid post ID', 'nexus-ai-wp-translator'));
                return;
            }
            
            // Validate target languages
            if (empty($target_languages)) {
                error_log('Nexus AI WP Translator: [AJAX] No target languages provided');
                wp_send_json_error(__('No target languages specified', 'nexus-ai-wp-translator'));
                return;
            }
            
            error_log('Nexus AI WP Translator: [AJAX] Starting translation process');
            $result = $this->translate_post($post_id, $target_languages);
            
            error_log('Nexus AI WP Translator: [AJAX] Translation result: ' . print_r($result, true));
            wp_send_json($result);
            
        } catch (Exception $e) {
            error_log('Nexus AI WP Translator: [AJAX] Exception: ' . $e->getMessage());
            error_log('Nexus AI WP Translator: [AJAX] Exception trace: ' . $e->getTraceAsString());
            wp_send_json_error(__('Translation failed: ', 'nexus-ai-wp-translator') . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Unlink translation
     */
    public function ajax_unlink_translation() {
        try {
            check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(__('Permission denied', 'nexus-ai-wp-translator'));
                return;
            }
            
            $post_id = intval($_POST['post_id']);
            $related_post_id = intval($_POST['related_post_id']);
            
            if (!$post_id || !$related_post_id) {
                wp_send_json_error(__('Invalid post IDs', 'nexus-ai-wp-translator'));
                return;
            }
            
            // Remove specific translation relationship between the two posts
            $result = $this->db->delete_specific_translation_relationship($post_id, $related_post_id);

            if ($result) {
                // Remove meta fields only from the related post
                delete_post_meta($related_post_id, '_nexus_ai_wp_translator_source_post');
                delete_post_meta($related_post_id, '_nexus_ai_wp_translator_translation_date');

                $this->db->log_translation_activity($post_id, 'unlink', 'completed', "Unlinked from post {$related_post_id}");

                wp_send_json_success(__('Translation unlinked successfully', 'nexus-ai-wp-translator'));
            } else {
                wp_send_json_error(__('Failed to unlink translation', 'nexus-ai-wp-translator'));
            }
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: AJAX unlink error: ' . $e->getMessage());
            }
            wp_send_json_error(__('Unlink failed: ', 'nexus-ai-wp-translator') . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Get translation status
     */
    public function ajax_get_translation_status() {
        try {
            check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(__('Permission denied', 'nexus-ai-wp-translator'));
                return;
            }
            
            $post_id = intval($_POST['post_id']);
            if (!$post_id) {
                wp_send_json_error(__('Invalid post ID', 'nexus-ai-wp-translator'));
                return;
            }
            
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
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: AJAX get status error: ' . $e->getMessage());
            }
            wp_send_json_error(__('Failed to get status: ', 'nexus-ai-wp-translator') . $e->getMessage());
        }
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
        error_log("Nexus AI WP Translator: *** ajax_handle_post_action() STARTED ***");
        
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            error_log("Nexus AI WP Translator: Permission denied for user in ajax_handle_post_action");
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }
        
        // Log all POST data for debugging
        error_log("Nexus AI WP Translator: POST data: " . print_r($_POST, true));
        
        $post_id = intval($_POST['post_id']);
        $post_action = sanitize_text_field($_POST['post_action']); // 'delete' or 'trash'
        $user_choice = sanitize_text_field($_POST['user_choice']); // 'delete_all' or 'unlink_only'
        
        error_log("Nexus AI WP Translator: Handling post action - Post: {$post_id}, Action: {$post_action}, Choice: {$user_choice}");
        
        // Validate input parameters
        if (empty($post_id) || empty($post_action) || empty($user_choice)) {
            error_log("Nexus AI WP Translator: Missing required parameters");
            wp_send_json_error('Missing required parameters');
            return;
        }
        
        if (!in_array($post_action, array('delete', 'trash'))) {
            error_log("Nexus AI WP Translator: Invalid post action: {$post_action}");
            wp_send_json_error('Invalid post action');
            return;
        }
        
        if (!in_array($user_choice, array('delete_all', 'unlink_only'))) {
            error_log("Nexus AI WP Translator: Invalid user choice: {$user_choice}");
            wp_send_json_error('Invalid user choice');
            return;
        }
        
        // Wrap everything in try-catch to prevent fatal errors
        try {
            if ($user_choice === 'unlink_only') {
                error_log("Nexus AI WP Translator: Processing unlink_only for post {$post_id}");
                
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
                    error_log("Nexus AI WP Translator: About to delete post {$post_id}");
                    $delete_result = wp_delete_post($post_id, true);
                    error_log("Nexus AI WP Translator: Delete post {$post_id} result: " . ($delete_result ? 'SUCCESS' : 'FAILED'));
                    if (!$delete_result) {
                        throw new Exception("Failed to delete post {$post_id}");
                    }
                } else {
                    error_log("Nexus AI WP Translator: About to trash post {$post_id}");
                    $trash_result = wp_trash_post($post_id);
                    error_log("Nexus AI WP Translator: Trash post {$post_id} result: " . ($trash_result ? 'SUCCESS' : 'FAILED'));
                    if (!$trash_result) {
                        throw new Exception("Failed to trash post {$post_id}");
                    }
                }
                
                $this->db->log_translation_activity($post_id, $post_action . '_unlink_only', 'completed', 'Post processed with unlink only option');
                
            } else { // delete_all
                error_log("Nexus AI WP Translator: Processing delete_all for post {$post_id}");
                
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
                error_log("Nexus AI WP Translator: Deleting translation relationships for post {$post_id}");
                $this->db->delete_translation_relationships($post_id);
                
                // Perform action on all posts
                $failed_posts = array();
                foreach ($all_post_ids as $id) {
                    if ($post_action === 'delete') {
                        error_log("Nexus AI WP Translator: About to delete post {$id}");
                        $delete_result = wp_delete_post($id, true);
                        error_log("Nexus AI WP Translator: Delete post {$id} result: " . ($delete_result ? 'SUCCESS' : 'FAILED'));
                        if (!$delete_result) {
                            $failed_posts[] = $id;
                        }
                    } else {
                        error_log("Nexus AI WP Translator: About to trash post {$id}");
                        $trash_result = wp_trash_post($id);
                        error_log("Nexus AI WP Translator: Trash post {$id} result: " . ($trash_result ? 'SUCCESS' : 'FAILED'));
                        if (!$trash_result) {
                            $failed_posts[] = $id;
                        }
                    }
                }
                
                if (!empty($failed_posts)) {
                    throw new Exception("Failed to process posts: " . implode(', ', $failed_posts));
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
        } catch (Throwable $e) {
            error_log("Nexus AI WP Translator: Throwable in ajax_handle_post_action: " . $e->getMessage());
            error_log("Nexus AI WP Translator: Throwable trace: " . $e->getTraceAsString());
            wp_send_json_error('Unexpected error occurred: ' . $e->getMessage());
        }
        
        error_log("Nexus AI WP Translator: *** ajax_handle_post_action() ENDED ***");
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