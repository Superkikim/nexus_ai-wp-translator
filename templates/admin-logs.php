<?php
/**
 * Admin Logs Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Translation Logs', 'nexus-ai-wp-translator'); ?></h1>
    
    <div class="nexus-ai-wp-logs-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="nexus-ai-wp-translator-logs" />
            
            <select name="status">
                <option value=""><?php _e('All Statuses', 'nexus-ai-wp-translator'); ?></option>
                <option value="success" <?php selected($_GET['status'] ?? '', 'success'); ?>><?php _e('Success', 'nexus-ai-wp-translator'); ?></option>
                <option value="error" <?php selected($_GET['status'] ?? '', 'error'); ?>><?php _e('Error', 'nexus-ai-wp-translator'); ?></option>
                <option value="processing" <?php selected($_GET['status'] ?? '', 'processing'); ?>><?php _e('Processing', 'nexus-ai-wp-translator'); ?></option>
            </select>
            
            <select name="action">
                <option value=""><?php _e('All Actions', 'nexus-ai-wp-translator'); ?></option>
                <option value="translate" <?php selected($_GET['action'] ?? '', 'translate'); ?>><?php _e('Translate', 'nexus-ai-wp-translator'); ?></option>
                <option value="delete" <?php selected($_GET['action'] ?? '', 'delete'); ?>><?php _e('Delete', 'nexus-ai-wp-translator'); ?></option>
                <option value="trash" <?php selected($_GET['action'] ?? '', 'trash'); ?>><?php _e('Trash', 'nexus-ai-wp-translator'); ?></option>
            </select>
            
            <input type="submit" class="button" value="<?php _e('Filter', 'nexus-ai-wp-translator'); ?>" />
            <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-logs'); ?>" class="button">
                <?php _e('Clear Filters', 'nexus-ai-wp-translator'); ?>
            </a>
        </form>
    </div>
    
    <?php if ($logs): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Date/Time', 'nexus-ai-wp-translator'); ?></th>
                    <th><?php _e('Post', 'nexus-ai-wp-translator'); ?></th>
                    <th><?php _e('Action', 'nexus-ai-wp-translator'); ?></th>
                    <th><?php _e('Status', 'nexus-ai-wp-translator'); ?></th>
                    <th><?php _e('Message', 'nexus-ai-wp-translator'); ?></th>
                    <th><?php _e('API Calls', 'nexus-ai-wp-translator'); ?></th>
                    <th><?php _e('Processing Time', 'nexus-ai-wp-translator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <strong><?php echo date('Y-m-d H:i:s', strtotime($log->created_at)); ?></strong><br>
                            <small><?php echo human_time_diff(strtotime($log->created_at), current_time('timestamp')) . ' ' . __('ago', 'nexus-ai-wp-translator'); ?></small>
                        </td>
                        <td>
                            <?php if ($log->post_title): ?>
                                <a href="<?php echo get_edit_post_link($log->post_id); ?>" target="_blank">
                                    <?php echo esc_html($log->post_title); ?>
                                </a>
                                <br><small>ID: <?php echo $log->post_id; ?></small>
                            <?php else: ?>
                                <span class="deleted-post">
                                    <?php _e('Deleted Post', 'nexus-ai-wp-translator'); ?>
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
                                        <?php _e('Show More', 'nexus-ai-wp-translator'); ?>
                                    </button>
                                </span>
                                <span class="log-message-full" style="display: none;">
                                    <?php echo esc_html($log->message); ?>
                                    <button type="button" class="button-link collapse-message">
                                        <?php _e('Show Less', 'nexus-ai-wp-translator'); ?>
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
            <p><?php _e('No translation logs found.', 'nexus-ai-wp-translator'); ?></p>
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