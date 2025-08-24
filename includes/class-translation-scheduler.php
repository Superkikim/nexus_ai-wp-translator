<?php
/**
 * Translation Scheduler
 * 
 * Handles automatic translation scheduling with cron jobs and queue management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_Scheduler {
    
    private static $instance = null;
    private $db;
    private $translation_manager;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = Nexus_AI_WP_Translator_Database::get_instance();
        $this->translation_manager = Nexus_AI_WP_Translator_Manager::get_instance();

        $this->init_hooks();
        $this->init_cron_schedules();

        // Ensure queue table exists on initialization
        $this->ensure_queue_table_exists();

        // Ensure cron is scheduled
        $this->ensure_cron_scheduled();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Register cron hooks
        add_action('nexus_ai_wp_translator_process_queue', array($this, 'process_translation_queue'));
        add_action('nexus_ai_wp_translator_auto_translate', array($this, 'auto_translate_new_posts'));
        add_action('nexus_ai_wp_translator_cleanup_queue', array($this, 'cleanup_old_queue_items'));
        
        // Hook into post publishing
        add_action('transition_post_status', array($this, 'handle_post_publish'), 10, 3);
        
        // Admin hooks
        add_action('wp_ajax_nexus_ai_wp_add_to_queue', array($this, 'ajax_add_to_queue'));
        add_action('wp_ajax_nexus_ai_wp_remove_from_queue', array($this, 'ajax_remove_from_queue'));
        add_action('wp_ajax_nexus_ai_wp_get_queue_status', array($this, 'ajax_get_queue_status'));
        add_action('wp_ajax_nexus_ai_wp_pause_queue', array($this, 'ajax_pause_queue'));
        add_action('wp_ajax_nexus_ai_wp_resume_queue', array($this, 'ajax_resume_queue'));
        add_action('wp_ajax_nexus_ai_wp_retry_queue_item', array($this, 'ajax_retry_queue_item'));
        add_action('wp_ajax_nexus_ai_wp_process_queue_now', array($this, 'ajax_process_queue_now'));
        
        // Activation/deactivation hooks
        register_activation_hook(NEXUS_AI_WP_TRANSLATOR_PLUGIN_FILE, array($this, 'activate_scheduler'));
        register_deactivation_hook(NEXUS_AI_WP_TRANSLATOR_PLUGIN_FILE, array($this, 'deactivate_scheduler'));
    }
    
    /**
     * Initialize custom cron schedules
     */
    private function init_cron_schedules() {
        add_filter('cron_schedules', array($this, 'add_custom_cron_schedules'));
    }
    
    /**
     * Add custom cron schedules
     */
    public function add_custom_cron_schedules($schedules) {
        $schedules['every_5_minutes'] = array(
            'interval' => 300,
            'display' => __('Every 5 Minutes', 'nexus-ai-wp-translator')
        );
        
        $schedules['every_15_minutes'] = array(
            'interval' => 900,
            'display' => __('Every 15 Minutes', 'nexus-ai-wp-translator')
        );
        
        $schedules['every_30_minutes'] = array(
            'interval' => 1800,
            'display' => __('Every 30 Minutes', 'nexus-ai-wp-translator')
        );
        
        $schedules['every_2_hours'] = array(
            'interval' => 7200,
            'display' => __('Every 2 Hours', 'nexus-ai-wp-translator')
        );
        
        return $schedules;
    }
    
    /**
     * Activate scheduler
     */
    public function activate_scheduler() {
        // Schedule queue processing
        if (!wp_next_scheduled('nexus_ai_wp_translator_process_queue')) {
            wp_schedule_event(time(), 'every_5_minutes', 'nexus_ai_wp_translator_process_queue');
        }
        
        // Schedule cleanup
        if (!wp_next_scheduled('nexus_ai_wp_translator_cleanup_queue')) {
            wp_schedule_event(time(), 'daily', 'nexus_ai_wp_translator_cleanup_queue');
        }
        
        // Schedule auto-translation if enabled
        $auto_translate_enabled = get_option('nexus_ai_wp_translator_auto_translate_enabled', false);
        if ($auto_translate_enabled) {
            $this->schedule_auto_translation();
        }
        
        // Create queue table
        $this->create_queue_table();
    }
    
    /**
     * Deactivate scheduler
     */
    public function deactivate_scheduler() {
        wp_clear_scheduled_hook('nexus_ai_wp_translator_process_queue');
        wp_clear_scheduled_hook('nexus_ai_wp_translator_auto_translate');
        wp_clear_scheduled_hook('nexus_ai_wp_translator_cleanup_queue');
    }
    
    /**
     * Create queue table
     */
    private function create_queue_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nexus_ai_translation_queue';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            target_languages text NOT NULL,
            priority int(11) DEFAULT 5,
            status varchar(20) DEFAULT 'pending',
            scheduled_time datetime DEFAULT NULL,
            attempts int(11) DEFAULT 0,
            max_attempts int(11) DEFAULT 3,
            error_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY scheduled_time (scheduled_time),
            KEY priority (priority)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Ensure queue table exists
     */
    private function ensure_queue_table_exists() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nexus_ai_translation_queue';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

        if (!$table_exists) {
            $this->create_queue_table();
        }
    }

    /**
     * Ensure cron is scheduled
     */
    private function ensure_cron_scheduled() {
        // Schedule queue processing if not already scheduled
        if (!wp_next_scheduled('nexus_ai_wp_translator_process_queue')) {
            wp_schedule_event(time(), 'every_5_minutes', 'nexus_ai_wp_translator_process_queue');
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: Queue processing cron scheduled');
            }
        }

        // Schedule cleanup if not already scheduled
        if (!wp_next_scheduled('nexus_ai_wp_translator_cleanup_queue')) {
            wp_schedule_event(time(), 'daily', 'nexus_ai_wp_translator_cleanup_queue');
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: Queue cleanup cron scheduled');
            }
        }

        // Ensure queue is not paused by default
        if (get_option('nexus_ai_wp_translator_queue_paused') === false) {
            // Option doesn't exist, set it to false explicitly
            update_option('nexus_ai_wp_translator_queue_paused', false);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: Queue unpaused by default');
            }
        }
    }
    
    /**
     * Add post to translation queue
     */
    public function add_to_queue($post_id, $target_languages, $options = array()) {
        global $wpdb;
        
        $defaults = array(
            'priority' => 5,
            'scheduled_time' => null,
            'max_attempts' => 3
        );
        
        $options = wp_parse_args($options, $defaults);
        
        // Convert languages array to JSON
        $languages_json = wp_json_encode($target_languages);
        
        $table_name = $wpdb->prefix . 'nexus_ai_translation_queue';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'target_languages' => $languages_json,
                'priority' => $options['priority'],
                'scheduled_time' => $options['scheduled_time'],
                'max_attempts' => $options['max_attempts'],
                'status' => 'pending'
            ),
            array('%d', '%s', '%d', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('Failed to add to queue', 'nexus-ai-wp-translator')
            );
        }
        
        return array(
            'success' => true,
            'queue_id' => $wpdb->insert_id,
            'message' => __('Added to translation queue', 'nexus-ai-wp-translator')
        );
    }
    
    /**
     * Remove post from queue
     */
    public function remove_from_queue($queue_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nexus_ai_translation_queue';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $queue_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get queue items
     */
    public function get_queue_items($status = null, $limit = 50) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nexus_ai_translation_queue';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            return array(); // Return empty array if table doesn't exist
        }

        $where = '';
        if ($status) {
            $where = $wpdb->prepare(' WHERE status = %s', $status);
        }

        $sql = "SELECT q.*, p.post_title, p.post_type
                FROM $table_name q
                LEFT JOIN {$wpdb->posts} p ON q.post_id = p.ID
                $where
                ORDER BY q.priority DESC, q.created_at ASC
                LIMIT %d";

        $results = $wpdb->get_results($wpdb->prepare($sql, $limit));

        // Return empty array if query failed
        return $results ? $results : array();
    }
    
    /**
     * Process translation queue
     */
    public function process_translation_queue() {
        // Check if queue processing is paused
        if (get_option('nexus_ai_wp_translator_queue_paused', false)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: Queue processing is paused');
            }
            return;
        }

        // Get pending items
        $queue_items = $this->get_queue_items('pending', 5); // Process 5 at a time

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Nexus AI WP Translator: Processing ' . count($queue_items) . ' queue items');
        }

        foreach ($queue_items as $item) {
            // Check if scheduled time has passed
            if ($item->scheduled_time && strtotime($item->scheduled_time) > time()) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Nexus AI WP Translator: Skipping queue item ' . $item->id . ' - scheduled for future');
                }
                continue;
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nexus AI WP Translator: Processing queue item ' . $item->id);
            }
            $this->process_queue_item($item);
        }
    }
    
    /**
     * Process individual queue item
     */
    private function process_queue_item($item) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nexus_ai_translation_queue';
        
        // Update status to processing
        $wpdb->update(
            $table_name,
            array('status' => 'processing'),
            array('id' => $item->id),
            array('%s'),
            array('%d')
        );
        
        try {
            // Decode target languages
            $target_languages = json_decode($item->target_languages, true);
            
            if (!$target_languages) {
                throw new Exception(__('Invalid target languages', 'nexus-ai-wp-translator'));
            }
            
            // Perform translation
            $result = $this->translation_manager->translate_post($item->post_id, $target_languages);
            
            if ($result['success']) {
                // Mark as completed
                $wpdb->update(
                    $table_name,
                    array(
                        'status' => 'completed',
                        'error_message' => null
                    ),
                    array('id' => $item->id),
                    array('%s', '%s'),
                    array('%d')
                );
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Nexus AI WP Translator: Queue item {$item->id} completed successfully");
                }
            } else {
                throw new Exception($result['message']);
            }
            
        } catch (Exception $e) {
            // Increment attempts
            $attempts = $item->attempts + 1;
            
            if ($attempts >= $item->max_attempts) {
                // Mark as failed
                $wpdb->update(
                    $table_name,
                    array(
                        'status' => 'failed',
                        'attempts' => $attempts,
                        'error_message' => $e->getMessage()
                    ),
                    array('id' => $item->id),
                    array('%s', '%d', '%s'),
                    array('%d')
                );
            } else {
                // Retry later
                $retry_time = date('Y-m-d H:i:s', time() + (300 * $attempts)); // Exponential backoff
                
                $wpdb->update(
                    $table_name,
                    array(
                        'status' => 'pending',
                        'attempts' => $attempts,
                        'scheduled_time' => $retry_time,
                        'error_message' => $e->getMessage()
                    ),
                    array('id' => $item->id),
                    array('%s', '%d', '%s', '%s'),
                    array('%d')
                );
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Nexus AI WP Translator: Queue item {$item->id} failed: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Handle post publish for auto-translation
     */
    public function handle_post_publish($new_status, $old_status, $post) {
        // Only process when post is published
        if ($new_status !== 'publish' || $old_status === 'publish') {
            return;
        }
        
        // Check if auto-translation is enabled
        $auto_translate_enabled = get_option('nexus_ai_wp_translator_auto_translate_enabled', false);
        if (!$auto_translate_enabled) {
            return;
        }
        
        // Check if post type is enabled for auto-translation
        $enabled_post_types = get_option('nexus_ai_wp_translator_auto_translate_post_types', array('post'));
        if (!in_array($post->post_type, $enabled_post_types)) {
            return;
        }
        
        // Get target languages
        $target_languages = get_option('nexus_ai_wp_translator_target_languages', array('es', 'fr', 'de'));
        
        // Add to queue with high priority
        $this->add_to_queue($post->ID, $target_languages, array(
            'priority' => 8, // High priority for new posts
            'scheduled_time' => null // Immediate processing
        ));
    }
    
    /**
     * Auto-translate new posts (cron job)
     */
    public function auto_translate_new_posts() {
        // This method can be used for batch processing of older posts
        // or for scheduled translation of specific content
        
        $auto_translate_enabled = get_option('nexus_ai_wp_translator_auto_translate_enabled', false);
        if (!$auto_translate_enabled) {
            return;
        }
        
        // Get posts that need translation
        $posts_to_translate = $this->get_posts_needing_translation();
        
        foreach ($posts_to_translate as $post) {
            $target_languages = get_option('nexus_ai_wp_translator_target_languages', array('es', 'fr', 'de'));
            
            $this->add_to_queue($post->ID, $target_languages, array(
                'priority' => 3, // Lower priority for batch processing
                'scheduled_time' => null
            ));
        }
    }
    
    /**
     * Get posts that need translation
     */
    private function get_posts_needing_translation() {
        global $wpdb;
        
        $enabled_post_types = get_option('nexus_ai_wp_translator_auto_translate_post_types', array('post'));
        $post_types_placeholder = implode(',', array_fill(0, count($enabled_post_types), '%s'));
        
        // Get posts that don't have translations yet
        $sql = "SELECT p.* FROM {$wpdb->posts} p 
                LEFT JOIN {$wpdb->prefix}nexus_ai_translation_relationships r ON p.ID = r.source_post_id 
                WHERE p.post_status = 'publish' 
                AND p.post_type IN ($post_types_placeholder)
                AND r.source_post_id IS NULL 
                AND p.post_date > DATE_SUB(NOW(), INTERVAL 7 DAY)
                LIMIT 10";
        
        return $wpdb->get_results($wpdb->prepare($sql, ...$enabled_post_types));
    }
    
    /**
     * Cleanup old queue items
     */
    public function cleanup_old_queue_items() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nexus_ai_translation_queue';
        
        // Remove completed items older than 7 days
        $wpdb->query(
            "DELETE FROM $table_name 
             WHERE status = 'completed' 
             AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        // Remove failed items older than 30 days
        $wpdb->query(
            "DELETE FROM $table_name 
             WHERE status = 'failed' 
             AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
    }
    
    /**
     * Schedule auto-translation
     */
    public function schedule_auto_translation() {
        $schedule = get_option('nexus_ai_wp_translator_auto_translate_schedule', 'hourly');
        
        if (!wp_next_scheduled('nexus_ai_wp_translator_auto_translate')) {
            wp_schedule_event(time(), $schedule, 'nexus_ai_wp_translator_auto_translate');
        }
    }
    
    /**
     * Unschedule auto-translation
     */
    public function unschedule_auto_translation() {
        wp_clear_scheduled_hook('nexus_ai_wp_translator_auto_translate');
    }

    /**
     * AJAX: Add to queue
     */
    public function ajax_add_to_queue() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $post_id = intval($_POST['post_id']);
        $target_languages = array_map('sanitize_text_field', $_POST['target_languages']);
        $priority = intval($_POST['priority']) ?: 5;
        $scheduled_time = sanitize_text_field($_POST['scheduled_time']) ?: null;

        if (!$post_id || empty($target_languages)) {
            wp_send_json_error(__('Invalid parameters', 'nexus-ai-wp-translator'));
        }

        $result = $this->add_to_queue($post_id, $target_languages, array(
            'priority' => $priority,
            'scheduled_time' => $scheduled_time
        ));

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX: Remove from queue
     */
    public function ajax_remove_from_queue() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $queue_id = intval($_POST['queue_id']);

        if (!$queue_id) {
            wp_send_json_error(__('Invalid queue ID', 'nexus-ai-wp-translator'));
        }

        $result = $this->remove_from_queue($queue_id);

        if ($result) {
            wp_send_json_success(__('Removed from queue', 'nexus-ai-wp-translator'));
        } else {
            wp_send_json_error(__('Failed to remove from queue', 'nexus-ai-wp-translator'));
        }
    }

    /**
     * AJAX: Get queue status
     */
    public function ajax_get_queue_status() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        // Ensure queue table exists
        $this->ensure_queue_table_exists();

        $status = sanitize_text_field($_POST['status']) ?: null;
        $limit = intval($_POST['limit']) ?: 50;

        $queue_items = $this->get_queue_items($status, $limit);

        // Get queue statistics
        $stats = $this->get_queue_statistics();

        wp_send_json_success(array(
            'items' => $queue_items,
            'statistics' => $stats
        ));
    }

    /**
     * AJAX: Pause queue
     */
    public function ajax_pause_queue() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        update_option('nexus_ai_wp_translator_queue_paused', true);

        wp_send_json_success(__('Queue paused', 'nexus-ai-wp-translator'));
    }

    /**
     * AJAX: Resume queue
     */
    public function ajax_resume_queue() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        update_option('nexus_ai_wp_translator_queue_paused', false);

        wp_send_json_success(__('Queue resumed', 'nexus-ai-wp-translator'));
    }

    /**
     * AJAX: Process queue now (manual trigger)
     */
    public function ajax_process_queue_now() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        // Process the queue immediately
        $this->process_translation_queue();

        wp_send_json_success(__('Queue processing triggered', 'nexus-ai-wp-translator'));
    }

    /**
     * AJAX: Retry queue item
     */
    public function ajax_retry_queue_item() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $queue_id = intval($_POST['queue_id']);

        if (!$queue_id) {
            wp_send_json_error(__('Invalid queue ID', 'nexus-ai-wp-translator'));
        }

        $result = $this->reschedule_queue_item($queue_id, null);

        if ($result) {
            wp_send_json_success(__('Item scheduled for retry', 'nexus-ai-wp-translator'));
        } else {
            wp_send_json_error(__('Failed to retry item', 'nexus-ai-wp-translator'));
        }
    }

    /**
     * Get queue statistics
     */
    public function get_queue_statistics() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nexus_ai_translation_queue';

        $stats = array(
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
            'total' => 0
        );

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            $stats['queue_paused'] = get_option('nexus_ai_wp_translator_queue_paused', false);
            return $stats; // Return default stats if table doesn't exist
        }

        $results = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM $table_name GROUP BY status"
        );

        if ($results) {
            foreach ($results as $result) {
                $stats[$result->status] = intval($result->count);
                $stats['total'] += intval($result->count);
            }
        }

        // Get next scheduled item
        $next_scheduled = $wpdb->get_var(
            "SELECT MIN(scheduled_time) FROM $table_name
             WHERE status = 'pending' AND scheduled_time > NOW()"
        );

        $stats['next_scheduled'] = $next_scheduled;
        $stats['queue_paused'] = get_option('nexus_ai_wp_translator_queue_paused', false);

        return $stats;
    }

    /**
     * Get queue item by ID
     */
    public function get_queue_item($queue_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nexus_ai_translation_queue';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT q.*, p.post_title, p.post_type
             FROM $table_name q
             LEFT JOIN {$wpdb->posts} p ON q.post_id = p.ID
             WHERE q.id = %d",
            $queue_id
        ));
    }

    /**
     * Update queue item priority
     */
    public function update_queue_priority($queue_id, $priority) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nexus_ai_translation_queue';

        return $wpdb->update(
            $table_name,
            array('priority' => $priority),
            array('id' => $queue_id),
            array('%d'),
            array('%d')
        );
    }

    /**
     * Reschedule queue item
     */
    public function reschedule_queue_item($queue_id, $scheduled_time) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nexus_ai_translation_queue';

        return $wpdb->update(
            $table_name,
            array(
                'scheduled_time' => $scheduled_time,
                'status' => 'pending',
                'attempts' => 0,
                'error_message' => null
            ),
            array('id' => $queue_id),
            array('%s', '%s', '%d', '%s'),
            array('%d')
        );
    }
}
