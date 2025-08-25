<?php
/**
 * Articles Management Tab Content
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<h2><?php _e('Articles to Translate', 'nexus-ai-wp-translator'); ?></h2>

<!-- Action Buttons Explanation -->
<div class="nexus-ai-wp-action-explanation">
    <h3><?php _e('Action Buttons Explained', 'nexus-ai-wp-translator'); ?></h3>
    <div class="nexus-ai-wp-action-buttons-info">
        <div class="nexus-ai-wp-action-info">
            <span class="nexus-ai-wp-action-button-demo button button-primary"><?php _e('Translate', 'nexus-ai-wp-translator'); ?></span>
            <div class="nexus-ai-wp-action-description">
                <strong><?php _e('Immediate Translation', 'nexus-ai-wp-translator'); ?></strong>
                <p><?php _e('Starts translation immediately in your browser. You can see real-time progress and the translation completes right away. Best for single posts or when you want to monitor the process.', 'nexus-ai-wp-translator'); ?></p>
            </div>
        </div>
        <div class="nexus-ai-wp-action-info">
            <span class="nexus-ai-wp-action-button-demo button"><?php _e('Queue', 'nexus-ai-wp-translator'); ?></span>
            <div class="nexus-ai-wp-action-description">
                <strong><?php _e('Background Translation', 'nexus-ai-wp-translator'); ?></strong>
                <p><?php _e('Adds the post to the translation queue for background processing. Translations happen automatically via cron jobs. Best for bulk operations or when you want to schedule translations for later.', 'nexus-ai-wp-translator'); ?></p>
            </div>
        </div>
    </div>
</div>

<div id="articles-list">
    <?php echo $this->render_posts_list('post'); ?>
</div>

<!-- Add a link button above the table -->
<script>
jQuery(document).ready(function($) {
    // Initialize checkbox functionality
    $('.select-all-checkbox').on('change', function() {
        var postType = $(this).attr('id').replace('select-all-', '');
        var isChecked = $(this).is(':checked');
        
        $('.select-post-checkbox[data-post-id]').each(function() {
            if ($(this).closest('tr').find('td').length > 0) { // Only for visible rows
                $(this).prop('checked', isChecked);
            }
        });
    });
    
    // Individual checkbox selection
    $('.select-post-checkbox').on('change', function() {
        updateLinkButtonState();
    });
    
    function updateLinkButtonState() {
        // This function is kept for compatibility but no longer used
        // The bulk actions interface handles all actions now
    }
    
    function linkSelectedItems() {
        // This function is implemented in assets/js/admin.js
        console.debug('[Nexus Translator]:Link selected items - functionality implemented in admin.js');
    }
});
</script>
