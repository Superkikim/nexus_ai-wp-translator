<?php
/**
 * Admin Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Claude Translator Dashboard', 'claude-translator'); ?></h1>
    
    <div class="claude-translator-dashboard">
        <!-- Statistics Cards -->
        <div class="claude-stats-cards">
            <div class="claude-stat-card">
                <h3><?php _e('Total Translations', 'claude-translator'); ?></h3>
                <div class="stat-number"><?php echo intval($stats['total']); ?></div>
            </div>
            
            <div class="claude-stat-card">
                <h3><?php _e('Recent (7 days)', 'claude-translator'); ?></h3>
                <div class="stat-number"><?php echo intval($stats['recent']); ?></div>
            </div>
            
            <div class="claude-stat-card">
                <h3><?php _e('Success Rate', 'claude-translator'); ?></h3>
                <div class="stat-number"><?php echo floatval($stats['success_rate']); ?>%</div>
            </div>
            
            <div class="claude-stat-card">
                <h3><?php _e('API Calls (7 days)', 'claude-translator'); ?></h3>
                <div class="stat-number"><?php echo intval($stats['api_calls']); ?></div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="claude-quick-actions">
            <h2><?php _e('Quick Actions', 'claude-translator'); ?></h2>
            <div class="action-buttons">
                <a href="<?php echo admin_url('admin.php?page=claude-translator-settings'); ?>" class="button button-primary">
                    <?php _e('Settings', 'claude-translator'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=claude-translator-logs'); ?>" class="button">
                    <?php _e('View Logs', 'claude-translator'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=claude-translator-relationships'); ?>" class="button">
                    <?php _e('Manage Relationships', 'claude-translator'); ?>
                </a>
                <button id="claude-refresh-stats" class="button">
                    <?php _e('Refresh Stats', 'claude-translator'); ?>
                </button>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="claude-recent-activity">
            <h2><?php _e('Recent Translation Activity', 'claude-translator'); ?></h2>
            
            <?php if ($recent_logs): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Post', 'claude-translator'); ?></th>
                            <th><?php _e('Action', 'claude-translator'); ?></th>
                            <th><?php _e('Status', 'claude-translator'); ?></th>
                            <th><?php _e('Time', 'claude-translator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td>
                                    <?php if ($log->post_title): ?>
                                        <a href="<?php echo get_edit_post_link($log->post_id); ?>"><?php echo esc_html($log->post_title); ?></a>
                                    <?php else: ?>
                                        <span class="deleted-post"><?php _e('Deleted Post', 'claude-translator'); ?> (ID: <?php echo $log->post_id; ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($log->action); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($log->status); ?>">
                                        <?php echo esc_html(ucfirst($log->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo human_time_diff(strtotime($log->created_at), current_time('timestamp')) . ' ' . __('ago', 'claude-translator'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p>
                    <a href="<?php echo admin_url('admin.php?page=claude-translator-logs'); ?>" class="button">
                        <?php _e('View All Logs', 'claude-translator'); ?>
                    </a>
                </p>
            <?php else: ?>
                <p><?php _e('No translation activity yet.', 'claude-translator'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- System Status -->
        <div class="claude-system-status">
            <h2><?php _e('System Status', 'claude-translator'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><?php _e('API Key Status', 'claude-translator'); ?></th>
                    <td>
                        <?php if (get_option('claude_translator_api_key')): ?>
                            <span class="status-success"><?php _e('Configured', 'claude-translator'); ?></span>
                        <?php else: ?>
                            <span class="status-error"><?php _e('Not Configured', 'claude-translator'); ?></span>
                            <a href="<?php echo admin_url('admin.php?page=claude-translator-settings'); ?>" class="button button-small">
                                <?php _e('Configure', 'claude-translator'); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th><?php _e('Auto Translation', 'claude-translator'); ?></th>
                    <td>
                        <?php if (get_option('claude_translator_auto_translate', true)): ?>
                            <span class="status-success"><?php _e('Enabled', 'claude-translator'); ?></span>
                        <?php else: ?>
                            <span class="status-warning"><?php _e('Disabled', 'claude-translator'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th><?php _e('Throttle Status', 'claude-translator'); ?></th>
                    <td>
                        <?php
                        $db = Claude_Translator_Database::get_instance();
                        $throttle_limit = get_option('claude_translator_throttle_limit', 10);
                        $current_calls = $db->get_throttle_status(60);
                        
                        if ($current_calls < $throttle_limit):
                        ?>
                            <span class="status-success"><?php printf(__('%d/%d calls used', 'claude-translator'), $current_calls, $throttle_limit); ?></span>
                        <?php else: ?>
                            <span class="status-error"><?php _e('Limit reached', 'claude-translator'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#claude-refresh-stats').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).text('<?php _e('Refreshing...', 'claude-translator'); ?>');
        
        $.post(ajaxurl, {
            action: 'claude_get_stats',
            period: '7 days',
            nonce: claude_translator_ajax.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('<?php _e('Error refreshing stats', 'claude-translator'); ?>');
            }
        }).always(function() {
            button.prop('disabled', false).text('<?php _e('Refresh Stats', 'claude-translator'); ?>');
        });
    });
});
</script>