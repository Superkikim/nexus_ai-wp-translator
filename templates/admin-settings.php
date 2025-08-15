<?php
/**
 * Admin Settings Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Nexus AI WP Translator Settings', 'nexus-ai-wp-translator'); ?></h1>

    <?php if (isset($message)) echo $message; ?>

    <form id="nexus-ai-wp-translator-settings-form" method="post" action="">
        <?php wp_nonce_field('nexus_ai_wp_translator_settings', 'nexus_ai_wp_translator_nonce'); ?>
        
        <div class="nexus-ai-wp-settings-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#api-settings" class="nav-tab nav-tab-active"><?php _e('API Settings', 'nexus-ai-wp-translator'); ?></a>
                <a href="#language-settings" class="nav-tab"><?php _e('Languages', 'nexus-ai-wp-translator'); ?></a>
                <a href="#behavior-settings" class="nav-tab"><?php _e('Behavior', 'nexus-ai-wp-translator'); ?></a>
                <a href="#performance-settings" class="nav-tab"><?php _e('Performance', 'nexus-ai-wp-translator'); ?></a>
            </nav>
            
            <!-- API Settings Tab -->
            <div id="api-settings" class="tab-content active">
                <h2><?php _e('Nexus AI API Configuration', 'nexus-ai-wp-translator'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="nexus_ai_wp_translator_api_key"><?php _e('API Key', 'nexus-ai-wp-translator'); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="nexus_ai_wp_translator_api_key" 
                                   name="nexus_ai_wp_translator_api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" 
                                   class="large-text" 
                                   autocomplete="off" />
                            <button type="button" id="nexus-ai-wp-test-api" class="button">
                                <?php _e('Test Connection', 'nexus-ai-wp-translator'); ?>
                            </button>
                            <button type="button" id="nexus-ai-wp-toggle-api-key" class="button">
                                <?php _e('Show', 'nexus-ai-wp-translator'); ?>
                            </button>
                            <p class="description">
                                <?php _e('Enter your Claude AI API key. You can get one from the Anthropic Console.', 'nexus-ai-wp-translator'); ?>
                            </p>
                            <div id="api-test-result"></div>
                        </td>
                    </tr>
                    
                    <tr id="model-selection-row">
                        <th scope="row">
                            <label for="nexus_ai_wp_translator_model"><?php _e('AI Model', 'nexus-ai-wp-translator'); ?></label>
                        </th>
                        <td>
                            <select id="nexus_ai_wp_translator_model" name="nexus_ai_wp_translator_model">
                                <?php if (empty($selected_model)): ?>
                                    <option value=""><?php _e('Please test API connection to load models', 'nexus-ai-wp-translator'); ?></option>
                                <?php else: ?>
                                    <option value="<?php echo esc_attr($selected_model); ?>" selected>
                                        <?php echo esc_html($selected_model); ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                            <button type="button" id="nexus-ai-wp-refresh-models" class="button" style="margin-left: 10px;">
                                <?php _e('Refresh Models', 'nexus-ai-wp-translator'); ?>
                            </button>
                            <p class="description">
                                <?php _e('Select the Claude AI model to use for translations. Test your API connection first to load available models.', 'nexus-ai-wp-translator'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Language Settings Tab -->
            <div id="language-settings" class="tab-content">
                <h2><?php _e('Language Configuration', 'nexus-ai-wp-translator'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="nexus_ai_wp_translator_source_language"><?php _e('Source Language', 'nexus-ai-wp-translator'); ?></label>
                        </th>
                        <td>
                            <select id="nexus_ai_wp_translator_source_language" name="nexus_ai_wp_translator_source_language">
                                <?php foreach ($languages as $code => $name): ?>
                                    <option value="<?php echo esc_attr($code); ?>" <?php selected($source_language, $code); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('The primary language of your content.', 'nexus-ai-wp-translator'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Target Languages', 'nexus-ai-wp-translator'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <?php foreach ($languages as $code => $name): ?>
                                    <?php if ($code !== $source_language): ?>
                                        <label>
                                            <input type="checkbox" 
                                                   name="nexus_ai_wp_translator_target_languages[]" 
                                                   value="<?php echo esc_attr($code); ?>" 
                                                   <?php checked(in_array($code, $target_languages)); ?> />
                                            <?php echo esc_html($name); ?>
                                        </label><br>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </fieldset>
                            <p class="description">
                                <?php _e('Select languages to automatically translate your content into.', 'nexus-ai-wp-translator'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Behavior Settings Tab -->
            <div id="behavior-settings" class="tab-content">
                <h2><?php _e('Translation Behavior', 'nexus-ai-wp-translator'); ?></h2>
                
                <table class="form-table">

                    
                    <tr>
                        <th scope="row"><?php _e('SEO Friendly URLs', 'nexus-ai-wp-translator'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" 
                                           id="nexus_ai_wp_translator_seo_friendly_urls" 
                                           name="nexus_ai_wp_translator_seo_friendly_urls" 
                                           value="1" 
                                           <?php checked($seo_friendly_urls); ?> />
                                    <?php _e('Enable SEO-friendly URLs for translations', 'nexus-ai-wp-translator'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('URLs will be structured as /language-code/post-slug/ (e.g., /es/my-post/).', 'nexus-ai-wp-translator'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Performance Settings Tab -->
            <div id="performance-settings" class="tab-content">
                <h2><?php _e('Performance & Rate Limiting', 'nexus-ai-wp-translator'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="nexus_ai_wp_translator_throttle_limit"><?php _e('API Call Limit', 'nexus-ai-wp-translator'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="nexus_ai_wp_translator_throttle_limit" 
                                   name="nexus_ai_wp_translator_throttle_limit" 
                                   value="<?php echo esc_attr($throttle_limit); ?>" 
                                   min="1" 
                                   max="1000" 
                                   class="small-text" />
                            <p class="description">
                                <?php _e('Maximum number of API calls allowed per time period.', 'nexus-ai-wp-translator'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="nexus_ai_wp_translator_throttle_period"><?php _e('Time Period (seconds)', 'nexus-ai-wp-translator'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="nexus_ai_wp_translator_throttle_period" 
                                   name="nexus_ai_wp_translator_throttle_period" 
                                   value="<?php echo esc_attr($throttle_period); ?>" 
                                   min="60" 
                                   max="86400" 
                                   class="small-text" />
                            <p class="description">
                                <?php _e('Time period for the rate limit (minimum 60 seconds).', 'nexus-ai-wp-translator'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="nexus_ai_wp_translator_retry_attempts"><?php _e('Retry Attempts', 'nexus-ai-wp-translator'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="nexus_ai_wp_translator_retry_attempts" 
                                   name="nexus_ai_wp_translator_retry_attempts" 
                                   value="<?php echo esc_attr($retry_attempts); ?>" 
                                   min="1" 
                                   max="10" 
                                   class="small-text" />
                            <p class="description">
                                <?php _e('Number of times to retry failed API calls.', 'nexus-ai-wp-translator'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Cache Translations', 'nexus-ai-wp-translator'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" 
                                           id="nexus_ai_wp_translator_cache_translations" 
                                           name="nexus_ai_wp_translator_cache_translations" 
                                           value="1" 
                                           <?php checked($cache_translations); ?> />
                                    <?php _e('Cache translations to improve performance', 'nexus-ai-wp-translator'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When enabled, translations are cached to reduce API calls for repeated content.', 'nexus-ai-wp-translator'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Debug Section -->
        <div class="nexus-ai-wp-debug-section" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
            <h2><?php _e('Debug Tools', 'nexus-ai-wp-translator'); ?></h2>
            <p class="description">
                <?php _e('Use these tools to diagnose translation issues and verify plugin functionality.', 'nexus-ai-wp-translator'); ?>
            </p>
            
            <div class="debug-tools">
                <button type="button" id="nexus-ai-wp-debug-plugin" class="button button-secondary">
                    <?php _e('Run Plugin Debug', 'nexus-ai-wp-translator'); ?>
                </button>
                <button type="button" id="nexus-ai-wp-test-translation" class="button button-secondary">
                    <?php _e('Test Translation System', 'nexus-ai-wp-translator'); ?>
                </button>
                <button type="button" id="nexus-ai-wp-check-database" class="button button-secondary">
                    <?php _e('Check Database', 'nexus-ai-wp-translator'); ?>
                </button>
            </div>
            
            <div id="nexus-ai-wp-debug-results" style="margin-top: 15px; padding: 10px; background: white; border: 1px solid #ccc; border-radius: 4px; display: none;">
                <h4><?php _e('Debug Results:', 'nexus-ai-wp-translator'); ?></h4>
                <div id="nexus-ai-wp-debug-output"></div>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Changes', 'nexus-ai-wp-translator'); ?>" />
        </p>
    </form>
</div>

<script>
console.log('NexusAI Debug: Inline script in admin-settings.php started');

// Wait for variables to be available
function waitForAjaxVars() {
    if (typeof nexus_ai_wp_translator_ajax === 'undefined') {
        console.log('NexusAI Debug: Waiting for AJAX variables...');
        setTimeout(waitForAjaxVars, 100);
        return;
    }
    console.log('NexusAI Debug: AJAX variables available in inline script:', nexus_ai_wp_translator_ajax);
    initInlineScript();
}

function initInlineScript() {
    console.log('NexusAI Debug: Initializing inline script functionality');

jQuery(document).ready(function($) {
    console.log('NexusAI Debug: Inline script jQuery ready');
    
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
    $('#nexus_ai_wp_translator_api_key').on('input', function() {
        apiKeyChanged = true;
        $('#api-test-result').empty();
    });
    
    // Auto-save API key on blur
    $('#nexus_ai_wp_translator_api_key').on('blur', function() {
        if (apiKeyChanged) {
            saveApiKey();
        }
    });
    
    function saveApiKey() {
        var apiKey = $('#nexus_ai_wp_translator_api_key').val().trim();
        if (!apiKey) return;
        
        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_save_settings',
            nexus_ai_wp_translator_api_key: apiKey,
            nonce: nexus_ai_wp_translator_ajax.nonce
        }, function(response) {
            if (response.success) {
                apiKeyChanged = false;
                console.log('API key saved automatically');
            }
        });
    }
    
    // API key toggle
    $('#nexus-ai-wp-toggle-api-key').on('click', function() {
        var input = $('#nexus_ai_wp_translator_api_key');
        var type = input.attr('type');
        var button = $(this);
        
        if (type === 'password') {
            input.attr('type', 'text');
            button.text('<?php _e('Hide', 'nexus-ai-wp-translator'); ?>');
        } else {
            input.attr('type', 'password');
            button.text('<?php _e('Show', 'nexus-ai-wp-translator'); ?>');
        }
    });
    
    // Test API connection
    $('#nexus-ai-wp-test-api').on('click', function() {
        // Save API key first if changed
        if (apiKeyChanged) {
            saveApiKey();
        }
        
        var button = $(this);
        var apiKey = $('#nexus_ai_wp_translator_api_key').val();
        var resultDiv = $('#api-test-result');
        
        if (!apiKey) {
            resultDiv.html('<div class="notice notice-error"><p><?php _e('Please enter an API key first.', 'nexus-ai-wp-translator'); ?></p></div>');
            return;
        }
        
        button.prop('disabled', true).text('<?php _e('Testing...', 'claude-translator'); ?>');
        button.prop('disabled', true).text('Testing...');
        resultDiv.html('<div class="notice notice-info"><p><?php _e('Testing API connection...', 'nexus-ai-wp-translator'); ?></p></div>');
        
        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_test_api',
            api_key: apiKey,
            nonce: nexus_ai_wp_translator_ajax.nonce
        }, function(response) {
            var noticeClass = response.success ? 'notice-success' : 'notice-error';
            resultDiv.html('<div class="notice ' + noticeClass + '"><p>' + response.message + '</p></div>');
            
            // Load models after successful API test
            if (response.success) {
                console.log('NexusAI Debug: API test successful, loading models...');
                loadModels(apiKey);
            }
        }).fail(function() {
            resultDiv.html('<div class="notice notice-error"><p>Connection failed. Please check your API key.</p></div>');
        }).always(function() {
            button.prop('disabled', false).text('Test Connection');
        });
    });
    
    // Load models function
    function loadModels(apiKey) {
        console.log('NexusAI Debug: Loading models with API key');
        var modelSelect = $('#nexus_ai_wp_translator_model');
        var currentSelection = modelSelect.val();
        
        modelSelect.html('<option value="">Loading models...</option>');
        
        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_get_models',
            api_key: apiKey,
            nonce: nexus_ai_wp_translator_ajax.nonce
        }, function(response) {
            console.log('NexusAI Debug: Models response:', response);
            
            if (response.success && response.models) {
                modelSelect.empty();
                $.each(response.models, function(modelId, displayName) {
                    var selected = (modelId === currentSelection || (modelId === 'claude-3-5-sonnet-20241022' && !currentSelection)) ? 'selected' : '';
                    modelSelect.append('<option value="' + modelId + '" ' + selected + '>' + displayName + '</option>');
                });
            } else {
                // Fallback models
                modelSelect.html('<option value="claude-3-5-sonnet-20241022" selected>Claude 3.5 Sonnet (Latest)</option>');
            }
        }).fail(function() {
            console.log('NexusAI Debug: Failed to load models, using fallback');
            modelSelect.html('<option value="claude-3-5-sonnet-20241022" selected>Claude 3.5 Sonnet (Latest)</option>');
        });
    }
    
    // AJAX save settings
    $('#nexus-ai-wp-save-settings').on('click', function() {
        var button = $(this);
        var form = $('#nexus-ai-wp-translator-settings-form');
        
        button.prop('disabled', true).text('Saving...');
        
        var formData = form.serialize();
        formData += '&action=nexus_ai_wp_save_settings&nonce=' + nexus_ai_wp_translator_ajax.nonce;
        
        $.post(nexus_ai_wp_translator_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                apiKeyChanged = false;
                $('<div class="notice notice-success is-dismissible"><p>' + response.data + '</p></div>')
                    .insertAfter('h1').delay(3000).fadeOut();
            } else {
                $('<div class="notice notice-error is-dismissible"><p>' + response.data + '</p></div>')
                    .insertAfter('h1');
            }
        }).always(function() {
            button.prop('disabled', false).text('Save Settings (AJAX)');
        });
    });
    
    // Debug Tools
    $('#nexus-ai-wp-debug-plugin').on('click', function() {
        var button = $(this);
        var resultsDiv = $('#nexus-ai-wp-debug-results');
        var outputDiv = $('#nexus-ai-wp-debug-output');
        
        button.prop('disabled', true).text('Running Debug...');
        resultsDiv.show();
        outputDiv.html('<div class="nexus-ai-wp-spinner"></div> Running comprehensive plugin debug...');
        
        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_debug_plugin',
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                outputDiv.html('<pre style="background: #f0f0f0; padding: 10px; border-radius: 4px; max-height: 400px; overflow-y: auto;">' + response.data + '</pre>');
            } else {
                outputDiv.html('<div class="notice notice-error"><p>Debug failed: ' + response.data + '</p></div>');
            }
        })
        .fail(function() {
            outputDiv.html('<div class="notice notice-error"><p>Debug request failed</p></div>');
        })
        .always(function() {
            button.prop('disabled', false).text('Run Plugin Debug');
        });
    });
    
    $('#nexus-ai-wp-test-translation').on('click', function() {
        var button = $(this);
        var resultsDiv = $('#nexus-ai-wp-debug-results');
        var outputDiv = $('#nexus-ai-wp-debug-output');
        
        button.prop('disabled', true).text('Testing...');
        resultsDiv.show();
        outputDiv.html('<div class="nexus-ai-wp-spinner"></div> Testing translation system...');
        
        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_test_translation_system',
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                outputDiv.html('<pre style="background: #f0f0f0; padding: 10px; border-radius: 4px; max-height: 400px; overflow-y: auto;">' + response.data + '</pre>');
            } else {
                outputDiv.html('<div class="notice notice-error"><p>Test failed: ' + response.data + '</p></div>');
            }
        })
        .fail(function() {
            outputDiv.html('<div class="notice notice-error"><p>Test request failed</p></div>');
        })
        .always(function() {
            button.prop('disabled', false).text('Test Translation System');
        });
    });
    
    $('#nexus-ai-wp-check-database').on('click', function() {
        var button = $(this);
        var resultsDiv = $('#nexus-ai-wp-debug-results');
        var outputDiv = $('#nexus-ai-wp-debug-output');
        
        button.prop('disabled', true).text('Checking...');
        resultsDiv.show();
        outputDiv.html('<div class="nexus-ai-wp-spinner"></div> Checking database...');
        
        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_check_database',
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                outputDiv.html('<pre style="background: #f0f0f0; padding: 10px; border-radius: 4px; max-height: 400px; overflow-y: auto;">' + response.data + '</pre>');
            } else {
                outputDiv.html('<div class="notice notice-error"><p>Database check failed: ' + response.data + '</p></div>');
            }
        })
        .fail(function() {
            outputDiv.html('<div class="notice notice-error"><p>Database check request failed</p></div>');
        })
        .always(function() {
            button.prop('disabled', false).text('Check Database');
        });
    });
});
}

// Start waiting for AJAX variables
waitForAjaxVars();
</script>