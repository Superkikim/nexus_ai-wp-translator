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
    private $seo_optimizer;
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
        $this->seo_optimizer = Nexus_AI_WP_Translator_SEO_Optimizer::get_instance();

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
        add_action('wp_ajax_nexus_ai_wp_link_posts', array($this, 'ajax_link_posts'));

        
        // Post management scripts removed per user request
    }
    
    // Post management scripts method removed per user request
    

    
    /**
     * Translate a post to all target languages
     */
    public function translate_post($post_id, $target_languages = null, $progress_id = null) {
        $start_time = microtime(true);

        // Validate post exists
        $post = get_post($post_id);
        if (!$post) {
            return array(
                'success' => false,
                'message' => __('Post not found.', 'nexus-ai-wp-translator'),
                'success_count' => 0,
                'error_count' => 1
            );
        }

        if (!$target_languages) {
            $target_languages = get_option('nexus_ai_wp_translator_target_languages', array('es', 'fr', 'de'));
        }

        // Ensure target_languages is an array (fix for string conversion issue)
        if (is_string($target_languages)) {
            // Handle serialized string or comma-separated values
            if (is_serialized($target_languages)) {
                $target_languages = maybe_unserialize($target_languages);
            } else {
                // Fallback: split by comma if it's a comma-separated string
                $target_languages = array_map('trim', explode(',', $target_languages));
            }
        }

        if (!is_array($target_languages)) {
            $target_languages = array($target_languages);
        }

        // Validate we have target languages
        if (empty($target_languages)) {
            return array(
                'success' => false,
                'message' => __('No target languages specified. Please configure target languages in Settings.', 'nexus-ai-wp-translator'),
                'success_count' => 0,
                'error_count' => 1
            );
        }

        // Validate that all target languages are supported
        if (!$this->validate_language_codes($target_languages)) {
            return array(
                'success' => false,
                'message' => __('One or more target languages are not supported. Please check your language settings.', 'nexus-ai-wp-translator'),
                'success_count' => 0,
                'error_count' => 1
            );
        }

        // Get source language, with automatic detection if not set
        $source_lang = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true);

        if (empty($source_lang)) {
            // Attempt to detect language automatically
            $detection_result = $this->api_handler->detect_language($post->post_content . ' ' . $post->post_title);

            if ($detection_result['success']) {
                $source_lang = $detection_result['language'];
                // Store the detected language for future use
                update_post_meta($post_id, '_nexus_ai_wp_translator_language', $source_lang);

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Nexus AI WP Translator: Auto-detected language '{$source_lang}' for post {$post_id}");
                }
            } else {
                // Fall back to default source language
                $source_lang = get_option('nexus_ai_wp_translator_source_language', 'en');

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Nexus AI WP Translator: Language detection failed for post {$post_id}, using default: {$source_lang}");
                }
            }
        }

        // Validate source language
        if (!$this->is_valid_language($source_lang)) {
            return array(
                'success' => false,
                'message' => sprintf(__('Source language "%s" is not supported. Please set a valid language for this post.', 'nexus-ai-wp-translator'), $source_lang),
                'success_count' => 0,
                'error_count' => 1
            );
        }

        $total_api_calls = 0;
        $success_count = 0;
        $errors = array();
        $skipped = array();
        
        foreach ($target_languages as $target_lang) {
            // Skip if target language is same as source
            if ($target_lang === $source_lang) {
                $skipped[] = $target_lang . ': ' . __('Same as source language', 'nexus-ai-wp-translator');
                continue;
            }

            // Check if translation already exists
            $existing_translation = $this->db->get_translated_post($post_id, $target_lang);
            $is_retranslation = false;

            if ($existing_translation && $existing_translation->status === 'completed') {
                // For manual translations, we allow retranslation by deleting the existing one
                // This is triggered from the admin interface when user wants to retranslate
                if (isset($_POST['force_retranslate']) || current_user_can('edit_posts')) {
                    // Delete existing translated post
                    wp_delete_post($existing_translation->translated_post_id, true);
                    // Remove translation relationship
                    $this->db->delete_translation_relationships($post_id, $target_lang);
                    $is_retranslation = true;

                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Nexus AI WP Translator: Retranslating post {$post_id} to {$target_lang} - deleted existing translation");
                    }
                } else {
                    $skipped[] = $target_lang . ': ' . __('Translation already exists', 'nexus-ai-wp-translator');
                    continue;
                }
            }

            // Clear cached translations for this post and target language to ensure fresh translation
            $this->api_handler->clear_post_translation_cache($post_id, array($target_lang));

            // Log translation start
            $this->db->log_translation_activity($post_id, 'translate_start', 'processing', "Starting translation to {$target_lang}");

            // Perform translation
            $result = $this->create_translated_post($post_id, $target_lang, $progress_id);

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

                // If it's a throttle error, stop trying other languages to avoid wasting attempts
                if (strpos($result['message'], 'API call limit reached') !== false) {
                    $errors[] = __('Stopping further translations due to API limit. Please increase throttle limit in Settings or try again later.', 'nexus-ai-wp-translator');
                    break;
                }
            }
        }
        
        $total_time = microtime(true) - $start_time;
        
        // Build detailed message
        $message_parts = array();

        if ($success_count > 0) {
            $message_parts[] = sprintf(__('Successfully translated to %d languages', 'nexus-ai-wp-translator'), $success_count);
        }

        if (!empty($skipped)) {
            $message_parts[] = sprintf(__('Skipped %d languages', 'nexus-ai-wp-translator'), count($skipped));
        }

        if (!empty($errors)) {
            $message_parts[] = sprintf(__('Failed %d languages', 'nexus-ai-wp-translator'), count($errors));
        }

        $message = implode(', ', $message_parts);

        // Add details for errors and skipped
        $details = array();
        if (!empty($skipped)) {
            $details[] = 'Skipped: ' . implode(', ', $skipped);
        }
        if (!empty($errors)) {
            $details[] = 'Errors: ' . implode(', ', $errors);
        }

        if (!empty($details)) {
            $message .= ' | ' . implode(' | ', $details);
        }

        // Log overall completion
        $status = empty($errors) ? 'completed' : ($success_count > 0 ? 'partial' : 'failed');
        $this->db->log_translation_activity($post_id, 'translate_complete', $status, $message, $total_api_calls, $total_time);

        return array(
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
    }
    
    /**
     * Create translated post using improved streaming approach
     */
    private function create_translated_post($source_post_id, $target_lang, $progress_id = null) {
        $source_post = get_post($source_post_id);
        if (!$source_post) {
            return array('success' => false, 'message' => __('Source post not found', 'nexus-ai-wp-translator'));
        }
        
        // Generate progress ID for real-time tracking if not provided
        if (!$progress_id) {
            $progress_id = 'trans_' . $source_post_id . '_' . $target_lang . '_' . time() . '_' . wp_rand(1000, 9999);
        }

        // Check if we should use streaming approach (default to true for better results)
        $use_streaming = get_option('nexus_ai_wp_translator_use_streaming', true);
        $use_complete_json = get_option('nexus_ai_wp_translator_use_complete_json', true);

        error_log("Nexus AI WP Translator: Creating translated post using " . 
            ($use_complete_json ? 'complete JSON' : 'block-by-block') . 
            " approach with " . ($use_streaming ? 'streaming' : 'standard') . " API calls");

        // Try the new complete JSON translation approach first
        if ($use_complete_json) {
            $translation_result = $this->api_handler->translate_post_content_complete(
                $source_post_id, 
                $target_lang, 
                $progress_id, 
                $use_streaming
            );

            // If the new approach fails, log and fall back to the old approach
            if (!$translation_result['success']) {
                error_log("Nexus AI WP Translator: Complete JSON translation failed: " . $translation_result['message']);
                
                // Check if this is an interruption that can be resumed
                if (isset($translation_result['interrupted']) && $translation_result['interrupted']) {
                    error_log("Nexus AI WP Translator: Translation was interrupted, attempting resume...");
                    return $this->handle_translation_interruption($source_post_id, $target_lang, $progress_id, $translation_result);
                }
                
                error_log("Nexus AI WP Translator: Falling back to block-by-block translation approach");
                $translation_result = $this->api_handler->translate_post_content($source_post_id, $target_lang, $progress_id);
            }
        } else {
            // Use the original block-by-block approach
            $translation_result = $this->api_handler->translate_post_content($source_post_id, $target_lang, $progress_id);
        }
        
        if (!$translation_result['success']) {
            return $translation_result;
        }
        
        // Determine post status based on settings
        $save_as_draft = get_option('nexus_ai_wp_translator_save_as_draft', false);
        $post_status = $save_as_draft ? 'draft' : $source_post->post_status;

        // Handle translated categories and tags
        $translated_categories = array();
        $translated_tags = array();

        // Create translated categories with language prefix
        if (!empty($translation_result['categories'])) {
            foreach ($translation_result['categories'] as $translated_cat_name) {
                $cat_slug = $target_lang . '_' . sanitize_title($translated_cat_name);

                // Check if category exists, create if not
                $existing_cat = get_category_by_slug($cat_slug);
                if (!$existing_cat) {
                    $cat_id = wp_insert_category(array(
                        'cat_name' => $translated_cat_name,
                        'category_nicename' => $cat_slug
                    ));
                    if (!is_wp_error($cat_id)) {
                        $translated_categories[] = $cat_id;
                    }
                } else {
                    $translated_categories[] = $existing_cat->term_id;
                }
            }
        }

        // Use translated tags or fallback to original
        if (!empty($translation_result['tags'])) {
            $translated_tags = $translation_result['tags'];
        } else {
            $translated_tags = wp_get_post_tags($source_post_id, array('fields' => 'names'));
        }

        // Create new post
        $translated_post_data = array(
            'post_title' => $translation_result['title'],
            'post_content' => $translation_result['content'],
            'post_excerpt' => $translation_result['excerpt'],
            'post_status' => $post_status,
            'post_type' => $source_post->post_type,
            'post_author' => $source_post->post_author,
            'post_category' => !empty($translated_categories) ? $translated_categories : wp_get_post_categories($source_post_id),
            'tags_input' => $translated_tags,
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
        
        // Store translation relationship (will be updated with quality data later)
        $source_lang = get_post_meta($source_post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');
        
        // Set language meta for source post if not set
        if (!get_post_meta($source_post_id, '_nexus_ai_wp_translator_language', true)) {
            update_post_meta($source_post_id, '_nexus_ai_wp_translator_language', $source_lang);
        }

        // Clear translation cache after successful post creation
        $this->api_handler->clear_post_translation_cache($source_post_id, array($target_lang));

        // Extract basic quality assessment from translation result
        $quality_assessment = null;
        $use_quality_assessment = get_option('nexus_ai_wp_translator_use_llm_quality_assessment', true);

        if ($use_quality_assessment && isset($translation_result['confidence_assessment'])) {
            // Store basic quality assessment from translation
            $quality_assessment = array(
                'assessment_type' => 'basic',
                'confidence_level' => $translation_result['confidence_assessment']['level'],
                'confidence_reason' => $translation_result['confidence_assessment']['reason'] ?? null,
                'assessment_date' => current_time('mysql'),
                'post_id' => $translated_post_id,
                'source_post_id' => $source_post_id,
                'detailed_assessment_available' => true
            );

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: Basic quality assessment: " . wp_json_encode($quality_assessment));
            }

            // Store basic quality assessment as post meta
            update_post_meta($translated_post_id, '_nexus_ai_wp_translator_quality_assessment', $quality_assessment);

            // Store translation relationship with basic quality data
            $this->db->store_translation_relationship($source_post_id, $translated_post_id, $source_lang, $target_lang, 'completed', $quality_assessment);
        } else {
            // Quality assessment disabled or not available
            if (defined('WP_DEBUG') && WP_DEBUG) {
                if (!$use_quality_assessment) {
                    error_log("Nexus AI WP Translator: Quality assessment disabled in settings");
                } else {
                    error_log("Nexus AI WP Translator: No confidence assessment in translation result");
                }
            }

            // Store translation relationship without quality data
            $this->db->store_translation_relationship($source_post_id, $translated_post_id, $source_lang, $target_lang, 'completed', null);
        }

        // Translate and add meta descriptions for SEO
        $this->seo_optimizer->add_translated_meta_description($translated_post_id, $source_post_id, $target_lang);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $quality_score = (isset($quality_assessment['overall_score'])) ? $quality_assessment['overall_score'] : 'N/A';
            error_log("Nexus AI WP Translator: Successfully created translated post {$translated_post_id}, quality score: {$quality_score}");
        }

        $translation_result['translated_post_id'] = $translated_post_id;
        $translation_result['quality_assessment'] = $quality_assessment;
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
        try {
            check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(__('Permission denied', 'nexus-ai-wp-translator'));
                return;
            }
            
            $post_id = intval($_POST['post_id']);
            $target_languages = isset($_POST['target_languages']) ? (array) $_POST['target_languages'] : null;
            $progress_id = isset($_POST['progress_id']) ? sanitize_text_field($_POST['progress_id']) : null;

            if (!$post_id) {
                wp_send_json_error(__('Invalid post ID', 'nexus-ai-wp-translator'));
                return;
            }

            // Validate target languages if provided
            if ($target_languages && !$this->validate_language_codes($target_languages)) {
                wp_send_json_error(__('One or more target languages are not supported', 'nexus-ai-wp-translator'));
                return;
            }

            $result = $this->translate_post($post_id, $target_languages, $progress_id);
            
            wp_send_json($result);
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: AJAX translate error: ' . $e->getMessage());
            }
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
            
            // Clear cached translations before unlinking
            $target_lang = get_post_meta($related_post_id, '_nexus_ai_wp_translator_language', true);
            if ($target_lang) {
                $this->api_handler->clear_post_translation_cache($post_id, array($target_lang));
            }

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
     * Validate language code
     */
    public function is_valid_language($language_code) {
        if (empty($language_code) || !is_string($language_code)) {
            return false;
        }

        $available_languages = $this->get_available_languages();
        return array_key_exists($language_code, $available_languages);
    }

    /**
     * Validate language codes array
     */
    public function validate_language_codes($language_codes) {
        if (!is_array($language_codes)) {
            return false;
        }

        foreach ($language_codes as $code) {
            if (!$this->is_valid_language($code)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get available languages - comprehensive ISO 639-1 list
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
            'zh' => __('Chinese', 'nexus-ai-wp-translator'),
            'ja' => __('Japanese', 'nexus-ai-wp-translator'),
            'ar' => __('Arabic', 'nexus-ai-wp-translator'),
            'hi' => __('Hindi', 'nexus-ai-wp-translator'),
            'nl' => __('Dutch', 'nexus-ai-wp-translator'),
            'sv' => __('Swedish', 'nexus-ai-wp-translator'),
            'da' => __('Danish', 'nexus-ai-wp-translator'),
            'no' => __('Norwegian', 'nexus-ai-wp-translator'),
            'fi' => __('Finnish', 'nexus-ai-wp-translator'),
            'pl' => __('Polish', 'nexus-ai-wp-translator'),
            'cs' => __('Czech', 'nexus-ai-wp-translator'),
            'hu' => __('Hungarian', 'nexus-ai-wp-translator'),
            'ko' => __('Korean', 'nexus-ai-wp-translator'),
            'tr' => __('Turkish', 'nexus-ai-wp-translator'),
            'uk' => __('Ukrainian', 'nexus-ai-wp-translator'),
            'ro' => __('Romanian', 'nexus-ai-wp-translator'),
            'bg' => __('Bulgarian', 'nexus-ai-wp-translator'),
            'hr' => __('Croatian', 'nexus-ai-wp-translator'),
            'sk' => __('Slovak', 'nexus-ai-wp-translator'),
            'sl' => __('Slovenian', 'nexus-ai-wp-translator'),
            'et' => __('Estonian', 'nexus-ai-wp-translator'),
            'lv' => __('Latvian', 'nexus-ai-wp-translator'),
            'lt' => __('Lithuanian', 'nexus-ai-wp-translator'),
            'mt' => __('Maltese', 'nexus-ai-wp-translator'),
            'el' => __('Greek', 'nexus-ai-wp-translator'),
            'cy' => __('Welsh', 'nexus-ai-wp-translator'),
            'ga' => __('Irish', 'nexus-ai-wp-translator'),
            'is' => __('Icelandic', 'nexus-ai-wp-translator'),
            'mk' => __('Macedonian', 'nexus-ai-wp-translator'),
            'sq' => __('Albanian', 'nexus-ai-wp-translator'),
            'sr' => __('Serbian', 'nexus-ai-wp-translator'),
            'bs' => __('Bosnian', 'nexus-ai-wp-translator'),
            'he' => __('Hebrew', 'nexus-ai-wp-translator'),
            'th' => __('Thai', 'nexus-ai-wp-translator'),
            'vi' => __('Vietnamese', 'nexus-ai-wp-translator'),
            'id' => __('Indonesian', 'nexus-ai-wp-translator'),
            'ms' => __('Malay', 'nexus-ai-wp-translator'),
            'tl' => __('Filipino', 'nexus-ai-wp-translator'),
            'sw' => __('Swahili', 'nexus-ai-wp-translator'),
            'am' => __('Amharic', 'nexus-ai-wp-translator'),
            'bn' => __('Bengali', 'nexus-ai-wp-translator'),
            'gu' => __('Gujarati', 'nexus-ai-wp-translator'),
            'kn' => __('Kannada', 'nexus-ai-wp-translator'),
            'ml' => __('Malayalam', 'nexus-ai-wp-translator'),
            'mr' => __('Marathi', 'nexus-ai-wp-translator'),
            'ne' => __('Nepali', 'nexus-ai-wp-translator'),
            'or' => __('Odia', 'nexus-ai-wp-translator'),
            'pa' => __('Punjabi', 'nexus-ai-wp-translator'),
            'si' => __('Sinhala', 'nexus-ai-wp-translator'),
            'ta' => __('Tamil', 'nexus-ai-wp-translator'),
            'te' => __('Telugu', 'nexus-ai-wp-translator'),
            'ur' => __('Urdu', 'nexus-ai-wp-translator'),
            'my' => __('Myanmar', 'nexus-ai-wp-translator'),
            'km' => __('Khmer', 'nexus-ai-wp-translator'),
            'lo' => __('Lao', 'nexus-ai-wp-translator'),
            'ka' => __('Georgian', 'nexus-ai-wp-translator'),
            'hy' => __('Armenian', 'nexus-ai-wp-translator'),
            'az' => __('Azerbaijani', 'nexus-ai-wp-translator'),
            'kk' => __('Kazakh', 'nexus-ai-wp-translator'),
            'ky' => __('Kyrgyz', 'nexus-ai-wp-translator'),
            'mn' => __('Mongolian', 'nexus-ai-wp-translator'),
            'tg' => __('Tajik', 'nexus-ai-wp-translator'),
            'tk' => __('Turkmen', 'nexus-ai-wp-translator'),
            'uz' => __('Uzbek', 'nexus-ai-wp-translator'),
            'af' => __('Afrikaans', 'nexus-ai-wp-translator'),
            'zu' => __('Zulu', 'nexus-ai-wp-translator'),
            'xh' => __('Xhosa', 'nexus-ai-wp-translator'),
            'yo' => __('Yoruba', 'nexus-ai-wp-translator'),
            'ig' => __('Igbo', 'nexus-ai-wp-translator'),
            'ha' => __('Hausa', 'nexus-ai-wp-translator'),
            'eu' => __('Basque', 'nexus-ai-wp-translator'),
            'ca' => __('Catalan', 'nexus-ai-wp-translator'),
            'gl' => __('Galician', 'nexus-ai-wp-translator'),
            'be' => __('Belarusian', 'nexus-ai-wp-translator'),
            'fo' => __('Faroese', 'nexus-ai-wp-translator'),
            'lb' => __('Luxembourgish', 'nexus-ai-wp-translator'),
            'br' => __('Breton', 'nexus-ai-wp-translator'),
            'co' => __('Corsican', 'nexus-ai-wp-translator'),
            'oc' => __('Occitan', 'nexus-ai-wp-translator'),
            'fy' => __('Frisian', 'nexus-ai-wp-translator'),
            'gd' => __('Scottish Gaelic', 'nexus-ai-wp-translator'),
            'kw' => __('Cornish', 'nexus-ai-wp-translator'),
            'gv' => __('Manx', 'nexus-ai-wp-translator')
        );
    }
    
    /**
     * AJAX: Link posts together
     */
    public function ajax_link_posts() {
        try {
            check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(__('Permission denied', 'nexus-ai-wp-translator'));
                return;
            }
            
            $original_post_id = intval($_POST['original_post_id']);
            $translated_post_ids = isset($_POST['translated_post_ids']) ? (array) $_POST['translated_post_ids'] : array();
            
            if (!$original_post_id || empty($translated_post_ids)) {
                wp_send_json_error(__('Invalid post IDs', 'nexus-ai-wp-translator'));
                return;
            }
            
            // Validate that all posts exist
            $original_post = get_post($original_post_id);
            if (!$original_post) {
                wp_send_json_error(__('Original post not found', 'nexus-ai-wp-translator'));
                return;
            }
            
            foreach ($translated_post_ids as $post_id) {
                $post = get_post($post_id);
                if (!$post) {
                    wp_send_json_error(__('One or more translated posts not found', 'nexus-ai-wp-translator'));
                    return;
                }
            }
            
            // Validate that all posts are from different languages
            $original_language = get_post_meta($original_post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');
            
            // Check that each translated post has a unique language
            $languages = array($original_language);
            
            foreach ($translated_post_ids as $post_id) {
                $language = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');
                if (in_array($language, $languages)) {
                    wp_send_json_error(__('All posts must be from different languages', 'nexus-ai-wp-translator'));
                    return;
                }
                $languages[] = $language;
            }
            
            // Store the relationships
            foreach ($translated_post_ids as $post_id) {
                $this->db->store_translation_relationship($original_post_id, $post_id, $original_language, get_post_meta($post_id, '_nexus_ai_wp_translator_language', true));
            }
            
            // Update meta for translated posts to indicate they're linked
            foreach ($translated_post_ids as $post_id) {
                update_post_meta($post_id, '_nexus_ai_wp_translator_source_post', $original_post_id);
            }
            
            // Log the linking action
            $this->db->log_translation_activity(
                $original_post_id,
                'link',
                'completed',
                'Linked posts together: ' . implode(', ', $translated_post_ids)
            );
            
            wp_send_json_success(__('Posts linked successfully', 'nexus-ai-wp-translator'));
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: AJAX link posts error: ' . $e->getMessage());
            }
            wp_send_json_error(__('Linking failed: ', 'nexus-ai-wp-translator') . $e->getMessage());
        }
    }

    /**
     * Handle translation interruption and attempt resume
     */
    private function handle_translation_interruption($source_post_id, $target_lang, $progress_id, $interrupted_result) {
        error_log("Nexus AI WP Translator: Handling translation interruption for post {$source_post_id} -> {$target_lang}");

        // Save the interrupted state for potential manual resume
        $interruption_data = array(
            'post_id' => $source_post_id,
            'target_lang' => $target_lang,
            'progress_id' => $progress_id,
            'interrupted_at' => time(),
            'partial_result' => $interrupted_result,
            'resume_attempts' => 0
        );

        // Cache interruption data for 1 hour
        $cache_key = 'nexus_ai_wp_interrupted_' . $source_post_id . '_' . $target_lang;
        set_transient($cache_key, $interruption_data, HOUR_IN_SECONDS);

        // Log the interruption
        $this->db->log_translation_activity(
            $source_post_id, 
            'interrupted', 
            'failed', 
            'Translation was interrupted - cached for potential resume'
        );

        // Return a detailed error response
        return array(
            'success' => false,
            'message' => __('Translation was interrupted due to connection issues. You can try again to resume from where it left off.', 'nexus-ai-wp-translator'),
            'interrupted' => true,
            'resumable' => true,
            'cache_key' => $cache_key
        );
    }

    /**
     * Check and resume interrupted translation
     */
    public function resume_interrupted_translation($source_post_id, $target_lang) {
        $cache_key = 'nexus_ai_wp_interrupted_' . $source_post_id . '_' . $target_lang;
        $interruption_data = get_transient($cache_key);

        if (!$interruption_data) {
            return false;
        }

        error_log("Nexus AI WP Translator: Resuming interrupted translation for post {$source_post_id} -> {$target_lang}");

        // Increment resume attempts
        $interruption_data['resume_attempts']++;
        
        // Don't allow too many resume attempts
        if ($interruption_data['resume_attempts'] > 3) {
            delete_transient($cache_key);
            return false;
        }

        // Update the cached data
        set_transient($cache_key, $interruption_data, HOUR_IN_SECONDS);

        // Try the complete JSON approach again with non-streaming to be safer
        $translation_result = $this->api_handler->translate_post_content_complete(
            $source_post_id, 
            $target_lang, 
            $interruption_data['progress_id'], 
            false // Use standard (non-streaming) API call for resume
        );

        if ($translation_result['success']) {
            // Clear the interruption cache on success
            delete_transient($cache_key);
            error_log("Nexus AI WP Translator: Successfully resumed interrupted translation");
        }

        return $translation_result;
    }

    /**
     * Get interrupted translation status
     */
    public function get_interrupted_translations($post_id = null) {
        $interrupted = array();
        
        if ($post_id) {
            // Check for specific post
            $target_languages = get_option('nexus_ai_wp_translator_target_languages', array('es', 'fr', 'de'));
            foreach ($target_languages as $target_lang) {
                $cache_key = 'nexus_ai_wp_interrupted_' . $post_id . '_' . $target_lang;
                $data = get_transient($cache_key);
                if ($data) {
                    $interrupted[] = $data;
                }
            }
        } else {
            // This would require a more complex implementation to scan all possible cache keys
            // For now, we'll just return empty array if no specific post is requested
        }

        return $interrupted;
    }

    /**
     * Clear interrupted translation cache
     */
    public function clear_interrupted_translation($source_post_id, $target_lang) {
        $cache_key = 'nexus_ai_wp_interrupted_' . $source_post_id . '_' . $target_lang;
        return delete_transient($cache_key);
    }

    /**
     * Perform detailed quality assessment on-demand
     */
    public function perform_detailed_quality_assessment($source_post_id, $translated_post_id, $source_lang, $target_lang) {
        // Get original and translated content
        $source_post = get_post($source_post_id);
        $translated_post = get_post($translated_post_id);

        if (!$source_post || !$translated_post) {
            return array(
                'success' => false,
                'message' => __('Could not load post content', 'nexus-ai-wp-translator')
            );
        }

        $original_content = $source_post->post_content;
        $translated_content = $translated_post->post_content;

        // Use API handler to perform detailed assessment
        $assessment_result = $this->api_handler->perform_detailed_quality_assessment(
            $original_content,
            $translated_content,
            $source_lang,
            $target_lang
        );

        if (!$assessment_result['success']) {
            return $assessment_result;
        }

        // Create comprehensive quality assessment
        $detailed_assessment = array(
            'assessment_type' => 'detailed',
            'overall_score' => $assessment_result['data']['overall_score'] ?? 0,
            'grade' => $assessment_result['data']['grade'] ?? 'F',
            'completeness_score' => $assessment_result['data']['completeness'] ?? 0,
            'consistency_score' => $assessment_result['data']['consistency'] ?? 0,
            'structure_score' => $assessment_result['data']['structure'] ?? 0,
            'length_score' => $assessment_result['data']['length'] ?? 0,
            'issues' => $assessment_result['data']['issues'] ?? array(),
            'suggestions' => $assessment_result['data']['suggestions'] ?? array(),
            'metrics' => $assessment_result['data']['metrics'] ?? array(),
            'assessment_date' => current_time('mysql'),
            'post_id' => $translated_post_id,
            'source_post_id' => $source_post_id,
            'api_cost' => $assessment_result['data']['cost'] ?? 0
        );

        // Update the quality assessment with detailed data
        update_post_meta($translated_post_id, '_nexus_ai_wp_translator_quality_assessment', $detailed_assessment);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Detailed assessment completed - Overall: {$detailed_assessment['overall_score']}%");
        }

        return array(
            'success' => true,
            'data' => $detailed_assessment
        );
    }

    /**
     * Calculate grade from score
     */
    private function calculate_grade_from_score($score) {
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 75) return 'B+';
        if ($score >= 70) return 'B';
        if ($score >= 65) return 'B-';
        if ($score >= 60) return 'C+';
        if ($score >= 55) return 'C';
        if ($score >= 50) return 'C-';
        if ($score >= 40) return 'D';
        return 'F';
    }


}
