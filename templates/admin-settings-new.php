<?php
/**
 * New Admin Settings Template - Redesigned Interface
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Nexus AI WP Translator Settings', 'nexus-ai-wp-translator'); ?></h1>
    
    <div class="nexus-ai-wp-settings-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#nexus-api-tab" class="nav-tab nav-tab-active"><?php _e('Nexus API Configuration', 'nexus-ai-wp-translator'); ?></a>
            <a href="#languages-tab" class="nav-tab"><?php _e('Languages', 'nexus-ai-wp-translator'); ?></a>
            <a href="#behavior-tab" class="nav-tab"><?php _e('Behavior', 'nexus-ai-wp-translator'); ?></a>
            <a href="#performance-tab" class="nav-tab"><?php _e('Performance', 'nexus-ai-wp-translator'); ?></a>
        </nav>
        
        <!-- Nexus API Configuration Tab -->
        <div id="nexus-api-tab" class="tab-content active">
            <h2><?php _e('Claude AI API Configuration', 'nexus-ai-wp-translator'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="nexus_ai_wp_translator_api_key"><?php _e('Claude API Key', 'nexus-ai-wp-translator'); ?></label>
                    </th>
                    <td>
                        <div class="nexus-ai-wp-api-key-container">
                            <input type="password" 
                                   id="nexus_ai_wp_translator_api_key" 
                                   name="nexus_ai_wp_translator_api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" 
                                   class="large-text nexus-ai-wp-auto-save" 
                                   autocomplete="off"
                                   data-setting="api_key" />
                            <button type="button" id="nexus-ai-wp-toggle-api-key" class="button">
                                <?php _e('Show', 'nexus-ai-wp-translator'); ?>
                            </button>
                            <button type="button" id="nexus-ai-wp-test-connection" class="button button-primary" <?php echo empty($api_key) ? 'disabled' : ''; ?>>
                                <?php _e('Test Connection', 'nexus-ai-wp-translator'); ?>
                            </button>
                        </div>
                        <p class="description">
                            <?php _e('Enter your Claude AI API key from Anthropic Console. The Test Connection button will save the key and verify it works.', 'nexus-ai-wp-translator'); ?>
                        </p>
                        <div id="api-connection-result" class="nexus-ai-wp-result-container"></div>
                    </td>
                </tr>
                
                <tr id="model-selection-row" style="<?php echo empty($api_key) ? 'display: none;' : ''; ?>">
                    <th scope="row">
                        <label for="nexus_ai_wp_translator_model"><?php _e('AI Model', 'nexus-ai-wp-translator'); ?></label>
                    </th>
                    <td>
                        <div class="nexus-ai-wp-model-container">
                            <select id="nexus_ai_wp_translator_model" 
                                    name="nexus_ai_wp_translator_model"
                                    class="nexus-ai-wp-auto-save"
                                    data-setting="model">
                                <option value=""><?php _e('Connect API first to load models', 'nexus-ai-wp-translator'); ?></option>
                                <?php if (!empty($selected_model)): ?>
                                    <option value="<?php echo esc_attr($selected_model); ?>" selected>
                                        <?php echo esc_html($selected_model); ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                            <button type="button" id="nexus-ai-wp-refresh-models" class="button" style="margin-left: 10px;">
                                <?php _e('Refresh Models', 'nexus-ai-wp-translator'); ?>
                            </button>
                        </div>
                        <p class="description">
                            <?php _e('Select the Claude AI model for translations. Models are loaded after successful API connection.', 'nexus-ai-wp-translator'); ?>
                        </p>
                        <div id="model-selection-result" class="nexus-ai-wp-result-container"></div>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Languages Tab -->
        <div id="languages-tab" class="tab-content">
            <h2><?php _e('Language Configuration', 'nexus-ai-wp-translator'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="nexus_ai_wp_translator_source_language"><?php _e('Source Language', 'nexus-ai-wp-translator'); ?></label>
                    </th>
                    <td>
                        <select id="nexus_ai_wp_translator_source_language" 
                                name="nexus_ai_wp_translator_source_language"
                                class="nexus-ai-wp-auto-save"
                                data-setting="source_language">
                            <option value=""><?php _e('Select source language...', 'nexus-ai-wp-translator'); ?></option>
                            <?php foreach ($languages as $code => $name): ?>
                                <option value="<?php echo esc_attr($code); ?>" <?php selected($source_language, $code); ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Select the primary language of your content. You must choose a source language before target languages will appear.', 'nexus-ai-wp-translator'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr id="target-languages-row" style="<?php echo empty($source_language) ? 'display: none;' : ''; ?>">
                    <th scope="row">
                        <label><?php _e('Target Languages', 'nexus-ai-wp-translator'); ?></label>
                    </th>
                    <td>
                        <fieldset id="nexus-ai-wp-target-languages-fieldset">
                            <legend class="screen-reader-text"><?php _e('Target Languages', 'nexus-ai-wp-translator'); ?></legend>
                            <!-- Target language checkboxes will be populated by JavaScript -->
                        </fieldset>
                        <p class="description">
                            <?php _e('Select languages to translate your content into. Changes are saved automatically.', 'nexus-ai-wp-translator'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Behavior Tab -->
        <div id="behavior-tab" class="tab-content">
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
                                       class="nexus-ai-wp-auto-save"
                                       data-setting="auto_redirect"
                                       <?php checked($auto_redirect); ?> />
                                <?php _e('Show language preference dialog to visitors', 'nexus-ai-wp-translator'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, visitors accessing content in a different language than their browser locale will see a dialog asking if they want to read the content in their preferred language. When disabled, no redirection dialog is shown.', 'nexus-ai-wp-translator'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Save as Draft', 'nexus-ai-wp-translator'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox"
                                       id="nexus_ai_wp_translator_save_as_draft"
                                       name="nexus_ai_wp_translator_save_as_draft"
                                       value="1"
                                       class="nexus-ai-wp-auto-save"
                                       data-setting="save_as_draft"
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
        
        <!-- Performance Tab -->
        <div id="performance-tab" class="tab-content">
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
                               class="small-text nexus-ai-wp-auto-save"
                               data-setting="throttle_limit" />
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
                               class="small-text nexus-ai-wp-auto-save"
                               data-setting="throttle_period" />
                        <p class="description">
                            <?php _e('Time period for the rate limit (minimum 60 seconds, maximum 24 hours).', 'nexus-ai-wp-translator'); ?>
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
                               class="small-text nexus-ai-wp-auto-save"
                               data-setting="retry_attempts" />
                        <p class="description">
                            <?php _e('Number of times to retry failed API calls (1-10).', 'nexus-ai-wp-translator'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <div class="nexus-ai-wp-performance-info">
                <h3><?php _e('Cache Information', 'nexus-ai-wp-translator'); ?></h3>
                <p><?php _e('Translation caching is always enabled to improve performance and reduce API costs. Translations are cached for 24 hours and automatically cleared when posts are updated.', 'nexus-ai-wp-translator'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Status Messages Container -->
    <div id="nexus-ai-wp-settings-messages" class="nexus-ai-wp-messages-container"></div>
</div>

<style>
/* Settings specific styles */
.nexus-ai-wp-settings-tabs .nav-tab-wrapper {
    margin-bottom: 20px;
    border-bottom: 1px solid #ccc;
}

.nexus-ai-wp-settings-tabs .tab-content {
    display: none;
    padding: 20px 0;
}

.nexus-ai-wp-settings-tabs .tab-content.active {
    display: block;
}

.nexus-ai-wp-api-key-container,
.nexus-ai-wp-model-container {
    display: flex;
    align-items: center;
    gap: 10px;
}

.nexus-ai-wp-api-key-container input {
    flex: 1;
}

.nexus-ai-wp-result-container {
    margin-top: 10px;
}

.nexus-ai-wp-result-container .notice {
    margin: 10px 0 0 0;
    padding: 8px 12px;
}

.nexus-ai-wp-messages-container {
    margin-top: 20px;
}

.nexus-ai-wp-messages-container .notice {
    margin: 10px 0;
}

.nexus-ai-wp-performance-info {
    background: #f0f8ff;
    border: 1px solid #b3d9ff;
    border-radius: 4px;
    padding: 15px;
    margin-top: 20px;
}

.nexus-ai-wp-performance-info h3 {
    margin-top: 0;
    color: #0073aa;
}

#nexus-ai-wp-target-languages-fieldset label {
    display: block;
    margin: 8px 0;
    cursor: pointer;
}

#nexus-ai-wp-target-languages-fieldset input {
    margin-right: 8px;
}

/* Auto-save indicator */
.nexus-ai-wp-auto-save.saving {
    background-color: #fff3cd;
    border-color: #ffc107;
}

.nexus-ai-wp-auto-save.saved {
    background-color: #d4edda;
    border-color: #28a745;
    transition: background-color 0.3s, border-color 0.3s;
}

.nexus-ai-wp-auto-save.error {
    background-color: #f8d7da;
    border-color: #dc3545;
}

/* Button states */
button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.button.loading {
    opacity: 0.7;
    cursor: wait;
}
</style>

<script>
jQuery(document).ready(function($) {
    var NexusAISettings = {
        
        // Available languages
        languages: <?php echo json_encode($languages); ?>,
        
        // Current settings
        currentSettings: {
            source_language: '<?php echo esc_js($source_language); ?>',
            target_languages: <?php echo json_encode($target_languages); ?>
        },
        
        // Auto-save timeout
        saveTimeout: null,
        
        init: function() {
            this.initTabs();
            this.initAutoSave();
            this.initAPIKeyHandling();
            this.initLanguageHandling();
            this.populateTargetLanguages();
        },
        
        initTabs: function() {
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').removeClass('active');
                $(target).addClass('active');
                
                // Save active tab
                localStorage.setItem('nexus_ai_wp_settings_active_tab', target);
            });
            
            // Restore active tab
            var activeTab = localStorage.getItem('nexus_ai_wp_settings_active_tab');
            if (activeTab && $(activeTab).length) {
                $('.nav-tab[href="' + activeTab + '"]').click();
            }
        },
        
        initAutoSave: function() {
            var self = this;
            
            // Handle all auto-save elements
            $('.nexus-ai-wp-auto-save').on('change blur keyup', function() {
                var element = $(this);
                var setting = element.data('setting');
                var value = self.getElementValue(element);
                
                // Clear previous timeout
                if (self.saveTimeout) {
                    clearTimeout(self.saveTimeout);
                }
                
                // Debounce save
                self.saveTimeout = setTimeout(function() {
                    self.saveSetting(setting, value, element);
                }, 500);
            });
        },
        
        initAPIKeyHandling: function() {
            var self = this;
            
            // API key toggle
            $('#nexus-ai-wp-toggle-api-key').on('click', function() {
                var input = $('#nexus_ai_wp_translator_api_key');
                var button = $(this);
                
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    button.text('<?php _e('Hide', 'nexus-ai-wp-translator'); ?>');
                } else {
                    input.attr('type', 'password');
                    button.text('<?php _e('Show', 'nexus-ai-wp-translator'); ?>');
                }
            });
            
            // Test connection
            $('#nexus-ai-wp-test-connection').on('click', function() {
                self.testAPIConnection();
            });
            
            // Refresh models
            $('#nexus-ai-wp-refresh-models').on('click', function() {
                self.refreshModels();
            });
            
            // Enable/disable test button based on API key
            $('#nexus_ai_wp_translator_api_key').on('input', function() {
                var hasKey = $(this).val().trim().length > 0;
                $('#nexus-ai-wp-test-connection').prop('disabled', !hasKey);
            });
        },
        
        initLanguageHandling: function() {
            var self = this;
            
            // Source language change
            $('#nexus_ai_wp_translator_source_language').on('change', function() {
                var sourceLanguage = $(this).val();
                self.currentSettings.source_language = sourceLanguage;
                
                if (sourceLanguage) {
                    $('#target-languages-row').show();
                    self.populateTargetLanguages();
                } else {
                    $('#target-languages-row').hide();
                }
            });
        },
        
        populateTargetLanguages: function() {
            var self = this;
            var sourceLanguage = self.currentSettings.source_language;
            var targetLanguages = self.currentSettings.target_languages;
            var fieldset = $('#nexus-ai-wp-target-languages-fieldset');
            
            if (!sourceLanguage) {
                fieldset.empty();
                return;
            }
            
            // Clear and rebuild
            fieldset.empty();
            
            $.each(self.languages, function(code, name) {
                if (code !== sourceLanguage) {
                    var isChecked = targetLanguages.indexOf(code) !== -1;
                    var checkbox = $('<label>')
                        .append($('<input>')
                            .attr('type', 'checkbox')
                            .attr('name', 'nexus_ai_wp_translator_target_languages[]')
                            .attr('value', code)
                            .addClass('nexus-ai-wp-auto-save')
                            .attr('data-setting', 'target_languages')
                            .prop('checked', isChecked)
                        )
                        .append(' ' + name + ' (' + code + ')');
                    
                    fieldset.append(checkbox);
                }
            });
            
            // Re-bind auto-save for new elements
            fieldset.find('.nexus-ai-wp-auto-save').on('change', function() {
                var element = $(this);
                var setting = element.data('setting');
                var value = self.getElementValue($('#nexus-ai-wp-target-languages-fieldset'));
                
                self.saveSetting(setting, value, element);
            });
        },
        
        getElementValue: function(element) {
            if (element.attr('type') === 'checkbox') {
                if (element.data('setting') === 'target_languages') {
                    // Special handling for target languages
                    var values = [];
                    $('#nexus-ai-wp-target-languages-fieldset input:checked').each(function() {
                        values.push($(this).val());
                    });
                    return values;
                } else {
                    return element.is(':checked') ? '1' : '0';
                }
            } else {
                return element.val();
            }
        },
        
        saveSetting: function(setting, value, element) {
            var self = this;
            
            // Visual feedback
            element.removeClass('saved error').addClass('saving');
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_save_settings',
                ['nexus_ai_wp_translator_' + setting]: value,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    element.removeClass('saving error').addClass('saved');
                    self.showMessage(response.data, 'success');
                    
                    // Update current settings
                    self.currentSettings[setting] = value;
                    
                    setTimeout(function() {
                        element.removeClass('saved');
                    }, 2000);
                } else {
                    element.removeClass('saving saved').addClass('error');
                    self.showMessage(response.data || 'Save failed', 'error');
                }
            })
            .fail(function() {
                element.removeClass('saving saved').addClass('error');
                self.showMessage('Network error occurred', 'error');
            });
        },
        
        testAPIConnection: function() {
            var self = this;
            var button = $('#nexus-ai-wp-test-connection');
            var apiKey = $('#nexus_ai_wp_translator_api_key').val();
            var resultDiv = $('#api-connection-result');
            
            if (!apiKey.trim()) {
                self.showResult(resultDiv, 'Please enter an API key first.', 'error');
                return;
            }
            
            button.prop('disabled', true).addClass('loading').text('<?php _e('Testing...', 'nexus-ai-wp-translator'); ?>');
            resultDiv.html('<div class="notice notice-info"><p><?php _e('Testing API connection and saving key...', 'nexus-ai-wp-translator'); ?></p></div>');
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_test_api',
                api_key: apiKey,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                var noticeClass = response.success ? 'notice-success' : 'notice-error';
                self.showResult(resultDiv, response.message, response.success ? 'success' : 'error');
                
                if (response.success) {
                    // Save the API key
                    self.currentSettings.api_key = apiKey;
                    // Show model selection
                    $('#model-selection-row').show();
                    // Load models automatically
                    self.loadModels(apiKey);
                }
            })
            .fail(function() {
                self.showResult(resultDiv, 'Connection test failed. Please check your API key.', 'error');
            })
            .always(function() {
                button.prop('disabled', false).removeClass('loading').text('<?php _e('Test Connection', 'nexus-ai-wp-translator'); ?>');
            });
        },
        
        loadModels: function(apiKey) {
            var self = this;
            var modelSelect = $('#nexus_ai_wp_translator_model');
            var resultDiv = $('#model-selection-result');
            var currentSelection = modelSelect.val();
            
            modelSelect.html('<option value="">Loading models...</option>').prop('disabled', true);
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_get_models',
                api_key: apiKey,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                modelSelect.prop('disabled', false);
                
                if (response.success && response.models) {
                    modelSelect.empty();
                    var hasSelection = false;
                    
                    $.each(response.models, function(modelId, displayName) {
                        var isSelected = (modelId === currentSelection || 
                                        (modelId === 'claude-3-5-sonnet-20241022' && !currentSelection));
                        if (isSelected) hasSelection = true;
                        
                        modelSelect.append($('<option>')
                            .val(modelId)
                            .prop('selected', isSelected)
                            .text(displayName)
                        );
                    });
                    
                    if (hasSelection) {
                        // Save the selected model automatically
                        var selectedModel = modelSelect.val();
                        self.saveSetting('model', selectedModel, modelSelect);
                    }
                    
                    self.showResult(resultDiv, 'Models loaded successfully', 'success');
                } else {
                    // Fallback models
                    modelSelect.html('<option value="claude-3-5-sonnet-20241022" selected>Claude 3.5 Sonnet (Latest)</option>');
                    self.showResult(resultDiv, 'Using fallback models (API did not return model list)', 'warning');
                }
            })
            .fail(function() {
                modelSelect.prop('disabled', false);
                modelSelect.html('<option value="claude-3-5-sonnet-20241022" selected>Claude 3.5 Sonnet (Latest)</option>');
                self.showResult(resultDiv, 'Failed to load models, using fallback', 'warning');
            });
        },
        
        refreshModels: function() {
            var apiKey = $('#nexus_ai_wp_translator_api_key').val();
            if (apiKey.trim()) {
                this.loadModels(apiKey);
            } else {
                this.showMessage('Please enter and test an API key first', 'error');
            }
        },
        
        showResult: function(container, message, type) {
            var noticeClass = 'notice-info';
            if (type === 'success') noticeClass = 'notice-success';
            if (type === 'error') noticeClass = 'notice-error';
            if (type === 'warning') noticeClass = 'notice-warning';
            
            container.html('<div class="notice ' + noticeClass + '"><p>' + message + '</p></div>');
        },
        
        showMessage: function(message, type) {
            var noticeClass = 'notice-info';
            if (type === 'success') noticeClass = 'notice-success';
            if (type === 'error') noticeClass = 'notice-error';
            if (type === 'warning') noticeClass = 'notice-warning';
            
            var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            $('#nexus-ai-wp-settings-messages').append(notice);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            }, 5000);
        }
    };
    
    // Initialize
    NexusAISettings.init();
});
</script>
