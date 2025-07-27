<?php
/**
 * Admin Logs Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Translation Logs', 'claude-translator'); ?></h1>
    
    <div class="claude-logs-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="claude-translator-logs" />
            
            <select name="status">
                <option value=""><?php _e('All Statuses', 'claude-translator'); ?></option>
                <option value="success" <?php selected($_GET['status'] ?? '', 'success'); ?>><?php _e('Success', 'claude-translator'); ?></option>
                <option value="error" <?php selected($_GET['status'] ?? '', 'error'); ?>><?php _e('Error', 'claude-translator'); ?></option>
                <option value="processing" <?php selected($_GET['status'] ?? '', 'processing'); ?>><?php _e('Processing', 'claude-translator'); ?></option>
            </select>
            
            <select name="action">
                <option value=""><?php _e('All Actions', 'claude-translator'); ?></option>
                <option value="translate" <?php selected($_GET['action'] ?? '', 'translate'); ?>><?php _e('Translate', 'claude-translator'); ?></option>
                <option value="delete" <?php selected($_GET['action'] ?? '', 'delete'); ?>><?php _e('Delete', 'claude-translator'); ?></option>
                <option value="trash" <?php selected($_GET['action'] ?? '', 'trash'); ?>><?php _e('Trash', 'claude-translator'); ?></option>
            </select>
            
            <input type="submit" class="button" value="<?php _e('Filter', 'claude-translator'); ?>" />
            <a href="<?php echo admin_url('admin.php?page=claude-translator-logs'); ?>" class="button">
                <?php _e('Clear Filters', 'claude-translator'); ?>
            </a>
        </form>
    </div>
    
    <?php if ($logs): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Date/Time', 'claude-translator'); ?></th>
                    <th><?php _e('Post', 'claude-translator'); ?></th>
                    <th><?php _e('Action', 'claude-translator'); ?></th>
                    <th><?php _e('Status', 'claude-translator'); ?></th>
                    <th><?php _e('Message', 'claude-translator'); ?></th>
                    <th><?php _e('API Calls', 'claude-translator'); ?></th>
                    <th><?php _e('Processing Time', 'claude-translator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <strong><?php echo date('Y-m-d H:i:s', strtotime($log->created_at)); ?></strong><br>
                            <small><?php echo human_time_diff(strtotime($log->created_at), current_time('timestamp')) . ' ' . __('ago', 'claude-translator'); ?></small>
                        </td>
                        <td>
                            <?php if ($log->post_title): ?>
                                <a href="<?php echo get_edit_post_link($log->post_id); ?>" target="_blank">
                                    <?php echo esc_html($log->post_title); ?>
                                </a>
                                <br><small>ID: <?php echo $log->post_id; ?></small>
                            <?php else: ?>
                                <span class="deleted-post">
                                    <?php _e('Deleted Post', 'claude-translator'); ?>
                                    <br><small>ID: <?php echo $log->post_id; ?></small>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code><?php echo esc_html($log->action); ?></code>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($log->status); ?>">
                                <?php echo esc_html(ucfirst($log->status)); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (strlen($log->message) > 100): ?>
                                <span class="log-message-short">
                                    <?php echo esc_html(substr($log->message, 0, 100)); ?>...
                                    <button type="button" class="button-link expand-message">
                                        <?php _e('Show More', 'claude-translator'); ?>
                                    </button>
                                </span>
                                <span class="log-message-full" style="display: none;">
                                    <?php echo esc_html($log->message); ?>
                                    <button type="button" class="button-link collapse-message">
                                        <?php _e('Show Less', 'claude-translator'); ?>
                                    </button>
                                </span>
                            <?php else: ?>
                                <?php echo esc_html($log->message); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo intval($log->api_calls_count); ?>
                        </td>
                        <td>
                            <?php if ($log->processing_time > 0): ?>
                                <?php echo number_format($log->processing_time, 2); ?>s
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                $total_items = count($logs);
                $per_page = 20;
                $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
                $total_pages = ceil($total_items / $per_page);
                
                if ($total_pages > 1) {
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                }
                ?>
            </div>
        </div>
    <?php else: ?>
        <div class="notice notice-info">
            <p><?php _e('No translation logs found.', 'claude-translator'); ?></p>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Expand/collapse long messages
    $('.expand-message').on('click', function() {
        $(this).closest('td').find('.log-message-short').hide();
        $(this).closest('td').find('.log-message-full').show();
    });
    
    $('.collapse-message').on('click', function() {
        $(this).closest('td').find('.log-message-full').hide();
        $(this).closest('td').find('.log-message-short').show();
    });
});
</script>