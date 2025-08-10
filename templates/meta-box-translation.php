<?php
/**
 * Translation Meta Box Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="nexus-ai-wp-translator-meta-box">
    <?php wp_nonce_field('nexus_ai_wp_translator_meta_box', 'nexus_ai_wp_translator_meta_box_nonce'); ?>
    
    <!-- Post Language -->
    <div class="nexus-ai-wp-meta-field">
        <label for="nexus_ai_wp_post_language">
            <strong><?php _e('Post Language:', 'nexus-ai-wp-translator'); ?></strong>
        </label>
        <select id="nexus_ai_wp_post_language" name="nexus_ai_wp_post_language">
            <?php foreach ($languages as $code => $name): ?>
                <option value="<?php echo esc_attr($code); ?>" <?php selected($post_language, $code); ?>>
                    <?php echo esc_html($name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php printf(__('Current source language: %s', 'nexus-ai-wp-translator'), $languages[$source_language] ?? $source_language); ?>
        </p>
    </div>
    
    <!-- Source Post Information -->
    <?php if ($source_post_id): ?>
        <div class="nexus-ai-wp-meta-field">
            <label><strong><?php _e('Translation Of:', 'nexus-ai-wp-translator'); ?></strong></label>
            <p>
                <a href="<?php echo get_edit_post_link($source_post_id); ?>" target="_blank">
                    <?php echo get_the_title($source_post_id); ?>
                </a>
                <small>(ID: <?php echo $source_post_id; ?>)</small>
            </p>
        </div>
    <?php endif; ?>
    
    <!-- Existing Translations -->
    <?php if ($translations): ?>
        <div class="nexus-ai-wp-meta-field">
            <label><strong><?php _e('Translations:', 'nexus-ai-wp-translator'); ?></strong></label>
            <ul class="nexus-ai-wp-translations-list">
                <?php foreach ($translations as $translation): ?>
                    <?php
                    $is_source = ($translation->source_post_id == $post->ID);
                    $related_post_id = $is_source ? $translation->translated_post_id : $translation->source_post_id;
                    $related_post = get_post($related_post_id);
                    $language = $is_source ? $translation->target_language : $translation->source_language;
                    ?>
                    <?php if ($related_post): ?>
                        <li class="nexus-ai-wp-translation-item">
                            <span class="language-code"><?php echo esc_html($language); ?></span>
                            <a href="<?php echo get_edit_post_link($related_post_id); ?>" target="_blank">
                                <?php echo esc_html($related_post->post_title); ?>
                            </a>
                            <span class="status-<?php echo esc_attr($translation->status); ?>">
                                (<?php echo esc_html($translation->status); ?>)
                            </span>
                            <button type="button" 
                                    class="button-link nexus-ai-wp-unlink-translation" 
                                    data-post-id="<?php echo $post->ID; ?>" 
                                    data-related-id="<?php echo $related_post_id; ?>">
                                <?php _e('Unlink', 'nexus-ai-wp-translator'); ?>
                            </button>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Translation Actions -->
    <div class="nexus-ai-wp-meta-field">
        <label><strong><?php _e('Translation Management:', 'nexus-ai-wp-translator'); ?></strong></label>

        <p class="description">
            <?php printf(
                __('To translate this post, go to <a href="%s">Nexus AI WP Translator Dashboard</a> and use the translation interface.', 'nexus-ai-wp-translator'),
                admin_url('admin.php?page=nexus-ai-wp-translator-dashboard')
            ); ?>
        </p>
    </div>
    
    <!-- Translation History -->
    <div class="nexus-ai-wp-meta-field">
        <label><strong><?php _e('Translation History:', 'nexus-ai-wp-translator'); ?></strong></label>
        <div id="nexus-ai-wp-translation-history">
            <button type="button" id="nexus-ai-wp-load-history" class="button button-small">
                <?php _e('Load History', 'nexus-ai-wp-translator'); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var postId = <?php echo intval($post->ID); ?>;
    
    // Note: The "Translate Now" button is handled by admin.js
    // This ensures proper initialization order and avoids script conflicts
    
    // Get translation status
    $('#nexus-ai-wp-get-translation-status').on('click', function() {
        var button = $(this);
        button.prop('disabled', true);
        
        var ajaxUrl = (typeof nexus_ai_wp_translator_ajax !== 'undefined') ? nexus_ai_wp_translator_ajax.ajax_url : ajaxurl;
        var nonce = (typeof nexus_ai_wp_translator_ajax !== 'undefined') ? nexus_ai_wp_translator_ajax.nonce : '';
        
        $.post(ajaxUrl, {
            action: 'nexus_ai_wp_get_translation_status',
            post_id: postId,
            nonce: nonce
        }, function(response) {
            if (response.success && response.data.length > 0) {
                var html = '<ul>';
                $.each(response.data, function(i, translation) {
                    html += '<li>' + translation.target_language + ': ' + translation.status + '</li>';
                });
                html += '</ul>';
                $('#nexus-ai-wp-translation-status').html(html);
            } else {
                $('#nexus-ai-wp-translation-status').html('<p><?php _e('No translations found.', 'nexus-ai-wp-translator'); ?></p>');
            }
        }).always(function() {
            button.prop('disabled', false);
        });
    });
    
    // Unlink translation
    $('.nexus-ai-wp-unlink-translation').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to unlink this translation?', 'nexus-ai-wp-translator'); ?>')) {
            return;
        }
        
        var button = $(this);
        var postId = button.data('post-id');
        var relatedId = button.data('related-id');
        
        var ajaxUrl = (typeof nexus_ai_wp_translator_ajax !== 'undefined') ? nexus_ai_wp_translator_ajax.ajax_url : ajaxurl;
        var nonce = (typeof nexus_ai_wp_translator_ajax !== 'undefined') ? nexus_ai_wp_translator_ajax.nonce : '';
        
        $.post(ajaxUrl, {
            action: 'nexus_ai_wp_unlink_translation',
            post_id: postId,
            related_post_id: relatedId,
            nonce: nonce
        }, function(response) {
            if (response.success) {
                button.closest('li').fadeOut();
            } else {
                alert('<?php _e('Error:', 'nexus-ai-wp-translator'); ?> ' + response.data);
            }
        });
    });
    
    // Load translation history
    $('#nexus-ai-wp-load-history').on('click', function() {
        var button = $(this);
        button.text('<?php _e('Loading...', 'nexus-ai-wp-translator'); ?>');
        
        // This would load translation history from logs
        // Implementation depends on specific requirements
        setTimeout(function() {
            $('#nexus-ai-wp-translation-history').html('<p><?php _e('History loaded (placeholder)', 'nexus-ai-wp-translator'); ?></p>');
        }, 1000);
    });
});
</script>