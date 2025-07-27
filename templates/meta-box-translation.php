<?php
/**
 * Translation Meta Box Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="claude-translator-meta-box">
    <?php wp_nonce_field('claude_translator_meta_box', 'claude_translator_meta_box_nonce'); ?>
    
    <!-- Post Language -->
    <div class="claude-meta-field">
        <label for="claude_post_language">
            <strong><?php _e('Post Language:', 'claude-translator'); ?></strong>
        </label>
        <select id="claude_post_language" name="claude_post_language">
            <option value=""><?php _e('Auto-detect', 'claude-translator'); ?></option>
            <?php foreach ($languages as $code => $name): ?>
                <option value="<?php echo esc_attr($code); ?>" <?php selected($post_language, $code); ?>>
                    <?php echo esc_html($name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <!-- Source Post Information -->
    <?php if ($source_post_id): ?>
        <div class="claude-meta-field">
            <label><strong><?php _e('Translation Of:', 'claude-translator'); ?></strong></label>
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
        <div class="claude-meta-field">
            <label><strong><?php _e('Translations:', 'claude-translator'); ?></strong></label>
            <ul class="claude-translations-list">
                <?php foreach ($translations as $translation): ?>
                    <?php
                    $is_source = ($translation->source_post_id == $post->ID);
                    $related_post_id = $is_source ? $translation->translated_post_id : $translation->source_post_id;
                    $related_post = get_post($related_post_id);
                    $language = $is_source ? $translation->target_language : $translation->source_language;
                    ?>
                    <?php if ($related_post): ?>
                        <li class="claude-translation-item">
                            <span class="language-code"><?php echo esc_html($language); ?></span>
                            <a href="<?php echo get_edit_post_link($related_post_id); ?>" target="_blank">
                                <?php echo esc_html($related_post->post_title); ?>
                            </a>
                            <span class="status-<?php echo esc_attr($translation->status); ?>">
                                (<?php echo esc_html($translation->status); ?>)
                            </span>
                            <button type="button" 
                                    class="button-link claude-unlink-translation" 
                                    data-post-id="<?php echo $post->ID; ?>" 
                                    data-related-id="<?php echo $related_post_id; ?>">
                                <?php _e('Unlink', 'claude-translator'); ?>
                            </button>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Translation Actions -->
    <div class="claude-meta-field">
        <label><strong><?php _e('Translation Actions:', 'claude-translator'); ?></strong></label>
        
        <?php if (get_option('claude_translator_api_key')): ?>
            <div class="claude-translation-actions">
                <div class="target-languages">
                    <label><?php _e('Translate to:', 'claude-translator'); ?></label>
                    <?php foreach ($target_languages as $lang_code): ?>
                        <?php if ($lang_code !== $post_language): ?>
                            <label>
                                <input type="checkbox" 
                                       name="claude_translate_to[]" 
                                       value="<?php echo esc_attr($lang_code); ?>" 
                                       class="claude-target-language" />
                                <?php echo esc_html($languages[$lang_code] ?? $lang_code); ?>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <p>
                    <button type="button" id="claude-translate-post" class="button button-primary">
                        <?php _e('Translate Now', 'claude-translator'); ?>
                    </button>
                    <button type="button" id="claude-get-translation-status" class="button">
                        <?php _e('Check Status', 'claude-translator'); ?>
                    </button>
                </p>
                
                <div id="claude-translation-status"></div>
            </div>
        <?php else: ?>
            <p class="description">
                <?php printf(
                    __('Please configure your Claude AI API key in the <a href="%s">settings</a> to enable translation.', 'claude-translator'),
                    admin_url('admin.php?page=claude-translator-settings')
                ); ?>
            </p>
        <?php endif; ?>
    </div>
    
    <!-- Translation History -->
    <div class="claude-meta-field">
        <label><strong><?php _e('Translation History:', 'claude-translator'); ?></strong></label>
        <div id="claude-translation-history">
            <button type="button" id="claude-load-history" class="button button-small">
                <?php _e('Load History', 'claude-translator'); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var postId = <?php echo intval($post->ID); ?>;
    
    // Translate post
    $('#claude-translate-post').on('click', function() {
        var button = $(this);
        var targetLanguages = [];
        
        $('.claude-target-language:checked').each(function() {
            targetLanguages.push($(this).val());
        });
        
        if (targetLanguages.length === 0) {
            alert('<?php _e('Please select at least one target language.', 'claude-translator'); ?>');
            return;
        }
        
        button.prop('disabled', true).text('<?php _e('Translating...', 'claude-translator'); ?>');
        $('#claude-translation-status').html('<div class="notice notice-info"><p><?php _e('Translation in progress...', 'claude-translator'); ?></p></div>');
        
        $.post(ajaxurl, {
            action: 'claude_translate_post',
            post_id: postId,
            target_languages: targetLanguages,
            nonce: claude_translator_ajax.nonce
        }, function(response) {
            var noticeClass = response.success ? 'notice-success' : 'notice-error';
            var message = response.success ? 
                '<?php _e('Translation completed successfully!', 'claude-translator'); ?>' : 
                ('<?php _e('Translation failed:', 'claude-translator'); ?> ' + response.message);
            
            $('#claude-translation-status').html('<div class="notice ' + noticeClass + '"><p>' + message + '</p></div>');
            
            if (response.success) {
                // Reload the page to show new translations
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }
        }).fail(function() {
            $('#claude-translation-status').html('<div class="notice notice-error"><p><?php _e('Network error occurred', 'claude-translator'); ?></p></div>');
        }).always(function() {
            button.prop('disabled', false).text('<?php _e('Translate Now', 'claude-translator'); ?>');
        });
    });
    
    // Get translation status
    $('#claude-get-translation-status').on('click', function() {
        var button = $(this);
        button.prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'claude_get_translation_status',
            post_id: postId,
            nonce: claude_translator_ajax.nonce
        }, function(response) {
            if (response.success && response.data.length > 0) {
                var html = '<ul>';
                $.each(response.data, function(i, translation) {
                    html += '<li>' + translation.target_language + ': ' + translation.status + '</li>';
                });
                html += '</ul>';
                $('#claude-translation-status').html(html);
            } else {
                $('#claude-translation-status').html('<p><?php _e('No translations found.', 'claude-translator'); ?></p>');
            }
        }).always(function() {
            button.prop('disabled', false);
        });
    });
    
    // Unlink translation
    $('.claude-unlink-translation').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to unlink this translation?', 'claude-translator'); ?>')) {
            return;
        }
        
        var button = $(this);
        var postId = button.data('post-id');
        var relatedId = button.data('related-id');
        
        $.post(ajaxurl, {
            action: 'claude_unlink_translation',
            post_id: postId,
            related_post_id: relatedId,
            nonce: claude_translator_ajax.nonce
        }, function(response) {
            if (response.success) {
                button.closest('li').fadeOut();
            } else {
                alert('<?php _e('Error:', 'claude-translator'); ?> ' + response.data);
            }
        });
    });
    
    // Load translation history
    $('#claude-load-history').on('click', function() {
        var button = $(this);
        button.text('<?php _e('Loading...', 'claude-translator'); ?>');
        
        // This would load translation history from logs
        // Implementation depends on specific requirements
        setTimeout(function() {
            $('#claude-translation-history').html('<p><?php _e('History loaded (placeholder)', 'claude-translator'); ?></p>');
        }, 1000);
    });
});
</script>