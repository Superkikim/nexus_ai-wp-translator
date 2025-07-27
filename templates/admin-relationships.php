<?php
/**
 * Admin Relationships Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Translation Relationships', 'nexus-ai-wp-translator'); ?></h1>
    
    <p class="description">
        <?php _e('Manage the relationships between source posts and their translations. You can unlink translations or view their status.', 'nexus-ai-wp-translator'); ?>
    </p>
    
    <?php if ($relationships): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Source Post', 'nexus-ai-wp-translator'); ?></th>
                    <th><?php _e('Source Language', 'nexus-ai-wp-translator'); ?></th>
                    <th><?php _e('Translated Post', 'nexus-ai-wp-translator'); ?></th>
                    <th><?php _e('Target Language', 'nexus-ai-wp-translator'); ?></th>
                    <th><?php _e('Status', 'nexus-ai-wp-translator'); ?></th>
                    <th><?php _e('Created', 'nexus-ai-wp-translator'); ?></th>
                    <th><?php _e('Actions', 'nexus-ai-wp-translator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($relationships as $relationship): ?>
                    <tr>
                        <td>
                            <?php if ($relationship->source_title && $relationship->source_status !== 'trash'): ?>
                                <a href="<?php echo get_edit_post_link($relationship->source_post_id); ?>" target="_blank">
                                    <?php echo esc_html($relationship->source_title); ?>
                                </a>
                                <br><small>ID: <?php echo $relationship->source_post_id; ?></small>
                                <?php if ($relationship->source_status !== 'publish'): ?>
                                    <br><span class="post-status">(<?php echo esc_html($relationship->source_status); ?>)</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="deleted-post">
                                    <?php _e('Deleted/Trashed Post', 'nexus-ai-wp-translator'); ?>
                                    <br><small>ID: <?php echo $relationship->source_post_id; ?></small>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code><?php echo esc_html($relationship->source_language); ?></code>
                        </td>
                        <td>
                            <?php if ($relationship->translated_title && $relationship->translated_status !== 'trash'): ?>
                                <a href="<?php echo get_edit_post_link($relationship->translated_post_id); ?>" target="_blank">
                                    <?php echo esc_html($relationship->translated_title); ?>
                                </a>
                                <br><small>ID: <?php echo $relationship->translated_post_id; ?></small>
                                <?php if ($relationship->translated_status !== 'publish'): ?>
                                    <br><span class="post-status">(<?php echo esc_html($relationship->translated_status); ?>)</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="deleted-post">
                                    <?php _e('Deleted/Trashed Post', 'nexus-ai-wp-translator'); ?>
                                    <br><small>ID: <?php echo $relationship->translated_post_id; ?></small>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code><?php echo esc_html($relationship->target_language); ?></code>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($relationship->status); ?>">
                                <?php 
                                $status_display = $relationship->status;
                                switch ($relationship->status) {
                                    case 'source_deleted':
                                        $status_display = __('Source Deleted', 'nexus-ai-wp-translator');
                                        break;
                                    case 'translation_deleted':
                                        $status_display = __('Translation Deleted', 'nexus-ai-wp-translator');
                                        break;
                                    default:
                                        $status_display = ucfirst($relationship->status);
                                }
                                echo esc_html($status_display);
                                ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('Y-m-d H:i', strtotime($relationship->created_at)); ?><br>
                            <small><?php echo human_time_diff(strtotime($relationship->created_at), current_time('timestamp')) . ' ' . __('ago', 'nexus-ai-wp-translator'); ?></small>
                        </td>
                        <td>
                            <div class="row-actions">
                                <?php if ($relationship->source_title && $relationship->translated_title): ?>
                                    <span class="view-source">
                                        <a href="<?php echo get_permalink($relationship->source_post_id); ?>" target="_blank">
                                            <?php _e('View Source', 'nexus-ai-wp-translator'); ?>
                                        </a> |
                                    </span>
                                    <span class="view-translation">
                                        <a href="<?php echo get_permalink($relationship->translated_post_id); ?>" target="_blank">
                                            <?php _e('View Translation', 'nexus-ai-wp-translator'); ?>
                                        </a> |
                                    </span>
                                <?php endif; ?>
                                <span class="unlink">
                                    <button type="button" 
                                            class="button-link unlink-translation" 
                                            data-source-id="<?php echo $relationship->source_post_id; ?>" 
                                            data-translated-id="<?php echo $relationship->translated_post_id; ?>">
                                        <?php _e('Unlink', 'nexus-ai-wp-translator'); ?>
                                    </button>
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                $total_items = count($relationships);
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
            <p><?php _e('No translation relationships found.', 'nexus-ai-wp-translator'); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Bulk Actions -->
    <div class="nexus-ai-wp-bulk-actions">
        <h2><?php _e('Bulk Actions', 'nexus-ai-wp-translator'); ?></h2>
        <p>
            <button type="button" id="cleanup-orphaned" class="button">
                <?php _e('Clean Up Orphaned Relationships', 'nexus-ai-wp-translator'); ?>
            </button>
            <span class="description">
                <?php _e('Remove relationships where source or translated posts have been deleted.', 'nexus-ai-wp-translator'); ?>
            </span>
        </p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Check if posts.js functions are available
    if (typeof window.NexusAIWPTranslatorPosts === 'undefined') {
        console.log('NexusAI Debug: posts.js not loaded, using inline unlink function');
    }
    
    // Unlink translation
    $('.unlink-translation').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to unlink this translation? This action cannot be undone.', 'nexus-ai-wp-translator'); ?>')) {
            return;
        }
        
        var button = $(this);
        var sourceId = button.data('source-id');
        var translatedId = button.data('translated-id');
        var row = button.closest('tr');
        
        button.prop('disabled', true).text('<?php _e('Unlinking...', 'nexus-ai-wp-translator'); ?>');
        
        $.post(ajaxurl, {
            action: 'nexus_ai_wp_unlink_translation',
            post_id: sourceId,
            related_post_id: translatedId,
            nonce: '<?php echo wp_create_nonce('nexus_ai_wp_translator_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                row.fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                alert('<?php _e('Error:', 'nexus-ai-wp-translator'); ?> ' + response.data);
                button.prop('disabled', false).text('<?php _e('Unlink', 'nexus-ai-wp-translator'); ?>');
            }
        }).fail(function() {
            alert('<?php _e('Network error occurred', 'nexus-ai-wp-translator'); ?>');
            button.prop('disabled', false).text('<?php _e('Unlink', 'nexus-ai-wp-translator'); ?>');
        });
    });
    
    // Clean up orphaned relationships
    $('#cleanup-orphaned').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to clean up orphaned relationships? This will remove all relationships where posts have been deleted.', 'nexus-ai-wp-translator'); ?>')) {
            return;
        }
        
        var button = $(this);
        button.prop('disabled', true).text('<?php _e('Cleaning up...', 'nexus-ai-wp-translator'); ?>');
        
        $.post(ajaxurl, {
            action: 'nexus_ai_wp_cleanup_orphaned',
            nonce: '<?php echo wp_create_nonce('nexus_ai_wp_translator_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('<?php _e('Error:', 'nexus-ai-wp-translator'); ?> ' + response.data);
            }
        }).always(function() {
            button.prop('disabled', false).text('<?php _e('Clean Up Orphaned Relationships', 'nexus-ai-wp-translator'); ?>');
        });
    });
});
</script>