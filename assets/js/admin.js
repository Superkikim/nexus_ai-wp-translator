/**
 * Nexus AI WP Translator Admin JavaScript
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        console.log('NexusAI Debug: Document ready, initializing admin interface');
        NexusAIWPTranslatorAdmin.init();
    });
    
    var NexusAIWPTranslatorAdmin = {
        
        init: function() {
            console.log('NexusAI Debug: Starting admin initialization');
            this.initTabSwitching();
            this.initApiTesting();
            this.initSettingsSave();
            this.initTranslationActions();
            this.initStatusRefresh();
            this.initBulkActions();
            
            // Load models on page load if API key exists
            var apiKey = $('#nexus_ai_wp_translator_api_key').val();
            console.log('NexusAI Debug: API key on page load:', apiKey ? 'EXISTS (length: ' + apiKey.length + ')' : 'NOT FOUND');
            
            if (apiKey && apiKey.trim().length > 0) {
                console.log('NexusAI Debug: API key found on page load, loading models automatically');
                this.loadAvailableModels();
            } else {
                console.log('NexusAI Debug: No API key found on page load, skipping model load');
            }
        },
        
        /**
         * Initialize tab switching functionality
         */
        initTabSwitching: function() {
            console.log('NexusAI Debug: Initializing tab switching');
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                console.log('NexusAI Debug: Tab clicked:', target);
                
                // Update nav tabs
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Update tab content
                $('.tab-content').removeClass('active');
                $(target).addClass('active');
                
                // Save active tab in localStorage
                localStorage.setItem('nexus_ai_wp_translator_active_tab', target);
            });
            
            // Restore active tab from localStorage
            var activeTab = localStorage.getItem('nexus_ai_wp_translator_active_tab');
            if (activeTab && $(activeTab).length) {
                console.log('NexusAI Debug: Restoring active tab:', activeTab);
                $('.nav-tab[href="' + activeTab + '"]').click();
            }
        },
        
        /**
         * Initialize API testing functionality
         */
        initApiTesting: function() {
            console.log('NexusAI Debug: Initializing API testing');
            
            // Test API connection
            $('#nexus-ai-wp-test-api').on('click', function() {
                console.log('NexusAI Debug: Test API button clicked');
                
                var button = $(this);
                var apiKey = $('#nexus_ai_wp_translator_api_key').val().trim();
                var resultDiv = $('#api-test-result');
                
                console.log('NexusAI Debug: API key for test:', apiKey ? 'EXISTS (length: ' + apiKey.length + ')' : 'EMPTY');
                
                if (!apiKey) {
                    console.log('NexusAI Debug: No API key provided');
                    NexusAIWPTranslatorAdmin.showNotice(resultDiv, 'error', 'Please enter an API key first.');
                    return;
                }
                
                console.log('NexusAI Debug: Auto-saving API key before test');
                // Auto-save API key before testing
                NexusAIWPTranslatorAdmin.autoSaveApiKey(apiKey, function() {
                    console.log('NexusAI Debug: API key auto-saved, proceeding with test');
                    // Proceed with test after saving
                    NexusAIWPTranslatorAdmin.performApiTest(button, apiKey, resultDiv);
                });
            });
            
            // Refresh models button
            $('#nexus-ai-wp-refresh-models').on('click', function() {
                console.log('NexusAI Debug: Refresh models button clicked');
                
                var button = $(this);
                var apiKey = $('#nexus_ai_wp_translator_api_key').val().trim();
                
                if (!apiKey) {
                    console.log('NexusAI Debug: No API key for refresh models');
                    alert('Please enter and test your API key first.');
                    return;
                }
                
                button.prop('disabled', true).text('Loading...');
                console.log('NexusAI Debug: Starting manual model refresh');
                
                NexusAIWPTranslatorAdmin.loadAvailableModels(function() {
                    button.prop('disabled', false).text('Refresh Models');
                    console.log('NexusAI Debug: Manual model refresh completed');
                });
            });
            
            // Auto-save when model selection changes
            $(document).on('change', '#nexus_ai_wp_translator_model', function() {
                console.log('NexusAI Debug: Model selection changed to:', $(this).val());
                var selectedModel = $(this).val();
                if (selectedModel) {
                    NexusAIWPTranslatorAdmin.autoSaveModel(selectedModel);
                }
            });
            
            // API key toggle visibility
            $('#nexus-ai-wp-toggle-api-key').on('click', function() {
                console.log('NexusAI Debug: Toggle API key visibility');
                var input = $('#nexus_ai_wp_translator_api_key');
                var type = input.attr('type');
                
                if (type === 'password') {
                    input.attr('type', 'text');
                    $(this).text('Hide');
                } else {
                    input.attr('type', 'password');
                    $(this).text('Show');
                }
            });
        },
        
        /**
         * Auto-save API key
         */
        autoSaveApiKey: function(apiKey, callback) {
            console.log('NexusAI Debug: Auto-saving API key');
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_save_settings',
                nexus_ai_wp_translator_api_key: apiKey,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                console.log('NexusAI Debug: API key auto-save response:', response);
                if (callback) callback();
            })
            .fail(function(xhr, status, error) {
                console.log('NexusAI Debug: Failed to auto-save API key:', error);
                if (callback) callback(); // Continue anyway
            });
        },
        
        /**
         * Auto-save selected model
         */
        autoSaveModel: function(model) {
            console.log('NexusAI Debug: Auto-saving model:', model);
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_save_settings',
                nexus_ai_wp_translator_model: model,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                console.log('NexusAI Debug: Model auto-save response:', response);
                // Show subtle feedback
                var feedback = $('<span class="model-saved-feedback" style="color: #46b450; margin-left: 10px;">✓ Saved</span>');
                $('#nexus_ai_wp_translator_model').after(feedback);
                setTimeout(function() {
                    feedback.fadeOut(function() { $(this).remove(); });
                }, 2000);
            })
            .fail(function(xhr, status, error) {
                console.log('NexusAI Debug: Failed to auto-save model:', error);
            });
        },
        
        /**
         * Perform API test
         */
        performApiTest: function(button, apiKey, resultDiv) {
            console.log('NexusAI Debug: Starting API test with key length:', apiKey.length);
            console.log('NexusAI Debug: AJAX URL:', nexus_ai_wp_translator_ajax.ajax_url);
            console.log('NexusAI Debug: Nonce:', nexus_ai_wp_translator_ajax.nonce);
            console.log('NexusAI Debug: About to make AJAX POST request for API test');
            
            button.prop('disabled', true).text('Testing...');
            resultDiv.html('<div class="nexus-ai-wp-spinner"></div> Testing connection...');
            
            console.log('NexusAI Debug: Making AJAX request with data:', {
                action: 'nexus_ai_wp_test_api',
                api_key: apiKey.substring(0, 10) + '...',
                nonce: nexus_ai_wp_translator_ajax.nonce
            });
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_test_api',
                api_key: apiKey,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                console.log('NexusAI Debug: API test response:', response);
                console.log('NexusAI Debug: Response type:', typeof response);
                console.log('NexusAI Debug: Response success:', response.success);
                
                var noticeClass = response.success ? 'success' : 'error';
                NexusAIWPTranslatorAdmin.showNotice(resultDiv, noticeClass, response.message);
                
                // If API test successful, load available models
                if (response.success) {
                    console.log('NexusAI Debug: API test successful, loading models now');
                    NexusAIWPTranslatorAdmin.loadAvailableModels();
                } else {
                    console.log('NexusAI Debug: API test failed:', response.message);
                }
            })
            .fail(function(xhr, status, error) {
                console.log('NexusAI Debug: API test failed - network error:', error);
                console.log('NexusAI Debug: XHR response:', xhr.responseText);
                NexusAIWPTranslatorAdmin.showNotice(resultDiv, 'error', 'Connection failed. Please check your internet connection.');
            })
            .always(function() {
                button.prop('disabled', false).text('Test Connection');
            });
        },
        
        /**
         * Load available models after successful API test
         */
        loadAvailableModels: function(callback) {
            console.log('NexusAI Debug: Starting to load available models');
            
            var apiKey = $('#nexus_ai_wp_translator_api_key').val().trim();
            var modelSelect = $('#nexus_ai_wp_translator_model');
            
            console.log('NexusAI Debug: Model select element found:', modelSelect.length > 0);
            console.log('NexusAI Debug: API key for models:', apiKey ? 'EXISTS (length: ' + apiKey.length + ')' : 'NOT FOUND');
            console.log('NexusAI Debug: Model select element ID:', modelSelect.attr('id'));
            console.log('NexusAI Debug: Current model select HTML:', modelSelect.length > 0 ? modelSelect[0].outerHTML.substring(0, 200) + '...' : 'NOT FOUND');
            
            if (!apiKey) {
                console.log('NexusAI Debug: No API key found, cannot load models');
                if (callback) callback();
                return;
            }
            
            // Store current selection
            var currentSelection = modelSelect.val();
            console.log('NexusAI Debug: Current model selection:', currentSelection);
            
            // Show loading state
            modelSelect.html('<option value="">Loading models...</option>');
            console.log('NexusAI Debug: Set loading state in dropdown');
            
            console.log('NexusAI Debug: Making AJAX request to get models');
            console.log('NexusAI Debug: AJAX data for models:', {
                action: 'nexus_ai_wp_get_models',
                api_key: apiKey.substring(0, 10) + '...',
                nonce: nexus_ai_wp_translator_ajax.nonce
            });
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_get_models',
                api_key: apiKey,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                console.log('NexusAI Debug: Get models AJAX response:', response);
                console.log('NexusAI Debug: Response type:', typeof response);
                console.log('NexusAI Debug: Response success:', response.success);
                console.log('NexusAI Debug: Response models:', response.models);
                
                if (response.success && response.models) {
                    console.log('NexusAI Debug: Models received successfully:', response.models);
                    modelSelect.empty();
                    
                    // Add models to dropdown
                    var modelCount = 0;
                    $.each(response.models, function(modelId, displayName) {
                        var selected = (modelId === currentSelection || (modelId === 'claude-3-5-sonnet-20241022' && !currentSelection)) ? 'selected' : '';
                        modelSelect.append('<option value="' + modelId + '" ' + selected + '>' + displayName + '</option>');
                        console.log('NexusAI Debug: Added model:', modelId, '→', displayName);
                        modelCount++;
                    });
                    
                    console.log('NexusAI Debug: Total models added:', modelCount);
                } else {
                    console.log('NexusAI Debug: Failed to get models or no models in response, using fallback');
                    console.log('NexusAI Debug: Response success:', response.success);
                    console.log('NexusAI Debug: Response models:', response.models);
                    console.log('NexusAI Debug: Full response object:', response);
                    
                    // Fallback to default models if API call fails
                    NexusAIWPTranslatorAdmin.setDefaultModels(modelSelect, currentSelection);
                }
            })
            .fail(function(xhr, status, error) {
                console.log('NexusAI Debug: Get models AJAX request failed:', error);
                console.log('NexusAI Debug: XHR status:', status);
                console.log('NexusAI Debug: XHR status code:', xhr.status);
                console.log('NexusAI Debug: XHR response:', xhr.responseText);
                console.log('NexusAI Debug: XHR status code:', xhr.status);
                console.log('NexusAI Debug: Full XHR object:', xhr);
                
                // Fallback to default models if request fails
                NexusAIWPTranslatorAdmin.setDefaultModels(modelSelect, currentSelection);
            })
            .always(function() {
                console.log('NexusAI Debug: API test request completed');
                console.log('NexusAI Debug: Load models request completed');
                if (callback) callback();
            });
        },
        
        /**
         * Set default models in dropdown
         */
        setDefaultModels: function(modelSelect, currentSelection) {
            console.log('NexusAI Debug: Setting default models, current selection:', currentSelection);
            
            var defaultModels = [
                {id: 'claude-3-5-sonnet-20241022', name: 'Claude 3.5 Sonnet (Latest)'},
                {id: 'claude-3-sonnet-20240229', name: 'Claude 3 Sonnet'},
                {id: 'claude-3-haiku-20240307', name: 'Claude 3 Haiku'},
                {id: 'claude-3-opus-20240229', name: 'Claude 3 Opus'}
            ];
            
            modelSelect.empty();
            $.each(defaultModels, function(index, model) {
                var selected = (model.id === currentSelection || (model.id === 'claude-3-5-sonnet-20241022' && !currentSelection)) ? 'selected' : '';
                modelSelect.append('<option value="' + model.id + '" ' + selected + '>' + model.name + '</option>');
                console.log('NexusAI Debug: Added default model:', model.id, '→', model.name);
            });
            
            console.log('NexusAI Debug: Default models set complete');
        },
        
        /**
         * Initialize settings save functionality
         */
        initSettingsSave: function() {
            console.log('NexusAI Debug: Initializing settings save');
            
            $('#nexus-ai-wp-save-settings').on('click', function() {
                console.log('NexusAI Debug: Save settings button clicked');
                
                var button = $(this);
                var form = $('#nexus-ai-wp-translator-settings-form');
                
                button.prop('disabled', true).text('Saving...');
                
                var formData = form.serialize();
                formData += '&action=nexus_ai_wp_save_settings&nonce=' + nexus_ai_wp_translator_ajax.nonce;
                
                console.log('NexusAI Debug: Saving settings with form data');
                
                $.post(nexus_ai_wp_translator_ajax.ajax_url, formData)
                .done(function(response) {
                    console.log('NexusAI Debug: Save settings response:', response);
                    var noticeClass = response.success ? 'success' : 'error';
                    NexusAIWPTranslatorAdmin.showGlobalNotice(noticeClass, response.data);
                })
                .fail(function(xhr, status, error) {
                    console.log('NexusAI Debug: Save settings failed:', error);
                    NexusAIWPTranslatorAdmin.showGlobalNotice('error', 'Failed to save settings. Please try again.');
                })
                .always(function() {
                    button.prop('disabled', false).text('Save Settings (AJAX)');
                });
            });
        },
        
        /**
         * Initialize translation actions
         */
        initTranslationActions: function() {
            console.log('NexusAI Debug: Initializing translation actions');
            
            // Manual translation trigger
            $(document).on('click', '#nexus-ai-wp-translate-post', function() {
                console.log('NexusAI Debug: Translate post button clicked');
                
                var button = $(this);
                var postId = NexusAIWPTranslatorAdmin.getPostId();
                var targetLanguages = [];
                
                $('.nexus-ai-wp-target-language:checked').each(function() {
                    targetLanguages.push($(this).val());
                });
                
                console.log('NexusAI Debug: Post ID:', postId, 'Target languages:', targetLanguages);
                
                if (targetLanguages.length === 0) {
                    alert('Please select at least one target language.');
                    return;
                }
                
                button.prop('disabled', true).text('Translating...');
                $('#nexus-ai-wp-translation-status').html('<div class="notice notice-info"><p>Translation in progress...</p></div>');
                
                $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                    action: 'nexus_ai_wp_translate_post',
                    post_id: postId,
                    target_languages: targetLanguages,
                    nonce: nexus_ai_wp_translator_ajax.nonce
                })
                .done(function(response) {
                    console.log('NexusAI Debug: Translation response:', response);
                    
                    var noticeClass = response.success ? 'notice-success' : 'notice-error';
                    var message = response.success ? 
                        'Translation completed successfully!' : 
                        ('Translation failed: ' + (response.message || 'Unknown error'));
                    
                    $('#nexus-ai-wp-translation-status').html('<div class="notice ' + noticeClass + '"><p>' + message + '</p></div>');
                    
                    if (response.success) {
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                })
                .fail(function(xhr, status, error) {
                    console.log('NexusAI Debug: Translation failed:', error);
                    $('#nexus-ai-wp-translation-status').html('<div class="notice notice-error"><p>Network error occurred</p></div>');
                })
                .always(function() {
                    button.prop('disabled', false).text('Translate Now');
                });
            });
            
            // Translation status check
            $(document).on('click', '#nexus-ai-wp-get-translation-status', function() {
                console.log('NexusAI Debug: Get translation status clicked');
                
                var button = $(this);
                var postId = NexusAIWPTranslatorAdmin.getPostId();
                
                button.prop('disabled', true);
                
                $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                    action: 'nexus_ai_wp_get_translation_status',
                    post_id: postId,
                    nonce: nexus_ai_wp_translator_ajax.nonce
                })
                .done(function(response) {
                    console.log('NexusAI Debug: Translation status response:', response);
                    
                    if (response.success && response.data.length > 0) {
                        var html = '<ul>';
                        $.each(response.data, function(i, translation) {
                            html += '<li>' + translation.target_language + ': ' + translation.status + '</li>';
                        });
                        html += '</ul>';
                        $('#nexus-ai-wp-translation-status').html(html);
                    } else {
                        $('#nexus-ai-wp-translation-status').html('<p>No translations found.</p>');
                    }
                })
                .always(function() {
                    button.prop('disabled', false);
                });
            });
            
            // Unlink translation
            $(document).on('click', '.nexus-ai-wp-unlink-translation, .unlink-translation', function() {
                console.log('NexusAI Debug: Unlink translation clicked');
                
                if (!confirm('Are you sure you want to unlink this translation?')) {
                    return;
                }
                
                var button = $(this);
                var postId = button.data('post-id') || button.data('source-id');
                var relatedId = button.data('related-id') || button.data('translated-id');
                var row = button.closest('tr, li');
                
                button.prop('disabled', true).text('Unlinking...');
                
                $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                    action: 'nexus_ai_wp_unlink_translation',
                    post_id: postId,
                    related_post_id: relatedId,
                    nonce: nexus_ai_wp_translator_ajax.nonce
                })
                .done(function(response) {
                    console.log('NexusAI Debug: Unlink response:', response);
                    
                    if (response.success) {
                        row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error: ' + response.data);
                        button.prop('disabled', false).text('Unlink');
                    }
                })
                .fail(function(xhr, status, error) {
                    console.log('NexusAI Debug: Unlink failed:', error);
                    alert('Network error occurred');
                    button.prop('disabled', false).text('Unlink');
                });
            });
        },
        
        /**
         * Initialize status refresh functionality
         */
        initStatusRefresh: function() {
            console.log('NexusAI Debug: Initializing status refresh');
            
            $('#nexus-ai-wp-refresh-stats').on('click', function() {
                console.log('NexusAI Debug: Refresh stats clicked');
                
                var button = $(this);
                button.prop('disabled', true).text('Refreshing...');
                
                $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                    action: 'nexus_ai_wp_get_stats',
                    period: '7 days',
                    nonce: nexus_ai_wp_translator_ajax.nonce
                })
                .done(function(response) {
                    console.log('NexusAI Debug: Refresh stats response:', response);
                    
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error refreshing stats');
                    }
                })
                .always(function() {
                    button.prop('disabled', false).text('Refresh Stats');
                });
            });
        },
        
        /**
         * Initialize bulk actions
         */
        initBulkActions: function() {
            console.log('NexusAI Debug: Initializing bulk actions');
            
            $('#cleanup-orphaned').on('click', function() {
                console.log('NexusAI Debug: Cleanup orphaned clicked');
                
                if (!confirm('Are you sure you want to clean up orphaned relationships? This will remove all relationships where posts have been deleted.')) {
                    return;
                }
                
                var button = $(this);
                button.prop('disabled', true).text('Cleaning up...');
                
                $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                    action: 'nexus_ai_wp_cleanup_orphaned',
                    nonce: nexus_ai_wp_translator_ajax.nonce
                })
                .done(function(response) {
                    console.log('NexusAI Debug: Cleanup response:', response);
                    
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                })
                .always(function() {
                    button.prop('disabled', false).text('Clean Up Orphaned Relationships');
                });
            });
        },
        
        /**
         * Show notice in a specific container
         */
        showNotice: function(container, type, message) {
            console.log('NexusAI Debug: Showing notice:', type, message);
            var noticeClass = 'notice-' + type;
            container.html('<div class="notice ' + noticeClass + '"><p>' + message + '</p></div>');
        },
        
        /**
         * Show global notice after H1
         */
        showGlobalNotice: function(type, message) {
            console.log('NexusAI Debug: Showing global notice:', type, message);
            var noticeClass = 'notice-' + type;
            var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            // Remove existing notices
            $('.notice.is-dismissible').remove();
            
            // Add new notice
            notice.insertAfter('h1');
            
            // Auto-hide success notices
            if (type === 'success') {
                setTimeout(function() {
                    notice.fadeOut();
                }, 3000);
            }
        },
        
        /**
         * Get current post ID
         */
        getPostId: function() {
            var postId = $('#post_ID').val();
            if (!postId) {
                var urlParams = new URLSearchParams(window.location.search);
                postId = urlParams.get('post');
            }
            console.log('NexusAI Debug: Current post ID:', postId);
            return postId;
        }
    };
    
    // Make NexusAIWPTranslatorAdmin globally available
    window.NexusAIWPTranslatorAdmin = NexusAIWPTranslatorAdmin;
    
})(jQuery);