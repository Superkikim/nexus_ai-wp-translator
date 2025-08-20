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
    
    <form id="nexus-ai-wp-translator-settings-form" method="post" action="options.php">
        <?php settings_fields('nexus_ai_wp_translator_settings'); ?>
        
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
                    
                    <tr id="model-selection-row" style="display: none;">
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
                                <?php _e('Select the Claude AI model to use for translations.', 'nexus-ai-wp-translator'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Language Settings Tab -->
            <div id="language-settings" class="tab-content">
                <h2><?php _e('Available Translation Languages', 'nexus-ai-wp-translator'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php _e('Available Languages', 'nexus-ai-wp-translator'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <?php foreach ($languages as $code => $name): ?>
                                    <label>
                                        <input type="checkbox" 
                                               name="nexus_ai_wp_translator_target_languages[]" 
                                               value="<?php echo esc_attr($code); ?>" 
                                               <?php checked(in_array($code, $target_languages)); ?> />
                                        <?php echo esc_html($name); ?>
                                    </label><br>
                                <?php endforeach; ?>
                            </fieldset>
                            <p class="description">
                                <?php _e('Select the languages you want to be available for translation. The source language for each post will be automatically detected or set at the post level.', 'nexus-ai-wp-translator'); ?>
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
                        <th scope="row"><?php _e('Auto Redirect to Translated Content', 'nexus-ai-wp-translator'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox"
                                           id="nexus_ai_wp_translator_auto_redirect"
                                           name="nexus_ai_wp_translator_auto_redirect"
                                           value="1"
                                           <?php checked(get_option('nexus_ai_wp_translator_auto_redirect', true)); ?> />
                                    <?php _e('Automatically redirect users to translated content based on their language preference', 'nexus-ai-wp-translator'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When enabled, users will be automatically redirected to the translated version of posts/pages based on their user preference (if logged in) or browser language.', 'nexus-ai-wp-translator'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Translation Status', 'nexus-ai-wp-translator'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox"
                                           id="nexus_ai_wp_translator_save_as_draft"
                                           name="nexus_ai_wp_translator_save_as_draft"
                                           value="1"
                                           <?php checked(get_option('nexus_ai_wp_translator_save_as_draft', false)); ?> />
                                    <?php _e('Save translations as drafts instead of publishing immediately', 'nexus-ai-wp-translator'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When enabled, translated posts will be saved as drafts for review before publishing. When disabled, translations will be published immediately with the same status as the source post.', 'nexus-ai-wp-translator'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>

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
                                    <br>
                                    <button type="button" id="nexus-ai-wp-clear-cache" class="button button-secondary" style="margin-top: 10px;">
                                        <?php _e('Clear Translation Cache', 'nexus-ai-wp-translator'); ?>
                                    </button>
                                    <span id="nexus-ai-wp-clear-cache-result" style="margin-left: 10px;"></span>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <p class="submit" id="form-submit-buttons">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Changes', 'nexus-ai-wp-translator'); ?>" />
            <button type="button" id="nexus-ai-wp-save-settings" class="button button-primary">
                <?php _e('Save Settings (AJAX)', 'nexus-ai-wp-translator'); ?>
            </button>
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
    var apiKeyValidated = false;
    
    // Determine current scenario based on server-side data
    var currentApiKey = window.nexusAiServerData ? window.nexusAiServerData.apiKey : '';
    var currentModel = window.nexusAiServerData ? window.nexusAiServerData.selectedModel : '';
    var currentScenario = 1; // Default: No API key, No model
    
    if (currentApiKey && currentModel) {
        currentScenario = 3; // Both API key and model exist
    } else if (currentApiKey && !currentModel) {
        currentScenario = 2; // API key exists, no model
    }
    
    console.log('NexusAI Debug: Detected scenario:', currentScenario, 'API key length:', currentApiKey.length, 'Model:', currentModel);
    
    // Handle initial state based on scenario
    handleInitialScenario(currentScenario);
    
    function handleInitialScenario(scenario) {
        var modelRow = $('#model-selection-row');
        
        switch(scenario) {
            case 1: // No API key, No model
                console.log('NexusAI Debug: Scenario 1 - No API key, no model');
                modelRow.hide(); // Keep model field hidden
                // No auto-testing
                break;
                
            case 2: // API key exists, No model  
                console.log('NexusAI Debug: Scenario 2 - API key exists, no model');
                modelRow.hide(); // Start hidden
                // Auto-test API key
                setTimeout(function() {
                    autoTestApiKey(currentApiKey, true); // true = show model field after success
                }, 500);
                break;
                
            case 3: // Both API key and model exist
                console.log('NexusAI Debug: Scenario 3 - Both API key and model exist');
                modelRow.hide(); // Start hidden
                // Auto-validate API key and show model field
                setTimeout(function() {
                    autoTestApiKey(currentApiKey, true); // true = show model field after success
                }, 500);
                break;
        }
    }
    
    // Function to manage save button visibility
    function manageSaveButtonVisibility(target) {
        var submitButtons = $('#form-submit-buttons');
        if (target === '#api-settings') {
            // Hide save buttons for API Settings tab (dynamic saving is active)
            submitButtons.hide();
        } else {
            // Show save buttons for other tabs
            submitButtons.show();
        }
    }
    
    // Initialize save button visibility on page load
    manageSaveButtonVisibility('#api-settings');
    
    // Tab switching with auto-test functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
        
        // Manage save button visibility
        manageSaveButtonVisibility(target);
        
        // Auto-test API when opening API Settings tab
        if (target === '#api-settings' && !apiKeyValidated) {
            var apiKey = $('#nexus_ai_wp_translator_api_key').val().trim();
            if (apiKey) {
                setTimeout(function() {
                    autoTestApiKey(apiKey);
                }, 300);
            }
        }
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
    
    // Auto-test API key function (silent test for page load)
    function autoTestApiKey(apiKey, showModelField) {
        console.log('NexusAI Debug: Auto-testing API key silently, showModelField:', showModelField);
        
        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_test_api',
            api_key: apiKey,
            nonce: nexus_ai_wp_translator_ajax.nonce
        }, function(response) {
            if (response.success) {
                console.log('NexusAI Debug: Auto-test successful, API key validated');
                apiKeyValidated = true;
                if (showModelField) {
                    loadModels(apiKey);
                }
            } else {
                console.log('NexusAI Debug: Auto-test failed');
                // For scenario 3, if validation fails, revert to scenario 2 behavior
                if (currentScenario === 3) {
                    console.log('NexusAI Debug: Scenario 3 validation failed, reverting to scenario 2 behavior');
                    // Clear the saved model since API key is invalid
                    if (window.nexusAiServerData) {
                        window.nexusAiServerData.selectedModel = '';
                    }
                    $('#model-selection-row').hide();
                }
            }
        }).fail(function() {
            console.log('NexusAI Debug: Auto-test failed with connection error');
            // For scenario 3, if validation fails, revert to scenario 2 behavior
            if (currentScenario === 3) {
                console.log('NexusAI Debug: Scenario 3 validation failed with connection error, reverting to scenario 2 behavior');
                if (window.nexusAiServerData) {
                    window.nexusAiServerData.selectedModel = '';
                }
                $('#model-selection-row').hide();
            }
        });
    }
    
    // Load models function
    function loadModels(apiKey) {
        console.log('NexusAI Debug: Loading models with API key');
        var modelSelect = $('#nexus_ai_wp_translator_model');
        var modelRow = $('#model-selection-row');
        
        // Use server-side data for current selection since the element might be hidden
        var currentSelection = window.nexusAiServerData ? window.nexusAiServerData.selectedModel : '';
        console.log('NexusAI Debug: Current selected model from server:', currentSelection);
        
        modelSelect.html('<option value="">Loading models...</option>');
        
        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_get_models',
            api_key: apiKey,
            nonce: nexus_ai_wp_translator_ajax.nonce
        }, function(response) {
            console.log('NexusAI Debug: Models response:', response);
            
            if (response.success && response.models) {
                modelSelect.empty();
                
                // Add default "Select model" option if no current selection
                if (!currentSelection) {
                    modelSelect.append('<option value="">Select model</option>');
                }
                
                $.each(response.models, function(modelId, displayName) {
                    var selected = (modelId === currentSelection) ? 'selected' : '';
                    modelSelect.append('<option value="' + modelId + '" ' + selected + '>' + displayName + '</option>');
                });
                
                // Show model selection row after successful load
                modelRow.show();
                apiKeyValidated = true;
                
            } else {
                // No models available, show error message
                modelSelect.html('<option value="">No models available</option>');
                modelRow.show();
                apiKeyValidated = true;
            }
        }).fail(function() {
            console.log('NexusAI Debug: Failed to load models');
            modelSelect.html('<option value="">Failed to load models</option>');
            modelRow.show();
            apiKeyValidated = true;
        });
    }
    
    // Dynamic save when model selection changes
    $('#nexus_ai_wp_translator_model').on('change', function() {
        if (apiKeyValidated && $(this).val()) {
            console.log('NexusAI Debug: Model selection changed, saving dynamically');
            // Update our local tracking of selected model
            if (window.nexusAiServerData) {
                window.nexusAiServerData.selectedModel = $(this).val();
                console.log('NexusAI Debug: Updated selectedModel to:', $(this).val());
            }
            dynamicSaveSettings();
        }
    });
    
    // Refresh models button
    $('#nexus-ai-wp-refresh-models').on('click', function() {
        var apiKey = $('#nexus_ai_wp_translator_api_key').val().trim();
        if (apiKey && apiKeyValidated) {
            console.log('NexusAI Debug: Refreshing models manually');
            loadModels(apiKey);
        } else {
            $('#api-test-result').html('<div class="notice notice-warning"><p><?php _e('Please test your API connection first.', 'nexus-ai-wp-translator'); ?></p></div>');
        }
    });
    
    // Dynamic save function
    function dynamicSaveSettings() {
        var form = $('#nexus-ai-wp-translator-settings-form');
        var formData = form.serialize();
        formData += '&action=nexus_ai_wp_save_settings&nonce=' + nexus_ai_wp_translator_ajax.nonce;
        
        $.post(nexus_ai_wp_translator_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                apiKeyChanged = false;
                console.log('Settings saved dynamically');
            }
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
});
}

// Start waiting for AJAX variables
waitForAjaxVars();
</script>

<script>
// Pass server-side values to JavaScript
window.nexusAiServerData = {
    selectedModel: '<?php echo esc_js($selected_model); ?>',
    apiKey: '<?php echo esc_js($api_key); ?>'
};
</script>
