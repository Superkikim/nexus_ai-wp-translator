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
                error_log('Nexus AI WP Translator: No models data in response, returning empty models array');
            }
            // No fallback - return empty models if API doesn't provide them
            return array(
                'success' => false,
                'message' => __('No models available from API. Please check your API key.', 'nexus-ai-wp-translator')
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
            
            // If no models found, return error - no fallback
            if (empty($models)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Nexus AI WP Translator: No models found in API response');
                }
                return array(
                    'success' => false,
                    'message' => __('No models found in API response. Please contact support.', 'nexus-ai-wp-translator')
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
     * Translate content using Claude AI with streaming support
     */
    public function translate_content($content, $source_lang, $target_lang, $context_or_model = null, $use_streaming = false) {
        // Handle backward compatibility - if context_or_model is a string, it's a model
        if (is_string($context_or_model)) {
            $model = $context_or_model;
            $context = array();
        } else {
            $context = is_array($context_or_model) ? $context_or_model : array();
            $model = null;
        }
        
        error_log("Nexus AI WP Translator: Starting translation from {$source_lang} to {$target_lang} (streaming: " . ($use_streaming ? 'yes' : 'no') . ")");
        
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
        
        // Get model from parameter or settings - no fallback
        if (!$model) {
            $model = get_option('nexus_ai_wp_translator_model', '');
        }
        
        // Validate that a model is selected
        if (empty($model)) {
            error_log('Nexus AI WP Translator: No model selected');
            return array(
                'success' => false,
                'message' => __('No AI model selected. Please select a model in Settings → API Settings.', 'nexus-ai-wp-translator')
            );
        }
        
        error_log("Nexus AI WP Translator: Using model: " . $model);
        
        // Prepare the prompt with context
        $prompt = $this->prepare_translation_prompt($content, $source_lang, $target_lang, $context);
        
        // Prepare request headers
        $headers = array(
            'Content-Type' => 'application/json',
            'x-api-key' => $this->api_key,
            'anthropic-version' => '2023-06-01'
        );
        
        // Prepare request body
        $body = array(
            'model' => $model,
            'max_tokens' => 8000, // Increased for complete content translation
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'stream' => $use_streaming
        );
        
        if ($use_streaming) {
            return $this->handle_streaming_translation($headers, $body, $content, $source_lang, $target_lang, $start_time);
        } else {
            return $this->handle_standard_translation($headers, $body, $content, $source_lang, $target_lang, $start_time);
        }
    }

    /**
     * Handle standard (non-streaming) translation
     */
    private function handle_standard_translation($headers, $body, $original_content, $source_lang, $target_lang, $start_time) {
        error_log('Nexus AI WP Translator: Making standard API request');
        
        // Make API request
        $response = wp_remote_post($this->api_endpoint, array(
            'headers' => $headers,
            'body' => wp_json_encode($body),
            'timeout' => 120 // Increased timeout for larger content
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
                : __('API request failed', 'nexus-ai-wp-translator');
            
            error_log('Nexus AI WP Translator: API Error - ' . $error_message);
                
            return array(
                'success' => false,
                'message' => $error_message,
                'processing_time' => $processing_time
            );
        }
        
        $data = json_decode($response_body, true);
        
        if (!isset($data['content'][0]['text'])) {
            error_log('Nexus AI WP Translator: Invalid API response format');
            return array(
                'success' => false,
                'message' => __('Invalid API response format', 'nexus-ai-wp-translator'),
                'processing_time' => $processing_time
            );
        }
        
        $translated_content = $data['content'][0]['text'];
        error_log('Nexus AI WP Translator: Translation successful');
        
        // Cache the translation
        $this->cache_translation($original_content, $source_lang, $target_lang, $translated_content);
        
        return array(
            'success' => true,
            'translated_content' => $translated_content,
            'processing_time' => $processing_time,
            'api_calls' => 1
        );
    }

    /**
     * Handle streaming translation with proper interruption support
     */
    private function handle_streaming_translation($headers, $body, $original_content, $source_lang, $target_lang, $start_time) {
        error_log('Nexus AI WP Translator: Starting streaming translation');
        
        // Use cURL for streaming support since wp_remote_post doesn't handle streams well
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->api_endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => wp_json_encode($body),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->api_key,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_WRITEFUNCTION => [$this, 'stream_callback'],
            CURLOPT_TIMEOUT => 300, // 5 minutes timeout for streaming
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Nexus-AI-WP-Translator/1.0'
        ]);
        
        // Initialize streaming variables
        $this->streaming_buffer = '';
        $this->streaming_result = '';
        $this->streaming_error = null;
        $this->streaming_interrupted = false;
        
        error_log('Nexus AI WP Translator: Executing streaming request');
        
        $curl_result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        curl_close($ch);
        
        $processing_time = microtime(true) - $start_time;
        
        if ($curl_error) {
            error_log('Nexus AI WP Translator: cURL error - ' . $curl_error);
            return array(
                'success' => false,
                'message' => __('Connection error: ', 'nexus-ai-wp-translator') . $curl_error,
                'processing_time' => $processing_time
            );
        }
        
        if ($this->streaming_error) {
            error_log('Nexus AI WP Translator: Streaming error - ' . $this->streaming_error);
            return array(
                'success' => false,
                'message' => $this->streaming_error,
                'processing_time' => $processing_time,
                'interrupted' => $this->streaming_interrupted
            );
        }
        
        if ($http_code !== 200) {
            error_log("Nexus AI WP Translator: HTTP error code {$http_code}");
            return array(
                'success' => false,
                'message' => sprintf(__('HTTP error: %d', 'nexus-ai-wp-translator'), $http_code),
                'processing_time' => $processing_time
            );
        }
        
        if (empty($this->streaming_result)) {
            error_log('Nexus AI WP Translator: Empty streaming result');
            return array(
                'success' => false,
                'message' => __('No content received from API', 'nexus-ai-wp-translator'),
                'processing_time' => $processing_time
            );
        }
        
        error_log('Nexus AI WP Translator: Streaming translation completed successfully');
        
        // Cache the translation
        $this->cache_translation($original_content, $source_lang, $target_lang, $this->streaming_result);
        
        return array(
            'success' => true,
            'translated_content' => $this->streaming_result,
            'processing_time' => $processing_time,
            'api_calls' => 1,
            'streaming' => true,
            'interrupted' => $this->streaming_interrupted
        );
    }

    /**
     * Streaming callback function to handle server-sent events
     */
    public function stream_callback($ch, $data) {
        $this->streaming_buffer .= $data;
        
        // Process complete lines
        while (($pos = strpos($this->streaming_buffer, "\n")) !== false) {
            $line = substr($this->streaming_buffer, 0, $pos);
            $this->streaming_buffer = substr($this->streaming_buffer, $pos + 1);
            
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            // Parse server-sent events
            if (strpos($line, 'data: ') === 0) {
                $json_data = substr($line, 6);
                
                if ($json_data === '[DONE]') {
                    break;
                }
                
                $event_data = json_decode($json_data, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }
                
                if (isset($event_data['type'])) {
                    switch ($event_data['type']) {
                        case 'content_block_delta':
                            if (isset($event_data['delta']['text'])) {
                                $this->streaming_result .= $event_data['delta']['text'];
                            }
                            break;
                            
                        case 'message_stop':
                            // Translation completed
                            break;
                            
                        case 'error':
                            $this->streaming_error = isset($event_data['error']['message']) 
                                ? $event_data['error']['message'] 
                                : __('Unknown streaming error', 'nexus-ai-wp-translator');
                            break;
                    }
                }
            }
        }
        
        // Check for interruption (e.g., user cancellation, timeout)
        if (connection_aborted()) {
            $this->streaming_interrupted = true;
            return 0; // Stop the transfer
        }
        
        return strlen($data);
    }

    // Streaming state variables
    private $streaming_buffer = '';
    private $streaming_result = '';
    private $streaming_error = null;
    private $streaming_interrupted = false;
    
    /**
     * Translate complete post content using streaming JSON approach
     */
    public function translate_post_content_complete($post_id, $target_lang, $progress_id = null, $use_streaming = true) {
        $post = get_post($post_id);
        if (!$post) {
            return array(
                'success' => false,
                'message' => __('Post not found', 'nexus-ai-wp-translator')
            );
        }

        $source_lang = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true) ?: 'auto';
        
        error_log("Nexus AI WP Translator: Starting complete post translation using " . ($use_streaming ? 'streaming' : 'standard') . " approach");

        // Prepare complete post data for translation
        $post_data = array(
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'categories' => wp_get_post_categories($post_id, array('fields' => 'names')),
            'tags' => wp_get_post_tags($post_id, array('fields' => 'names'))
        );

        // Create JSON prompt for complete translation
        $json_content = wp_json_encode($post_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        $context = array(
            'content_type' => 'complete_post_json',
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'use_streaming' => $use_streaming
        );

        $prompt = $this->prepare_complete_json_translation_prompt($json_content, $source_lang, $target_lang, $context);

        // Initialize progress tracking
        if ($progress_id) {
            $this->init_progress_tracking($progress_id, $post->post_title, array($target_lang));
            $this->update_progress($progress_id, 'complete_translation', 'processing', 'Translating complete post content...', 20);
        }

        // Perform translation
        $result = $this->translate_content($json_content, $source_lang, $target_lang, $context, $use_streaming);

        if (!$result['success']) {
            if ($progress_id) {
                $this->update_progress($progress_id, 'complete_translation', 'failed', $result['message'], 20);
            }
            return $result;
        }

        // Parse the JSON response
        $translated_data = json_decode($result['translated_content'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Nexus AI WP Translator: Failed to parse JSON response: ' . json_last_error_msg());
            if ($progress_id) {
                $this->update_progress($progress_id, 'parse_response', 'failed', 'Failed to parse translation JSON', 30);
            }
            return array(
                'success' => false,
                'message' => __('Invalid JSON response from translation API', 'nexus-ai-wp-translator'),
                'raw_response' => $result['translated_content']
            );
        }

        // Validate and clean the translated data
        $cleaned_result = $this->validate_and_clean_json_translation($translated_data, $post_data);

        if (!$cleaned_result['success']) {
            if ($progress_id) {
                $this->update_progress($progress_id, 'validate_translation', 'failed', $cleaned_result['message'], 40);
            }
            return $cleaned_result;
        }

        if ($progress_id) {
            $this->update_progress($progress_id, 'complete_translation', 'completed', 'Translation completed successfully', 100);
            $this->complete_progress($progress_id, true, 'Complete post translation finished');
        }

        return array(
            'success' => true,
            'title' => $cleaned_result['data']['title'],
            'content' => $cleaned_result['data']['content'],
            'excerpt' => $cleaned_result['data']['excerpt'],
            'categories' => $cleaned_result['data']['categories'],
            'tags' => $cleaned_result['data']['tags'],
            'api_calls' => $result['api_calls'],
            'processing_time' => $result['processing_time'],
            'streaming' => isset($result['streaming']) ? $result['streaming'] : false,
            'interrupted' => isset($result['interrupted']) ? $result['interrupted'] : false
        );
    }

    /**
     * Prepare complete JSON translation prompt
     */
    private function prepare_complete_json_translation_prompt($json_content, $source_lang, $target_lang, $context = array()) {
        $source_lang_name = $this->get_language_name($source_lang);
        $target_lang_name = $this->get_language_name($target_lang);

        $prompt = sprintf(
            "You are a professional translator. You will receive a JSON object containing a WordPress post in %s and must translate it to %s.\n\n" .

            "CRITICAL REQUIREMENTS:\n" .
            "1. OUTPUT ONLY VALID JSON - Return only the translated JSON object, no explanations or additional text\n" .
            "2. MAINTAIN JSON STRUCTURE - Keep the exact same JSON structure with the same keys\n" .
            "3. TITLE CONSISTENCY - Ensure the translated title is identical in both the 'title' field and within the 'content' if it appears there\n" .
            "4. COMPLETE TRANSLATION - Translate ALL text content while preserving HTML formatting, WordPress blocks, and technical elements\n" .
            "5. PRESERVE FORMATTING - Maintain ALL HTML tags, CSS classes, WordPress block structures, and technical attributes exactly\n" .
            "6. TECHNICAL PRESERVATION - Keep URLs, email addresses, code snippets, and technical identifiers unchanged\n" .
            "7. PROPER NOUNS - Keep brand names, person names, and place names in original form unless they have official translations\n" .
            "8. CONTENT COHERENCE - Ensure the entire translation maintains coherence and consistency throughout\n\n" .

            "JSON FIELD TRANSLATIONS:\n" .
            "- 'title': Translate the post title\n" .
            "- 'content': Translate the full post content, maintaining ALL WordPress blocks and HTML structure\n" .
            "- 'excerpt': Translate the post excerpt if present\n" .
            "- 'categories': Translate category names appropriately for the target language\n" .
            "- 'tags': Translate tag names appropriately for the target language\n\n" .

            "ABSOLUTELY FORBIDDEN:\n" .
            "- Adding explanations, comments, or meta-text outside the JSON\n" .
            "- Modifying the JSON structure or key names\n" .
            "- Stopping mid-translation or asking for continuation\n" .
            "- Adding phrases like 'Here is the translation:' or similar\n" .
            "- Breaking or corrupting WordPress block structures\n" .
            "- Translating technical attributes, CSS classes, or code elements\n\n" .

            "RESPOND WITH ONLY THE TRANSLATED JSON OBJECT:\n\n" .
            "%s",
            $source_lang_name,
            $target_lang_name,
            $json_content
        );

        return $prompt;
    }

    /**
     * Validate and clean JSON translation response
     */
    private function validate_and_clean_json_translation($translated_data, $original_data) {
        if (!is_array($translated_data)) {
            return array(
                'success' => false,
                'message' => __('Translation response is not a valid array', 'nexus-ai-wp-translator')
            );
        }

        $required_fields = array('title', 'content', 'excerpt', 'categories', 'tags');
        $cleaned_data = array();

        foreach ($required_fields as $field) {
            if (!isset($translated_data[$field])) {
                error_log("Nexus AI WP Translator: Missing required field: {$field}");
                // Use original data as fallback
                $cleaned_data[$field] = isset($original_data[$field]) ? $original_data[$field] : '';
            } else {
                $cleaned_data[$field] = $translated_data[$field];
            }
        }

        // Validate title consistency in content
        if (!empty($cleaned_data['title']) && !empty($cleaned_data['content'])) {
            $title_in_content = $this->extract_title_from_content($cleaned_data['content']);
            if ($title_in_content && $title_in_content !== $cleaned_data['title']) {
                error_log("Nexus AI WP Translator: Title inconsistency detected, fixing...");
                $cleaned_data['content'] = $this->fix_title_consistency($cleaned_data['content'], $cleaned_data['title']);
            }
        }

        // Ensure categories and tags are arrays
        $cleaned_data['categories'] = is_array($cleaned_data['categories']) ? $cleaned_data['categories'] : array();
        $cleaned_data['tags'] = is_array($cleaned_data['tags']) ? $cleaned_data['tags'] : array();

        // Validate content integrity
        if (empty($cleaned_data['content']) && !empty($original_data['content'])) {
            error_log("Nexus AI WP Translator: Content was lost during translation, using original");
            return array(
                'success' => false,
                'message' => __('Content was lost during translation', 'nexus-ai-wp-translator')
            );
        }

        return array(
            'success' => true,
            'data' => $cleaned_data
        );
    }

    /**
     * Extract title from content (look for h1 tags or similar)
     */
    private function extract_title_from_content($content) {
        // Look for h1 tags first
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $content, $matches)) {
            return strip_tags($matches[1]);
        }
        
        // Look for WordPress heading blocks
        if (preg_match('/<!-- wp:heading {"level":1[^}]*} -->\s*<h1[^>]*>(.*?)<\/h1>/i', $content, $matches)) {
            return strip_tags($matches[1]);
        }

        return null;
    }

    /**
     * Fix title consistency in content
     */
    private function fix_title_consistency($content, $correct_title) {
        // Replace h1 tags
        $content = preg_replace('/<h1[^>]*>(.*?)<\/h1>/i', '<h1>$' . $correct_title . '</h1>', $content);
        
        // Replace WordPress heading blocks
        $content = preg_replace('/<!-- wp:heading {"level":1[^}]*} -->\s*<h1[^>]*>(.*?)<\/h1>/i', 
            '<!-- wp:heading {"level":1} --><h1>' . $correct_title . '</h1>', $content);

        return $content;
    }

    /**
     * Prepare translation prompt
     */
    private function prepare_translation_prompt($content, $source_lang, $target_lang, $context = array()) {
        // Check if this is a complete JSON translation
        if (isset($context['content_type']) && $context['content_type'] === 'complete_post_json') {
            return $this->prepare_complete_json_translation_prompt($content, $source_lang, $target_lang, $context);
        }

        // Get templates manager
        $templates_manager = Nexus_AI_WP_Translator_Templates::get_instance();

        // Determine content type
        $content_type = isset($context['content_type']) ? $context['content_type'] : 'general';
        $post_id = isset($context['post_id']) ? $context['post_id'] : null;

        // Get appropriate template
        $template = $templates_manager->get_template_for_content_type($content_type, $post_id);

        if ($template) {
            // Use template to generate prompt
            $prompt = $templates_manager->apply_template($template, $content, $source_lang, $target_lang, $context);
        } else {
            // Fallback to default prompt
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
        }

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

        $source_lang = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true) ?: 'auto';

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
    public function translate_post_content($post_id, $target_lang, $progress_id = null) {
        $post = get_post($post_id);
        if (!$post) {
            return array(
                'success' => false,
                'message' => __('Post not found', 'nexus-ai-wp-translator')
            );
        }

        $source_lang = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true) ?: 'auto';
        $retry_attempts = get_option('nexus_ai_wp_translator_retry_attempts', 3);

        // Use new block-by-block translation method
        return $this->translate_post_content_by_blocks($post, $source_lang, $target_lang, $retry_attempts, false, $progress_id);
    }

    /**
     * Validate content before translation
     */
    private function validate_content_for_translation($content) {
        if (empty($content) || empty(trim($content))) {
            return false;
        }
        
        // Remove HTML tags and check if there's actual text content
        $text_only = strip_tags($content);
        $text_only = preg_replace('/\s+/', ' ', $text_only);
        $text_only = trim($text_only);
        
        // Check if we have meaningful content (at least 1 character)
        if (empty($text_only) || strlen($text_only) < 1) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate and filter AI response for unwanted comments/apologies
     */
    private function validate_and_filter_ai_response($translated_content, $original_content = '') {
        if (empty($translated_content)) {
            return array(
                'valid' => false,
                'filtered_content' => '',
                'issues' => array('Empty response')
            );
        }
        
        $issues = array();
        $filtered_content = $translated_content;
        
        // Common AI apology/comment patterns to detect
        $invalid_patterns = array(
            '/I apologize,?\s*but\s*/i',
            '/I\'m sorry,?\s*but\s*/i',
            '/I cannot\s+translate\s*/i',
            '/There is no\s+.*?\s*to translate/i',
            '/Could you provide\s+.*?\s*to translate/i',
            '/Please provide\s+.*?\s*to translate/i',
            '/I don\'t see\s+.*?\s*to translate/i',
            '/There\'s no\s+.*?\s*within\s+.*?\s*tags/i',
            '/I need\s+.*?\s*in order to translate/i',
            '/Here is the translation:?/i',
            '/The translation is:?/i',
            '/Translated text:?/i',
        );
        
        // Check for invalid patterns
        foreach ($invalid_patterns as $pattern) {
            if (preg_match($pattern, $translated_content)) {
                $issues[] = 'Contains AI comments/apologies';
                break;
            }
        }
        
        // Check if response is just an explanation rather than translation
        $explanation_patterns = array(
            '/^(The|This)\s+.*?\s+(means|translates to|is)/i',
            '/^In\s+\w+,?\s+.*?\s+(means|is)/i',
            '/^To translate\s+.*?\s+into\s+\w+/i'
        );
        
        foreach ($explanation_patterns as $pattern) {
            if (preg_match($pattern, $translated_content)) {
                $issues[] = 'Response is explanation, not direct translation';
                break;
            }
        }
        
        // Filter out common prefixes/suffixes that shouldn't be in translations
        $filter_patterns = array(
            '/^Here is the translation:?\s*/i',
            '/^The translation is:?\s*/i',
            '/^Translated text:?\s*/i',
            '/^Translation:?\s*/i',
            '/^In\s+\w+:?\s*/i',
        );
        
        foreach ($filter_patterns as $pattern) {
            $filtered_content = preg_replace($pattern, '', $filtered_content);
        }
        
        $filtered_content = trim($filtered_content);
        
        // Final validation - check if we have actual content
        if (empty($filtered_content)) {
            $issues[] = 'No usable content after filtering';
            return array(
                'valid' => false,
                'filtered_content' => '',
                'issues' => $issues
            );
        }
        
        // Check if the filtered content is suspiciously similar to common error messages
        $error_indicators = array(
            'no text', 'no content', 'empty', 'missing', 'cannot find',
            'please provide', 'could you provide', 'I need'
        );
        
        $lower_content = strtolower($filtered_content);
        foreach ($error_indicators as $indicator) {
            if (strpos($lower_content, $indicator) !== false && strlen($filtered_content) < 100) {
                $issues[] = 'Response appears to be an error message';
                return array(
                    'valid' => false,
                    'filtered_content' => $filtered_content,
                    'issues' => $issues
                );
            }
        }
        
        $is_valid = empty($issues);
        
        return array(
            'valid' => $is_valid,
            'filtered_content' => $filtered_content,
            'issues' => $issues
        );
    }
    
    /**
     * Translate with cache check and retry mechanism
     */
    private function translate_with_cache_and_retry($content, $source_lang, $target_lang, $retry_attempts = 3, $context = array()) {
        // Validate content first - skip empty content
        if (!$this->validate_content_for_translation($content)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: Skipping empty/invalid content - no API call made");
            }
            return array(
                'success' => true,
                'translated_content' => $content, // Return original if empty
                'from_cache' => false,
                'api_calls' => 0,
                'skipped' => true,
                'skip_reason' => 'Empty or invalid content'
            );
        }
        
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

        // Translate with retry and response validation
        for ($i = 0; $i < $retry_attempts; $i++) {
            $result = $this->translate_content($content, $source_lang, $target_lang, $context);
            
            if ($result['success']) {
                // Validate and filter the AI response
                $validation = $this->validate_and_filter_ai_response($result['translated_content'], $content);
                
                if ($validation['valid']) {
                    // Use filtered content
                    $result['translated_content'] = $validation['filtered_content'];
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Nexus AI WP Translator: Valid translation received and filtered");
                    }
                    return $result;
                } else {
                    // Log invalid response
                    $issues_text = implode(', ', $validation['issues']);
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Nexus AI WP Translator: Invalid AI response (attempt " . ($i + 1) . "): " . $issues_text);
                        error_log("Nexus AI WP Translator: Response content: " . substr($result['translated_content'], 0, 200));
                    }
                    
                    // Continue to retry if this was an invalid response
                    $result['success'] = false;
                    $result['message'] = 'Invalid AI response: ' . $issues_text;
                }
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
    public function translate_post_content_by_blocks($post, $source_lang, $target_lang, $retry_attempts = 3, $resume = false, $progress_id = null) {
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
            'api_calls' => 0,
            'resumed' => false,
            'progress_id' => $progress_id
        );

        // Initialize progress tracking
        if ($progress_id) {
            $this->init_progress_tracking($progress_id, $post->post_title, array($target_lang));
        }

        // Check for partial translation cache if resuming
        if ($resume) {
            $partial_cache = $this->get_partial_translation_cache($post->ID, $target_lang);
            if ($partial_cache) {
                $results = array_merge($results, $partial_cache);
                $results['resumed'] = true;
                $results['progress'][] = array('step' => 'resume', 'status' => 'completed', 'message' => 'Resumed from cached progress');

                if ($progress_id) {
                    $this->update_progress($progress_id, 'resume', 'completed', 'Resumed from cached progress', 10);
                }
            }
        }

        // Step 1: Translate title
        $results['progress'][] = array('step' => 'title', 'status' => 'processing');
        if ($progress_id) {
            $this->update_progress($progress_id, 'title', 'processing', 'Translating post title...', 15);
        }

        // Prepare context for translation
        $translation_context = array(
            'content_type' => $post->post_type,
            'post_id' => $post->ID,
            'post_title' => $post->post_title,
            'post_type' => $post->post_type
        );

        $title_result = $this->translate_with_cache_and_retry($post->post_title, $source_lang, $target_lang, $retry_attempts, $translation_context);

        if (!$title_result['success']) {
            $results['progress'][] = array('step' => 'title', 'status' => 'failed', 'message' => $title_result['message']);
            if ($progress_id) {
                $this->update_progress($progress_id, 'title', 'failed', $title_result['message'], 15);
            }
            return $results;
        }

        $results['title'] = $title_result['translated_content'];
        $results['api_calls'] += $title_result['api_calls'];
        $results['progress'][] = array('step' => 'title', 'status' => 'completed');
        if ($progress_id) {
            $this->update_progress($progress_id, 'title', 'completed', 'Title translated successfully', 25);
        }

        // Step 2: Split and translate content blocks
        $content_blocks = $this->split_content_into_blocks($post->post_content);
        $results['total_blocks'] = count($content_blocks) + 1; // +1 for title
        $results['completed_blocks'] = 1; // Title completed

        $translated_blocks = array();

        foreach ($content_blocks as $block_index => $block) {
            $current_progress = 25 + (($block_index / count($content_blocks)) * 40); // 25-65% for content blocks

            $results['progress'][] = array(
                'step' => 'content_block',
                'status' => 'processing',
                'block_type' => $block['type'],
                'block_index' => $block['index']
            );

            if ($progress_id) {
                $this->update_progress($progress_id, 'content_block', 'processing',
                    "Translating content block " . ($block_index + 1) . " of " . count($content_blocks),
                    $current_progress);
            }

            $block_result = $this->translate_with_cache_and_retry($block['content'], $source_lang, $target_lang, $retry_attempts, $translation_context);

            if (!$block_result['success']) {
                $results['progress'][] = array(
                    'step' => 'content_block',
                    'status' => 'failed',
                    'message' => $block_result['message'],
                    'block_index' => $block['index']
                );

                if ($progress_id) {
                    $this->update_progress($progress_id, 'content_block', 'failed',
                        "Failed to translate block " . ($block_index + 1) . ": " . $block_result['message'],
                        $current_progress);
                }

                // Save partial results for resume functionality
                $results['partial_failure'] = true;
                $results['failed_at_block'] = $block['index'];
                $results['translated_blocks'] = $translated_blocks;
                $this->save_partial_translation_cache($post->ID, $target_lang, $results);

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

            if ($progress_id) {
                $this->update_progress($progress_id, 'content_block', 'completed',
                    "Completed block " . ($block_index + 1) . " of " . count($content_blocks),
                    $current_progress + (40 / count($content_blocks)));
            }
        }

        // Reconstruct content from translated blocks
        $results['content'] = $this->reconstruct_content_from_blocks($translated_blocks, $post->post_content);

        // Step 3: Translate excerpt if exists
        if (!empty($post->post_excerpt)) {
            $results['progress'][] = array('step' => 'excerpt', 'status' => 'processing');
            if ($progress_id) {
                $this->update_progress($progress_id, 'excerpt', 'processing', 'Translating post excerpt...', 70);
            }

            $excerpt_result = $this->translate_with_cache_and_retry($post->post_excerpt, $source_lang, $target_lang, $retry_attempts, $translation_context);

            if ($excerpt_result['success']) {
                $results['excerpt'] = $excerpt_result['translated_content'];
                $results['api_calls'] += $excerpt_result['api_calls'];
                $results['progress'][] = array('step' => 'excerpt', 'status' => 'completed');
                if ($progress_id) {
                    $this->update_progress($progress_id, 'excerpt', 'completed', 'Excerpt translated successfully', 75);
                }
            } else {
                $results['progress'][] = array('step' => 'excerpt', 'status' => 'failed', 'message' => $excerpt_result['message']);
                if ($progress_id) {
                    $this->update_progress($progress_id, 'excerpt', 'failed', $excerpt_result['message'], 75);
                }
            }
        }

        // Step 4: Translate categories
        $categories = wp_get_post_categories($post->ID, array('fields' => 'names'));
        if (!empty($categories)) {
            $results['progress'][] = array('step' => 'categories', 'status' => 'processing');
            if ($progress_id) {
                $this->update_progress($progress_id, 'categories', 'processing', 'Translating categories...', 80);
            }

            foreach ($categories as $category) {
                $cat_result = $this->translate_with_cache_and_retry($category, $source_lang, $target_lang, $retry_attempts, $translation_context);
                if ($cat_result['success']) {
                    $results['categories'][] = $cat_result['translated_content'];
                    $results['api_calls'] += $cat_result['api_calls'];
                }
            }
            $results['progress'][] = array('step' => 'categories', 'status' => 'completed');
            if ($progress_id) {
                $this->update_progress($progress_id, 'categories', 'completed', 'Categories translated successfully', 85);
            }
        }

        // Step 5: Translate tags
        $tags = wp_get_post_tags($post->ID, array('fields' => 'names'));
        if (!empty($tags)) {
            $results['progress'][] = array('step' => 'tags', 'status' => 'processing');
            if ($progress_id) {
                $this->update_progress($progress_id, 'tags', 'processing', 'Translating tags...', 90);
            }

            foreach ($tags as $tag) {
                $tag_result = $this->translate_with_cache_and_retry($tag, $source_lang, $target_lang, $retry_attempts, $translation_context);
                if ($tag_result['success']) {
                    $results['tags'][] = $tag_result['translated_content'];
                    $results['api_calls'] += $tag_result['api_calls'];
                }
            }
            $results['progress'][] = array('step' => 'tags', 'status' => 'completed');
            if ($progress_id) {
                $this->update_progress($progress_id, 'tags', 'completed', 'Tags translated successfully', 95);
            }
        }

        $results['success'] = true;

        // Clear partial translation cache on successful completion
        $this->clear_partial_translation_cache($post->ID, $target_lang);

        // Complete progress tracking
        if ($progress_id) {
            $this->complete_progress($progress_id, true, 'Translation completed successfully');
        }

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

    /**
     * Get partial translation cache for a post
     */
    public function get_partial_translation_cache($post_id, $target_lang) {
        $cache_key = 'nexus_ai_wp_partial_translation_' . $post_id . '_' . $target_lang;
        $cached = get_transient($cache_key);

        if ($cached && is_array($cached)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: Found partial translation cache for post {$post_id} -> {$target_lang}");
            }
            return $cached;
        }

        return false;
    }

    /**
     * Save partial translation cache
     */
    public function save_partial_translation_cache($post_id, $target_lang, $partial_results) {
        $cache_key = 'nexus_ai_wp_partial_translation_' . $post_id . '_' . $target_lang;

        // Cache for 24 hours
        $cached = set_transient($cache_key, $partial_results, 24 * HOUR_IN_SECONDS);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Saved partial translation cache for post {$post_id} -> {$target_lang}");
        }

        return $cached;
    }

    /**
     * Clear partial translation cache
     */
    public function clear_partial_translation_cache($post_id, $target_lang = null) {
        if ($target_lang) {
            $cache_key = 'nexus_ai_wp_partial_translation_' . $post_id . '_' . $target_lang;
            delete_transient($cache_key);
        } else {
            // Clear all partial caches for this post
            $target_languages = get_option('nexus_ai_wp_translator_target_languages', array('es', 'fr', 'de'));
            
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
                $target_languages = array('es', 'fr', 'de'); // fallback to default
            }
            
            foreach ($target_languages as $lang) {
                $cache_key = 'nexus_ai_wp_partial_translation_' . $post_id . '_' . $lang;
                delete_transient($cache_key);
            }
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Cleared partial translation cache for post {$post_id}");
        }
    }

    /**
     * Check if post has partial translation cache
     */
    public function has_partial_translation_cache($post_id, $target_lang) {
        $cache_key = 'nexus_ai_wp_partial_translation_' . $post_id . '_' . $target_lang;
        return get_transient($cache_key) !== false;
    }

    /**
     * Initialize progress tracking for a translation session
     */
    public function init_progress_tracking($progress_id, $post_title, $target_languages) {
        $progress_data = array(
            'post_title' => $post_title,
            'target_languages' => $target_languages,
            'start_time' => time(),
            'status' => 'started',
            'current_step' => 'initializing',
            'progress_percentage' => 0,
            'steps' => array(),
            'errors' => array()
        );

        set_transient('nexus_ai_wp_progress_' . $progress_id, $progress_data, 3600); // 1 hour

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Initialized progress tracking for session {$progress_id}");
        }
    }

    /**
     * Update progress for a translation session
     */
    public function update_progress($progress_id, $step, $status, $message, $percentage = null) {
        $progress_data = get_transient('nexus_ai_wp_progress_' . $progress_id);

        if (!$progress_data) {
            return false;
        }

        $progress_data['current_step'] = $step;
        $progress_data['last_update'] = time();

        if ($percentage !== null) {
            $progress_data['progress_percentage'] = $percentage;
        }

        // Add step to history
        $progress_data['steps'][] = array(
            'step' => $step,
            'status' => $status,
            'message' => $message,
            'timestamp' => time()
        );

        if ($status === 'failed') {
            $progress_data['errors'][] = array(
                'step' => $step,
                'message' => $message,
                'timestamp' => time()
            );
        }

        set_transient('nexus_ai_wp_progress_' . $progress_id, $progress_data, 3600);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Updated progress {$progress_id}: {$step} -> {$status} ({$percentage}%)");
        }

        return true;
    }

    /**
     * Get progress data for a translation session
     */
    public function get_progress($progress_id) {
        return get_transient('nexus_ai_wp_progress_' . $progress_id);
    }

    /**
     * Complete progress tracking
     */
    public function complete_progress($progress_id, $success = true, $final_message = '') {
        $progress_data = get_transient('nexus_ai_wp_progress_' . $progress_id);

        if (!$progress_data) {
            return false;
        }

        $progress_data['status'] = $success ? 'completed' : 'failed';
        $progress_data['end_time'] = time();
        $progress_data['progress_percentage'] = 100;
        $progress_data['final_message'] = $final_message;

        set_transient('nexus_ai_wp_progress_' . $progress_id, $progress_data, 3600);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Completed progress tracking for session {$progress_id}: " . ($success ? 'SUCCESS' : 'FAILED'));
        }

        return true;
    }

    /**
     * Clear progress tracking data
     */
    public function clear_progress($progress_id) {
        delete_transient('nexus_ai_wp_progress_' . $progress_id);
    }
}
