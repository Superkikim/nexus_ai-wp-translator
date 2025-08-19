<?php
/**
 * Database handler for Nexus AI WP Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_Database {
    
    private static $instance = null;
    private $wpdb;
    
    // Table names
    public $translations_table;
    public $logs_table;
    public $preferences_table;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Set table names
        $this->translations_table = $wpdb->prefix . 'nexus_ai_wp_translations';
        $this->logs_table = $wpdb->prefix . 'nexus_ai_wp_translation_logs';
        $this->preferences_table = $wpdb->prefix . 'nexus_ai_wp_user_preferences';
    }
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $instance = self::get_instance();
        
        // Translations table
        $sql_translations = "CREATE TABLE {$instance->translations_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_post_id bigint(20) NOT NULL,
            translated_post_id bigint(20) NOT NULL,
            source_language varchar(10) NOT NULL,
            target_language varchar(10) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY source_post_id (source_post_id),
            KEY translated_post_id (translated_post_id),
            KEY source_language (source_language),
            KEY target_language (target_language)
        ) $charset_collate;";
        
        // Logs table
        $sql_logs = "CREATE TABLE {$instance->logs_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            message text,
            api_calls_count int(11) DEFAULT 0,
            processing_time float DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // User preferences table
        $sql_preferences = "CREATE TABLE {$instance->preferences_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            preferred_language varchar(10) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_translations);
        dbDelta($sql_logs);
        dbDelta($sql_preferences);
    }
    
    /**
     * Store translation relationship
     */
    public function store_translation_relationship($source_post_id, $translated_post_id, $source_lang, $target_lang, $status = 'completed') {
        return $this->wpdb->insert(
            $this->translations_table,
            array(
                'source_post_id' => $source_post_id,
                'translated_post_id' => $translated_post_id,
                'source_language' => $source_lang,
                'target_language' => $target_lang,
                'status' => $status
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get translation relationships for a post
     */
    public function get_post_translations($post_id) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->translations_table} 
                WHERE source_post_id = %d OR translated_post_id = %d",
                $post_id, $post_id
            )
        );
    }
    
    /**
     * Get translated post by language
     */
    public function get_translated_post($source_post_id, $target_language) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->translations_table} 
                WHERE source_post_id = %d AND target_language = %s",
                $source_post_id, $target_language
            )
        );
    }
    
    /**
     * Log translation activity
     */
    public function log_translation_activity($post_id, $action, $status, $message = '', $api_calls = 0, $processing_time = 0) {
        // Ensure api_calls_count is at least 1 if we're logging a successful translation
        if ($action === 'translate' && $status === 'success' && $api_calls === 0) {
            $api_calls = 1;
        }
        
        return $this->wpdb->insert(
            $this->logs_table,
            array(
                'post_id' => $post_id,
                'action' => $action,
                'status' => $status,
                'message' => $message,
                'api_calls_count' => $api_calls,
                'processing_time' => $processing_time
            ),
            array('%d', '%s', '%s', '%s', '%d', '%f')
        );
    }
    
    /**
     * Log invalid AI response for debugging
     */
    public function log_invalid_ai_response($post_id, $original_content, $ai_response, $issues, $target_lang) {
        $message = sprintf(
            'Invalid AI response filtered for %s translation. Issues: %s. Original: %s... Response: %s...',
            $target_lang,
            implode(', ', $issues),
            substr($original_content, 0, 50),
            substr($ai_response, 0, 100)
        );
        
        return $this->log_translation_activity(
            $post_id, 
            'invalid_response_filtered', 
            'warning', 
            $message, 
            0,  // No API calls consumed for invalid responses
            0
        );
    }
    
    /**
     * Log empty content skipped
     */
    public function log_empty_content_skipped($post_id, $content_type, $reason = '') {
        $message = sprintf(
            'Skipped empty %s content - no API call made. %s',
            $content_type,
            $reason
        );
        
        return $this->log_translation_activity(
            $post_id, 
            'empty_content_skipped', 
            'info', 
            $message, 
            0,  // No API calls for empty content
            0
        );
    }
    
    /**
     * Get translation logs
     */
    public function get_translation_logs($limit = 50, $offset = 0) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT l.*, p.post_title 
                FROM {$this->logs_table} l 
                LEFT JOIN {$this->wpdb->posts} p ON l.post_id = p.ID 
                ORDER BY l.created_at DESC 
                LIMIT %d OFFSET %d",
                $limit, $offset
            )
        );
    }
    
    /**
     * Get translation statistics
     */
    public function get_translation_stats($period = '7 days') {
        $stats = array();
        
        // Total translations
        $stats['total'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->translations_table}"
        );
        
        // Handle period parameter properly
        $interval_clause = '';
        switch ($period) {
            case '1 day':
                $interval_clause = 'INTERVAL 1 DAY';
                break;
            case '7 days':
                $interval_clause = 'INTERVAL 7 DAY';
                break;
            case '30 days':
                $interval_clause = 'INTERVAL 30 DAY';
                break;
            default:
                $interval_clause = 'INTERVAL 7 DAY';
        }
        
        // Recent translations
        $stats['recent'] = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->translations_table} 
            WHERE created_at >= DATE_SUB(NOW(), {$interval_clause})"
        );
        
        // Success rate
        $stats['success_rate'] = (float) $this->wpdb->get_var(
            "SELECT COALESCE(ROUND(
                (COUNT(CASE WHEN status = 'success' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0)), 2
            ), 0) FROM {$this->logs_table} 
            WHERE created_at >= DATE_SUB(NOW(), {$interval_clause})"
        );
        
        // API calls
        $stats['api_calls'] = (int) $this->wpdb->get_var(
            "SELECT COALESCE(SUM(api_calls_count), 0) FROM {$this->logs_table} 
            WHERE created_at >= DATE_SUB(NOW(), {$interval_clause})"
        );
        
        // Ensure all values are properly typed
        $stats['total'] = (int) $stats['total'];
        
        return $stats;
    }
    
    /**
     * Store user language preference
     */
    public function store_user_preference($user_id, $language) {
        return $this->wpdb->replace(
            $this->preferences_table,
            array(
                'user_id' => $user_id,
                'preferred_language' => $language
            ),
            array('%d', '%s')
        );
    }
    
    /**
     * Get user language preference
     */
    public function get_user_preference($user_id) {
        return $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT preferred_language FROM {$this->preferences_table} WHERE user_id = %d",
                $user_id
            )
        );
    }
    
    /**
     * Delete translation relationships
     */
    public function delete_translation_relationships($post_id, $target_language = null) {
        if ($target_language) {
            // Delete specific language translation
            return $this->wpdb->delete(
                $this->translations_table,
                array(
                    'source_post_id' => $post_id,
                    'target_language' => $target_language
                ),
                array('%d', '%s')
            );
        } else {
            // Delete all translations for this post
            return $this->wpdb->delete(
                $this->translations_table,
                array('source_post_id' => $post_id),
                array('%d')
            ) && $this->wpdb->delete(
                $this->translations_table,
                array('translated_post_id' => $post_id),
                array('%d')
            );
        }
    }
    
    /**
     * Get throttle status
     */
    public function get_throttle_status($period_minutes = 60) {
        return $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->logs_table} 
                WHERE action = 'translate' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL %d MINUTE)",
                $period_minutes
            )
        );
    }
}
