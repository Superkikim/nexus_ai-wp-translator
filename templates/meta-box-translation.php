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
            <option value=""><?php _e('Auto-detect', 'nexus-ai-wp-translator'); ?></option>
            <?php foreach ($languages as $code => $name): ?>
                <option value="<?php echo esc_attr($code); ?>" <?php selected($post_language, $code); ?>>
                    <?php echo esc_html($name); ?>
                </option>
            <?php endforeach; ?>
        </select>
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
        <label><strong><?php _e('Translation Actions:', 'nexus-ai-wp-translator'); ?></strong></label>
        
        <?php if (get_option('nexus_ai_wp_translator_api_key')): ?>
            <div class="nexus-ai-wp-translation-actions">
                <div class="target-languages">
                    <label><?php _e('Translate to:', 'nexus-ai-wp-translator'); ?></label>
                    <?php foreach ($target_languages as $lang_code): ?>
                        <?php if ($lang_code !== $post_language): ?>
                            <label>
                                <input type="checkbox" 
                                       name="nexus_ai_wp_translate_to[]" 
                                       value="<?php echo esc_attr($lang_code); ?>" 
                                       class="nexus-ai-wp-target-language" />
                                <?php echo esc_html($languages[$lang_code] ?? $lang_code); ?>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <p>
                    <button type="button" id="nexus-ai-wp-translate-post" class="button button-primary">
                        <?php _e('Translate Now', 'nexus-ai-wp-translator'); ?>
                    </button>
                    <button type="button" id="nexus-ai-wp-get-translation-status" class="button">
                        <?php _e('Check Status', 'nexus-ai-wp-translator'); ?>
                    </button>
                </p>
                
                <div id="nexus-ai-wp-translation-status"></div>
            </div>
        <?php else: ?>
            <p class="description">
                <?php printf(
                    __('Please configure your Nexus AI API key in the <a href="%s">settings</a> to enable translation.', 'nexus-ai-wp-translator'),
                    admin_url('admin.php?page=nexus-ai-wp-translator-settings')
                ); ?>
            </p>
        <?php endif; ?>
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
    
    // Translate post
    $('#nexus-ai-wp-translate-post').on('click', function() {
        var button = $(this);
        var targetLanguages = [];
        
        $('.nexus-ai-wp-target-language:checked').each(function() {
            targetLanguages.push($(this).val());
        });
        
        if (targetLanguages.length === 0) {
            alert('<?php _e('Please select at least one target language.', 'nexus-ai-wp-translator'); ?>');
            return;
        }
        
        button.prop('disabled', true).text('<?php _e('Translating...', 'nexus-ai-wp-translator'); ?>');
        $('#nexus-ai-wp-translation-status').html('<div class="notice notice-info"><p><?php _e('Translation in progress...', 'nexus-ai-wp-translator'); ?></p></div>');
        
        $.post(ajaxurl, {
            action: 'nexus_ai_wp_translate_post',
            post_id: postId,
            target_languages: targetLanguages,
            nonce: nexus_ai_wp_translator_ajax.nonce
        }, function(response) {
            var noticeClass = response.success ? 'notice-success' : 'notice-error';
            var message = response.success ? 
                '<?php _e('Translation completed successfully!', 'nexus-ai-wp-translator'); ?>' : 
                ('<?php _e('Translation failed:', 'nexus-ai-wp-translator'); ?> ' + response.message);
            
            $('#nexus-ai-wp-translation-status').html('<div class="notice ' + noticeClass + '"><p>' + message + '</p></div>');
            
            if (response.success) {
                // Reload the page to show new translations
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }
        }).fail(function() {
            $('#nexus-ai-wp-translation-status').html('<div class="notice notice-error"><p><?php _e('Network error occurred', 'nexus-ai-wp-translator'); ?></p></div>');
        }).always(function() {
            button.prop('disabled', false).text('<?php _e('Translate Now', 'nexus-ai-wp-translator'); ?>');
        });
    });
    
    // Get translation status
    $('#nexus-ai-wp-get-translation-status').on('click', function() {
        var button = $(this);
        button.prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'nexus_ai_wp_get_translation_status',
            post_id: postId,
            nonce: nexus_ai_wp_translator_ajax.nonce
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
        
        $.post(ajaxurl, {
            action: 'nexus_ai_wp_unlink_translation',
            post_id: postId,
            related_post_id: relatedId,
            nonce: nexus_ai_wp_translator_ajax.nonce
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