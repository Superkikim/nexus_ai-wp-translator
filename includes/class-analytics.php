<?php
/**
 * Translation Analytics
 * 
 * Tracks and analyzes translation performance, API usage, costs, and quality metrics
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_Analytics {
    
    private static $instance = null;
    private $db;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = Nexus_AI_WP_Translator_Database::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Track translation events
        add_action('nexus_ai_wp_translator_translation_completed', array($this, 'track_translation_completed'), 10, 3);
        add_action('nexus_ai_wp_translator_translation_failed', array($this, 'track_translation_failed'), 10, 3);
        add_action('nexus_ai_wp_translator_api_call_made', array($this, 'track_api_call'), 10, 4);
        
        // AJAX handlers
        add_action('wp_ajax_nexus_ai_wp_get_analytics_data', array($this, 'ajax_get_analytics_data'));
        add_action('wp_ajax_nexus_ai_wp_export_analytics', array($this, 'ajax_export_analytics'));
        
        // Create analytics tables on activation
        add_action('nexus_ai_wp_translator_activate', array($this, 'create_analytics_tables'));
    }
    
    /**
     * Create analytics tables
     */
    public function create_analytics_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Translation events table
        $events_table = $wpdb->prefix . 'nexus_ai_translation_events';
        $events_sql = "CREATE TABLE $events_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            post_id bigint(20) NOT NULL,
            source_language varchar(10) NOT NULL,
            target_language varchar(10) NOT NULL,
            api_calls int(11) DEFAULT 0,
            tokens_used int(11) DEFAULT 0,
            processing_time float DEFAULT 0,
            quality_score int(11) DEFAULT NULL,
            error_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY post_id (post_id),
            KEY created_at (created_at),
            KEY target_language (target_language)
        ) $charset_collate;";
        
        // API usage table
        $api_table = $wpdb->prefix . 'nexus_ai_api_usage';
        $api_sql = "CREATE TABLE $api_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            endpoint varchar(100) NOT NULL,
            model varchar(50) NOT NULL,
            tokens_input int(11) DEFAULT 0,
            tokens_output int(11) DEFAULT 0,
            cost_estimate decimal(10,6) DEFAULT 0,
            response_time float DEFAULT 0,
            success tinyint(1) DEFAULT 1,
            error_code varchar(50) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY endpoint (endpoint),
            KEY model (model),
            KEY created_at (created_at),
            KEY success (success)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($events_sql);
        dbDelta($api_sql);
    }
    
    /**
     * Track translation completion
     */
    public function track_translation_completed($post_id, $target_language, $translation_data) {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'nexus_ai_translation_events';
        
        $source_language = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');
        
        $wpdb->insert(
            $events_table,
            array(
                'event_type' => 'translation_completed',
                'post_id' => $post_id,
                'source_language' => $source_language,
                'target_language' => $target_language,
                'api_calls' => isset($translation_data['api_calls']) ? $translation_data['api_calls'] : 0,
                'tokens_used' => isset($translation_data['tokens_used']) ? $translation_data['tokens_used'] : 0,
                'processing_time' => isset($translation_data['processing_time']) ? $translation_data['processing_time'] : 0,
                'quality_score' => isset($translation_data['quality_score']) ? $translation_data['quality_score'] : null
            ),
            array('%s', '%d', '%s', '%s', '%d', '%d', '%f', '%d')
        );
    }
    
    /**
     * Track translation failure
     */
    public function track_translation_failed($post_id, $target_language, $error_data) {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'nexus_ai_translation_events';
        
        $source_language = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');
        
        $wpdb->insert(
            $events_table,
            array(
                'event_type' => 'translation_failed',
                'post_id' => $post_id,
                'source_language' => $source_language,
                'target_language' => $target_language,
                'error_message' => isset($error_data['message']) ? $error_data['message'] : 'Unknown error'
            ),
            array('%s', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Track API call
     */
    public function track_api_call($endpoint, $model, $usage_data, $success) {
        global $wpdb;
        
        $api_table = $wpdb->prefix . 'nexus_ai_api_usage';
        
        $wpdb->insert(
            $api_table,
            array(
                'endpoint' => $endpoint,
                'model' => $model,
                'tokens_input' => isset($usage_data['input_tokens']) ? $usage_data['input_tokens'] : 0,
                'tokens_output' => isset($usage_data['output_tokens']) ? $usage_data['output_tokens'] : 0,
                'cost_estimate' => isset($usage_data['cost_estimate']) ? $usage_data['cost_estimate'] : 0,
                'response_time' => isset($usage_data['response_time']) ? $usage_data['response_time'] : 0,
                'success' => $success ? 1 : 0,
                'error_code' => isset($usage_data['error_code']) ? $usage_data['error_code'] : null
            ),
            array('%s', '%s', '%d', '%d', '%f', '%f', '%d', '%s')
        );
    }
    
    /**
     * Get analytics data
     */
    public function get_analytics_data($period = '30_days', $filters = array()) {
        $data = array(
            'overview' => $this->get_overview_stats($period),
            'translation_trends' => $this->get_translation_trends($period),
            'language_distribution' => $this->get_language_distribution($period),
            'quality_metrics' => $this->get_quality_metrics($period),
            'api_usage' => $this->get_api_usage_stats($period),
            'cost_analysis' => $this->get_cost_analysis($period),
            'performance_metrics' => $this->get_performance_metrics($period),
            'error_analysis' => $this->get_error_analysis($period)
        );
        
        return $data;
    }
    
    /**
     * Get overview statistics
     */
    private function get_overview_stats($period) {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($period);
        $events_table = $wpdb->prefix . 'nexus_ai_translation_events';
        $api_table = $wpdb->prefix . 'nexus_ai_api_usage';
        
        // Total translations
        $total_translations = $wpdb->get_var(
            "SELECT COUNT(*) FROM $events_table 
             WHERE event_type = 'translation_completed' $date_condition"
        );
        
        // Failed translations
        $failed_translations = $wpdb->get_var(
            "SELECT COUNT(*) FROM $events_table 
             WHERE event_type = 'translation_failed' $date_condition"
        );
        
        // Success rate
        $success_rate = $total_translations > 0 ? 
            round(($total_translations / ($total_translations + $failed_translations)) * 100, 1) : 0;
        
        // Total API calls
        $total_api_calls = $wpdb->get_var(
            "SELECT COUNT(*) FROM $api_table $date_condition"
        );
        
        // Total tokens used
        $total_tokens = $wpdb->get_var(
            "SELECT SUM(tokens_input + tokens_output) FROM $api_table $date_condition"
        );
        
        // Total estimated cost
        $total_cost = $wpdb->get_var(
            "SELECT SUM(cost_estimate) FROM $api_table $date_condition"
        );
        
        // Average quality score
        $avg_quality = $wpdb->get_var(
            "SELECT AVG(quality_score) FROM $events_table 
             WHERE event_type = 'translation_completed' 
             AND quality_score IS NOT NULL $date_condition"
        );
        
        return array(
            'total_translations' => intval($total_translations),
            'failed_translations' => intval($failed_translations),
            'success_rate' => floatval($success_rate),
            'total_api_calls' => intval($total_api_calls),
            'total_tokens' => intval($total_tokens),
            'total_cost' => floatval($total_cost),
            'average_quality' => $avg_quality ? round(floatval($avg_quality), 1) : null
        );
    }
    
    /**
     * Get translation trends
     */
    private function get_translation_trends($period) {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($period);
        $events_table = $wpdb->prefix . 'nexus_ai_translation_events';
        
        $interval = $this->get_interval_for_period($period);
        
        $results = $wpdb->get_results(
            "SELECT 
                DATE_FORMAT(created_at, '$interval') as period,
                COUNT(CASE WHEN event_type = 'translation_completed' THEN 1 END) as completed,
                COUNT(CASE WHEN event_type = 'translation_failed' THEN 1 END) as failed
             FROM $events_table 
             WHERE 1=1 $date_condition
             GROUP BY period 
             ORDER BY period"
        );
        
        return $results;
    }
    
    /**
     * Get language distribution
     */
    private function get_language_distribution($period) {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($period);
        $events_table = $wpdb->prefix . 'nexus_ai_translation_events';
        
        $results = $wpdb->get_results(
            "SELECT 
                target_language,
                COUNT(*) as count,
                AVG(quality_score) as avg_quality
             FROM $events_table 
             WHERE event_type = 'translation_completed' $date_condition
             GROUP BY target_language 
             ORDER BY count DESC"
        );
        
        return $results;
    }
    
    /**
     * Get quality metrics
     */
    private function get_quality_metrics($period) {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($period);
        $events_table = $wpdb->prefix . 'nexus_ai_translation_events';
        
        // Quality distribution
        $quality_distribution = $wpdb->get_results(
            "SELECT 
                CASE 
                    WHEN quality_score >= 90 THEN 'Excellent (90-100)'
                    WHEN quality_score >= 80 THEN 'Good (80-89)'
                    WHEN quality_score >= 70 THEN 'Fair (70-79)'
                    WHEN quality_score >= 60 THEN 'Poor (60-69)'
                    ELSE 'Very Poor (<60)'
                END as quality_range,
                COUNT(*) as count
             FROM $events_table 
             WHERE event_type = 'translation_completed' 
             AND quality_score IS NOT NULL $date_condition
             GROUP BY quality_range 
             ORDER BY MIN(quality_score) DESC"
        );
        
        // Quality trends
        $interval = $this->get_interval_for_period($period);
        $quality_trends = $wpdb->get_results(
            "SELECT 
                DATE_FORMAT(created_at, '$interval') as period,
                AVG(quality_score) as avg_quality,
                COUNT(*) as count
             FROM $events_table 
             WHERE event_type = 'translation_completed' 
             AND quality_score IS NOT NULL $date_condition
             GROUP BY period 
             ORDER BY period"
        );
        
        return array(
            'distribution' => $quality_distribution,
            'trends' => $quality_trends
        );
    }
    
    /**
     * Get API usage statistics
     */
    private function get_api_usage_stats($period) {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($period);
        $api_table = $wpdb->prefix . 'nexus_ai_api_usage';
        
        // Usage by model
        $model_usage = $wpdb->get_results(
            "SELECT 
                model,
                COUNT(*) as calls,
                SUM(tokens_input + tokens_output) as tokens,
                AVG(response_time) as avg_response_time,
                SUM(cost_estimate) as total_cost
             FROM $api_table 
             WHERE 1=1 $date_condition
             GROUP BY model 
             ORDER BY calls DESC"
        );
        
        // Usage trends
        $interval = $this->get_interval_for_period($period);
        $usage_trends = $wpdb->get_results(
            "SELECT 
                DATE_FORMAT(created_at, '$interval') as period,
                COUNT(*) as calls,
                SUM(tokens_input + tokens_output) as tokens,
                SUM(cost_estimate) as cost
             FROM $api_table 
             WHERE 1=1 $date_condition
             GROUP BY period 
             ORDER BY period"
        );
        
        return array(
            'model_usage' => $model_usage,
            'usage_trends' => $usage_trends
        );
    }
    
    /**
     * Get cost analysis
     */
    private function get_cost_analysis($period) {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($period);
        $api_table = $wpdb->prefix . 'nexus_ai_api_usage';
        
        // Cost breakdown by model
        $cost_by_model = $wpdb->get_results(
            "SELECT 
                model,
                SUM(cost_estimate) as total_cost,
                COUNT(*) as calls,
                SUM(cost_estimate) / COUNT(*) as cost_per_call
             FROM $api_table 
             WHERE 1=1 $date_condition
             GROUP BY model 
             ORDER BY total_cost DESC"
        );
        
        // Daily cost trends
        $cost_trends = $wpdb->get_results(
            "SELECT 
                DATE(created_at) as date,
                SUM(cost_estimate) as daily_cost,
                COUNT(*) as daily_calls
             FROM $api_table 
             WHERE 1=1 $date_condition
             GROUP BY DATE(created_at) 
             ORDER BY date"
        );
        
        return array(
            'cost_by_model' => $cost_by_model,
            'cost_trends' => $cost_trends
        );
    }
    
    /**
     * Get performance metrics
     */
    private function get_performance_metrics($period) {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($period);
        $events_table = $wpdb->prefix . 'nexus_ai_translation_events';
        $api_table = $wpdb->prefix . 'nexus_ai_api_usage';
        
        // Average processing time
        $avg_processing_time = $wpdb->get_var(
            "SELECT AVG(processing_time) FROM $events_table 
             WHERE event_type = 'translation_completed' $date_condition"
        );
        
        // Average API response time
        $avg_response_time = $wpdb->get_var(
            "SELECT AVG(response_time) FROM $api_table 
             WHERE success = 1 $date_condition"
        );
        
        // Performance trends
        $interval = $this->get_interval_for_period($period);
        $performance_trends = $wpdb->get_results(
            "SELECT 
                DATE_FORMAT(e.created_at, '$interval') as period,
                AVG(e.processing_time) as avg_processing_time,
                AVG(a.response_time) as avg_response_time
             FROM $events_table e
             LEFT JOIN $api_table a ON DATE(e.created_at) = DATE(a.created_at)
             WHERE e.event_type = 'translation_completed' $date_condition
             GROUP BY period 
             ORDER BY period"
        );
        
        return array(
            'avg_processing_time' => floatval($avg_processing_time),
            'avg_response_time' => floatval($avg_response_time),
            'performance_trends' => $performance_trends
        );
    }
    
    /**
     * Get error analysis
     */
    private function get_error_analysis($period) {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($period);
        $events_table = $wpdb->prefix . 'nexus_ai_translation_events';
        $api_table = $wpdb->prefix . 'nexus_ai_api_usage';
        
        // Common error messages
        $common_errors = $wpdb->get_results(
            "SELECT 
                error_message,
                COUNT(*) as count
             FROM $events_table 
             WHERE event_type = 'translation_failed' 
             AND error_message IS NOT NULL $date_condition
             GROUP BY error_message 
             ORDER BY count DESC 
             LIMIT 10"
        );
        
        // API error codes
        $api_errors = $wpdb->get_results(
            "SELECT 
                error_code,
                COUNT(*) as count
             FROM $api_table 
             WHERE success = 0 
             AND error_code IS NOT NULL $date_condition
             GROUP BY error_code 
             ORDER BY count DESC"
        );
        
        return array(
            'common_errors' => $common_errors,
            'api_errors' => $api_errors
        );
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
     * AJAX: Get analytics data
     */
    public function ajax_get_analytics_data() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $period = sanitize_text_field($_POST['period']) ?: '30_days';
        $filters = isset($_POST['filters']) ? array_map('sanitize_text_field', $_POST['filters']) : array();

        $analytics_data = $this->get_analytics_data($period, $filters);

        wp_send_json_success($analytics_data);
    }

    /**
     * AJAX: Export analytics
     */
    public function ajax_export_analytics() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $period = sanitize_text_field($_POST['period']) ?: '30_days';
        $format = sanitize_text_field($_POST['format']) ?: 'csv';

        $analytics_data = $this->get_analytics_data($period);

        if ($format === 'csv') {
            $this->export_to_csv($analytics_data, $period);
        } else {
            $this->export_to_json($analytics_data, $period);
        }
    }

    /**
     * Export analytics to CSV
     */
    private function export_to_csv($data, $period) {
        $filename = 'nexus-ai-analytics-' . $period . '-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Overview section
        fputcsv($output, array('OVERVIEW STATISTICS'));
        fputcsv($output, array('Metric', 'Value'));
        fputcsv($output, array('Total Translations', $data['overview']['total_translations']));
        fputcsv($output, array('Failed Translations', $data['overview']['failed_translations']));
        fputcsv($output, array('Success Rate (%)', $data['overview']['success_rate']));
        fputcsv($output, array('Total API Calls', $data['overview']['total_api_calls']));
        fputcsv($output, array('Total Tokens', $data['overview']['total_tokens']));
        fputcsv($output, array('Total Cost ($)', $data['overview']['total_cost']));
        fputcsv($output, array('Average Quality Score', $data['overview']['average_quality']));
        fputcsv($output, array(''));

        // Language distribution
        fputcsv($output, array('LANGUAGE DISTRIBUTION'));
        fputcsv($output, array('Language', 'Count', 'Average Quality'));
        foreach ($data['language_distribution'] as $lang) {
            fputcsv($output, array($lang->target_language, $lang->count, round($lang->avg_quality, 1)));
        }
        fputcsv($output, array(''));

        // Translation trends
        fputcsv($output, array('TRANSLATION TRENDS'));
        fputcsv($output, array('Period', 'Completed', 'Failed'));
        foreach ($data['translation_trends'] as $trend) {
            fputcsv($output, array($trend->period, $trend->completed, $trend->failed));
        }

        fclose($output);
        exit;
    }

    /**
     * Export analytics to JSON
     */
    private function export_to_json($data, $period) {
        $filename = 'nexus-ai-analytics-' . $period . '-' . date('Y-m-d') . '.json';

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo wp_json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Get analytics summary for dashboard widget
     */
    public function get_dashboard_summary() {
        $overview = $this->get_overview_stats('7_days');

        return array(
            'translations_this_week' => $overview['total_translations'],
            'success_rate' => $overview['success_rate'],
            'avg_quality' => $overview['average_quality'],
            'total_cost_this_week' => $overview['total_cost']
        );
    }

    /**
     * Get top performing languages
     */
    public function get_top_languages($limit = 5) {
        global $wpdb;

        $events_table = $wpdb->prefix . 'nexus_ai_translation_events';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT
                target_language,
                COUNT(*) as translation_count,
                AVG(quality_score) as avg_quality
             FROM $events_table
             WHERE event_type = 'translation_completed'
             AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY target_language
             ORDER BY translation_count DESC
             LIMIT %d",
            $limit
        ));
    }

    /**
     * Get recent translation activity
     */
    public function get_recent_activity($limit = 10) {
        global $wpdb;

        $events_table = $wpdb->prefix . 'nexus_ai_translation_events';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT
                e.*,
                p.post_title,
                p.post_type
             FROM $events_table e
             LEFT JOIN {$wpdb->posts} p ON e.post_id = p.ID
             ORDER BY e.created_at DESC
             LIMIT %d",
            $limit
        ));
    }

    /**
     * Calculate cost savings estimate
     */
    public function calculate_cost_savings() {
        global $wpdb;

        $api_table = $wpdb->prefix . 'nexus_ai_api_usage';

        // Get total tokens used in last 30 days
        $total_tokens = $wpdb->get_var(
            "SELECT SUM(tokens_input + tokens_output) FROM $api_table
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        // Estimate cost savings compared to human translation
        // Assuming human translation costs $0.10 per word and AI uses ~1.3 tokens per word
        $estimated_words = $total_tokens / 1.3;
        $human_cost_estimate = $estimated_words * 0.10;

        $ai_cost = $wpdb->get_var(
            "SELECT SUM(cost_estimate) FROM $api_table
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        $savings = $human_cost_estimate - $ai_cost;
        $savings_percentage = $human_cost_estimate > 0 ? ($savings / $human_cost_estimate) * 100 : 0;

        return array(
            'ai_cost' => floatval($ai_cost),
            'human_cost_estimate' => floatval($human_cost_estimate),
            'savings' => floatval($savings),
            'savings_percentage' => round($savings_percentage, 1),
            'words_translated' => round($estimated_words)
        );
    }
}
