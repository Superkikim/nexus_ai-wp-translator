/**
 * Settings Page JavaScript
 * Handles settings page functionality including API testing and model loading
 */

(function($) {
    'use strict';

    // Settings Page Controller
    window.NexusAIWPTranslatorSettingsPage = {
        
        /**
         * Initialize settings page
         */
        init: function() {
            console.debug('[Nexus Translator]: Settings page JavaScript initialized');
            this.waitForAjaxVars();
        },

        /**
         * Wait for AJAX variables to be available
         */
        waitForAjaxVars: function() {
            if (typeof nexus_ai_wp_translator_ajax === 'undefined') {
                console.debug('[Nexus Translator]: Waiting for AJAX variables...');
                setTimeout(this.waitForAjaxVars.bind(this), 100);
                return;
            }
            
            console.debug('[Nexus Translator]: AJAX variables loaded in settings page');
            this.initInlineScript();
        },

        /**
         * Initialize inline script functionality
         */
        initInlineScript: function() {
            // Determine current scenario based on server-side data
            var hasApiKey = window.nexusAiServerData ? window.nexusAiServerData.hasApiKey : false;
            var currentModel = window.nexusAiServerData ? window.nexusAiServerData.selectedModel : '';
            var currentScenario = 1; // Default: No API key, No model
            
            if (hasApiKey && currentModel) {
                currentScenario = 3; // Both API key and model exist
            } else if (hasApiKey && !currentModel) {
                currentScenario = 2; // API key exists, no model
            }
            
            console.debug('[Nexus Translator]: Detected scenario:', currentScenario, 'Has API key:', hasApiKey, 'Model:', currentModel);

            var modelRow = $('#model-selection-row');
            
            switch (currentScenario) {
                case 1: // No API key, No model
                    console.debug('[Nexus Translator]: Scenario 1 - No API key, no model');
                    modelRow.hide();
                    break;
                    
                case 2: // API key exists, No model  
                    console.debug('[Nexus Translator]: Scenario 2 - API key exists, no model');
                    modelRow.hide(); // Start hidden
                    // Perform background API test using current form value
                    var apiKeyFromForm = $('#nexus_ai_wp_translator_api_key').val();
                    if (apiKeyFromForm) {
                        this.performBackgroundApiTest(apiKeyFromForm);
                    }
                    break;
                    
                case 3: // Both API key and model exist
                    console.debug('[Nexus Translator]: Scenario 3 - Both API key and model exist');
                    modelRow.hide(); // Start hidden
                    // Perform background API test using current form value
                    var apiKeyFromForm = $('#nexus_ai_wp_translator_api_key').val();
                    if (apiKeyFromForm) {
                        this.performBackgroundApiTest(apiKeyFromForm);
                    }
                    break;
            }
        },

        /**
         * Perform background API test
         */
        performBackgroundApiTest: function(apiKey) {
            console.debug('[Nexus Translator]: Performing background API test');
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_test_api',
                api_key: apiKey,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                console.debug('[Nexus Translator]: Background API test response:', response);
                if (response.success) {
                    console.debug('[Nexus Translator]: API test successful, loading models');
                    this.loadModels(apiKey);
                } else {
                    console.debug('[Nexus Translator]: API test failed:', response.message);
                }
            }.bind(this))
            .fail(function(xhr, status, error) {
                console.debug('[Nexus Translator]: Background API test failed:', error);
            });
        },

        /**
         * Load available models
         */
        loadModels: function(apiKey) {
            console.debug('[Nexus Translator]: Loading models for API key');
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_get_models',
                api_key: apiKey,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                console.debug('[Nexus Translator]: Models response:', response);
                if (response.success && response.data && response.data.models) {
                    this.populateModelDropdown(response.data.models);
                    $('#model-selection-row').show();
                } else {
                    console.debug('[Nexus Translator]: Failed to load models:', response.message);
                }
            }.bind(this))
            .fail(function(xhr, status, error) {
                console.debug('[Nexus Translator]: Model loading failed:', error);
            });
        },

        /**
         * Populate model dropdown with available models
         */
        populateModelDropdown: function(models) {
            var modelSelect = $('#nexus_ai_wp_translator_model');
            var currentValue = modelSelect.val();
            
            // Clear existing options except the first one
            modelSelect.find('option:not(:first)').remove();
            
            // Add new model options
            models.forEach(function(model) {
                var option = $('<option></option>')
                    .attr('value', model.id)
                    .text(model.display_name || model.id);
                    
                if (model.id === currentValue) {
                    option.attr('selected', 'selected');
                }
                
                modelSelect.append(option);
            });
            
            console.debug('[Nexus Translator]: Model dropdown populated with', models.length, 'models');
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if we're on the settings page
        if ($('#nexus-ai-wp-translator-settings-form').length) {
            NexusAIWPTranslatorSettingsPage.init();
        }
    });

})(jQuery);
