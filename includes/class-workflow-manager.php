<?php
/**
 * Translation Workflow Manager
 * 
 * Handles team collaboration, approval workflows, and translation review processes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_Workflow_Manager {
    
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
        // Workflow hooks
        add_action('nexus_ai_wp_translator_translation_completed', array($this, 'handle_translation_completed'), 10, 3);
        add_action('nexus_ai_wp_translator_workflow_status_changed', array($this, 'handle_workflow_status_change'), 10, 4);
        
        // AJAX handlers
        add_action('wp_ajax_nexus_ai_wp_submit_for_review', array($this, 'ajax_submit_for_review'));
        add_action('wp_ajax_nexus_ai_wp_approve_translation', array($this, 'ajax_approve_translation'));
        add_action('wp_ajax_nexus_ai_wp_reject_translation', array($this, 'ajax_reject_translation'));
        add_action('wp_ajax_nexus_ai_wp_get_workflow_items', array($this, 'ajax_get_workflow_items'));
        add_action('wp_ajax_nexus_ai_wp_assign_reviewer', array($this, 'ajax_assign_reviewer'));
        add_action('wp_ajax_nexus_ai_wp_add_workflow_comment', array($this, 'ajax_add_workflow_comment'));
        
        // User role and capability hooks
        add_action('init', array($this, 'add_workflow_capabilities'));
        
        // Notification hooks
        add_action('nexus_ai_wp_translator_workflow_notification', array($this, 'send_workflow_notification'), 10, 3);
        
        // Create tables on activation
        add_action('nexus_ai_wp_translator_activate', array($this, 'create_workflow_tables'));
    }
    
    /**
     * Create workflow tables
     */
    public function create_workflow_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Workflow items table
        $workflow_table = $wpdb->prefix . 'nexus_ai_translation_workflow';
        $workflow_sql = "CREATE TABLE $workflow_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            translated_post_id bigint(20) NOT NULL,
            source_language varchar(10) NOT NULL,
            target_language varchar(10) NOT NULL,
            status varchar(20) DEFAULT 'draft',
            priority int(11) DEFAULT 5,
            assigned_reviewer bigint(20) DEFAULT NULL,
            submitted_by bigint(20) NOT NULL,
            submitted_at datetime DEFAULT NULL,
            reviewed_by bigint(20) DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            due_date datetime DEFAULT NULL,
            workflow_type varchar(50) DEFAULT 'standard',
            metadata text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY translated_post_id (translated_post_id),
            KEY status (status),
            KEY assigned_reviewer (assigned_reviewer),
            KEY submitted_by (submitted_by),
            KEY due_date (due_date)
        ) $charset_collate;";
        
        // Workflow comments table
        $comments_table = $wpdb->prefix . 'nexus_ai_translation_workflow_comments';
        $comments_sql = "CREATE TABLE $comments_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            workflow_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            comment_type varchar(20) DEFAULT 'comment',
            comment_text text NOT NULL,
            metadata text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY workflow_id (workflow_id),
            KEY user_id (user_id),
            KEY comment_type (comment_type),
            FOREIGN KEY (workflow_id) REFERENCES $workflow_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Workflow history table
        $history_table = $wpdb->prefix . 'nexus_ai_translation_workflow_history';
        $history_sql = "CREATE TABLE $history_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            workflow_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            old_status varchar(20) DEFAULT NULL,
            new_status varchar(20) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY workflow_id (workflow_id),
            KEY user_id (user_id),
            KEY action (action),
            FOREIGN KEY (workflow_id) REFERENCES $workflow_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($workflow_sql);
        dbDelta($comments_sql);
        dbDelta($history_sql);
    }
    
    /**
     * Add workflow capabilities to user roles
     */
    public function add_workflow_capabilities() {
        // Add capabilities to administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_translation_workflow');
            $admin_role->add_cap('review_translations');
            $admin_role->add_cap('approve_translations');
            $admin_role->add_cap('assign_translation_reviewers');
        }
        
        // Add capabilities to editor
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('review_translations');
            $editor_role->add_cap('approve_translations');
        }
        
        // Create translation reviewer role
        if (!get_role('translation_reviewer')) {
            add_role('translation_reviewer', __('Translation Reviewer', 'nexus-ai-wp-translator'), array(
                'read' => true,
                'review_translations' => true,
                'edit_posts' => true,
                'edit_others_posts' => true
            ));
        }
    }
    
    /**
     * Handle translation completion
     */
    public function handle_translation_completed($post_id, $target_language, $translation_data) {
        $workflow_enabled = get_option('nexus_ai_wp_translator_workflow_enabled', false);
        
        if (!$workflow_enabled) {
            return;
        }
        
        $translated_post_id = isset($translation_data['translated_post_id']) ? $translation_data['translated_post_id'] : null;
        
        if (!$translated_post_id) {
            return;
        }
        
        // Create workflow item
        $workflow_id = $this->create_workflow_item($post_id, $translated_post_id, $target_language);
        
        if ($workflow_id) {
            // Auto-assign reviewer if configured
            $auto_assign_reviewer = get_option('nexus_ai_wp_translator_auto_assign_reviewer', false);
            
            if ($auto_assign_reviewer) {
                $reviewer_id = $this->get_next_available_reviewer($target_language);
                if ($reviewer_id) {
                    $this->assign_reviewer($workflow_id, $reviewer_id);
                }
            }
            
            // Send notification
            do_action('nexus_ai_wp_translator_workflow_notification', $workflow_id, 'translation_ready', array(
                'post_id' => $post_id,
                'translated_post_id' => $translated_post_id,
                'target_language' => $target_language
            ));
        }
    }
    
    /**
     * Create workflow item
     */
    public function create_workflow_item($post_id, $translated_post_id, $target_language, $options = array()) {
        global $wpdb;
        
        $workflow_table = $wpdb->prefix . 'nexus_ai_translation_workflow';
        
        $source_language = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');
        
        $defaults = array(
            'status' => 'draft',
            'priority' => 5,
            'workflow_type' => 'standard',
            'due_date' => null,
            'metadata' => null
        );
        
        $options = wp_parse_args($options, $defaults);
        
        $result = $wpdb->insert(
            $workflow_table,
            array(
                'post_id' => $post_id,
                'translated_post_id' => $translated_post_id,
                'source_language' => $source_language,
                'target_language' => $target_language,
                'status' => $options['status'],
                'priority' => $options['priority'],
                'submitted_by' => get_current_user_id(),
                'workflow_type' => $options['workflow_type'],
                'due_date' => $options['due_date'],
                'metadata' => $options['metadata']
            ),
            array('%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        $workflow_id = $wpdb->insert_id;
        
        // Log workflow creation
        $this->log_workflow_action($workflow_id, 'created', null, $options['status']);
        
        return $workflow_id;
    }
    
    /**
     * Submit translation for review
     */
    public function submit_for_review($workflow_id, $notes = '') {
        global $wpdb;
        
        $workflow_table = $wpdb->prefix . 'nexus_ai_translation_workflow';
        
        $workflow = $this->get_workflow_item($workflow_id);
        
        if (!$workflow || $workflow->status !== 'draft') {
            return false;
        }
        
        $result = $wpdb->update(
            $workflow_table,
            array(
                'status' => 'pending_review',
                'submitted_at' => current_time('mysql')
            ),
            array('id' => $workflow_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Log action
            $this->log_workflow_action($workflow_id, 'submitted_for_review', 'draft', 'pending_review', $notes);
            
            // Add comment if provided
            if (!empty($notes)) {
                $this->add_workflow_comment($workflow_id, 'submission_note', $notes);
            }
            
            // Send notification
            do_action('nexus_ai_wp_translator_workflow_notification', $workflow_id, 'submitted_for_review', array(
                'notes' => $notes
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Approve translation
     */
    public function approve_translation($workflow_id, $notes = '') {
        global $wpdb;
        
        if (!current_user_can('approve_translations')) {
            return false;
        }
        
        $workflow_table = $wpdb->prefix . 'nexus_ai_translation_workflow';
        
        $workflow = $this->get_workflow_item($workflow_id);
        
        if (!$workflow || !in_array($workflow->status, array('pending_review', 'in_review'))) {
            return false;
        }
        
        $result = $wpdb->update(
            $workflow_table,
            array(
                'status' => 'approved',
                'reviewed_by' => get_current_user_id(),
                'reviewed_at' => current_time('mysql')
            ),
            array('id' => $workflow_id),
            array('%s', '%d', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Publish the translated post
            wp_update_post(array(
                'ID' => $workflow->translated_post_id,
                'post_status' => 'publish'
            ));
            
            // Log action
            $this->log_workflow_action($workflow_id, 'approved', $workflow->status, 'approved', $notes);
            
            // Add comment if provided
            if (!empty($notes)) {
                $this->add_workflow_comment($workflow_id, 'approval_note', $notes);
            }
            
            // Send notification
            do_action('nexus_ai_wp_translator_workflow_notification', $workflow_id, 'approved', array(
                'notes' => $notes
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Reject translation
     */
    public function reject_translation($workflow_id, $reason = '') {
        global $wpdb;
        
        if (!current_user_can('review_translations')) {
            return false;
        }
        
        $workflow_table = $wpdb->prefix . 'nexus_ai_translation_workflow';
        
        $workflow = $this->get_workflow_item($workflow_id);
        
        if (!$workflow || !in_array($workflow->status, array('pending_review', 'in_review'))) {
            return false;
        }
        
        $result = $wpdb->update(
            $workflow_table,
            array(
                'status' => 'rejected',
                'reviewed_by' => get_current_user_id(),
                'reviewed_at' => current_time('mysql')
            ),
            array('id' => $workflow_id),
            array('%s', '%d', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Log action
            $this->log_workflow_action($workflow_id, 'rejected', $workflow->status, 'rejected', $reason);
            
            // Add comment with rejection reason
            if (!empty($reason)) {
                $this->add_workflow_comment($workflow_id, 'rejection_reason', $reason);
            }
            
            // Send notification
            do_action('nexus_ai_wp_translator_workflow_notification', $workflow_id, 'rejected', array(
                'reason' => $reason
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Assign reviewer to workflow item
     */
    public function assign_reviewer($workflow_id, $reviewer_id) {
        global $wpdb;
        
        if (!current_user_can('assign_translation_reviewers')) {
            return false;
        }
        
        $workflow_table = $wpdb->prefix . 'nexus_ai_translation_workflow';
        
        $result = $wpdb->update(
            $workflow_table,
            array('assigned_reviewer' => $reviewer_id),
            array('id' => $workflow_id),
            array('%d'),
            array('%d')
        );
        
        if ($result !== false) {
            // Log action
            $reviewer = get_user_by('id', $reviewer_id);
            $this->log_workflow_action($workflow_id, 'reviewer_assigned', null, null, 'Assigned to: ' . $reviewer->display_name);
            
            // Send notification
            do_action('nexus_ai_wp_translator_workflow_notification', $workflow_id, 'reviewer_assigned', array(
                'reviewer_id' => $reviewer_id
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Add workflow comment
     */
    public function add_workflow_comment($workflow_id, $comment_type, $comment_text, $metadata = null) {
        global $wpdb;
        
        $comments_table = $wpdb->prefix . 'nexus_ai_translation_workflow_comments';
        
        $result = $wpdb->insert(
            $comments_table,
            array(
                'workflow_id' => $workflow_id,
                'user_id' => get_current_user_id(),
                'comment_type' => $comment_type,
                'comment_text' => $comment_text,
                'metadata' => $metadata
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
        
        return $result !== false ? $wpdb->insert_id : false;
    }
    
    /**
     * Log workflow action
     */
    private function log_workflow_action($workflow_id, $action, $old_status = null, $new_status = null, $notes = null) {
        global $wpdb;
        
        $history_table = $wpdb->prefix . 'nexus_ai_translation_workflow_history';
        
        $wpdb->insert(
            $history_table,
            array(
                'workflow_id' => $workflow_id,
                'user_id' => get_current_user_id(),
                'action' => $action,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'notes' => $notes
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get workflow item
     */
    public function get_workflow_item($workflow_id) {
        global $wpdb;
        
        $workflow_table = $wpdb->prefix . 'nexus_ai_translation_workflow';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT w.*, 
                    p.post_title as source_title,
                    tp.post_title as translated_title,
                    u1.display_name as submitted_by_name,
                    u2.display_name as reviewer_name
             FROM $workflow_table w
             LEFT JOIN {$wpdb->posts} p ON w.post_id = p.ID
             LEFT JOIN {$wpdb->posts} tp ON w.translated_post_id = tp.ID
             LEFT JOIN {$wpdb->users} u1 ON w.submitted_by = u1.ID
             LEFT JOIN {$wpdb->users} u2 ON w.assigned_reviewer = u2.ID
             WHERE w.id = %d",
            $workflow_id
        ));
    }

    /**
     * Get workflow items with filters
     */
    public function get_workflow_items($filters = array(), $limit = 50, $offset = 0) {
        global $wpdb;

        $workflow_table = $wpdb->prefix . 'nexus_ai_translation_workflow';

        $where_conditions = array();
        $where_values = array();

        if (isset($filters['status'])) {
            $where_conditions[] = "w.status = %s";
            $where_values[] = $filters['status'];
        }

        if (isset($filters['assigned_reviewer'])) {
            $where_conditions[] = "w.assigned_reviewer = %d";
            $where_values[] = $filters['assigned_reviewer'];
        }

        if (isset($filters['target_language'])) {
            $where_conditions[] = "w.target_language = %s";
            $where_values[] = $filters['target_language'];
        }

        if (isset($filters['due_date_before'])) {
            $where_conditions[] = "w.due_date <= %s";
            $where_values[] = $filters['due_date_before'];
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        $where_values[] = $limit;
        $where_values[] = $offset;

        $sql = "SELECT w.*,
                       p.post_title as source_title,
                       tp.post_title as translated_title,
                       u1.display_name as submitted_by_name,
                       u2.display_name as reviewer_name
                FROM $workflow_table w
                LEFT JOIN {$wpdb->posts} p ON w.post_id = p.ID
                LEFT JOIN {$wpdb->posts} tp ON w.translated_post_id = tp.ID
                LEFT JOIN {$wpdb->users} u1 ON w.submitted_by = u1.ID
                LEFT JOIN {$wpdb->users} u2 ON w.assigned_reviewer = u2.ID
                $where_clause
                ORDER BY w.priority DESC, w.created_at ASC
                LIMIT %d OFFSET %d";

        return $wpdb->get_results($wpdb->prepare($sql, ...$where_values));
    }

    /**
     * Get next available reviewer
     */
    private function get_next_available_reviewer($target_language = null) {
        // Get users with review capabilities
        $reviewers = get_users(array(
            'capability' => 'review_translations',
            'fields' => 'ID'
        ));

        if (empty($reviewers)) {
            return null;
        }

        // Simple round-robin assignment
        $reviewer_workloads = array();

        foreach ($reviewers as $reviewer_id) {
            $workload = $this->get_reviewer_workload($reviewer_id);
            $reviewer_workloads[$reviewer_id] = $workload;
        }

        // Return reviewer with lowest workload
        asort($reviewer_workloads);
        return array_key_first($reviewer_workloads);
    }

    /**
     * Get reviewer workload
     */
    private function get_reviewer_workload($reviewer_id) {
        global $wpdb;

        $workflow_table = $wpdb->prefix . 'nexus_ai_translation_workflow';

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $workflow_table
             WHERE assigned_reviewer = %d
             AND status IN ('pending_review', 'in_review')",
            $reviewer_id
        ));
    }

    /**
     * Get workflow statistics
     */
    public function get_workflow_statistics() {
        global $wpdb;

        $workflow_table = $wpdb->prefix . 'nexus_ai_translation_workflow';

        $stats = array();

        // Count by status
        $status_counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM $workflow_table GROUP BY status"
        );

        foreach ($status_counts as $status_count) {
            $stats['by_status'][$status_count->status] = intval($status_count->count);
        }

        // Average review time
        $avg_review_time = $wpdb->get_var(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, submitted_at, reviewed_at))
             FROM $workflow_table
             WHERE status IN ('approved', 'rejected')
             AND submitted_at IS NOT NULL
             AND reviewed_at IS NOT NULL"
        );

        $stats['avg_review_time_hours'] = floatval($avg_review_time);

        // Overdue items
        $overdue_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM $workflow_table
             WHERE due_date < NOW()
             AND status NOT IN ('approved', 'rejected')"
        );

        $stats['overdue_count'] = intval($overdue_count);

        // Reviewer workloads
        $reviewer_workloads = $wpdb->get_results(
            "SELECT assigned_reviewer, COUNT(*) as workload
             FROM $workflow_table
             WHERE status IN ('pending_review', 'in_review')
             AND assigned_reviewer IS NOT NULL
             GROUP BY assigned_reviewer"
        );

        $stats['reviewer_workloads'] = array();
        foreach ($reviewer_workloads as $workload) {
            $user = get_user_by('id', $workload->assigned_reviewer);
            $stats['reviewer_workloads'][] = array(
                'reviewer_id' => $workload->assigned_reviewer,
                'reviewer_name' => $user ? $user->display_name : 'Unknown',
                'workload' => intval($workload->workload)
            );
        }

        return $stats;
    }

    /**
     * Send workflow notification
     */
    public function send_workflow_notification($workflow_id, $notification_type, $data = array()) {
        $workflow = $this->get_workflow_item($workflow_id);

        if (!$workflow) {
            return;
        }

        $notification_settings = get_option('nexus_ai_wp_translator_workflow_notifications', array());

        if (!isset($notification_settings[$notification_type]) || !$notification_settings[$notification_type]) {
            return;
        }

        $recipients = $this->get_notification_recipients($workflow, $notification_type);

        if (empty($recipients)) {
            return;
        }

        $subject = $this->get_notification_subject($workflow, $notification_type);
        $message = $this->get_notification_message($workflow, $notification_type, $data);

        foreach ($recipients as $recipient_email) {
            wp_mail($recipient_email, $subject, $message);
        }
    }

    /**
     * Get notification recipients
     */
    private function get_notification_recipients($workflow, $notification_type) {
        $recipients = array();

        switch ($notification_type) {
            case 'translation_ready':
            case 'submitted_for_review':
                // Notify assigned reviewer or all reviewers
                if ($workflow->assigned_reviewer) {
                    $reviewer = get_user_by('id', $workflow->assigned_reviewer);
                    if ($reviewer) {
                        $recipients[] = $reviewer->user_email;
                    }
                } else {
                    $reviewers = get_users(array('capability' => 'review_translations'));
                    foreach ($reviewers as $reviewer) {
                        $recipients[] = $reviewer->user_email;
                    }
                }
                break;

            case 'approved':
            case 'rejected':
            case 'reviewer_assigned':
                // Notify submitter
                $submitter = get_user_by('id', $workflow->submitted_by);
                if ($submitter) {
                    $recipients[] = $submitter->user_email;
                }
                break;
        }

        return array_unique($recipients);
    }

    /**
     * Get notification subject
     */
    private function get_notification_subject($workflow, $notification_type) {
        $subjects = array(
            'translation_ready' => sprintf(__('Translation Ready for Review: %s', 'nexus-ai-wp-translator'), $workflow->source_title),
            'submitted_for_review' => sprintf(__('Translation Submitted for Review: %s', 'nexus-ai-wp-translator'), $workflow->source_title),
            'approved' => sprintf(__('Translation Approved: %s', 'nexus-ai-wp-translator'), $workflow->source_title),
            'rejected' => sprintf(__('Translation Rejected: %s', 'nexus-ai-wp-translator'), $workflow->source_title),
            'reviewer_assigned' => sprintf(__('You have been assigned to review: %s', 'nexus-ai-wp-translator'), $workflow->source_title)
        );

        return isset($subjects[$notification_type]) ? $subjects[$notification_type] : __('Workflow Notification', 'nexus-ai-wp-translator');
    }

    /**
     * Get notification message
     */
    private function get_notification_message($workflow, $notification_type, $data = array()) {
        $messages = array(
            'translation_ready' => sprintf(
                __('A new translation is ready for review.\n\nOriginal Post: %s\nTarget Language: %s\nSubmitted by: %s\n\nPlease review the translation at your earliest convenience.', 'nexus-ai-wp-translator'),
                $workflow->source_title,
                $workflow->target_language,
                $workflow->submitted_by_name
            ),
            'submitted_for_review' => sprintf(
                __('A translation has been submitted for review.\n\nOriginal Post: %s\nTarget Language: %s\nSubmitted by: %s\n\nNotes: %s', 'nexus-ai-wp-translator'),
                $workflow->source_title,
                $workflow->target_language,
                $workflow->submitted_by_name,
                isset($data['notes']) ? $data['notes'] : 'None'
            ),
            'approved' => sprintf(
                __('Your translation has been approved and published.\n\nOriginal Post: %s\nTarget Language: %s\nReviewed by: %s\n\nNotes: %s', 'nexus-ai-wp-translator'),
                $workflow->source_title,
                $workflow->target_language,
                $workflow->reviewer_name,
                isset($data['notes']) ? $data['notes'] : 'None'
            ),
            'rejected' => sprintf(
                __('Your translation has been rejected.\n\nOriginal Post: %s\nTarget Language: %s\nReviewed by: %s\n\nReason: %s', 'nexus-ai-wp-translator'),
                $workflow->source_title,
                $workflow->target_language,
                $workflow->reviewer_name,
                isset($data['reason']) ? $data['reason'] : 'No reason provided'
            ),
            'reviewer_assigned' => sprintf(
                __('You have been assigned to review a translation.\n\nOriginal Post: %s\nTarget Language: %s\nSubmitted by: %s\n\nPlease review the translation at your earliest convenience.', 'nexus-ai-wp-translator'),
                $workflow->source_title,
                $workflow->target_language,
                $workflow->submitted_by_name
            )
        );

        return isset($messages[$notification_type]) ? $messages[$notification_type] : __('Workflow notification', 'nexus-ai-wp-translator');
    }

    /**
     * AJAX handlers
     */
    public function ajax_submit_for_review() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $workflow_id = intval($_POST['workflow_id']);
        $notes = sanitize_textarea_field($_POST['notes']);

        $result = $this->submit_for_review($workflow_id, $notes);

        if ($result) {
            wp_send_json_success(__('Submitted for review', 'nexus-ai-wp-translator'));
        } else {
            wp_send_json_error(__('Failed to submit for review', 'nexus-ai-wp-translator'));
        }
    }

    public function ajax_approve_translation() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        $workflow_id = intval($_POST['workflow_id']);
        $notes = sanitize_textarea_field($_POST['notes']);

        $result = $this->approve_translation($workflow_id, $notes);

        if ($result) {
            wp_send_json_success(__('Translation approved', 'nexus-ai-wp-translator'));
        } else {
            wp_send_json_error(__('Failed to approve translation', 'nexus-ai-wp-translator'));
        }
    }

    public function ajax_reject_translation() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        $workflow_id = intval($_POST['workflow_id']);
        $reason = sanitize_textarea_field($_POST['reason']);

        $result = $this->reject_translation($workflow_id, $reason);

        if ($result) {
            wp_send_json_success(__('Translation rejected', 'nexus-ai-wp-translator'));
        } else {
            wp_send_json_error(__('Failed to reject translation', 'nexus-ai-wp-translator'));
        }
    }

    public function ajax_get_workflow_items() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('review_translations')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $filters = isset($_POST['filters']) ? array_map('sanitize_text_field', $_POST['filters']) : array();
        $limit = intval($_POST['limit']) ?: 50;
        $offset = intval($_POST['offset']) ?: 0;

        $items = $this->get_workflow_items($filters, $limit, $offset);
        $stats = $this->get_workflow_statistics();

        wp_send_json_success(array(
            'items' => $items,
            'statistics' => $stats
        ));
    }

    public function ajax_assign_reviewer() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        $workflow_id = intval($_POST['workflow_id']);
        $reviewer_id = intval($_POST['reviewer_id']);

        $result = $this->assign_reviewer($workflow_id, $reviewer_id);

        if ($result) {
            wp_send_json_success(__('Reviewer assigned', 'nexus-ai-wp-translator'));
        } else {
            wp_send_json_error(__('Failed to assign reviewer', 'nexus-ai-wp-translator'));
        }
    }

    public function ajax_add_workflow_comment() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $workflow_id = intval($_POST['workflow_id']);
        $comment_text = sanitize_textarea_field($_POST['comment_text']);
        $comment_type = sanitize_text_field($_POST['comment_type']) ?: 'comment';

        $comment_id = $this->add_workflow_comment($workflow_id, $comment_type, $comment_text);

        if ($comment_id) {
            wp_send_json_success(array('comment_id' => $comment_id));
        } else {
            wp_send_json_error(__('Failed to add comment', 'nexus-ai-wp-translator'));
        }
    }
}
