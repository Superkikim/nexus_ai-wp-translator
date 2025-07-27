<?php
/**
 * Admin Settings Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Nexus AI WP Translator Settings', 'claude-translator'); ?></h1>
    
    <form id="claude-translator-settings-form" method="post" action="options.php">
        <?php settings_fields('claude_translator_settings'); ?>
        
        <div class="claude-settings-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#api-settings" class="nav-tab nav-tab-active"><?php _e('API Settings', 'claude-translator'); ?></a>
                <a href="#language-settings" class="nav-tab"><?php _e('Languages', 'claude-translator'); ?></a>
                <a href="#behavior-settings" class="nav-tab"><?php _e('Behavior', 'claude-translator'); ?></a>
                <a href="#performance-settings" class="nav-tab"><?php _e('Performance', 'claude-translator'); ?></a>
            </nav>
            
            <!-- API Settings Tab -->
            <div id="api-settings" class="tab-content active">
                <h2><?php _e('Claude AI API Configuration', 'claude-translator'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="claude_translator_api_key"><?php _e('API Key', 'claude-translator'); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="claude_translator_api_key" 
                                   name="claude_translator_api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" 
                                   class="large-text" 
                                   autocomplete="off" />
                            <button type="button" id="claude-test-api" class="button">
                                <?php _e('Test Connection', 'claude-translator'); ?>
                            </button>
                            <button type="button" id="claude-toggle-api-key" class="button">
                                <?php _e('Show', 'claude-translator'); ?>
                            </button>
                            <p class="description">
                                <?php _e('Enter your Claude AI API key. You can get one from the Anthropic Console.', 'claude-translator'); ?>
                            </p>
                            <div id="api-test-result"></div>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Language Settings Tab -->
            <div id="language-settings" class="tab-content">
                <h2><?php _e('Language Configuration', 'claude-translator'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="claude_translator_source_language"><?php _e('Source Language', 'claude-translator'); ?></label>
                        </th>
                        <td>
                            <select id="claude_translator_source_language" name="claude_translator_source_language">
                                <?php foreach ($languages as $code => $name): ?>
                                    <option value="<?php echo esc_attr($code); ?>" <?php selected($source_language, $code); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('The primary language of your content.', 'claude-translator'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Target Languages', 'claude-translator'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <?php foreach ($languages as $code => $name): ?>
                                    <?php if ($code !== $source_language): ?>
                                        <label>
                                            <input type="checkbox" 
                                                   name="claude_translator_target_languages[]" 
                                                   value="<?php echo esc_attr($code); ?>" 
                                                   <?php checked(in_array($code, $target_languages)); ?> />
                                            <?php echo esc_html($name); ?>
                                        </label><br>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </fieldset>
                            <p class="description">
                                <?php _e('Select languages to automatically translate your content into.', 'claude-translator'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Behavior Settings Tab -->
            <div id="behavior-settings" class="tab-content">
                <h2><?php _e('Translation Behavior', 'claude-translator'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Auto Translation', 'claude-translator'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" 
                                           id="claude_translator_auto_translate" 
                                           name="claude_translator_auto_translate" 
                                           value="1" 
                                           <?php checked($auto_translate); ?> />
                                    <?php _e('Automatically translate posts when published', 'claude-translator'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When enabled, posts will be automatically translated to all target languages upon publication.', 'claude-translator'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('SEO Friendly URLs', 'claude-translator'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" 
                                           id="claude_translator_seo_friendly_urls" 
                                           name="claude_translator_seo_friendly_urls" 
                                           value="1" 
                                           <?php checked($seo_friendly_urls); ?> />
                                    <?php _e('Enable SEO-friendly URLs for translations', 'claude-translator'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('URLs will be structured as /language-code/post-slug/ (e.g., /es/my-post/).', 'claude-translator'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Performance Settings Tab -->
            <div id="performance-settings" class="tab-content">
                <h2><?php _e('Performance & Rate Limiting', 'claude-translator'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="claude_translator_throttle_limit"><?php _e('API Call Limit', 'claude-translator'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="claude_translator_throttle_limit" 
                                   name="claude_translator_throttle_limit" 
                                   value="<?php echo esc_attr($throttle_limit); ?>" 
                                   min="1" 
                                   max="1000" 
                                   class="small-text" />
                            <p class="description">
                                <?php _e('Maximum number of API calls allowed per time period.', 'claude-translator'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="claude_translator_throttle_period"><?php _e('Time Period (seconds)', 'claude-translator'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="claude_translator_throttle_period" 
                                   name="claude_translator_throttle_period" 
                                   value="<?php echo esc_attr($throttle_period); ?>" 
                                   min="60" 
                                   max="86400" 
                                   class="small-text" />
                            <p class="description">
                                <?php _e('Time period for the rate limit (minimum 60 seconds).', 'claude-translator'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="claude_translator_retry_attempts"><?php _e('Retry Attempts', 'claude-translator'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="claude_translator_retry_attempts" 
                                   name="claude_translator_retry_attempts" 
                                   value="<?php echo esc_attr($retry_attempts); ?>" 
                                   min="1" 
                                   max="10" 
                                   class="small-text" />
                            <p class="description">
                                <?php _e('Number of times to retry failed API calls.', 'claude-translator'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Cache Translations', 'claude-translator'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" 
                                           id="claude_translator_cache_translations" 
                                           name="claude_translator_cache_translations" 
                                           value="1" 
                                           <?php checked($cache_translations); ?> />
                                    <?php _e('Cache translations to improve performance', 'claude-translator'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When enabled, translations are cached to reduce API calls for repeated content.', 'claude-translator'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Changes', 'claude-translator'); ?>" />
            <button type="button" id="claude-save-settings" class="button button-primary">
                <?php _e('Save Settings (AJAX)', 'claude-translator'); ?>
            </button>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    var apiKeyChanged = false;
    
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Track API key changes
    $('#claude_translator_api_key').on('input', function() {
        apiKeyChanged = true;
        $('#api-test-result').empty();
    });
    
    // Auto-save API key on blur
    $('#claude_translator_api_key').on('blur', function() {
        if (apiKeyChanged) {
            saveApiKey();
        }
    });
    
    function saveApiKey() {
        var apiKey = $('#claude_translator_api_key').val().trim();
        if (!apiKey) return;
        
        $.post(ajaxurl, {
            action: 'claude_save_settings',
            claude_translator_api_key: apiKey,
            nonce: claude_translator_ajax.nonce
        }, function(response) {
            if (response.success) {
                apiKeyChanged = false;
                console.log('API key saved automatically');
            }
        });
    }
    
    // API key toggle
    $('#claude-toggle-api-key').on('click', function() {
        var input = $('#claude_translator_api_key');
        var type = input.attr('type');
        var button = $(this);
        
        if (type === 'password') {
            input.attr('type', 'text');
            button.text('<?php _e('Hide', 'claude-translator'); ?>');
        } else {
            input.attr('type', 'password');
            button.text('<?php _e('Show', 'claude-translator'); ?>');
        }
    });
    
    // Test API connection
    $('#claude-test-api').on('click', function() {
        // Save API key first if changed
        if (apiKeyChanged) {
            saveApiKey();
        }
        
        var button = $(this);
        var apiKey = $('#claude_translator_api_key').val();
        var resultDiv = $('#api-test-result');
        
        if (!apiKey) {
            resultDiv.html('<div class="notice notice-error"><p><?php _e('Please enter an API key first.', 'claude-translator'); ?></p></div>');
            return;
        }
        
        button.prop('disabled', true).text('<?php _e('Testing...', 'claude-translator'); ?>');
        resultDiv.html('<div class="notice notice-info"><p><?php _e('Testing API connection...', 'claude-translator'); ?></p></div>');
        
        $.post(ajaxurl, {
            action: 'claude_test_api',
            api_key: apiKey,
            nonce: claude_translator_ajax.nonce
        }, function(response) {
            var noticeClass = response.success ? 'notice-success' : 'notice-error';
            resultDiv.html('<div class="notice ' + noticeClass + '"><p>' + response.message + '</p></div>');
        }).fail(function() {
            resultDiv.html('<div class="notice notice-error"><p><?php _e('Connection failed. Please check your API key.', 'claude-translator'); ?></p></div>');
        }).always(function() {
            button.prop('disabled', false).text('<?php _e('Test Connection', 'claude-translator'); ?>');
        });
    });
    
    // AJAX save settings
    $('#claude-save-settings').on('click', function() {
        var button = $(this);
        var form = $('#claude-translator-settings-form');
        
        button.prop('disabled', true).text('<?php _e('Saving...', 'claude-translator'); ?>');
        
        var formData = form.serialize();
        formData += '&action=claude_save_settings&nonce=' + claude_translator_ajax.nonce;
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                apiKeyChanged = false;
                $('<div class="notice notice-success is-dismissible"><p>' + response.data + '</p></div>')
                    .insertAfter('h1').delay(3000).fadeOut();
            } else {
                $('<div class="notice notice-error is-dismissible"><p>' + response.data + '</p></div>')
                    .insertAfter('h1');
            }
        }).always(function() {
            button.prop('disabled', false).text('<?php _e('Save Settings (AJAX)', 'claude-translator'); ?>');
        });
    });
});
</script>