<?php
/**
 * Pages Management Tab Content
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<h2><?php _e('Pages to Translate', 'nexus-ai-wp-translator'); ?></h2>
<div id="pages-list">
    <?php echo $this->render_posts_list('page'); ?>
</div>
