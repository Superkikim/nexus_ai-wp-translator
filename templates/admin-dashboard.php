<?php
/**
 * Admin Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Nexus AI WP Translator Dashboard', 'nexus-ai-wp-translator'); ?></h1>
    
    <div class="nexus-ai-wp-translator-dashboard">
        <!-- Statistics Cards -->
        <div class="nexus-ai-wp-stats-cards">
            <div class="nexus-ai-wp-stat-card">
                <h3><?php _e('Total Translations', 'nexus-ai-wp-translator'); ?></h3>
                <div class="stat-number"><?php echo number_format(intval($stats['total'] ?? 0)); ?></div>
            </div>
            
            <div class="nexus-ai-wp-stat-card">
                <h3><?php _e('Recent (7 days)', 'nexus-ai-wp-translator'); ?></h3>
                <div class="stat-number"><?php echo number_format(intval($stats['recent'] ?? 0)); ?></div>
            </div>
            
            <div class="nexus-ai-wp-stat-card">
                <h3><?php _e('Success Rate', 'nexus-ai-wp-translator'); ?></h3>
                <div class="stat-number"><?php echo number_format(floatval($stats['success_rate'] ?? 0), 1); ?>%</div>
            </div>
            
            <div class="nexus-ai-wp-stat-card">
                <h3><?php _e('API Calls (7 days)', 'nexus-ai-wp-translator'); ?></h3>
                <div class="stat-number"><?php echo number_format(intval($stats['api_calls'] ?? 0)); ?></div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="nexus-ai-wp-quick-actions">
            <h2><?php _e('Quick Actions', 'nexus-ai-wp-translator'); ?></h2>
            <div class="action-buttons">
                <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-settings'); ?>" class="button button-primary">
                    <?php _e('Settings', 'nexus-ai-wp-translator'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-logs'); ?>" class="button">
                    <?php _e('View Logs', 'nexus-ai-wp-translator'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-relationships'); ?>" class="button">
                    <?php _e('Manage Relationships', 'nexus-ai-wp-translator'); ?>
                </a>
                <button id="nexus-ai-wp-refresh-stats" class="button">
                    <?php _e('Refresh Stats', 'nexus-ai-wp-translator'); ?>
                </button>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="nexus-ai-wp-recent-activity">
            <h2><?php _e('Recent Translation Activity', 'nexus-ai-wp-translator'); ?></h2>
            
            <?php if ($recent_logs): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Post', 'nexus-ai-wp-translator'); ?></th>
                            <th><?php _e('Action', 'nexus-ai-wp-translator'); ?></th>
                            <th><?php _e('Status', 'nexus-ai-wp-translator'); ?></th>
                            <th><?php _e('Time', 'nexus-ai-wp-translator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td>
                                    <?php if ($log->post_title): ?>
                                        <a href="<?php echo get_edit_post_link($log->post_id); ?>"><?php echo esc_html($log->post_title); ?></a>
                                    <?php else: ?>
                                        <span class="deleted-post"><?php _e('Deleted Post', 'nexus-ai-wp-translator'); ?> (ID: <?php echo $log->post_id; ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($log->action); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($log->status); ?>">
                                        <?php echo esc_html(ucfirst($log->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo human_time_diff(strtotime($log->created_at), current_time('timestamp')) . ' ' . __('ago', 'nexus-ai-wp-translator'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p>
                    <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-logs'); ?>" class="button">
                        <?php _e('View All Logs', 'nexus-ai-wp-translator'); ?>
                    </a>
                </p>
            <?php else: ?>
                <p><?php _e('No translation activity yet.', 'nexus-ai-wp-translator'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- System Status -->
        <div class="nexus-ai-wp-system-status">
            <h2><?php _e('System Status', 'nexus-ai-wp-translator'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><?php _e('API Key Status', 'nexus-ai-wp-translator'); ?></th>
                    <td>
                        <?php if (get_option('nexus_ai_wp_translator_api_key')): ?>
                            <span class="status-success"><?php _e('Configured', 'nexus-ai-wp-translator'); ?></span>
                        <?php else: ?>
                            <span class="status-error"><?php _e('Not Configured', 'nexus-ai-wp-translator'); ?></span>
                            <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-settings'); ?>" class="button button-small">
                                <?php _e('Configure', 'nexus-ai-wp-translator'); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th><?php _e('Auto Translation', 'nexus-ai-wp-translator'); ?></th>
                    <td>
                        <?php if (get_option('nexus_ai_wp_translator_auto_translate', true)): ?>
                            <span class="status-success"><?php _e('Enabled', 'nexus-ai-wp-translator'); ?></span>
                        <?php else: ?>
                            <span class="status-warning"><?php _e('Disabled', 'nexus-ai-wp-translator'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th><?php _e('Throttle Status', 'nexus-ai-wp-translator'); ?></th>
                    <td>
                        <?php
                        $db = Nexus_AI_WP_Translator_Database::get_instance();
                        $throttle_limit = get_option('nexus_ai_wp_translator_throttle_limit', 10);
                        $current_calls = $db->get_throttle_status(60);
                        
                        if ($current_calls < $throttle_limit):
                        ?>
                            <span class="status-success"><?php printf(__('%d/%d calls used', 'nexus-ai-wp-translator'), $current_calls, $throttle_limit); ?></span>
                        <?php else: ?>
                            <span class="status-error"><?php _e('Limit reached', 'nexus-ai-wp-translator'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#nexus-ai-wp-refresh-stats').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).text('<?php _e('Refreshing...', 'nexus-ai-wp-translator'); ?>');
        
        $.post(ajaxurl, {
            action: 'nexus_ai_wp_get_stats',
            period: '7 days',
            nonce: nexus_ai_wp_translator_ajax.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('<?php _e('Error refreshing stats', 'nexus-ai-wp-translator'); ?>');
            }
        }).always(function() {
            button.prop('disabled', false).text('<?php _e('Refresh Stats', 'nexus-ai-wp-translator'); ?>');
        });
    });
});
</script>