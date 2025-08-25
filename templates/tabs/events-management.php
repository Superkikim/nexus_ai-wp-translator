<?php
/**
 * Events Management Tab Content
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<h2><?php _e('Events to Translate', 'nexus-ai-wp-translator'); ?></h2>
<div id="events-list">
    <?php echo $this->render_posts_list('event'); ?>
</div>
