<?php
/**
 * Claude AI API Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class Claude_Translator_API_Handler {
    
    private static $instance = null;
    private $api_key;
    private $api_endpoint = 'https://api.anthropic.com/v1/messages';
    private $model = 'claude-3-sonnet-20240229';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->api_key = get_option('claude_translator_api_key', '');
    }
    
    /**
     * Refresh API key from database
     */
    public function refresh_api_key() {
        $this->api_key = get_option('claude_translator_api_key', '');
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
                'message' => __('API key is required', 'claude-translator')
            );
        }
        
        $test_content = 'Hello, this is a test.';
        error_log('Nexus AI WP Translator: Starting test translation');
        $result = $this->translate_content($test_content, 'en', 'es');
        
        if ($result['success']) {
            error_log('Nexus AI WP Translator: API test successful');
            return array(
                'success' => true,
                'message' => __('API connection successful', 'claude-translator')
            );
        }
        
        error_log('Nexus AI WP Translator: API test failed - ' . $result['message']);
        return $result;
    }
    
    /**
     * Translate content using Claude AI
     */
    public function translate_content($content, $source_lang, $target_lang) {
        error_log("Nexus AI WP Translator: Starting translation from {$source_lang} to {$target_lang}");
        
        if (empty($this->api_key)) {
            error_log('Nexus AI WP Translator: API key not configured');
            return array(
                'success' => false,
                'message' => __('API key not configured', 'claude-translator')
            );
        }
        
        // Check throttle limits
        if (!$this->check_throttle_limits()) {
            error_log('Nexus AI WP Translator: API call limit reached');
            return array(
                'success' => false,
                'message' => __('API call limit reached. Please try again later.', 'claude-translator')
            );
        }
        
        $start_time = microtime(true);
        
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
            'model' => $this->model,
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
                'message' => __('Invalid API response format', 'claude-translator'),
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
        $db = Claude_Translator_Database::get_instance();
        $throttle_limit = get_option('claude_translator_throttle_limit', 10);
        $throttle_period = get_option('claude_translator_throttle_period', 3600) / 60; // Convert to minutes
        
        $current_calls = $db->get_throttle_status($throttle_period);
        
        return $current_calls < $throttle_limit;
    }
    
    /**
     * Cache translation
     */
    private function cache_translation($original, $source_lang, $target_lang, $translation) {
        if (!get_option('claude_translator_cache_translations', true)) {
            return;
        }
        
        $cache_key = 'claude_translation_' . md5($original . $source_lang . $target_lang);
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
        if (!get_option('claude_translator_cache_translations', true)) {
            return false;
        }
        
        $cache_key = 'claude_translation_' . md5($original . $source_lang . $target_lang);
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
                'message' => __('Post not found', 'claude-translator')
            );
        }
        
        $source_lang = get_post_meta($post_id, '_claude_translator_language', true) ?: get_option('claude_translator_source_language', 'en');
        $content = $post->post_content;
        $title = $post->post_title;
        $excerpt = $post->post_excerpt;
        
        $retry_attempts = get_option('claude_translator_retry_attempts', 3);
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
                    'message' => __('Failed to translate title: ', 'claude-translator') . $title_result['message']
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
                    'message' => __('Failed to translate content: ', 'claude-translator') . $content_result['message']
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