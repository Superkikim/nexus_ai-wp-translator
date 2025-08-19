<?php
/**
 * Nexus AI WP Translator API Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_API_Handler {
    
    private static $instance = null;
    private $api_key;
    private $api_endpoint = 'https://api.anthropic.com/v1/messages';
    private $models_endpoint = 'https://api.anthropic.com/v1/models';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->api_key = get_option('nexus_ai_wp_translator_api_key', '');
    }
    
    /**
     * Refresh API key from database
     */
    public function refresh_api_key() {
        $this->api_key = get_option('nexus_ai_wp_translator_api_key', '');
    }
    
    /**
     * Get available models from Claude API
     */
    public function get_available_models() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: Getting available models from API');
        }
        
        if (empty($this->api_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: No API key provided for getting models');
            }
            return array(
                'success' => false,
                'message' => __('API key is required', 'nexus-ai-wp-translator')
            );
        }
        
        $headers = array(
            'Content-Type' => 'application/json',
            'x-api-key' => $this->api_key,
            'anthropic-version' => '2023-06-01'
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: Making request to models endpoint: ' . $this->models_endpoint);
        }
        
        $response = wp_remote_get($this->models_endpoint, array(
            'headers' => $headers,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: WP Error getting models: ' . $response->get_error_message());
            }
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: Models API response code: ' . $response_code);
            error_log('Nexus AI WP Translator: Models API response body: ' . substr($response_body, 0, 500) . '...');
        }
        
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) 
                ? $error_data['error']['message'] 
                : __('Failed to retrieve models', 'nexus-ai-wp-translator');
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: Models API error: ' . $error_message);
            }
                
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
        
        $data = json_decode($response_body, true);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: Decoded models data: ' . print_r($data, true));
        }
        
        // Check if we have a valid response structure
        if (!isset($data['data']) || !is_array($data['data'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: No models data in response, using fallback models');
            }
            // Fallback to known models if API doesn't return model list
            $models = array(
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Latest)',
                'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku',
                'claude-3-opus-20240229' => 'Claude 3 Opus'
            );
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: Processing models from API response');
            }
            $models = array();
            foreach ($data['data'] as $model) {
                if (isset($model['id'])) {
                    $display_name = $this->format_model_name($model['id']);
                    $models[$model['id']] = $display_name;
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Nexus AI WP Translator: Added model: ' . $model['id'] . ' -> ' . $display_name);
                    }
                }
            }
            
            // If no models found, use fallback
            if (empty($models)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Nexus AI WP Translator: No models found in API response, using fallback');
                }
                $models = array(
                    'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Latest)',
                    'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
                    'claude-3-haiku-20240307' => 'Claude 3 Haiku',
                    'claude-3-opus-20240229' => 'Claude 3 Opus'
                );
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: Final models array: ' . print_r($models, true));
        }
        
        return array(
            'success' => true,
            'models' => $models
        );
    }
    
    /**
     * Format model name for display
     */
    private function format_model_name($model_id) {
        $name_map = array(
            'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Latest)',
            'claude-3-5-sonnet-20240620' => 'Claude 3.5 Sonnet',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku',
            'claude-3-opus-20240229' => 'Claude 3 Opus'
        );
        
        return isset($name_map[$model_id]) ? $name_map[$model_id] : ucwords(str_replace('-', ' ', $model_id));
    }
    
    /**
     * Test API connection
     */
    public function test_api_connection() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: Testing API connection');
        }
        
        if (empty($this->api_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: API key is empty');
            }
            return array(
                'success' => false,
                'message' => __('API key is required', 'nexus-ai-wp-translator')
            );
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: Making simple API test request');
        }
        
        $headers = array(
            'Content-Type' => 'application/json',
            'x-api-key' => $this->api_key,
            'anthropic-version' => '2023-06-01'
        );
        
        // Simple test message - just check if API key works
        $body = array(
            'model' => 'claude-3-haiku-20240307', // Use fastest/cheapest model for test
            'max_tokens' => 10,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => 'Hi'
                )
            )
        );
        
        $response = wp_remote_post($this->api_endpoint, array(
            'headers' => $headers,
            'body' => wp_json_encode($body),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: Connection test failed - ' . $error_message);
            }
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: API test response code: {$response_code}");
        }
        
        if ($response_code === 200) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: API connection test successful');
            }
            return array(
                'success' => true,
                'message' => __('API connection successful', 'nexus-ai-wp-translator')
            );
        } else {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) 
                ? $error_data['error']['message'] 
                : __('API connection failed', 'nexus-ai-wp-translator');
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: API test failed - ' . $error_message);
            }
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
    }
    
    /**
     * Translate content using Claude AI
     */
    public function translate_content($content, $source_lang, $target_lang, $model = null) {
        error_log("Nexus AI WP Translator: Starting translation from {$source_lang} to {$target_lang}");
        
        if (empty($this->api_key)) {
            error_log('Nexus AI WP Translator: API key not configured');
            return array(
                'success' => false,
                'message' => __('API key not configured', 'nexus-ai-wp-translator')
            );
        }
        
        // Check throttle limits
        $throttle_check = $this->check_throttle_limits_detailed();
        if (!$throttle_check['allowed']) {
            error_log('Nexus AI WP Translator: API call limit reached');
            return array(
                'success' => false,
                'message' => $throttle_check['message']
            );
        }
        
        $start_time = microtime(true);
        
        // Get model from parameter or settings with detailed debugging
        if (!$model) {
            $model = get_option('nexus_ai_wp_translator_model', 'claude-3-5-sonnet-20241022');
            error_log("Nexus AI WP Translator: Retrieved model from settings: '" . $model . "'");
            error_log("Nexus AI WP Translator: Model length: " . strlen($model));
        } else {
            error_log("Nexus AI WP Translator: Using model from parameter: '" . $model . "'");
        }
        
        error_log("Nexus AI WP Translator: Final model to use: " . $model);
        
        // Prepare the prompt
        $prompt = $this->prepare_translation_prompt($content, $source_lang, $target_lang);
        error_log('Nexus AI WP Translator: Prepared prompt for translation');
        
        // Prepare request
        $headers = array(
            'Content-Type' => 'application/json',
            'x-api-key' => $this->api_key,
            'anthropic-version' => '2023-06-01'
        );
        
        $body = array(
            'model' => $model,
            'max_tokens' => 4000,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            )
        );
        
        error_log('Nexus AI WP Translator: Making API request to ' . $this->api_endpoint);
        
        // Make API request
        $response = wp_remote_post($this->api_endpoint, array(
            'headers' => $headers,
            'body' => wp_json_encode($body),
            'timeout' => 60
        ));
        
        $processing_time = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('Nexus AI WP Translator: WP Error - ' . $error_message);
            return array(
                'success' => false,
                'message' => $error_message,
                'processing_time' => $processing_time
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        error_log("Nexus AI WP Translator: API response code: {$response_code}");
        
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) 
                ? $error_data['error']['message'] 
                : __('API request failed', 'claude-translator');
            
            error_log('Nexus AI WP Translator: API Error - ' . $error_message);
            error_log('Nexus AI WP Translator: Response body - ' . $response_body);
                
            return array(
                'success' => false,
                'message' => $error_message,
                'processing_time' => $processing_time
            );
        }
        
        $data = json_decode($response_body, true);
        
        if (!isset($data['content'][0]['text'])) {
            error_log('Nexus AI WP Translator: Invalid API response format');
            error_log('Nexus AI WP Translator: Response data - ' . print_r($data, true));
            return array(
                'success' => false,
                'message' => __('Invalid API response format', 'nexus-ai-wp-translator'),
                'processing_time' => $processing_time
            );
        }
        
        $translated_content = $data['content'][0]['text'];
        error_log('Nexus AI WP Translator: Translation successful');
        
        // Cache the translation
        $this->cache_translation($content, $source_lang, $target_lang, $translated_content);
        
        return array(
            'success' => true,
            'translated_content' => $translated_content,
            'processing_time' => $processing_time,
            'api_calls' => 1
        );
    }
    
    /**
     * Prepare translation prompt
     */
    private function prepare_translation_prompt($content, $source_lang, $target_lang) {
        $source_lang_name = $this->get_language_name($source_lang);
        $target_lang_name = $this->get_language_name($target_lang);

        $prompt = sprintf(
            "You are a professional translator. Translate the following %s content to %s with absolute precision.\n\n" .

            "CRITICAL REQUIREMENTS:\n" .
            "1. OUTPUT ONLY THE TRANSLATION - No explanations, comments, meta-text, or additional content\n" .
            "2. COMPLETE TRANSLATION - Translate the entire content without stopping or asking to continue\n" .
            "3. NATURAL TONE - Maintain the questioning tone and conversational style of the original\n" .
            "4. DIRECT TRANSLATION - Provide a natural, fluent translation that adapts to standard %s phrasing\n" .
            "5. PRESERVE FORMATTING - Maintain ALL HTML tags, CSS classes, and structural elements exactly\n" .
            "6. TECHNICAL PRESERVATION - Keep URLs, email addresses, code snippets, and technical identifiers unchanged\n" .
            "7. PROPER NOUNS - Keep brand names, person names, and place names in original form\n" .
            "8. DATES & TIMES - Preserve exact format and adapt to target language conventions\n" .
            "9. WORDPRESS BLOCKS - Preserve all WordPress block structures and attributes\n\n" .

            "ABSOLUTELY FORBIDDEN:\n" .
            "- Adding any comments, explanations, or notes\n" .
            "- Stopping mid-translation or asking for continuation\n" .
            "- Modifying HTML attributes, CSS classes, or technical elements\n" .
            "- Translating content inside code blocks or technical attributes\n" .
            "- Adding phrases like 'Here is the translation:' or similar\n" .
            "- Approximating times/dates with words like 'around', 'about', 'o'clock'\n\n" .

            "RESPOND WITH ONLY THE COMPLETE TRANSLATION:\n\n" .
            "%s",
            $source_lang_name,
            $target_lang_name,
            $target_lang_name,
            $content
        );

        return $prompt;
    }
    
    /**
     * Get language name from code
     */
    private function get_language_name($lang_code) {
        $languages = array(
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'zh' => 'Chinese',
            'ar' => 'Arabic',
            'hi' => 'Hindi',
            'nl' => 'Dutch',
            'sv' => 'Swedish',
            'da' => 'Danish',
            'no' => 'Norwegian',
            'fi' => 'Finnish',
            'pl' => 'Polish',
            'cs' => 'Czech',
            'hu' => 'Hungarian'
        );
        
        return isset($languages[$lang_code]) ? $languages[$lang_code] : $lang_code;
    }
    
    /**
     * Check throttle limits
     */
    private function check_throttle_limits() {
        $result = $this->check_throttle_limits_detailed();
        return $result['allowed'];
    }

    /**
     * Check throttle limits with detailed information
     */
    private function check_throttle_limits_detailed() {
        $db = Nexus_AI_WP_Translator_Database::get_instance();
        $throttle_limit = get_option('nexus_ai_wp_translator_throttle_limit', 100);
        $throttle_period = get_option('nexus_ai_wp_translator_throttle_period', 3600) / 60; // Convert to minutes

        $current_calls = $db->get_throttle_status($throttle_period);
        $allowed = $current_calls < $throttle_limit;

        if (!$allowed) {
            $period_hours = round($throttle_period / 60, 1);
            $message = sprintf(
                __('API call limit reached: %d/%d calls used in the last %s hours. Please wait or increase the limit in Settings → Performance & Rate Limiting.', 'nexus-ai-wp-translator'),
                $current_calls,
                $throttle_limit,
                $period_hours
            );
        } else {
            $message = sprintf(
                __('Throttle status: %d/%d calls used', 'nexus-ai-wp-translator'),
                $current_calls,
                $throttle_limit
            );
        }

        return array(
            'allowed' => $allowed,
            'current_calls' => $current_calls,
            'limit' => $throttle_limit,
            'period_minutes' => $throttle_period,
            'message' => $message
        );
    }
    
    /**
     * Cache translation
     */
    private function cache_translation($original, $source_lang, $target_lang, $translation) {
        if (!get_option('nexus_ai_wp_translator_cache_translations', true)) {
            return;
        }
        
        $cache_key = 'nexus_ai_wp_translation_' . md5($original . $source_lang . $target_lang);
        $cache_data = array(
            'translation' => $translation,
            'timestamp' => time()
        );
        
        set_transient($cache_key, $cache_data, DAY_IN_SECONDS);
    }
    
    /**
     * Get cached translation
     */
    public function get_cached_translation($original, $source_lang, $target_lang) {
        if (!get_option('nexus_ai_wp_translator_cache_translations', true)) {
            return false;
        }

        $cache_key = 'nexus_ai_wp_translation_' . md5($original . $source_lang . $target_lang);
        $cached = get_transient($cache_key);

        if ($cached && isset($cached['translation'])) {
            return $cached['translation'];
        }

        return false;
    }

    /**
     * Clear cached translation for specific content
     */
    public function clear_cached_translation($original, $source_lang, $target_lang) {
        $cache_key = 'nexus_ai_wp_translation_' . md5($original . $source_lang . $target_lang);
        delete_transient($cache_key);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Cleared cache for translation: {$source_lang} -> {$target_lang}");
        }
    }

    /**
     * Clear all cached translations for a specific post
     */
    public function clear_post_translation_cache($post_id, $target_languages = null) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        $source_lang = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');

        if (!$target_languages) {
            $target_languages = get_option('nexus_ai_wp_translator_target_languages', array('es', 'fr', 'de'));
        }

        if (!is_array($target_languages)) {
            $target_languages = array($target_languages);
        }

        foreach ($target_languages as $target_lang) {
            // Clear cache for title
            $this->clear_cached_translation($post->post_title, $source_lang, $target_lang);

            // Clear cache for content blocks
            $content_blocks = $this->split_content_into_blocks($post->post_content);
            foreach ($content_blocks as $block) {
                $this->clear_cached_translation($block['content'], $source_lang, $target_lang);
            }

            // Clear cache for excerpt
            if (!empty($post->post_excerpt)) {
                $this->clear_cached_translation($post->post_excerpt, $source_lang, $target_lang);
            }

            // Clear cache for categories and tags
            $categories = wp_get_post_categories($post_id, array('fields' => 'names'));
            foreach ($categories as $category) {
                $this->clear_cached_translation($category, $source_lang, $target_lang);
            }

            $tags = wp_get_post_tags($post_id, array('fields' => 'names'));
            foreach ($tags as $tag) {
                $this->clear_cached_translation($tag, $source_lang, $target_lang);
            }
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Cleared all cached translations for post {$post_id}");
        }
    }

    /**
     * Split content into translatable blocks
     */
    public function split_content_into_blocks($content) {
        $blocks = array();

        // Check if content contains Gutenberg blocks
        if (has_blocks($content)) {
            // Parse Gutenberg blocks
            $parsed_blocks = parse_blocks($content);

            foreach ($parsed_blocks as $index => $block) {
                if (!empty($block['blockName']) && !empty($block['innerHTML'])) {
                    // Extract text content from block HTML
                    $text_content = $this->extract_text_from_block($block);

                    if (!empty(trim($text_content))) {
                        $blocks[] = array(
                            'type' => 'gutenberg_block',
                            'block_name' => $block['blockName'],
                            'content' => $text_content,
                            'original_html' => $block['innerHTML'],
                            'attributes' => $block['attrs'] ?? array(),
                            'index' => $index
                        );
                    }
                }
            }
        } else {
            // Classic editor content - split by paragraphs
            $blocks = $this->split_classic_content($content);
        }

        return $blocks;
    }

    /**
     * Extract translatable text from Gutenberg block
     */
    private function extract_text_from_block($block) {
        $html = $block['innerHTML'];

        // Remove script and style tags completely
        $html = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/is', '', $html);

        // Extract text content while preserving some structure
        $text = strip_tags($html, '<p><br><h1><h2><h3><h4><h5><h6><li><strong><em><b><i>');

        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Split classic editor content into blocks
     */
    private function split_classic_content($content) {
        $blocks = array();

        // Split by double line breaks (paragraphs)
        $paragraphs = preg_split('/\n\s*\n/', $content);

        foreach ($paragraphs as $index => $paragraph) {
            $paragraph = trim($paragraph);
            if (!empty($paragraph)) {
                $blocks[] = array(
                    'type' => 'classic_paragraph',
                    'content' => $paragraph,
                    'index' => $index
                );
            }
        }

        return $blocks;
    }
    
    /**
     * Translate post content with block-by-block approach and caching
     */
    public function translate_post_content($post_id, $target_lang) {
        $post = get_post($post_id);
        if (!$post) {
            return array(
                'success' => false,
                'message' => __('Post not found', 'nexus-ai-wp-translator')
            );
        }

        $source_lang = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');
        $retry_attempts = get_option('nexus_ai_wp_translator_retry_attempts', 3);

        // Use new block-by-block translation method
        return $this->translate_post_content_by_blocks($post, $source_lang, $target_lang, $retry_attempts);
    }

    /**
     * Translate with cache check and retry mechanism
     */
    private function translate_with_cache_and_retry($content, $source_lang, $target_lang, $retry_attempts = 3) {
        // Check cache first
        $cached = $this->get_cached_translation($content, $source_lang, $target_lang);
        if ($cached) {
            return array(
                'success' => true,
                'translated_content' => $cached,
                'from_cache' => true,
                'api_calls' => 0
            );
        }

        // Translate with retry
        for ($i = 0; $i < $retry_attempts; $i++) {
            $result = $this->translate_content($content, $source_lang, $target_lang);
            if ($result['success']) {
                return $result;
            }

            if ($i < $retry_attempts - 1) {
                sleep(1); // Wait before retry
            }
        }

        return $result; // Return last failed result
    }

    /**
     * Translate post content block by block with progress tracking
     */
    public function translate_post_content_by_blocks($post, $source_lang, $target_lang, $retry_attempts = 3) {
        $results = array(
            'success' => false,
            'title' => '',
            'content' => '',
            'excerpt' => '',
            'categories' => array(),
            'tags' => array(),
            'progress' => array(),
            'total_blocks' => 0,
            'completed_blocks' => 0,
            'api_calls' => 0
        );

        // Step 1: Translate title
        $results['progress'][] = array('step' => 'title', 'status' => 'processing');
        $title_result = $this->translate_with_cache_and_retry($post->post_title, $source_lang, $target_lang, $retry_attempts);

        if (!$title_result['success']) {
            $results['progress'][] = array('step' => 'title', 'status' => 'failed', 'message' => $title_result['message']);
            return $results;
        }

        $results['title'] = $title_result['translated_content'];
        $results['api_calls'] += $title_result['api_calls'];
        $results['progress'][] = array('step' => 'title', 'status' => 'completed');

        // Step 2: Split and translate content blocks
        $content_blocks = $this->split_content_into_blocks($post->post_content);
        $results['total_blocks'] = count($content_blocks) + 1; // +1 for title
        $results['completed_blocks'] = 1; // Title completed

        $translated_blocks = array();

        foreach ($content_blocks as $block) {
            $results['progress'][] = array(
                'step' => 'content_block',
                'status' => 'processing',
                'block_type' => $block['type'],
                'block_index' => $block['index']
            );

            $block_result = $this->translate_with_cache_and_retry($block['content'], $source_lang, $target_lang, $retry_attempts);

            if (!$block_result['success']) {
                $results['progress'][] = array(
                    'step' => 'content_block',
                    'status' => 'failed',
                    'message' => $block_result['message'],
                    'block_index' => $block['index']
                );
                return $results;
            }

            // Store translated block with structure info
            $translated_blocks[] = array(
                'original' => $block,
                'translated_content' => $block_result['translated_content']
            );

            $results['api_calls'] += $block_result['api_calls'];
            $results['completed_blocks']++;
            $results['progress'][] = array(
                'step' => 'content_block',
                'status' => 'completed',
                'block_index' => $block['index']
            );
        }

        // Reconstruct content from translated blocks
        $results['content'] = $this->reconstruct_content_from_blocks($translated_blocks, $post->post_content);

        // Step 3: Translate excerpt if exists
        if (!empty($post->post_excerpt)) {
            $results['progress'][] = array('step' => 'excerpt', 'status' => 'processing');
            $excerpt_result = $this->translate_with_cache_and_retry($post->post_excerpt, $source_lang, $target_lang, $retry_attempts);

            if ($excerpt_result['success']) {
                $results['excerpt'] = $excerpt_result['translated_content'];
                $results['api_calls'] += $excerpt_result['api_calls'];
                $results['progress'][] = array('step' => 'excerpt', 'status' => 'completed');
            } else {
                $results['progress'][] = array('step' => 'excerpt', 'status' => 'failed', 'message' => $excerpt_result['message']);
            }
        }

        // Step 4: Translate categories
        $categories = wp_get_post_categories($post->ID, array('fields' => 'names'));
        if (!empty($categories)) {
            $results['progress'][] = array('step' => 'categories', 'status' => 'processing');
            foreach ($categories as $category) {
                $cat_result = $this->translate_with_cache_and_retry($category, $source_lang, $target_lang, $retry_attempts);
                if ($cat_result['success']) {
                    $results['categories'][] = $cat_result['translated_content'];
                    $results['api_calls'] += $cat_result['api_calls'];
                }
            }
            $results['progress'][] = array('step' => 'categories', 'status' => 'completed');
        }

        // Step 5: Translate tags
        $tags = wp_get_post_tags($post->ID, array('fields' => 'names'));
        if (!empty($tags)) {
            $results['progress'][] = array('step' => 'tags', 'status' => 'processing');
            foreach ($tags as $tag) {
                $tag_result = $this->translate_with_cache_and_retry($tag, $source_lang, $target_lang, $retry_attempts);
                if ($tag_result['success']) {
                    $results['tags'][] = $tag_result['translated_content'];
                    $results['api_calls'] += $tag_result['api_calls'];
                }
            }
            $results['progress'][] = array('step' => 'tags', 'status' => 'completed');
        }

        $results['success'] = true;
        return $results;
    }

    /**
     * Reconstruct content from translated blocks
     */
    private function reconstruct_content_from_blocks($translated_blocks, $original_content) {
        if (empty($translated_blocks)) {
            return $original_content;
        }

        // For now, simple reconstruction - join translated content
        // TODO: Improve to maintain original structure better
        $reconstructed = '';

        foreach ($translated_blocks as $block_data) {
            $original = $block_data['original'];
            $translated = $block_data['translated_content'];

            if ($original['type'] === 'gutenberg_block') {
                // Try to maintain block structure
                $reconstructed .= "\n\n" . $translated;
            } else {
                // Classic content
                $reconstructed .= "\n\n" . $translated;
            }
        }

        return trim($reconstructed);
    }
}
