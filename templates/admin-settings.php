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
                <a href="#global-settings" class="nav-tab nav-tab-active"><?php _e('Global Settings', 'nexus-ai-wp-translator'); ?></a>
                <a href="#api-settings" class="nav-tab"><?php _e('API Settings', 'nexus-ai-wp-translator'); ?></a>
                <a href="#language-settings" class="nav-tab"><?php _e('Languages', 'nexus-ai-wp-translator'); ?></a>
                <a href="#performance-settings" class="nav-tab"><?php _e('Performance', 'nexus-ai-wp-translator'); ?></a>
            </nav>
            
            <!-- Global Settings Tab -->
            <div id="global-settings" class="tab-content active">
                <h2><?php _e('Global Translation Settings', 'nexus-ai-wp-translator'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Save as draft', 'nexus-ai-wp-translator'); ?></th>
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
                </table>
            </div>
            
            <!-- API Settings Tab -->
            <div id="api-settings" class="tab-content">
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
                            <p class="description">
                                <?php _e('Select languages to enable for translation. Posts can be translated into any of the selected languages.', 'nexus-ai-wp-translator'); ?>
                            </p>
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
                // No background testing - no API key configured
                break;
                
            case 2: // API key exists, No model  
                console.log('NexusAI Debug: Scenario 2 - API key exists, no model');
                modelRow.hide(); // Start hidden
                // Perform background API test
                performBackgroundApiTest(currentApiKey);
                break;
                
            case 3: // Both API key and model exist
                console.log('NexusAI Debug: Scenario 3 - Both API key and model exist');
                modelRow.hide(); // Start hidden
                // Perform background API test
                performBackgroundApiTest(currentApiKey);
                break;
        }
    }
    
    // Semaphore management functions
    function setApiErrorSemaphore(message) {
        var errorData = {
            hasError: true,
            message: message,
            timestamp: Date.now()
        };
        sessionStorage.setItem('nexus_ai_api_error', JSON.stringify(errorData));
        console.log('NexusAI Debug: API error semaphore set:', errorData);
    }
    
    function getApiErrorSemaphore() {
        var errorData = sessionStorage.getItem('nexus_ai_api_error');
        if (errorData) {
            try {
                return JSON.parse(errorData);
            } catch (e) {
                console.log('NexusAI Debug: Error parsing semaphore data:', e);
                return null;
            }
        }
        return null;
    }
    
    function clearApiErrorSemaphore() {
        sessionStorage.removeItem('nexus_ai_api_error');
        console.log('NexusAI Debug: API error semaphore cleared');
    }
    
    // Background API testing function
    function performBackgroundApiTest(apiKey) {
        console.log('NexusAI Debug: Performing background API test');
        
        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_test_api',
            api_key: apiKey,
            nonce: nexus_ai_wp_translator_ajax.nonce
        }, function(response) {
            if (response.success) {
                console.log('NexusAI Debug: Background API test successful');
                // Clear any existing error semaphore
                clearApiErrorSemaphore();
                apiKeyValidated = true;
                // Load models silently
                loadModels(apiKey);
            } else {
                console.log('NexusAI Debug: Background API test failed:', response.message);
                // Set error semaphore
                setApiErrorSemaphore(response.message || 'API connection failed');
                // Show error popup
                showApiErrorPopup(response.message || 'API connection failed');
            }
        }).fail(function() {
            console.log('NexusAI Debug: Background API test failed with connection error');
            var errorMsg = 'Connection failed. Please check your API key and network connection.';
            setApiErrorSemaphore(errorMsg);
            showApiErrorPopup(errorMsg);
        });
    }
    
    // Error popup function
    function showApiErrorPopup(message) {
        console.log('NexusAI Debug: Showing API error popup');
        
        // Create modal HTML if it doesn't exist
        if ($('#nexus-api-error-modal').length === 0) {
            var modalHtml = 
                '<div id="nexus-api-error-modal" style="display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">' +
                    '<div style="position: relative; margin: 10% auto; padding: 20px; width: 60%; max-width: 500px; background-color: white; border-radius: 5px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">' +
                        '<div style="display: flex; align-items: center; margin-bottom: 15px;">' +
                            '<span class="dashicons dashicons-warning" style="color: #d63638; font-size: 24px; margin-right: 10px;"></span>' +
                            '<h3 style="margin: 0; color: #d63638;">API Configuration Issue</h3>' +
                        '</div>' +
                        '<p id="nexus-api-error-message" style="margin-bottom: 20px; line-height: 1.5;"></p>' +
                        '<div style="text-align: right;">' +
                            '<button id="nexus-api-error-ok" class="button button-primary">OK - Go to API Settings</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            $('body').append(modalHtml);
        }
        
        // Set message and show modal
        $('#nexus-api-error-message').text(message);
        $('#nexus-api-error-modal').fadeIn(300);
        
        // Handle OK button click
        $('#nexus-api-error-ok').off('click').on('click', function() {
            $('#nexus-api-error-modal').fadeOut(300);
            // Switch to API Settings tab
            switchToApiSettingsTab();
        });
    }
    
    // Function to switch to API Settings tab programmatically
    function switchToApiSettingsTab() {
        console.log('NexusAI Debug: Switching to API Settings tab');
        
        // Update tab navigation
        $('.nav-tab').removeClass('nav-tab-active');
        $('a[href="#api-settings"]').addClass('nav-tab-active');
        
        // Update tab content
        $('.tab-content').removeClass('active');
        $('#api-settings').addClass('active');
        
        // Check for error semaphore and display message
        displayApiErrorIfExists();
    }
    
    // Function to display API error if semaphore exists
    function displayApiErrorIfExists() {
        var errorData = getApiErrorSemaphore();
        if (errorData && errorData.hasError) {
            console.log('NexusAI Debug: Displaying API error from semaphore:', errorData.message);
            var resultDiv = $('#api-test-result');
            resultDiv.html('<div class="notice notice-error"><p><strong>API Error:</strong> ' + errorData.message + '</p></div>');
        }
    }
    
    
    // Auto-save functionality for all form fields
    function initAutoSave() {
        // Handle all checkboxes in the form
        $('#nexus-ai-wp-translator-settings-form input[type="checkbox"]').on('change', function() {
            console.log('NexusAI Debug: Checkbox changed, auto-saving:', $(this).attr('name'));
            dynamicSaveSettings();
        });
        
        // Handle number inputs with debouncing (Performance Settings tab)
        var saveTimeout;
        $('#nexus-ai-wp-translator-settings-form input[type="number"]').on('input', function() {
            var fieldName = $(this).attr('name');
            console.log('NexusAI Debug: Number input changed:', fieldName);
            
            // Clear previous timeout
            clearTimeout(saveTimeout);
            
            // Set new timeout to save after user stops typing (500ms delay)
            saveTimeout = setTimeout(function() {
                console.log('NexusAI Debug: Auto-saving after number input change');
                dynamicSaveSettings();
            }, 500);
        });
        
        console.log('NexusAI Debug: Auto-save initialized for all form fields');
    }
    
    // Initialize auto-save for all form fields
    initAutoSave();
    
    // Tab switching with auto-test functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
        
        // Check for existing error semaphore when entering API Settings tab
        if (target === '#api-settings') {
            displayApiErrorIfExists();
            
            // Auto-test API when opening API Settings tab (only if not yet validated)
            if (!apiKeyValidated) {
                var apiKey = $('#nexus_ai_wp_translator_api_key').val().trim();
                if (apiKey) {
                    setTimeout(function() {
                        autoTestApiKey(apiKey);
                    }, 300);
                }
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
        var button = $(this);
        var apiKey = $('#nexus_ai_wp_translator_api_key').val();
        var resultDiv = $('#api-test-result');
        
        if (!apiKey) {
            resultDiv.html('<div class="notice notice-error"><p><?php _e('Please enter an API key first.', 'nexus-ai-wp-translator'); ?></p></div>');
            return;
        }
        
        // Always save settings first, then test
        console.log('NexusAI Debug: Saving settings before API test...');
        button.prop('disabled', true).text('<?php _e('Saving & Testing...', 'nexus-ai-wp-translator'); ?>');
        resultDiv.html('<div class="notice notice-info"><p><?php _e('Saving settings and testing API connection...', 'nexus-ai-wp-translator'); ?></p></div>');
        
        // First save all current settings
        dynamicSaveSettings(function() {
            // Then test API connection
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
                    // Clear any existing error semaphore on successful validation
                    clearApiErrorSemaphore();
                    apiKeyValidated = true;
                    loadModels(apiKey);
                }
            }).fail(function() {
                resultDiv.html('<div class="notice notice-error"><p>Connection failed. Please check your API key.</p></div>');
            }).always(function() {
                button.prop('disabled', false).text('<?php _e('Test Connection', 'nexus-ai-wp-translator'); ?>');
            });
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
    function dynamicSaveSettings(callback) {
        var form = $('#nexus-ai-wp-translator-settings-form');
        var formData = form.serialize();
        formData += '&action=nexus_ai_wp_save_settings&nonce=' + nexus_ai_wp_translator_ajax.nonce;
        
        $.post(nexus_ai_wp_translator_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                apiKeyChanged = false;
                console.log('Settings saved dynamically');
                // Show brief success feedback for auto-saves (only if no callback)
                if (!callback) {
                    showAutoSaveSuccess();
                }
                // Execute callback if provided
                if (typeof callback === 'function') {
                    callback();
                }
            } else {
                console.log('Dynamic save failed:', response);
                if (!callback) {
                    showAutoSaveError();
                }
                // Still execute callback even if save failed
                if (typeof callback === 'function') {
                    callback();
                }
            }
        }).fail(function() {
            console.log('Dynamic save request failed');
            if (!callback) {
                showAutoSaveError();
            }
            // Still execute callback even if save failed
            if (typeof callback === 'function') {
                callback();
            }
        });
    }
    
    // Show brief auto-save success feedback
    function showAutoSaveSuccess() {
        var feedback = $('#auto-save-feedback');
        if (feedback.length === 0) {
            $('h1').after('<div id="auto-save-feedback" style="position: fixed; top: 32px; right: 20px; z-index: 9999; background: #00a32a; color: white; padding: 8px 12px; border-radius: 4px; font-size: 13px;"></div>');
            feedback = $('#auto-save-feedback');
        }
        feedback.text('Settings saved').stop(true, true).show().delay(2000).fadeOut();
    }
    
    // Show brief auto-save error feedback
    function showAutoSaveError() {
        var feedback = $('#auto-save-feedback');
        if (feedback.length === 0) {
            $('h1').after('<div id="auto-save-feedback" style="position: fixed; top: 32px; right: 20px; z-index: 9999; background: #d63638; color: white; padding: 8px 12px; border-radius: 4px; font-size: 13px;"></div>');
            feedback = $('#auto-save-feedback');
        }
        feedback.text('Save failed').stop(true, true).show().delay(3000).fadeOut();
    }
    
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
