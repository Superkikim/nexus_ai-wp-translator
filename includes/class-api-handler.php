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
     * Get available models from Anthropic API
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

        $models = array();

        // Check if we have a 'data' array (standard format)
        if (isset($data['data']) && is_array($data['data'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: Processing models from API response data array');
            }
            foreach ($data['data'] as $model) {
                if (isset($model['id'])) {
                    $display_name = $this->format_model_name($model['id']);
                    $models[$model['id']] = $display_name;
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Nexus AI WP Translator: Added model: ' . $model['id'] . ' -> ' . $display_name);
                    }
                }
            }
        }
        // Check if we have a direct array of models
        elseif (is_array($data)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: Processing models from direct array response');
            }
            foreach ($data as $model) {
                if (is_string($model)) {
                    // Simple string model ID
                    $display_name = $this->format_model_name($model);
                    $models[$model] = $display_name;
                } elseif (isset($model['id'])) {
                    // Object with ID
                    $display_name = $this->format_model_name($model['id']);
                    $models[$model['id']] = $display_name;
                }
            }
        }

        // If no models found, use fallback
        if (empty($models)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: No models found in API response, using fallback');
            }
            $models = array(
                'claude-opus-4-1-20250805' => 'Claude Opus 4.1 (Most Capable)',
                'claude-sonnet-4-20250514' => 'Claude Sonnet 4 (High Performance)',
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Recommended)',
                'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku (Fast & Cost-Effective)'
            );
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
     * Format model name for display - intelligently parse model IDs
     */
    private function format_model_name($model_id) {
        // Handle known specific models for better display names
        $known_models = array(
            'claude-opus-4-1-20250805' => 'Claude Opus 4.1 (Latest)',
            'claude-opus-4-20250514' => 'Claude Opus 4',
            'claude-sonnet-4-20250514' => 'Claude Sonnet 4',
            'claude-3-7-sonnet-20250219' => 'Claude 3.7 Sonnet',
            'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Latest)',
            'claude-3-5-sonnet-20240620' => 'Claude 3.5 Sonnet',
            'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku (Latest)',
            'claude-3-opus-20240229' => 'Claude 3 Opus',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku'
        );

        if (isset($known_models[$model_id])) {
            return $known_models[$model_id];
        }

        // Intelligent parsing for unknown models
        $formatted = $model_id;

        // Remove date suffix (e.g., -20241022)
        $formatted = preg_replace('/-\d{8}$/', '', $formatted);

        // Handle claude-X-Y-Z pattern
        if (preg_match('/^claude-(.+)$/', $formatted, $matches)) {
            $parts = explode('-', $matches[1]);
            $name = 'Claude';

            foreach ($parts as $part) {
                if (is_numeric($part)) {
                    // Version number
                    $name .= ' ' . $part;
                } elseif (in_array(strtolower($part), ['opus', 'sonnet', 'haiku'])) {
                    // Model type
                    $name .= ' ' . ucfirst($part);
                } else {
                    // Other parts
                    $name .= ' ' . ucfirst($part);
                }
            }

            return $name;
        }

        // Fallback: just clean up the name
        return ucwords(str_replace(['-', '_'], ' ', $model_id));
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
        error_log("Nexus AI WP Translator: [API] Starting translation from {$source_lang} to {$target_lang}");
        error_log("Nexus AI WP Translator: [API] Content length: " . strlen($content) . " characters");
        
        if (empty($this->api_key)) {
            error_log('Nexus AI WP Translator: [API] API key not configured');
            return array(
                'success' => false,
                'message' => __('API key not configured', 'nexus-ai-wp-translator')
            );
        }
        
        // Check throttle limits
        $throttle_check = $this->check_throttle_limits_detailed();
        if (!$throttle_check['allowed']) {
            error_log('Nexus AI WP Translator: [API] API call limit reached');
            return array(
                'success' => false,
                'message' => $throttle_check['message']
            );
        }
        
        $start_time = microtime(true);
        
        // Get model from parameter or settings with detailed debugging
        if (!$model) {
            $model = get_option('nexus_ai_wp_translator_model', 'claude-3-5-sonnet-20241022');
            error_log("Nexus AI WP Translator: [API] Retrieved model from settings: '" . $model . "'");
        } else {
            error_log("Nexus AI WP Translator: [API] Using model from parameter: '" . $model . "'");
        }
        
        error_log("Nexus AI WP Translator: [API] Final model to use: " . $model);
        
        // Prepare the prompt
        $prompt = $this->prepare_translation_prompt($content, $source_lang, $target_lang);
        error_log('Nexus AI WP Translator: [API] Prepared prompt for translation');
        
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
        
        error_log('Nexus AI WP Translator: [API] Making API request to ' . $this->api_endpoint);
        
        // Calculate timeout based on content length (minimum 120 seconds, up to 300 seconds for long content)
        $content_length = strlen($content);
        $timeout = max(120, min(300, 120 + ($content_length / 1000))); // 120s base + 1s per 1000 chars

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: [API] Content length: {$content_length} chars, timeout: {$timeout}s");
        }

        // Make API request
        $response = wp_remote_post($this->api_endpoint, array(
            'headers' => $headers,
            'body' => wp_json_encode($body),
            'timeout' => $timeout
        ));
        
        $processing_time = microtime(true) - $start_time;
        error_log("Nexus AI WP Translator: [API] Request completed in {$processing_time}s");
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('Nexus AI WP Translator: [API] WP Error - ' . $error_message);

            // Special handling for timeout errors
            if (strpos($error_message, 'timeout') !== false || strpos($error_message, 'timed out') !== false) {
                $user_message = __('Translation timeout: The content is too large or the API is slow. Try breaking the content into smaller parts or try again later.', 'nexus-ai-wp-translator');
            } else {
                $user_message = __('Request failed: ', 'nexus-ai-wp-translator') . $error_message;
            }

            return array(
                'success' => false,
                'message' => $user_message,
                'processing_time' => $processing_time
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        error_log("Nexus AI WP Translator: [API] Response code: {$response_code}");
        
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) 
                ? $error_data['error']['message'] 
                : __('API request failed', 'nexus-ai-wp-translator');
            
            error_log('Nexus AI WP Translator: [API] Error - ' . $error_message);
            error_log('Nexus AI WP Translator: [API] Response body - ' . substr($response_body, 0, 500));
                
            return array(
                'success' => false,
                'message' => $error_message,
                'processing_time' => $processing_time
            );
        }
        
        $data = json_decode($response_body, true);
        
        if (!isset($data['content'][0]['text'])) {
            error_log('Nexus AI WP Translator: [API] Invalid response format');
            error_log('Nexus AI WP Translator: [API] Response data - ' . print_r($data, true));
            return array(
                'success' => false,
                'message' => __('Invalid API response format', 'nexus-ai-wp-translator'),
                'processing_time' => $processing_time
            );
        }
        
        $translated_content = $data['content'][0]['text'];
        error_log('Nexus AI WP Translator: [API] Translation successful, output length: ' . strlen($translated_content));
        
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
            "You are an expert native %s translator with deep cultural knowledge. " .
            "Your task is to create a natural, fluent translation that reads as if originally written in %s.\n\n" .

            "TRANSLATION PHILOSOPHY:\n" .
            "- Prioritize NATURAL FLOW and READABILITY over literal word-for-word translation\n" .
            "- Adapt expressions, idioms, and cultural references to feel native in %s\n" .
            "- Use the most appropriate %s terminology and phrasing for the context\n" .
            "- Maintain the original meaning while making it sound completely natural\n\n" .

            "CRITICAL REQUIREMENTS:\n" .
            "1. NATURALNESS: The translation must sound like it was originally written by a native %s speaker\n" .
            "2. CULTURAL ADAPTATION: Adapt idioms, expressions, and cultural references appropriately\n" .
            "3. CONTEXT AWARENESS: Consider the target audience and adjust formality/tone accordingly\n" .
            "4. DATES & TIMES: Use target language conventions (e.g., 'August 22, 2022 at 11 AM' not 'at 11 o'clock')\n" .
            "5. FORMATTING: Preserve ALL HTML tags, CSS classes, and structural elements exactly\n" .
            "6. TECHNICAL PRESERVATION: Keep URLs, emails, code snippets, and technical identifiers unchanged\n" .
            "7. PROPER NOUNS: Maintain brand names, person names, and place names in original form\n" .
            "8. WORDPRESS CONTENT: This is WordPress content - preserve all block structures and shortcodes\n\n" .

            "STYLE GUIDELINES:\n" .
            "- Avoid overly literal translations that sound awkward or unnatural\n" .
            "- Use active voice when it sounds more natural in %s\n" .
            "- Choose the most appropriate register (formal/informal) for the content type\n" .
            "- Ensure smooth transitions and logical flow between sentences\n\n" .

            "FORBIDDEN:\n" .
            "- Do not add explanations, comments, or meta-text\n" .
            "- Do not create word-for-word literal translations that sound unnatural\n" .
            "- Do not modify HTML attributes, CSS classes, or technical elements\n" .
            "- Do not translate content inside code blocks, shortcodes, or technical attributes\n" .
            "- Do not approximate specific times/dates with vague terms\n\n" .

            "Source language: %s\n" .
            "Target language: %s\n\n" .
            "Content to translate:\n%s",
            $target_lang_name,
            $target_lang_name,
            $target_lang_name,
            $target_lang_name,
            $target_lang_name,
            $target_lang_name,
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
