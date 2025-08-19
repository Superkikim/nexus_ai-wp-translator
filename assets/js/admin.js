/**
 * Nexus AI WP Translator Admin JavaScript
 */

// Debug: Log when script file is loaded
console.log('NexusAI Debug: admin.js file loaded');
console.log('NexusAI Debug: Current URL:', window.location.href);
console.log('NexusAI Debug: jQuery available:', typeof jQuery !== 'undefined');

// Check if jQuery is available
if (typeof jQuery === 'undefined') {
    console.error('NexusAI Debug: jQuery is not loaded!');
} else {
    console.log('NexusAI Debug: jQuery is available');
}

// Check if our localized variables are available
if (typeof nexus_ai_wp_translator_ajax === 'undefined') {
    console.error('NexusAI Debug: nexus_ai_wp_translator_ajax is not defined!');
    console.log('NexusAI Debug: Available global variables:', Object.keys(window));
} else {
    console.log('NexusAI Debug: AJAX variables available:', nexus_ai_wp_translator_ajax);
}

// Define the admin object immediately when script loads
var NexusAIWPTranslatorAdmin = {
    initialized: false,
    
    init: function() {
        if (this.initialized) {
            console.log('NexusAI Debug: Admin already initialized, skipping');
            return;
        }
        this.initialized = true;
        
        console.log('NexusAI Debug: Starting admin initialization');
        console.log('NexusAI Debug: Admin object initialized successfully');
        this.initTabSwitching();
        this.initApiTesting();
        this.initSettingsSave();
        this.initTranslationActions();
        this.initStatusRefresh();
        this.initBulkActions();
        this.initProgressDialog();
        this.initBulkActionsInterface();
        
        // Load models on page load if API key exists
        var apiKey = $('#nexus_ai_wp_translator_api_key').val();
        console.log('NexusAI Debug: API key on page load:', apiKey ? 'EXISTS (length: ' + apiKey.length + ')' : 'NOT FOUND');
        
        if (apiKey && apiKey.trim().length > 0) {
            console.log('NexusAI Debug: API key found on page load, loading models automatically');
            this.loadAvailableModels();
        } else {
            console.log('NexusAI Debug: No API key found on page load, skipping model load');
        }
        
        // Check for auto translation on post edit pages
        if ($('#post_ID').length > 0) {
            console.log('NexusAI Debug: Post edit page detected, checking for auto translation');
            var self = this;
            setTimeout(function() {
                self.checkAutoTranslation();
            }, 1000); // Wait 1 second for page to fully load
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

        // Clear translation cache button
        $('#nexus-ai-wp-clear-cache').on('click', function() {
            console.log('NexusAI Debug: Clear cache button clicked');

            var button = $(this);
            var resultSpan = $('#nexus-ai-wp-clear-cache-result');

            button.prop('disabled', true).text('Clearing...');
            resultSpan.text('');

            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_clear_translation_cache',
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                console.log('NexusAI Debug: Clear cache response:', response);
                if (response.success) {
                    resultSpan.html('<span style="color: green;">✓ ' + response.data + '</span>');
                } else {
                    resultSpan.html('<span style="color: red;">✗ ' + response.data + '</span>');
                }
            })
            .fail(function(xhr, status, error) {
                console.log('NexusAI Debug: Clear cache failed:', error);
                resultSpan.html('<span style="color: red;">✗ Failed to clear cache</span>');
            })
            .always(function() {
                button.prop('disabled', false).text('Clear Translation Cache');
                // Clear the result message after 3 seconds
                setTimeout(function() {
                    resultSpan.fadeOut(500, function() {
                        resultSpan.text('').show();
                    });
                }, 3000);
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

            // Debug: Check if AJAX variables are available
            if (typeof nexus_ai_wp_translator_ajax === 'undefined') {
                console.error('NexusAI Debug: AJAX variables not available!');
                alert('Error: AJAX variables not loaded');
                return;
            }

            // Get post title for progress dialog
            var postTitle = $('#title').val() || $('#post-title-0').val() || 'Untitled Post';

            // Start translation with progress dialog
            NexusAIWPTranslatorAdmin.startTranslationWithProgress(postId, postTitle, targetLanguages, button);
        });

        // Resume translation trigger
        $(document).on('click', '.resume-translation-btn', function() {
            console.log('NexusAI Debug: Resume translation button clicked');

            var button = $(this);
            var postId = button.data('post-id');
            var postTitle = button.data('post-title');
            var resumableLanguages = button.data('resumable-languages').split(',');

            console.log('NexusAI Debug: Resume for post ID:', postId, 'Languages:', resumableLanguages);

            if (!confirm('Resume failed translations for: ' + resumableLanguages.join(', ') + '?')) {
                return;
            }

            // Start resume process with progress dialog
            NexusAIWPTranslatorAdmin.startResumeTranslation(postId, postTitle, resumableLanguages, button);
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
     * Initialize progress dialog event handlers
     */
    initProgressDialog: function() {
        console.log('NexusAI Debug: Initializing progress dialog');

        // Progress dialog close button
        $(document).on('click', '#nexus-ai-wp-progress-close', function() {
            $('#nexus-ai-wp-progress-overlay').hide();
        });

        // Progress dialog cancel button
        $(document).on('click', '#nexus-ai-wp-progress-cancel', function() {
            if (confirm('Are you sure you want to cancel the translation?')) {
                $('#nexus-ai-wp-progress-overlay').hide();
                // TODO: Implement actual cancellation logic
            }
        });

        // Success dialog close buttons
        $(document).on('click', '#nexus-ai-wp-success-close, #nexus-ai-wp-success-close-btn', function() {
            $('#nexus-ai-wp-success-overlay').hide();
        });

        // Success dialog view translations button
        $(document).on('click', '#nexus-ai-wp-success-view', function() {
            // Reload page to show new translations
            location.reload();
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
    },
    

    
    /**
     * Show auto translation progress popup
     */
    showAutoTranslationProgress: function(data) {
        console.log('NexusAI Debug: Showing auto translation progress:', data);
        
        // Language names mapping
        var languageNames = {
            'en': 'English',
            'es': 'Spanish', 
            'fr': 'French',
            'de': 'German',
            'it': 'Italian',
            'pt': 'Portuguese',
            'ru': 'Russian',
            'ja': 'Japanese',
            'ko': 'Korean',
            'zh': 'Chinese',
            'ar': 'Arabic',
            'hi': 'Hindi',
            'nl': 'Dutch',
            'sv': 'Swedish',
            'da': 'Danish',
            'no': 'Norwegian',
            'fi': 'Finnish',
            'pl': 'Polish',
            'cs': 'Czech',
            'hu': 'Hungarian'
        };
        
        var languagesHtml = '';
        $.each(data.target_languages, function(i, langCode) {
            var langName = languageNames[langCode] || langCode;
            var isCompleted = data.completed_languages.indexOf(langCode) !== -1;
            var isFailed = data.failed_languages.indexOf(langCode) !== -1;
            var status = 'processing';
            var statusText = 'Translating...';
            var iconHtml = '<div class="nexus-ai-wp-progress-spinner"></div>';
            
            if (isCompleted) {
                status = 'completed';
                statusText = 'Completed';
                iconHtml = '<div class="nexus-ai-wp-progress-check"></div>';
            } else if (isFailed) {
                status = 'error';
                statusText = 'Failed';
                iconHtml = '<div class="nexus-ai-wp-progress-error"></div>';
            }
            
            languagesHtml += 
                '<div class="nexus-ai-wp-progress-language ' + status + '" data-lang="' + langCode + '">' +
                    '<div class="nexus-ai-wp-progress-language-info">' +
                        '<span class="nexus-ai-wp-progress-language-name">' + langName + '</span>' +
                        '<span class="nexus-ai-wp-progress-language-code">' + langCode + '</span>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-progress-status">' +
                        '<div class="nexus-ai-wp-progress-icon">' + iconHtml + '</div>' +
                        '<span>' + statusText + '</span>' +
                    '</div>' +
                '</div>';
        });
        
        var summaryHtml = '';
        var buttonsHtml = '';
        
        if (data.all_done) {
            var successCount = data.completed_languages.length;
            var errorCount = data.failed_languages.length;
            
            if (errorCount === 0) {
                summaryHtml = '<div class="nexus-ai-wp-progress-summary success">' +
                    '<strong>Auto Translation Completed!</strong><br>' +
                    'Successfully translated "' + data.post_title + '" to ' + successCount + ' language(s).' +
                    '</div>';
            } else {
                summaryHtml = '<div class="nexus-ai-wp-progress-summary error">' +
                    '<strong>Auto Translation Completed with Errors</strong><br>' +
                    'Success: ' + successCount + ', Errors: ' + errorCount +
                    '</div>';
            }
            
            buttonsHtml = '<div class="nexus-ai-wp-progress-buttons">' +
                '<button class="button button-primary" onclick="location.reload()">Refresh Page</button>' +
                '<button class="button" onclick="NexusAIWPTranslatorAdmin.dismissAutoTranslation()">Dismiss</button>' +
                '</div>';
        }
        
        var popupHtml = 
            '<div class="nexus-ai-wp-progress-popup" id="nexus-ai-wp-auto-progress-popup">' +
                '<div class="nexus-ai-wp-progress-content">' +
                    '<button class="nexus-ai-wp-progress-close" onclick="NexusAIWPTranslatorAdmin.dismissAutoTranslation()">&times;</button>' +
                    '<div class="nexus-ai-wp-progress-header">' +
                        '<h3>' + (data.all_done ? 'Auto Translation Complete' : 'Auto Translation in Progress') + '</h3>' +
                        '<p>Translating "' + data.post_title + '"...</p>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-progress-languages">' +
                        languagesHtml +
                    '</div>' +
                    summaryHtml +
                    buttonsHtml +
                '</div>' +
            '</div>';
        
        // Remove existing popup
        $('#nexus-ai-wp-auto-progress-popup').remove();
        
        // Add new popup
        $('body').append(popupHtml);
        $('#nexus-ai-wp-auto-progress-popup').addClass('show');
        
        // If not all done, poll for updates
        if (!data.all_done) {
            setTimeout(function() {
                NexusAIWPTranslatorAdmin.checkAutoTranslation();
            }, 2000); // Check every 2 seconds
        }
    },
    
    /**
     * Dismiss auto translation popup
     */
    dismissAutoTranslation: function() {
        console.log('NexusAI Debug: Dismissing auto translation popup');

        var postId = this.getPostId();
        if (postId) {
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_dismiss_auto_translation',
                post_id: postId,
                nonce: nexus_ai_wp_translator_ajax.nonce
            });
        }

        $('#nexus-ai-wp-auto-progress-popup').removeClass('show').fadeOut(300, function() {
            $(this).remove();
        });
    },

    /**
     * Start translation with progress dialog
     */
    startTranslationWithProgress: function(postId, postTitle, targetLanguages, button) {
        console.log('NexusAI Debug: Starting translation with progress dialog');

        // Show progress dialog
        this.showProgressDialog(postTitle, targetLanguages);

        // Disable the translate button
        button.prop('disabled', true).text('Translating...');

        // Start the translation process
        this.performTranslationWithProgress(postId, targetLanguages, button);
    },

    /**
     * Show progress dialog
     */
    showProgressDialog: function(postTitle, targetLanguages) {
        console.log('NexusAI Debug: Showing progress dialog');

        // Update dialog content
        $('#nexus-ai-wp-progress-post-title').text(postTitle);
        $('#nexus-ai-wp-progress-target-langs').text(targetLanguages.join(', '));

        // Reset progress
        $('#nexus-ai-wp-progress-bar').css('width', '0%');
        $('#nexus-ai-wp-progress-percentage').text('0%');
        $('#nexus-ai-wp-progress-steps').empty();

        // Show dialog
        $('#nexus-ai-wp-progress-overlay').css('display', 'flex');

        // Initialize progress steps
        this.initializeProgressSteps(targetLanguages);
    },

    /**
     * Initialize progress steps
     */
    initializeProgressSteps: function(targetLanguages) {
        var stepsContainer = $('#nexus-ai-wp-progress-steps');
        var steps = [
            {id: 'title', title: 'Translating Title', description: 'Processing post title...'},
            {id: 'content', title: 'Translating Content', description: 'Processing content blocks...'},
            {id: 'excerpt', title: 'Translating Excerpt', description: 'Processing post excerpt...'},
            {id: 'categories', title: 'Translating Categories', description: 'Processing categories...'},
            {id: 'tags', title: 'Translating Tags', description: 'Processing tags...'},
            {id: 'creating', title: 'Creating Posts', description: 'Creating translated posts...'}
        ];

        stepsContainer.empty();

        steps.forEach(function(step) {
            var stepHtml =
                '<div class="nexus-ai-wp-progress-step pending" id="step-' + step.id + '">' +
                    '<div class="nexus-ai-wp-progress-step-icon">⏳</div>' +
                    '<div class="nexus-ai-wp-progress-step-content">' +
                        '<div class="nexus-ai-wp-progress-step-title">' + step.title + '</div>' +
                        '<div class="nexus-ai-wp-progress-step-description">' + step.description + '</div>' +
                    '</div>' +
                '</div>';
            stepsContainer.append(stepHtml);
        });
    },

    /**
     * Perform translation with progress updates
     */
    performTranslationWithProgress: function(postId, targetLanguages, button) {
        var self = this;

        console.log('NexusAI Debug: Starting AJAX translation request');

        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_translate_post',
            post_id: postId,
            target_languages: targetLanguages,
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            console.log('NexusAI Debug: Translation response:', response);

            if (response.success) {
                self.handleTranslationSuccess(response, button);
            } else {
                self.handleTranslationError(response.message || 'Unknown error', button);
            }
        })
        .fail(function(xhr, status, error) {
            console.log('NexusAI Debug: Translation failed:', error);
            self.handleTranslationError('Network error: ' + error, button);
        });

        // Simulate progress updates (since we don't have real-time updates yet)
        this.simulateProgress();
    },

    /**
     * Simulate progress updates
     */
    simulateProgress: function() {
        var self = this;
        var progress = 0;
        var steps = ['title', 'content', 'excerpt', 'categories', 'tags', 'creating'];
        var currentStep = 0;

        var progressInterval = setInterval(function() {
            progress += Math.random() * 15 + 5; // Random progress between 5-20%

            if (progress > 100) progress = 100;

            // Update progress bar
            $('#nexus-ai-wp-progress-bar').css('width', progress + '%');
            $('#nexus-ai-wp-progress-percentage').text(Math.round(progress) + '%');

            // Update current step
            if (currentStep < steps.length && progress > (currentStep + 1) * 16) {
                self.updateStepStatus(steps[currentStep], 'completed');
                currentStep++;
                if (currentStep < steps.length) {
                    self.updateStepStatus(steps[currentStep], 'processing');
                }
            }

            if (progress >= 100) {
                clearInterval(progressInterval);
            }
        }, 800);

        // Store interval for cleanup
        this.progressInterval = progressInterval;
    },

    /**
     * Update step status
     */
    updateStepStatus: function(stepId, status) {
        var step = $('#step-' + stepId);
        var icon = step.find('.nexus-ai-wp-progress-step-icon');

        step.removeClass('pending processing completed failed').addClass(status);

        switch(status) {
            case 'processing':
                icon.text('⚡');
                break;
            case 'completed':
                icon.text('✓');
                break;
            case 'failed':
                icon.text('✗');
                break;
            default:
                icon.text('⏳');
        }
    },

    /**
     * Handle translation success
     */
    handleTranslationSuccess: function(response, button) {
        console.log('NexusAI Debug: Handling translation success');

        // Clear progress interval
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }

        // Complete all steps
        var steps = ['title', 'content', 'excerpt', 'categories', 'tags', 'creating'];
        var self = this;
        steps.forEach(function(stepId) {
            self.updateStepStatus(stepId, 'completed');
        });

        // Update progress to 100%
        $('#nexus-ai-wp-progress-bar').css('width', '100%');
        $('#nexus-ai-wp-progress-percentage').text('100%');

        // Show success dialog after a short delay
        setTimeout(function() {
            self.showSuccessDialog(response);
            button.prop('disabled', false).text('Translate Now');
        }, 1000);
    },

    /**
     * Handle translation error
     */
    handleTranslationError: function(errorMessage, button) {
        console.log('NexusAI Debug: Handling translation error:', errorMessage);

        // Clear progress interval
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }

        // Mark current step as failed
        $('.nexus-ai-wp-progress-step.processing').removeClass('processing').addClass('failed');
        $('.nexus-ai-wp-progress-step.failed .nexus-ai-wp-progress-step-icon').text('✗');
        $('.nexus-ai-wp-progress-step.failed .nexus-ai-wp-progress-step-description').text('Error: ' + errorMessage);

        // Enable cancel button to close
        $('#nexus-ai-wp-progress-cancel').text('Close').prop('disabled', false);

        // Re-enable translate button
        button.prop('disabled', false).text('Translate Now');
    },

    /**
     * Show success dialog
     */
    showSuccessDialog: function(response) {
        console.log('NexusAI Debug: Showing success dialog');

        var successCount = response.success_count || 0;
        var errorCount = response.error_count || 0;
        var totalLanguages = successCount + errorCount;

        var message = 'Successfully translated to ' + successCount + ' language(s)';
        if (errorCount > 0) {
            message += ' (' + errorCount + ' failed)';
        }

        $('#nexus-ai-wp-success-message').text(message);
        $('#nexus-ai-wp-success-details').html(
            '<strong>API Calls:</strong> ' + (response.total_api_calls || 0) + '<br>' +
            '<strong>Processing Time:</strong> ' + (response.total_processing_time || 0) + 's'
        );

        // Hide progress dialog and show success dialog
        $('#nexus-ai-wp-progress-overlay').hide();
        $('#nexus-ai-wp-success-overlay').css('display', 'flex');
    },

    /**
     * Initialize bulk actions interface
     */
    initBulkActionsInterface: function() {
        console.log('NexusAI Debug: Initializing bulk actions interface');

        // Update selection count when checkboxes change
        $(document).on('change', '.select-post-checkbox, .select-all-checkbox', function() {
            NexusAIWPTranslatorAdmin.updateBulkSelectionCount();
        });

        // Enable/disable apply button when action is selected
        $(document).on('change', '.nexus-ai-wp-bulk-action-select', function() {
            var applyButton = $(this).siblings('.nexus-ai-wp-bulk-action-apply');
            var selectedCount = $(this).closest('.nexus-ai-wp-bulk-actions-container').siblings('table').find('.select-post-checkbox:checked').length;

            if ($(this).val() && selectedCount > 0) {
                applyButton.prop('disabled', false);
            } else {
                applyButton.prop('disabled', true);
            }
        });

        // Handle bulk action apply
        $(document).on('click', '.nexus-ai-wp-bulk-action-apply', function() {
            var button = $(this);
            var postType = button.data('post-type');
            var action = button.siblings('.nexus-ai-wp-bulk-action-select').val();
            var selectedPosts = [];

            // Get selected posts
            button.closest('.nexus-ai-wp-bulk-actions-container').siblings('table').find('.select-post-checkbox:checked').each(function() {
                selectedPosts.push({
                    id: $(this).data('post-id'),
                    title: $(this).closest('tr').find('td:nth-child(2) strong a').text(),
                    language: $(this).data('language')
                });
            });

            if (selectedPosts.length === 0) {
                alert('Please select at least one item.');
                return;
            }

            console.log('NexusAI Debug: Bulk action:', action, 'for posts:', selectedPosts);

            // Handle different actions
            switch(action) {
                case 'translate':
                    NexusAIWPTranslatorAdmin.handleBulkTranslate(selectedPosts);
                    break;
                case 'link':
                    NexusAIWPTranslatorAdmin.handleBulkLink(selectedPosts);
                    break;
                case 'unlink':
                    NexusAIWPTranslatorAdmin.handleBulkUnlink(selectedPosts);
                    break;
                case 'delete':
                    NexusAIWPTranslatorAdmin.handleBulkDelete(selectedPosts);
                    break;
                case 'clear_cache':
                    NexusAIWPTranslatorAdmin.handleBulkClearCache(selectedPosts);
                    break;
                default:
                    alert('Please select an action.');
            }
        });
    },

    /**
     * Update bulk selection count
     */
    updateBulkSelectionCount: function() {
        $('.nexus-ai-wp-bulk-actions-container').each(function() {
            var container = $(this);
            var selectedCount = container.siblings('table').find('.select-post-checkbox:checked').length;
            var countSpan = container.find('.nexus-ai-wp-bulk-selection-count');
            var applyButton = container.find('.nexus-ai-wp-bulk-action-apply');
            var actionSelect = container.find('.nexus-ai-wp-bulk-action-select');

            countSpan.text(selectedCount + ' items selected');

            if (selectedCount > 0) {
                countSpan.addClass('has-selection');
                if (actionSelect.val()) {
                    applyButton.prop('disabled', false);
                }
            } else {
                countSpan.removeClass('has-selection');
                applyButton.prop('disabled', true);
            }
        });
    },

    /**
     * Handle bulk translate action
     */
    handleBulkTranslate: function(selectedPosts) {
        console.log('NexusAI Debug: Handling bulk translate');

        // Check existing translations and show dialog
        this.showBulkTranslateDialog(selectedPosts);
    },

    /**
     * Show bulk translate dialog
     */
    showBulkTranslateDialog: function(selectedPosts) {
        var dialogHtml =
            '<div id="nexus-ai-wp-bulk-translate-dialog" class="nexus-ai-wp-bulk-dialog-overlay">' +
                '<div class="nexus-ai-wp-bulk-dialog">' +
                    '<div class="nexus-ai-wp-bulk-dialog-header">' +
                        '<h3>Bulk Translate</h3>' +
                        '<button type="button" class="nexus-ai-wp-bulk-dialog-close">&times;</button>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-bulk-dialog-body">' +
                        '<p>Select target languages for translation:</p>' +
                        '<div class="nexus-ai-wp-selected-items">';

        selectedPosts.forEach(function(post) {
            dialogHtml +=
                '<div class="nexus-ai-wp-selected-item">' +
                    '<div class="nexus-ai-wp-selected-item-title">' + post.title + '</div>' +
                    '<div class="nexus-ai-wp-selected-item-meta">ID: ' + post.id + ' | Language: ' + post.language + '</div>' +
                '</div>';
        });

        dialogHtml +=
                        '</div>' +
                        '<div class="nexus-ai-wp-language-selector">' +
                            '<label>Target Languages:</label>' +
                            '<div class="nexus-ai-wp-language-checkboxes">' +
                                '<label><input type="checkbox" value="es"> Spanish (es)</label>' +
                                '<label><input type="checkbox" value="fr"> French (fr)</label>' +
                                '<label><input type="checkbox" value="de"> German (de)</label>' +
                                '<label><input type="checkbox" value="it"> Italian (it)</label>' +
                                '<label><input type="checkbox" value="pt"> Portuguese (pt)</label>' +
                                '<label><input type="checkbox" value="ru"> Russian (ru)</label>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-bulk-dialog-footer">' +
                        '<button type="button" class="button nexus-ai-wp-bulk-dialog-cancel">Cancel</button>' +
                        '<button type="button" class="button button-primary nexus-ai-wp-bulk-translate-confirm">Start Translation</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $('body').append(dialogHtml);
        $('#nexus-ai-wp-bulk-translate-dialog').css('display', 'flex');

        // Handle dialog events
        this.initBulkDialogEvents('#nexus-ai-wp-bulk-translate-dialog', selectedPosts);
    },

    /**
     * Initialize bulk dialog events
     */
    initBulkDialogEvents: function(dialogId, selectedPosts) {
        var self = this;

        // Close dialog
        $(document).on('click', dialogId + ' .nexus-ai-wp-bulk-dialog-close, ' + dialogId + ' .nexus-ai-wp-bulk-dialog-cancel', function() {
            $(dialogId).remove();
        });

        // Handle translate confirm
        $(document).on('click', dialogId + ' .nexus-ai-wp-bulk-translate-confirm', function() {
            var selectedLanguages = [];
            $(dialogId + ' .nexus-ai-wp-language-checkboxes input:checked').each(function() {
                selectedLanguages.push($(this).val());
            });

            if (selectedLanguages.length === 0) {
                alert('Please select at least one target language.');
                return;
            }

            $(dialogId).remove();
            self.performBulkTranslation(selectedPosts, selectedLanguages);
        });
    },

    /**
     * Perform bulk translation
     */
    performBulkTranslation: function(selectedPosts, targetLanguages) {
        console.log('NexusAI Debug: Starting bulk translation for', selectedPosts.length, 'posts to', targetLanguages);

        // Show progress dialog for bulk translation
        this.showBulkTranslationProgress(selectedPosts, targetLanguages);

        // Process each post sequentially
        this.processBulkTranslation(selectedPosts, targetLanguages, 0);
    },

    /**
     * Show bulk translation progress
     */
    showBulkTranslationProgress: function(selectedPosts, targetLanguages) {
        var progressHtml =
            '<div id="nexus-ai-wp-bulk-progress-dialog" class="nexus-ai-wp-progress-overlay">' +
                '<div class="nexus-ai-wp-progress-dialog">' +
                    '<div class="nexus-ai-wp-progress-header">' +
                        '<h3>Bulk Translation Progress</h3>' +
                        '<button type="button" class="nexus-ai-wp-progress-close">&times;</button>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-progress-body">' +
                        '<div class="nexus-ai-wp-progress-info">' +
                            '<div class="nexus-ai-wp-progress-post-title">Translating ' + selectedPosts.length + ' posts</div>' +
                            '<div class="nexus-ai-wp-progress-languages">Target languages: ' + targetLanguages.join(', ') + '</div>' +
                        '</div>' +
                        '<div class="nexus-ai-wp-progress-bar-container">' +
                            '<div class="nexus-ai-wp-progress-bar"></div>' +
                            '<div class="nexus-ai-wp-progress-percentage">0%</div>' +
                        '</div>' +
                        '<div class="nexus-ai-wp-progress-steps" id="nexus-ai-wp-bulk-progress-steps">' +
                            // Steps will be added dynamically +
                        '</div>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-progress-footer">' +
                        '<button type="button" class="nexus-ai-wp-progress-cancel">Cancel</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $('body').append(progressHtml);
        $('#nexus-ai-wp-bulk-progress-dialog').css('display', 'flex');

        // Initialize progress steps for each post
        var stepsContainer = $('#nexus-ai-wp-bulk-progress-steps');
        selectedPosts.forEach(function(post, index) {
            var stepHtml =
                '<div class="nexus-ai-wp-progress-step pending" id="bulk-step-' + post.id + '">' +
                    '<div class="nexus-ai-wp-progress-step-icon">⏳</div>' +
                    '<div class="nexus-ai-wp-progress-step-content">' +
                        '<div class="nexus-ai-wp-progress-step-title">' + post.title + '</div>' +
                        '<div class="nexus-ai-wp-progress-step-description">Waiting to translate...</div>' +
                    '</div>' +
                '</div>';
            stepsContainer.append(stepHtml);
        });
    },

    /**
     * Process bulk translation sequentially
     */
    processBulkTranslation: function(selectedPosts, targetLanguages, currentIndex) {
        if (currentIndex >= selectedPosts.length) {
            // All posts processed
            this.completeBulkTranslation();
            return;
        }

        var currentPost = selectedPosts[currentIndex];
        var self = this;

        // Update progress
        var progress = (currentIndex / selectedPosts.length) * 100;
        $('#nexus-ai-wp-bulk-progress-dialog .nexus-ai-wp-progress-bar').css('width', progress + '%');
        $('#nexus-ai-wp-bulk-progress-dialog .nexus-ai-wp-progress-percentage').text(Math.round(progress) + '%');

        // Update current step
        $('#bulk-step-' + currentPost.id).removeClass('pending').addClass('processing');
        $('#bulk-step-' + currentPost.id + ' .nexus-ai-wp-progress-step-icon').text('⚡');
        $('#bulk-step-' + currentPost.id + ' .nexus-ai-wp-progress-step-description').text('Translating...');

        // Perform translation
        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_translate_post',
            post_id: currentPost.id,
            target_languages: targetLanguages,
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                $('#bulk-step-' + currentPost.id).removeClass('processing').addClass('completed');
                $('#bulk-step-' + currentPost.id + ' .nexus-ai-wp-progress-step-icon').text('✓');
                $('#bulk-step-' + currentPost.id + ' .nexus-ai-wp-progress-step-description').text('Completed successfully');
            } else {
                $('#bulk-step-' + currentPost.id).removeClass('processing').addClass('failed');
                $('#bulk-step-' + currentPost.id + ' .nexus-ai-wp-progress-step-icon').text('✗');
                $('#bulk-step-' + currentPost.id + ' .nexus-ai-wp-progress-step-description').text('Failed: ' + (response.message || 'Unknown error'));
            }
        })
        .fail(function() {
            $('#bulk-step-' + currentPost.id).removeClass('processing').addClass('failed');
            $('#bulk-step-' + currentPost.id + ' .nexus-ai-wp-progress-step-icon').text('✗');
            $('#bulk-step-' + currentPost.id + ' .nexus-ai-wp-progress-step-description').text('Network error');
        })
        .always(function() {
            // Process next post after a short delay
            setTimeout(function() {
                self.processBulkTranslation(selectedPosts, targetLanguages, currentIndex + 1);
            }, 1000);
        });
    },

    /**
     * Complete bulk translation
     */
    completeBulkTranslation: function() {
        // Update progress to 100%
        $('#nexus-ai-wp-bulk-progress-dialog .nexus-ai-wp-progress-bar').css('width', '100%');
        $('#nexus-ai-wp-bulk-progress-dialog .nexus-ai-wp-progress-percentage').text('100%');

        // Change cancel button to close
        $('#nexus-ai-wp-bulk-progress-dialog .nexus-ai-wp-progress-cancel').text('Close').removeClass('nexus-ai-wp-progress-cancel').addClass('nexus-ai-wp-progress-close');

        // Show completion message
        setTimeout(function() {
            alert('Bulk translation completed! The page will refresh to show the results.');
            location.reload();
        }, 2000);
    },

    /**
     * Handle bulk link action
     */
    handleBulkLink: function(selectedPosts) {
        if (selectedPosts.length < 2) {
            alert('Please select at least 2 posts to link together.');
            return;
        }

        // Show link dialog to select source post
        this.showBulkLinkDialog(selectedPosts);
    },

    /**
     * Handle bulk unlink action
     */
    handleBulkUnlink: function(selectedPosts) {
        if (!confirm('Are you sure you want to unlink the selected posts? This will remove all translation relationships between them.')) {
            return;
        }

        // Perform bulk unlink
        this.performBulkUnlink(selectedPosts);
    },

    /**
     * Handle bulk delete action
     */
    handleBulkDelete: function(selectedPosts) {
        if (!confirm('Are you sure you want to delete the selected posts? This action cannot be undone.')) {
            return;
        }

        // Perform bulk delete
        this.performBulkDelete(selectedPosts);
    },

    /**
     * Handle bulk clear cache action
     */
    handleBulkClearCache: function(selectedPosts) {
        if (!confirm('Are you sure you want to clear translation cache for the selected posts?')) {
            return;
        }

        // Perform bulk cache clear
        this.performBulkClearCache(selectedPosts);
    },

    /**
     * Start resume translation with progress dialog
     */
    startResumeTranslation: function(postId, postTitle, resumableLanguages, button) {
        console.log('NexusAI Debug: Starting resume translation');

        // Show progress dialog
        this.showProgressDialog(postTitle + ' (Resume)', resumableLanguages);

        // Disable the resume button
        button.prop('disabled', true).text('Resuming...');

        // Start the resume process
        this.performResumeTranslation(postId, resumableLanguages, button);
    },

    /**
     * Perform resume translation
     */
    performResumeTranslation: function(postId, resumableLanguages, button) {
        var self = this;

        console.log('NexusAI Debug: Starting AJAX resume translation request');

        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_resume_translation',
            post_id: postId,
            target_languages: resumableLanguages,
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            console.log('NexusAI Debug: Resume translation response:', response);

            if (response.success) {
                self.handleTranslationSuccess(response, button);
            } else {
                self.handleTranslationError(response.message || 'Unknown error', button);
            }
        })
        .fail(function(xhr, status, error) {
            console.log('NexusAI Debug: Resume translation failed:', error);
            self.handleTranslationError('Network error: ' + error, button);
        });

        // Simulate progress updates for resume
        this.simulateResumeProgress();
    },

    /**
     * Simulate resume progress updates
     */
    simulateResumeProgress: function() {
        var self = this;
        var progress = 25; // Start at 25% since we're resuming
        var steps = ['title', 'content', 'excerpt', 'categories', 'tags', 'creating'];
        var currentStep = 1; // Start from content since title might be done

        // Mark title as completed
        this.updateStepStatus('title', 'completed');
        this.updateStepStatus('content', 'processing');

        var progressInterval = setInterval(function() {
            progress += Math.random() * 15 + 10; // Faster progress for resume

            if (progress > 100) progress = 100;

            // Update progress bar
            $('#nexus-ai-wp-progress-bar').css('width', progress + '%');
            $('#nexus-ai-wp-progress-percentage').text(Math.round(progress) + '%');

            // Update current step
            if (currentStep < steps.length && progress > (currentStep + 1) * 16) {
                self.updateStepStatus(steps[currentStep], 'completed');
                currentStep++;
                if (currentStep < steps.length) {
                    self.updateStepStatus(steps[currentStep], 'processing');
                }
            }

            if (progress >= 100) {
                clearInterval(progressInterval);
            }
        }, 600); // Faster interval for resume

        // Store interval for cleanup
        this.progressInterval = progressInterval;
    }
};

// Make NexusAIWPTranslatorAdmin globally available immediately
window.NexusAIWPTranslatorAdmin = NexusAIWPTranslatorAdmin;
console.log('NexusAI Debug: NexusAIWPTranslatorAdmin made globally available');

(function($) {
    'use strict';
    
    console.log('NexusAI Debug: JavaScript function wrapper started');
    
    // Initialize when document is ready
    $(document).ready(function() {
        console.log('NexusAI Debug: Document ready, initializing admin interface');
        if (window.NexusAIWPTranslatorAdmin) {
            window.NexusAIWPTranslatorAdmin.init();
        } else {
            console.error('NexusAI Debug: NexusAIWPTranslatorAdmin not available in document ready!');
        }
    });
    
})(jQuery);
