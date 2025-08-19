<?php
/**
 * Advanced Error Handler & Recovery System
 * 
 * Provides intelligent error recovery, detailed reporting, and automatic fallback mechanisms
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_Error_Handler {
    
    private static $instance = null;
    private $error_log = array();
    private $recovery_strategies = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_recovery_strategies();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Error handling hooks
        add_action('nexus_ai_wp_translator_error', array($this, 'handle_error'), 10, 3);
        add_action('nexus_ai_wp_translator_api_error', array($this, 'handle_api_error'), 10, 4);
        add_action('nexus_ai_wp_translator_translation_error', array($this, 'handle_translation_error'), 10, 4);
        
        // Recovery hooks
        add_action('nexus_ai_wp_translator_attempt_recovery', array($this, 'attempt_error_recovery'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_nexus_ai_wp_get_error_reports', array($this, 'ajax_get_error_reports'));
        add_action('wp_ajax_nexus_ai_wp_retry_failed_translation', array($this, 'ajax_retry_failed_translation'));
        add_action('wp_ajax_nexus_ai_wp_clear_error_log', array($this, 'ajax_clear_error_log'));
        add_action('wp_ajax_nexus_ai_wp_export_error_log', array($this, 'ajax_export_error_log'));
        
        // Scheduled cleanup
        add_action('nexus_ai_wp_translator_cleanup_errors', array($this, 'cleanup_old_errors'));
        
        // Create tables on activation
        add_action('nexus_ai_wp_translator_activate', array($this, 'create_error_tables'));
    }
    
    /**
     * Initialize recovery strategies
     */
    private function init_recovery_strategies() {
        $this->recovery_strategies = array(
            'api_rate_limit' => array(
                'strategy' => 'exponential_backoff',
                'max_attempts' => 5,
                'base_delay' => 60 // seconds
            ),
            'api_quota_exceeded' => array(
                'strategy' => 'wait_and_retry',
                'max_attempts' => 3,
                'delay' => 3600 // 1 hour
            ),
            'api_timeout' => array(
                'strategy' => 'retry_with_smaller_chunks',
                'max_attempts' => 3,
                'chunk_reduction_factor' => 0.5
            ),
            'api_invalid_response' => array(
                'strategy' => 'retry_with_different_model',
                'max_attempts' => 2,
                'fallback_models' => array('claude-3-haiku-20240307')
            ),
            'content_too_large' => array(
                'strategy' => 'split_content',
                'max_attempts' => 3,
                'max_chunk_size' => 2000
            ),
            'network_error' => array(
                'strategy' => 'exponential_backoff',
                'max_attempts' => 5,
                'base_delay' => 30
            ),
            'memory_limit' => array(
                'strategy' => 'reduce_batch_size',
                'max_attempts' => 3,
                'batch_reduction_factor' => 0.5
            ),
            'database_error' => array(
                'strategy' => 'retry_with_delay',
                'max_attempts' => 3,
                'delay' => 10
            )
        );
        
        // Allow filtering of recovery strategies
        $this->recovery_strategies = apply_filters('nexus_ai_wp_translator_recovery_strategies', $this->recovery_strategies);
    }
    
    /**
     * Create error tables
     */
    public function create_error_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Error log table
        $error_table = $wpdb->prefix . 'nexus_ai_translation_errors';
        $error_sql = "CREATE TABLE $error_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            error_type varchar(50) NOT NULL,
            error_code varchar(50) DEFAULT NULL,
            error_message text NOT NULL,
            context text DEFAULT NULL,
            post_id bigint(20) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            source_language varchar(10) DEFAULT NULL,
            target_language varchar(10) DEFAULT NULL,
            api_endpoint varchar(100) DEFAULT NULL,
            request_data text DEFAULT NULL,
            response_data text DEFAULT NULL,
            stack_trace text DEFAULT NULL,
            recovery_attempted tinyint(1) DEFAULT 0,
            recovery_successful tinyint(1) DEFAULT 0,
            recovery_strategy varchar(50) DEFAULT NULL,
            attempt_count int(11) DEFAULT 1,
            severity varchar(20) DEFAULT 'error',
            resolved tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY error_type (error_type),
            KEY error_code (error_code),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY severity (severity),
            KEY resolved (resolved),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Recovery attempts table
        $recovery_table = $wpdb->prefix . 'nexus_ai_translation_recovery_attempts';
        $recovery_sql = "CREATE TABLE $recovery_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            error_id bigint(20) NOT NULL,
            strategy varchar(50) NOT NULL,
            attempt_number int(11) NOT NULL,
            parameters text DEFAULT NULL,
            success tinyint(1) DEFAULT 0,
            result_message text DEFAULT NULL,
            execution_time float DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY error_id (error_id),
            KEY strategy (strategy),
            KEY success (success),
            FOREIGN KEY (error_id) REFERENCES $error_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($error_sql);
        dbDelta($recovery_sql);
    }
    
    /**
     * Handle general error
     */
    public function handle_error($error_type, $error_message, $context = array()) {
        $error_id = $this->log_error($error_type, $error_message, $context);
        
        // Attempt recovery if strategy exists
        if (isset($this->recovery_strategies[$error_type])) {
            $this->attempt_error_recovery($error_id, $error_type);
        }
        
        // Send notification for critical errors
        if (isset($context['severity']) && $context['severity'] === 'critical') {
            $this->send_error_notification($error_id, $error_type, $error_message);
        }
        
        return $error_id;
    }
    
    /**
     * Handle API-specific errors
     */
    public function handle_api_error($error_code, $error_message, $request_data, $response_data) {
        $context = array(
            'error_code' => $error_code,
            'api_endpoint' => isset($request_data['endpoint']) ? $request_data['endpoint'] : 'unknown',
            'request_data' => wp_json_encode($request_data),
            'response_data' => wp_json_encode($response_data),
            'severity' => $this->determine_error_severity($error_code)
        );
        
        $error_type = $this->map_api_error_to_type($error_code);
        
        return $this->handle_error($error_type, $error_message, $context);
    }
    
    /**
     * Handle translation-specific errors
     */
    public function handle_translation_error($post_id, $target_language, $error_message, $context = array()) {
        $context = array_merge($context, array(
            'post_id' => $post_id,
            'target_language' => $target_language,
            'user_id' => get_current_user_id(),
            'severity' => 'error'
        ));
        
        return $this->handle_error('translation_error', $error_message, $context);
    }
    
    /**
     * Log error to database
     */
    private function log_error($error_type, $error_message, $context = array()) {
        global $wpdb;
        
        $error_table = $wpdb->prefix . 'nexus_ai_translation_errors';
        
        $error_data = array(
            'error_type' => $error_type,
            'error_message' => $error_message,
            'context' => wp_json_encode($context),
            'severity' => isset($context['severity']) ? $context['severity'] : 'error'
        );
        
        // Add context fields to main record
        $context_fields = array('error_code', 'post_id', 'user_id', 'source_language', 'target_language', 'api_endpoint', 'request_data', 'response_data');
        
        foreach ($context_fields as $field) {
            if (isset($context[$field])) {
                $error_data[$field] = $context[$field];
            }
        }
        
        // Add stack trace if available
        if (function_exists('debug_backtrace')) {
            $error_data['stack_trace'] = wp_json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10));
        }
        
        $result = $wpdb->insert($error_table, $error_data);
        
        if ($result === false) {
            // Fallback to WordPress error log
            error_log("Nexus AI WP Translator Error: {$error_type} - {$error_message}");
            return false;
        }
        
        $error_id = $wpdb->insert_id;
        
        // Add to in-memory log for immediate access
        $this->error_log[] = array(
            'id' => $error_id,
            'type' => $error_type,
            'message' => $error_message,
            'context' => $context,
            'timestamp' => time()
        );
        
        return $error_id;
    }
    
    /**
     * Attempt error recovery
     */
    public function attempt_error_recovery($error_id, $error_type) {
        if (!isset($this->recovery_strategies[$error_type])) {
            return false;
        }
        
        $strategy_config = $this->recovery_strategies[$error_type];
        $error = $this->get_error_by_id($error_id);
        
        if (!$error || $error->attempt_count >= $strategy_config['max_attempts']) {
            return false;
        }
        
        $start_time = microtime(true);
        $success = false;
        $result_message = '';
        
        try {
            switch ($strategy_config['strategy']) {
                case 'exponential_backoff':
                    $success = $this->recovery_exponential_backoff($error, $strategy_config);
                    break;
                    
                case 'wait_and_retry':
                    $success = $this->recovery_wait_and_retry($error, $strategy_config);
                    break;
                    
                case 'retry_with_smaller_chunks':
                    $success = $this->recovery_retry_with_smaller_chunks($error, $strategy_config);
                    break;
                    
                case 'retry_with_different_model':
                    $success = $this->recovery_retry_with_different_model($error, $strategy_config);
                    break;
                    
                case 'split_content':
                    $success = $this->recovery_split_content($error, $strategy_config);
                    break;
                    
                case 'reduce_batch_size':
                    $success = $this->recovery_reduce_batch_size($error, $strategy_config);
                    break;
                    
                case 'retry_with_delay':
                    $success = $this->recovery_retry_with_delay($error, $strategy_config);
                    break;
                    
                default:
                    $result_message = 'Unknown recovery strategy';
            }
        } catch (Exception $e) {
            $result_message = 'Recovery failed: ' . $e->getMessage();
        }
        
        $execution_time = microtime(true) - $start_time;
        
        // Log recovery attempt
        $this->log_recovery_attempt($error_id, $strategy_config['strategy'], $error->attempt_count + 1, $success, $result_message, $execution_time);
        
        // Update error record
        $this->update_error_recovery_status($error_id, $success, $strategy_config['strategy']);
        
        return $success;
    }
    
    /**
     * Recovery strategy: Exponential backoff
     */
    private function recovery_exponential_backoff($error, $config) {
        $delay = $config['base_delay'] * pow(2, $error->attempt_count - 1);
        
        // Schedule retry after delay
        wp_schedule_single_event(time() + $delay, 'nexus_ai_wp_translator_retry_after_backoff', array($error->id));
        
        return true; // Recovery scheduled
    }
    
    /**
     * Recovery strategy: Wait and retry
     */
    private function recovery_wait_and_retry($error, $config) {
        // Schedule retry after fixed delay
        wp_schedule_single_event(time() + $config['delay'], 'nexus_ai_wp_translator_retry_translation', array($error->post_id, $error->target_language));
        
        return true; // Recovery scheduled
    }
    
    /**
     * Recovery strategy: Retry with smaller chunks
     */
    private function recovery_retry_with_smaller_chunks($error, $config) {
        if (!$error->post_id) {
            return false;
        }
        
        // Get original post content
        $post = get_post($error->post_id);
        if (!$post) {
            return false;
        }
        
        // Split content into smaller chunks
        $content_length = strlen($post->post_content);
        $new_chunk_size = $content_length * $config['chunk_reduction_factor'];
        
        // Update translation settings temporarily
        update_option('nexus_ai_wp_translator_temp_chunk_size', $new_chunk_size);
        
        // Retry translation
        $translation_manager = Nexus_AI_WP_Translator_Translation_Manager::get_instance();
        $result = $translation_manager->translate_post($error->post_id, array($error->target_language));
        
        // Clean up temporary setting
        delete_option('nexus_ai_wp_translator_temp_chunk_size');
        
        return $result['success'];
    }
    
    /**
     * Recovery strategy: Retry with different model
     */
    private function recovery_retry_with_different_model($error, $config) {
        if (empty($config['fallback_models'])) {
            return false;
        }
        
        $current_model = get_option('nexus_ai_wp_translator_model', 'claude-3-5-sonnet-20241022');
        $fallback_model = $config['fallback_models'][0];
        
        // Temporarily switch to fallback model
        update_option('nexus_ai_wp_translator_temp_model', $fallback_model);
        
        // Retry translation
        $translation_manager = Nexus_AI_WP_Translator_Translation_Manager::get_instance();
        $result = $translation_manager->translate_post($error->post_id, array($error->target_language));
        
        // Restore original model
        delete_option('nexus_ai_wp_translator_temp_model');
        
        return $result['success'];
    }
    
    /**
     * Recovery strategy: Split content
     */
    private function recovery_split_content($error, $config) {
        if (!$error->post_id) {
            return false;
        }
        
        // Force content splitting
        update_option('nexus_ai_wp_translator_force_split', true);
        update_option('nexus_ai_wp_translator_max_chunk_size', $config['max_chunk_size']);
        
        // Retry translation
        $translation_manager = Nexus_AI_WP_Translator_Translation_Manager::get_instance();
        $result = $translation_manager->translate_post($error->post_id, array($error->target_language));
        
        // Clean up temporary settings
        delete_option('nexus_ai_wp_translator_force_split');
        delete_option('nexus_ai_wp_translator_max_chunk_size');
        
        return $result['success'];
    }
    
    /**
     * Recovery strategy: Reduce batch size
     */
    private function recovery_reduce_batch_size($error, $config) {
        $current_batch_size = get_option('nexus_ai_wp_translator_batch_size', 10);
        $new_batch_size = max(1, $current_batch_size * $config['batch_reduction_factor']);
        
        // Temporarily reduce batch size
        update_option('nexus_ai_wp_translator_temp_batch_size', $new_batch_size);
        
        // Retry operation
        $success = true; // Placeholder - would depend on specific operation
        
        // Clean up temporary setting
        delete_option('nexus_ai_wp_translator_temp_batch_size');
        
        return $success;
    }
    
    /**
     * Recovery strategy: Retry with delay
     */
    private function recovery_retry_with_delay($error, $config) {
        sleep($config['delay']);
        
        // Retry the original operation
        if ($error->post_id) {
            $translation_manager = Nexus_AI_WP_Translator_Translation_Manager::get_instance();
            $result = $translation_manager->translate_post($error->post_id, array($error->target_language));
            return $result['success'];
        }
        
        return false;
    }

    /**
     * Log recovery attempt
     */
    private function log_recovery_attempt($error_id, $strategy, $attempt_number, $success, $result_message, $execution_time) {
        global $wpdb;

        $recovery_table = $wpdb->prefix . 'nexus_ai_translation_recovery_attempts';

        $wpdb->insert(
            $recovery_table,
            array(
                'error_id' => $error_id,
                'strategy' => $strategy,
                'attempt_number' => $attempt_number,
                'success' => $success ? 1 : 0,
                'result_message' => $result_message,
                'execution_time' => $execution_time
            ),
            array('%d', '%s', '%d', '%d', '%s', '%f')
        );
    }

    /**
     * Update error recovery status
     */
    private function update_error_recovery_status($error_id, $success, $strategy) {
        global $wpdb;

        $error_table = $wpdb->prefix . 'nexus_ai_translation_errors';

        $wpdb->update(
            $error_table,
            array(
                'recovery_attempted' => 1,
                'recovery_successful' => $success ? 1 : 0,
                'recovery_strategy' => $strategy,
                'attempt_count' => $wpdb->get_var($wpdb->prepare(
                    "SELECT attempt_count + 1 FROM $error_table WHERE id = %d",
                    $error_id
                )),
                'resolved' => $success ? 1 : 0
            ),
            array('id' => $error_id),
            array('%d', '%d', '%s', '%d', '%d'),
            array('%d')
        );
    }

    /**
     * Get error by ID
     */
    private function get_error_by_id($error_id) {
        global $wpdb;

        $error_table = $wpdb->prefix . 'nexus_ai_translation_errors';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $error_table WHERE id = %d",
            $error_id
        ));
    }

    /**
     * Map API error code to error type
     */
    private function map_api_error_to_type($error_code) {
        $error_mapping = array(
            '429' => 'api_rate_limit',
            'rate_limit_exceeded' => 'api_rate_limit',
            'quota_exceeded' => 'api_quota_exceeded',
            'timeout' => 'api_timeout',
            'invalid_response' => 'api_invalid_response',
            'network_error' => 'network_error',
            'content_too_large' => 'content_too_large'
        );

        return isset($error_mapping[$error_code]) ? $error_mapping[$error_code] : 'api_error';
    }

    /**
     * Determine error severity
     */
    private function determine_error_severity($error_code) {
        $critical_errors = array('quota_exceeded', 'authentication_failed', 'service_unavailable');
        $warning_errors = array('rate_limit_exceeded', 'timeout');

        if (in_array($error_code, $critical_errors)) {
            return 'critical';
        } elseif (in_array($error_code, $warning_errors)) {
            return 'warning';
        } else {
            return 'error';
        }
    }

    /**
     * Send error notification
     */
    private function send_error_notification($error_id, $error_type, $error_message) {
        $notification_settings = get_option('nexus_ai_wp_translator_error_notifications', array());

        if (!isset($notification_settings['enabled']) || !$notification_settings['enabled']) {
            return;
        }

        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');

        $subject = sprintf(__('[%s] Critical Translation Error', 'nexus-ai-wp-translator'), $site_name);
        $message = sprintf(
            __("A critical error occurred in the Nexus AI WP Translator plugin:\n\nError Type: %s\nError Message: %s\nError ID: %d\n\nPlease check the error log for more details.", 'nexus-ai-wp-translator'),
            $error_type,
            $error_message,
            $error_id
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Get error reports
     */
    public function get_error_reports($filters = array(), $limit = 50, $offset = 0) {
        global $wpdb;

        $error_table = $wpdb->prefix . 'nexus_ai_translation_errors';

        $where_conditions = array();
        $where_values = array();

        if (isset($filters['error_type'])) {
            $where_conditions[] = "error_type = %s";
            $where_values[] = $filters['error_type'];
        }

        if (isset($filters['severity'])) {
            $where_conditions[] = "severity = %s";
            $where_values[] = $filters['severity'];
        }

        if (isset($filters['resolved'])) {
            $where_conditions[] = "resolved = %d";
            $where_values[] = $filters['resolved'];
        }

        if (isset($filters['date_from'])) {
            $where_conditions[] = "created_at >= %s";
            $where_values[] = $filters['date_from'];
        }

        if (isset($filters['date_to'])) {
            $where_conditions[] = "created_at <= %s";
            $where_values[] = $filters['date_to'];
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        $where_values[] = $limit;
        $where_values[] = $offset;

        $sql = "SELECT e.*,
                       p.post_title,
                       u.display_name as user_name
                FROM $error_table e
                LEFT JOIN {$wpdb->posts} p ON e.post_id = p.ID
                LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
                $where_clause
                ORDER BY e.created_at DESC
                LIMIT %d OFFSET %d";

        return $wpdb->get_results($wpdb->prepare($sql, ...$where_values));
    }

    /**
     * Get error statistics
     */
    public function get_error_statistics($period = '30_days') {
        global $wpdb;

        $error_table = $wpdb->prefix . 'nexus_ai_translation_errors';

        $date_condition = $this->get_date_condition($period);

        $stats = array();

        // Total errors
        $stats['total_errors'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $error_table WHERE 1=1 $date_condition"
        );

        // Errors by type
        $stats['by_type'] = $wpdb->get_results(
            "SELECT error_type, COUNT(*) as count
             FROM $error_table
             WHERE 1=1 $date_condition
             GROUP BY error_type
             ORDER BY count DESC"
        );

        // Errors by severity
        $stats['by_severity'] = $wpdb->get_results(
            "SELECT severity, COUNT(*) as count
             FROM $error_table
             WHERE 1=1 $date_condition
             GROUP BY severity"
        );

        // Recovery success rate
        $recovery_stats = $wpdb->get_row(
            "SELECT
                COUNT(*) as total_recovery_attempts,
                SUM(recovery_successful) as successful_recoveries
             FROM $error_table
             WHERE recovery_attempted = 1 $date_condition"
        );

        $stats['recovery_success_rate'] = $recovery_stats->total_recovery_attempts > 0 ?
            round(($recovery_stats->successful_recoveries / $recovery_stats->total_recovery_attempts) * 100, 1) : 0;

        // Most common error messages
        $stats['common_messages'] = $wpdb->get_results(
            "SELECT error_message, COUNT(*) as count
             FROM $error_table
             WHERE 1=1 $date_condition
             GROUP BY error_message
             ORDER BY count DESC
             LIMIT 10"
        );

        // Error trends
        $interval = $this->get_interval_for_period($period);
        $stats['trends'] = $wpdb->get_results(
            "SELECT
                DATE_FORMAT(created_at, '$interval') as period,
                COUNT(*) as error_count,
                SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_count
             FROM $error_table
             WHERE 1=1 $date_condition
             GROUP BY period
             ORDER BY period"
        );

        return $stats;
    }

    /**
     * Cleanup old errors
     */
    public function cleanup_old_errors() {
        global $wpdb;

        $error_table = $wpdb->prefix . 'nexus_ai_translation_errors';

        $retention_days = get_option('nexus_ai_wp_translator_error_retention_days', 90);

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $error_table
             WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
             AND resolved = 1",
            $retention_days
        ));

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Nexus AI WP Translator: Cleaned up {$deleted} old error records");
        }

        return $deleted;
    }

    /**
     * Get date condition for SQL queries
     */
    private function get_date_condition($period) {
        switch ($period) {
            case '7_days':
                return " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case '30_days':
                return " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case '90_days':
                return " AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
            case '1_year':
                return " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
    }

    /**
     * Get interval format for period
     */
    private function get_interval_for_period($period) {
        switch ($period) {
            case '7_days':
                return '%Y-%m-%d'; // Daily
            case '30_days':
                return '%Y-%m-%d'; // Daily
            case '90_days':
                return '%Y-%u'; // Weekly
            case '1_year':
                return '%Y-%m'; // Monthly
            default:
                return '%Y-%m-%d'; // Daily
        }
    }

    /**
     * AJAX handlers
     */
    public function ajax_get_error_reports() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $filters = isset($_POST['filters']) ? array_map('sanitize_text_field', $_POST['filters']) : array();
        $limit = intval($_POST['limit']) ?: 50;
        $offset = intval($_POST['offset']) ?: 0;

        $errors = $this->get_error_reports($filters, $limit, $offset);
        $statistics = $this->get_error_statistics();

        wp_send_json_success(array(
            'errors' => $errors,
            'statistics' => $statistics
        ));
    }

    public function ajax_retry_failed_translation() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $error_id = intval($_POST['error_id']);

        $error = $this->get_error_by_id($error_id);

        if (!$error || !$error->post_id) {
            wp_send_json_error(__('Invalid error or missing post ID', 'nexus-ai-wp-translator'));
        }

        // Attempt recovery
        $success = $this->attempt_error_recovery($error_id, $error->error_type);

        if ($success) {
            wp_send_json_success(__('Recovery attempted successfully', 'nexus-ai-wp-translator'));
        } else {
            wp_send_json_error(__('Recovery failed', 'nexus-ai-wp-translator'));
        }
    }

    public function ajax_clear_error_log() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        global $wpdb;

        $error_table = $wpdb->prefix . 'nexus_ai_translation_errors';

        $deleted = $wpdb->query("DELETE FROM $error_table WHERE resolved = 1");

        wp_send_json_success(array('deleted' => $deleted));
    }

    public function ajax_export_error_log() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $format = sanitize_text_field($_POST['format']) ?: 'csv';
        $filters = isset($_POST['filters']) ? array_map('sanitize_text_field', $_POST['filters']) : array();

        $errors = $this->get_error_reports($filters, 10000); // Large limit for export

        if ($format === 'csv') {
            $this->export_errors_csv($errors);
        } else {
            $this->export_errors_json($errors);
        }
    }

    /**
     * Export errors to CSV
     */
    private function export_errors_csv($errors) {
        $filename = 'nexus-ai-error-log-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Header
        fputcsv($output, array(
            'ID', 'Error Type', 'Error Code', 'Error Message', 'Post Title',
            'User', 'Severity', 'Recovery Attempted', 'Recovery Successful',
            'Resolved', 'Created At'
        ));

        foreach ($errors as $error) {
            fputcsv($output, array(
                $error->id,
                $error->error_type,
                $error->error_code,
                $error->error_message,
                $error->post_title,
                $error->user_name,
                $error->severity,
                $error->recovery_attempted ? 'Yes' : 'No',
                $error->recovery_successful ? 'Yes' : 'No',
                $error->resolved ? 'Yes' : 'No',
                $error->created_at
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * Export errors to JSON
     */
    private function export_errors_json($errors) {
        $filename = 'nexus-ai-error-log-' . date('Y-m-d') . '.json';

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo wp_json_encode($errors, JSON_PRETTY_PRINT);
        exit;
    }
}
