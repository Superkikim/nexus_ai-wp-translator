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
        <label><strong><?php _e('Translation Actions:', 'nexus-ai-wp-translator'); ?></strong></label>
        
        <?php if (get_option('nexus_ai_wp_translator_api_key')): ?>
            <div class="nexus-ai-wp-translation-actions">
                <div class="target-languages">
                    <label><?php _e('Translate to:', 'nexus-ai-wp-translator'); ?></label>
                    <?php if (!empty($target_languages)): ?>
                        <?php foreach ($target_languages as $lang_code): ?>
                            <?php if ($lang_code !== $post_language): ?>
                                <label>
                                    <input type="checkbox" 
                                           name="nexus_ai_wp_translate_to[]" 
                                           value="<?php echo esc_attr($lang_code); ?>" 
                                           class="nexus-ai-wp-target-language" />
                                    <?php echo esc_html($languages[$lang_code] ?? $lang_code); ?>
                                </label><br>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="description">
                            <?php printf(
                                __('No target languages configured. Please <a href="%s">configure target languages</a> in settings.', 'nexus-ai-wp-translator'),
                                admin_url('admin.php?page=nexus-ai-wp-translator-settings')
                            ); ?>
                        </p>
                    <?php endif; ?>
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
console.log('NexusAI Debug: Meta box script loaded');
console.log('NexusAI Debug: Meta box - jQuery available:', typeof jQuery !== 'undefined');

// Check if admin script is available with better debugging
function checkAdminScript() {
    if (typeof window.NexusAIWPTranslatorAdmin !== 'undefined') {
        console.log('NexusAI Debug: Meta box - Admin script available: true');
        return true;
    } else {
        console.log('NexusAI Debug: Meta box - Admin script available: false');
        console.log('NexusAI Debug: Available globals containing "nexus":', 
            Object.keys(window).filter(k => k.toLowerCase().includes('nexus')));
        return false;
    }
}

checkAdminScript();

jQuery(document).ready(function($) {
    console.log('NexusAI Debug: Meta box jQuery ready');
    
    // Check if admin script is available with retry mechanism
    var adminScriptAvailable = checkAdminScript();
    
    if (!adminScriptAvailable) {
        console.log('NexusAI Debug: Admin script not available on document ready, trying again in 500ms...');
        setTimeout(function() {
            adminScriptAvailable = checkAdminScript();
            if (!adminScriptAvailable) {
                console.error('NexusAI Debug: Admin script still not available after retry!');
            }
        }, 500);
        // Continue with basic functionality
    } else {
        console.log('NexusAI Debug: Admin script available');
    }
    
    var postId = <?php echo intval($post->ID); ?>;
    
    // Translate post
    $('#nexus-ai-wp-translate-post').on('click', function() {
        console.log('NexusAI Debug: Translate button clicked in meta box');
        var button = $(this);
        var targetLanguages = [];
        
        $('.nexus-ai-wp-target-language:checked').each(function() {
            targetLanguages.push($(this).val());
        });
        
        console.log('NexusAI Debug: Target languages:', targetLanguages);
        
        if (targetLanguages.length === 0) {
            alert('<?php _e('Please select at least one target language.', 'nexus-ai-wp-translator'); ?>');
            return;
        }
        
        // Show progress popup if available
        if (typeof window.NexusAIWPTranslatorAdmin !== 'undefined') {
            console.log('NexusAI Debug: About to show progress popup from meta box');
            window.NexusAIWPTranslatorAdmin.showTranslationProgress(targetLanguages);
        }
        
        button.prop('disabled', true).text('<?php _e('Translating...', 'nexus-ai-wp-translator'); ?>');
        
        console.log('NexusAI Debug: Making AJAX request from meta box');
        
        // Check if AJAX variables are available
        var ajaxUrl = (typeof nexus_ai_wp_translator_ajax !== 'undefined') ? nexus_ai_wp_translator_ajax.ajax_url : ajaxurl;
        var nonce = (typeof nexus_ai_wp_translator_ajax !== 'undefined') ? nexus_ai_wp_translator_ajax.nonce : '';
        
        $.post(ajaxUrl, {
            action: 'nexus_ai_wp_translate_post',
            post_id: postId,
            target_languages: targetLanguages,
            nonce: nonce
        }, function(response) {
            console.log('NexusAI Debug: Translation response in meta box:', response);
            
            // Update progress popup with results
            if (typeof window.NexusAIWPTranslatorAdmin !== 'undefined') {
                window.NexusAIWPTranslatorAdmin.updateTranslationProgress(response);
            }
            
            if (response.success) {
                setTimeout(function() {
                    location.reload();
                }, 3000);
            }
        }).fail(function() {
            console.log('NexusAI Debug: Translation failed in meta box');
            if (typeof window.NexusAIWPTranslatorAdmin !== 'undefined') {
                window.NexusAIWPTranslatorAdmin.updateTranslationProgress({
                    success: false,
                    message: 'Network error occurred'
                });
            }
        }).always(function() {
            button.prop('disabled', false).text('<?php _e('Translate Now', 'nexus-ai-wp-translator'); ?>');
        });
    });
    
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