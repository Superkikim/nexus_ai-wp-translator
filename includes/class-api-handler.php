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
        error_log('Nexus AI WP Translator: Getting available models from API');
        
        if (empty($this->api_key)) {
            error_log('Nexus AI WP Translator: No API key provided for getting models');
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
        
        error_log('Nexus AI WP Translator: Making request to models endpoint: ' . $this->models_endpoint);
        
        $response = wp_remote_get($this->models_endpoint, array(
            'headers' => $headers,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('Nexus AI WP Translator: WP Error getting models: ' . $response->get_error_message());
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        error_log('Nexus AI WP Translator: Models API response code: ' . $response_code);
        error_log('Nexus AI WP Translator: Models API response body: ' . $response_body);
        
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) 
                ? $error_data['error']['message'] 
                : __('Failed to retrieve models', 'nexus-ai-wp-translator');
            
            error_log('Nexus AI WP Translator: Models API error: ' . $error_message);
                
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
        
        $data = json_decode($response_body, true);
        error_log('Nexus AI WP Translator: Decoded models data: ' . print_r($data, true));
        
        if (!isset($data['data']) || !is_array($data['data'])) {
            error_log('Nexus AI WP Translator: No models data in response, using fallback models');
            // Fallback to known models if API doesn't return model list
            $models = array(
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Latest)',
                'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku',
                'claude-3-opus-20240229' => 'Claude 3 Opus'
            );
        } else {
            error_log('Nexus AI WP Translator: Processing models from API response');
            $models = array();
            foreach ($data['data'] as $model) {
                if (isset($model['id'])) {
                    $display_name = $this->format_model_name($model['id']);
                    $models[$model['id']] = $display_name;
                    error_log('Nexus AI WP Translator: Added model: ' . $model['id'] . ' -> ' . $display_name);
                }
            }
            
            // If no models found, use fallback
            if (empty($models)) {
                error_log('Nexus AI WP Translator: No models found in API response, using fallback');
                $models = array(
                    'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Latest)',
                    'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
                    'claude-3-haiku-20240307' => 'Claude 3 Haiku',
                    'claude-3-opus-20240229' => 'Claude 3 Opus'
                );
            }
        }
        
        error_log('Nexus AI WP Translator: Final models array: ' . print_r($models, true));
        
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
        error_log('Nexus AI WP Translator: Testing API connection');
        
        if (empty($this->api_key)) {
            error_log('Nexus AI WP Translator: API key is empty');
            return array(
                'success' => false,
                'message' => __('API key is required', 'nexus-ai-wp-translator')
            );
        }
        
        error_log('Nexus AI WP Translator: Making simple API test request');
        
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
            error_log('Nexus AI WP Translator: Connection test failed - ' . $error_message);
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        error_log("Nexus AI WP Translator: API test response code: {$response_code}");
        
        if ($response_code === 200) {
            error_log('Nexus AI WP Translator: API connection test successful');
            return array(
                'success' => true,
                'message' => __('API connection successful', 'nexus-ai-wp-translator')
            );
        } else {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) 
                ? $error_data['error']['message'] 
                : __('API connection failed', 'nexus-ai-wp-translator');
            
            error_log('Nexus AI WP Translator: API test failed - ' . $error_message);
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
        if (!$this->check_throttle_limits()) {
            error_log('Nexus AI WP Translator: API call limit reached');
            return array(
                'success' => false,
                'message' => __('API call limit reached. Please try again later.', 'nexus-ai-wp-translator')
            );
        }
        
        $start_time = microtime(true);
        
        // Get model from parameter or settings
        if (!$model) {
            $model = get_option('nexus_ai_wp_translator_model', 'claude-3-5-sonnet-20241022');
        }
        
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
            "Please translate the following %s content to %s. " .
            "Maintain the original formatting, HTML tags, and structure. " .
            "Preserve any URLs, email addresses, and proper nouns. " .
            "Only return the translated content without any additional commentary.\n\n" .
            "Content to translate:\n%s",
            $source_lang_name,
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
        $db = Nexus_AI_WP_Translator_Database::get_instance();
        $throttle_limit = get_option('nexus_ai_wp_translator_throttle_limit', 10);
        $throttle_period = get_option('nexus_ai_wp_translator_throttle_period', 3600) / 60; // Convert to minutes
        
        $current_calls = $db->get_throttle_status($throttle_period);
        
        return $current_calls < $throttle_limit;
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
     * Translate post content with retry mechanism
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
        $content = $post->post_content;
        $title = $post->post_title;
        $excerpt = $post->post_excerpt;
        
        $retry_attempts = get_option('nexus_ai_wp_translator_retry_attempts', 3);
        $results = array();
        
        // Check cache first
        $cached_content = $this->get_cached_translation($content, $source_lang, $target_lang);
        $cached_title = $this->get_cached_translation($title, $source_lang, $target_lang);
        
        if ($cached_content && $cached_title) {
            return array(
                'success' => true,
                'title' => $cached_title,
                'content' => $cached_content,
                'excerpt' => $excerpt ? $this->get_cached_translation($excerpt, $source_lang, $target_lang) : '',
                'from_cache' => true
            );
        }
        
        // Translate title
        for ($i = 0; $i < $retry_attempts; $i++) {
            $title_result = $this->translate_content($title, $source_lang, $target_lang);
            if ($title_result['success']) {
                $results['title'] = $title_result['translated_content'];
                break;
            }
            
            if ($i === $retry_attempts - 1) {
                return array(
                    'success' => false,
                    'message' => __('Failed to translate title: ', 'nexus-ai-wp-translator') . $title_result['message']
                );
            }
            
            sleep(1); // Wait before retry
        }
        
        // Translate content
        for ($i = 0; $i < $retry_attempts; $i++) {
            $content_result = $this->translate_content($content, $source_lang, $target_lang);
            if ($content_result['success']) {
                $results['content'] = $content_result['translated_content'];
                break;
            }
            
            if ($i === $retry_attempts - 1) {
                return array(
                    'success' => false,
                    'message' => __('Failed to translate content: ', 'nexus-ai-wp-translator') . $content_result['message']
                );
            }
            
            sleep(1); // Wait before retry
        }
        
        // Translate excerpt if exists
        if (!empty($excerpt)) {
            $excerpt_result = $this->translate_content($excerpt, $source_lang, $target_lang);
            $results['excerpt'] = $excerpt_result['success'] ? $excerpt_result['translated_content'] : '';
        } else {
            $results['excerpt'] = '';
        }
        
        $results['success'] = true;
        $results['api_calls'] = isset($content_result['api_calls']) ? $content_result['api_calls'] + 1 : 2;
        $results['processing_time'] = (isset($title_result['processing_time']) ? $title_result['processing_time'] : 0) + 
                                    (isset($content_result['processing_time']) ? $content_result['processing_time'] : 0);
        
        return $results;
    }
}