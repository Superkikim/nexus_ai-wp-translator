/**
 * Nexus AI WP Translator AJAX Handler
 * Centralized AJAX functionality
 */

(function() {
    'use strict';

    // AJAX handler object
    var NexusAIWPTranslatorAjax = {
        
        /**
         * Auto-save API key
         */
        autoSaveApiKey: function(apiKey, callback) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('autoSaveApiKey')) return;
            var $ = jQuery;
            
            console.debug('[Nexus Translator]: Auto-saving API key');
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_save_settings',
                nexus_ai_wp_translator_api_key: apiKey,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                console.debug('[Nexus Translator]: API key auto-save response:', response);
                if (callback) callback();
            })
            .fail(function(xhr, status, error) {
                console.debug('[Nexus Translator]: Failed to auto-save API key:', error);
                if (callback) callback(); // Continue anyway
            });
        },
        
        /**
         * Auto-save selected model
         */
        autoSaveModel: function(model) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('autoSaveModel')) return;
            var $ = jQuery;
            
            console.debug('[Nexus Translator]: Auto-saving model:', model);
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_save_settings',
                nexus_ai_wp_translator_model: model,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                console.debug('[Nexus Translator]: Model auto-save response:', response);
                // Show subtle feedback
                var feedback = $('<span class="model-saved-feedback" style="color: #46b450; margin-left: 10px;">✓ Saved</span>');
                $('#nexus_ai_wp_translator_model').after(feedback);
                setTimeout(function() {
                    feedback.fadeOut(function() { $(this).remove(); });
                }, 2000);
            })
            .fail(function(xhr, status, error) {
                console.debug('[Nexus Translator]: Failed to auto-save model:', error);
            });
        },

        /**
         * Perform API test
         */
        performApiTest: function(button, apiKey, resultDiv) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('performApiTest')) return;
            var $ = jQuery;
            
            console.debug('[Nexus Translator]: Starting API test with key length:', apiKey.length);
            console.debug('[Nexus Translator]: AJAX URL:', nexus_ai_wp_translator_ajax.ajax_url);
            console.debug('[Nexus Translator]: Nonce:', nexus_ai_wp_translator_ajax.nonce);
            
            button.prop('disabled', true).text(nexus_ai_wp_translator_ajax.strings.testing);
            resultDiv.html('<span style="color: #0073aa;">Testing...</span>');
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_test_api',
                api_key: apiKey,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                console.debug('[Nexus Translator]: API test response:', response);
                
                if (response.success) {
                    resultDiv.html('<span style="color: #46b450;">✓ ' + nexus_ai_wp_translator_ajax.strings.success + '</span>');
                    
                    // Auto-save the API key after successful test
                    NexusAIWPTranslatorAjax.autoSaveApiKey(apiKey, function() {
                        // Load models after saving API key
                        NexusAIWPTranslatorAjax.loadAvailableModels();
                    });
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                    resultDiv.html('<span style="color: #dc3232;">✗ ' + nexus_ai_wp_translator_ajax.strings.error + ' ' + errorMsg + '</span>');
                }
            })
            .fail(function(xhr, status, error) {
                console.debug('[Nexus Translator]: API test failed:', error);
                resultDiv.html('<span style="color: #dc3232;">✗ Network error: ' + error + '</span>');
            })
            .always(function() {
                button.prop('disabled', false).text('Test API');
            });
        },

        /**
         * Load available models after successful API test
         */
        loadAvailableModels: function(callback) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('loadAvailableModels')) return;
            var $ = jQuery;
            
            console.debug('[Nexus Translator]: Starting to load available models');
            
            var apiKey = $('#nexus_ai_wp_translator_api_key').val().trim();
            if (!apiKey) {
                console.debug('[Nexus Translator]: No API key available for loading models');
                return;
            }
            
            var modelSelect = $('#nexus_ai_wp_translator_model');
            if (modelSelect.length === 0) {
                console.debug('[Nexus Translator]: Model select element not found');
                return;
            }
            
            // Show loading state
            var loadingOption = $('<option value="">Loading models...</option>');
            modelSelect.html(loadingOption);
            
            console.debug('[Nexus Translator]: Making AJAX request to load models');
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_get_models',
                api_key: apiKey,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                console.debug('[Nexus Translator]: Models response:', response);
                
                if (response.success && response.data && response.data.models) {
                    var models = response.data.models;
                    var currentModel = response.data.current_model || '';
                    
                    console.debug('[Nexus Translator]: Available models:', models);
                    console.debug('[Nexus Translator]: Current model:', currentModel);
                    
                    // Clear loading option
                    modelSelect.empty();
                    
                    // Add default option
                    modelSelect.append('<option value="">Select a model...</option>');
                    
                    // Add model options
                    models.forEach(function(model) {
                        var option = $('<option></option>')
                            .attr('value', model.id)
                            .text(model.name + (model.description ? ' - ' + model.description : ''));
                        
                        if (model.id === currentModel) {
                            option.prop('selected', true);
                        }
                        
                        modelSelect.append(option);
                    });
                    
                    console.debug('[Nexus Translator]: Models loaded successfully, total:', models.length);
                    
                    // Auto-save model selection on change
                    modelSelect.off('change.nexus-model-save').on('change.nexus-model-save', function() {
                        var selectedModel = $(this).val();
                        if (selectedModel) {
                            NexusAIWPTranslatorAjax.autoSaveModel(selectedModel);
                        }
                    });
                    
                } else {
                    console.debug('[Nexus Translator]: Failed to load models:', response.data ? response.data.message : 'Unknown error');
                    modelSelect.html('<option value="">Failed to load models</option>');
                }
                
                if (callback) callback(response);
            })
            .fail(function(xhr, status, error) {
                console.debug('[Nexus Translator]: Failed to load models - network error:', error);
                modelSelect.html('<option value="">Error loading models</option>');
                if (callback) callback({success: false, error: error});
            });
        },

        /**
         * Get translation status
         */
        getTranslationStatus: function(postId, callback) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('getTranslationStatus')) return;
            var $ = jQuery;
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_get_translation_status',
                post_id: postId,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (callback) callback(response);
            })
            .fail(function(xhr, status, error) {
                console.debug('[Nexus Translator]: Failed to get translation status:', error);
                if (callback) callback({success: false, error: error});
            });
        },

        /**
         * Translate post
         */
        translatePost: function(postId, targetLanguages, progressId, callback) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('translatePost')) return;
            var $ = jQuery;
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_translate_post',
                post_id: postId,
                target_languages: targetLanguages,
                progress_id: progressId,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (callback) callback(response);
            })
            .fail(function(xhr, status, error) {
                console.debug('[Nexus Translator]: Translation failed:', error);
                if (callback) callback({success: false, error: error});
            });
        },

        /**
         * Get translation progress
         */
        getTranslationProgress: function(progressId, callback) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('getTranslationProgress')) return;
            var $ = jQuery;
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_get_translation_progress',
                progress_id: progressId,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (callback) callback(response);
            })
            .fail(function(xhr, status, error) {
                console.debug('[Nexus Translator]: Failed to get progress:', error);
                if (callback) callback({success: false, error: error});
            });
        },

        /**
         * Resume translation
         */
        resumeTranslation: function(postId, resumableLanguages, callback) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('resumeTranslation')) return;
            var $ = jQuery;
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_resume_translation',
                post_id: postId,
                resumable_languages: resumableLanguages,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (callback) callback(response);
            })
            .fail(function(xhr, status, error) {
                console.debug('[Nexus Translator]: Resume translation failed:', error);
                if (callback) callback({success: false, error: error});
            });
        },

        /**
         * Get stats
         */
        getStats: function(period, callback) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('getStats')) return;
            var $ = jQuery;
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_get_stats',
                period: period,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (callback) callback(response);
            })
            .fail(function(xhr, status, error) {
                console.debug('[Nexus Translator]: Failed to get stats:', error);
                if (callback) callback({success: false, error: error});
            });
        }
    };

    // Make AJAX handler globally available
    window.NexusAIWPTranslatorAjax = NexusAIWPTranslatorAjax;
    console.debug('[Nexus Translator]: NexusAIWPTranslatorAjax made globally available');

})();
