/**
 * Nexus AI WP Translator Settings Tabs
 * Tab switching and settings functionality
 */

(function() {
    'use strict';

    // Settings tabs object
    var NexusAIWPTranslatorSettingsTabs = {
        
        /**
         * Initialize tab switching functionality
         */
        init: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initTabSwitching')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Initializing tab switching');
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                console.debug('[Nexus Translator]: Tab clicked:', target);
                
                // Remove active class from all tabs and content
                $('.nav-tab').removeClass('nav-tab-active');
                $('.tab-content').removeClass('active').hide();
                
                // Add active class to clicked tab
                $(this).addClass('nav-tab-active');
                
                // Show corresponding content
                $(target).addClass('active').show();
                
                // Store active tab in localStorage
                localStorage.setItem('nexus_ai_wp_translator_active_tab', target);
            });
            
            // Restore active tab from localStorage
            var activeTab = localStorage.getItem('nexus_ai_wp_translator_active_tab');
            if (activeTab && $(activeTab).length > 0) {
                $('.nav-tab[href="' + activeTab + '"]').trigger('click');
            } else {
                // Default to first tab
                $('.nav-tab').first().trigger('click');
            }
        },

        /**
         * Initialize API testing functionality
         */
        initApiTesting: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initApiTesting')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Initializing API testing');
            
            $('#nexus-ai-wp-test-api').on('click', function() {
                var button = $(this);
                var apiKey = $('#nexus_ai_wp_translator_api_key').val().trim();
                var resultDiv = $('#nexus-ai-wp-api-test-result');
                
                console.debug('[Nexus Translator]: Test API button clicked');
                console.debug('[Nexus Translator]: API key length:', apiKey.length);
                
                if (!apiKey) {
                    resultDiv.html('<span style="color: #dc3232;">Please enter an API key first.</span>');
                    return;
                }
                
                NexusAIWPTranslatorAjax.performApiTest(button, apiKey, resultDiv);
            });
            
            // Auto-test API when key is pasted or changed (with debounce)
            $('#nexus_ai_wp_translator_api_key').on('input paste', NexusAIWPTranslatorCore.debounce(function() {
                var apiKey = $(this).val().trim();
                var resultDiv = $('#nexus-ai-wp-api-test-result');
                
                if (apiKey.length > 20) { // Reasonable minimum length for API key
                    console.debug('[Nexus Translator]: Auto-testing API key on input');
                    var button = $('#nexus-ai-wp-test-api');
                    NexusAIWPTranslatorAjax.performApiTest(button, apiKey, resultDiv);
                } else {
                    resultDiv.html('');
                }
            }, 1000));
        },

        /**
         * Initialize settings save functionality
         */
        initSettingsSave: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initSettingsSave')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Initializing settings save');
            
            // Handle form submission
            $('#nexus-ai-wp-translator-settings-form').on('submit', function(e) {
                e.preventDefault();
                
                var form = $(this);
                var submitButton = form.find('input[type="submit"]');
                var originalText = submitButton.val();
                
                // Show saving state
                submitButton.val('Saving...').prop('disabled', true);
                
                // Serialize form data
                var formData = form.serialize();
                formData += '&action=nexus_ai_wp_save_settings&nonce=' + nexus_ai_wp_translator_ajax.nonce;
                
                $.post(nexus_ai_wp_translator_ajax.ajax_url, formData)
                .done(function(response) {
                    console.debug('[Nexus Translator]: Settings save response:', response);
                    
                    if (response.success) {
                        NexusAIWPTranslatorCore.showGlobalNotice('success', 'Settings saved successfully!');
                    } else {
                        var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                        NexusAIWPTranslatorCore.showGlobalNotice('error', 'Failed to save settings: ' + errorMsg);
                    }
                })
                .fail(function(xhr, status, error) {
                    console.debug('[Nexus Translator]: Settings save failed:', error);
                    NexusAIWPTranslatorCore.showGlobalNotice('error', 'Network error: ' + error);
                })
                .always(function() {
                    submitButton.val(originalText).prop('disabled', false);
                });
            });
            
            // Auto-save certain settings on change
            this.initAutoSave();
        },

        /**
         * Initialize auto-save functionality
         */
        initAutoSave: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initAutoSave')) return;
            var $ = jQuery;

            // Auto-save API key
            $('#nexus_ai_wp_translator_api_key').on('blur', function() {
                var apiKey = $(this).val().trim();
                if (apiKey) {
                    NexusAIWPTranslatorAjax.autoSaveApiKey(apiKey);
                }
            });

            // Auto-save model selection
            $('#nexus_ai_wp_translator_model').on('change', function() {
                var model = $(this).val();
                if (model) {
                    NexusAIWPTranslatorAjax.autoSaveModel(model);
                }
            });

            // Auto-save other critical settings
            var autoSaveFields = [
                '#nexus_ai_wp_translator_source_language',
                '#nexus_ai_wp_translator_seo_friendly_urls',
                '#nexus_ai_wp_translator_auto_translate'
            ];

            autoSaveFields.forEach(function(selector) {
                $(selector).on('change', NexusAIWPTranslatorCore.debounce(function() {
                    var field = $(this);
                    var value = field.is(':checkbox') ? field.is(':checked') : field.val();
                    var name = field.attr('name');
                    
                    if (name) {
                        var data = {
                            action: 'nexus_ai_wp_save_settings',
                            nonce: nexus_ai_wp_translator_ajax.nonce
                        };
                        data[name] = value;
                        
                        $.post(nexus_ai_wp_translator_ajax.ajax_url, data)
                        .done(function(response) {
                            if (response.success) {
                                // Show subtle feedback
                                var feedback = $('<span class="auto-save-feedback" style="color: #46b450; margin-left: 10px;">✓</span>');
                                field.after(feedback);
                                setTimeout(function() {
                                    feedback.fadeOut(function() { $(this).remove(); });
                                }, 1500);
                            }
                        });
                    }
                }, 500));
            });
        },

        /**
         * Initialize status refresh functionality
         */
        initStatusRefresh: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initStatusRefresh')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Initializing status refresh');
            
            $('#nexus-ai-wp-refresh-stats').on('click', function() {
                console.debug('[Nexus Translator]: Refresh stats clicked');
                
                var button = $(this);
                button.prop('disabled', true).text('Refreshing...');
                
                NexusAIWPTranslatorAjax.getStats('7 days', function(response) {
                    console.debug('[Nexus Translator]: Refresh stats response:', response);
                    
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error refreshing stats');
                    }
                    
                    button.prop('disabled', false).text('Refresh Stats');
                });
            });
        },

        /**
         * Initialize cache management
         */
        initCacheManagement: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initCacheManagement')) return;
            var $ = jQuery;

            // Clear translation cache
            $('#nexus-ai-wp-clear-cache').on('click', function() {
                var button = $(this);
                var resultSpan = $('#nexus-ai-wp-clear-cache-result');
                
                if (!confirm('Are you sure you want to clear all translation cache? This action cannot be undone.')) {
                    return;
                }
                
                button.prop('disabled', true).text('Clearing...');
                resultSpan.html('<span style="color: #0073aa;">Clearing cache...</span>');
                
                $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                    action: 'nexus_ai_wp_clear_translation_cache',
                    nonce: nexus_ai_wp_translator_ajax.nonce
                })
                .done(function(response) {
                    if (response.success) {
                        resultSpan.html('<span style="color: #46b450;">✓ Cache cleared successfully!</span>');
                    } else {
                        var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                        resultSpan.html('<span style="color: #dc3232;">✗ Error: ' + errorMsg + '</span>');
                    }
                })
                .fail(function(xhr, status, error) {
                    resultSpan.html('<span style="color: #dc3232;">✗ Network error: ' + error + '</span>');
                })
                .always(function() {
                    button.prop('disabled', false).text('Clear Cache');
                    setTimeout(function() {
                        resultSpan.html('');
                    }, 5000);
                });
            });
        },

        /**
         * Initialize all settings functionality
         */
        initAll: function() {
            this.init();
            this.initApiTesting();
            this.initSettingsSave();
            this.initStatusRefresh();
            this.initCacheManagement();
            
            // Load models on page load if API key exists
            var $ = jQuery;
            var apiKey = $('#nexus_ai_wp_translator_api_key').val();
            console.debug('[Nexus Translator]: API key on page load:', apiKey ? 'EXISTS (length: ' + apiKey.length + ')' : 'NOT FOUND');
            
            if (apiKey && apiKey.trim().length > 0) {
                console.debug('[Nexus Translator]: API key found on page load, loading models automatically');
                NexusAIWPTranslatorAjax.loadAvailableModels();
            } else {
                console.debug('[Nexus Translator]: No API key found on page load, skipping model load');
            }
        }
    };

    // Make settings tabs globally available
    window.NexusAIWPTranslatorSettingsTabs = NexusAIWPTranslatorSettingsTabs;
    console.debug('[Nexus Translator]: NexusAIWPTranslatorSettingsTabs made globally available');

})();
