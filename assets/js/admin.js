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
        var $ = jQuery; // Ensure $ is available within this method
        
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
        this.initQualityAssessmentInterface();
        this.initTranslationQueueInterface();
        
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
        var $ = jQuery; // Ensure $ is available within this method
        
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
        var $ = jQuery; // Ensure $ is available within this method
        
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
        
        // Note: API key toggle is handled in inline script (admin-settings.php) for proper i18n support
    },
    
    /**
     * Auto-save API key
     */
    autoSaveApiKey: function(apiKey, callback) {
        var $ = jQuery; // Ensure $ is available within this method
        
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
        var $ = jQuery; // Ensure $ is available within this method
        
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
        var $ = jQuery; // Ensure $ is available within this method
        
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
        var $ = jQuery; // Ensure $ is available within this method
        
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
                
                // Add "Select model" placeholder first
                modelSelect.append('<option value="">Select model</option>');
                
                // Add models to dropdown
                var modelCount = 0;
                $.each(response.models, function(modelId, displayName) {
                    var selected = (modelId === currentSelection) ? 'selected' : '';
                    modelSelect.append('<option value="' + modelId + '" ' + selected + '>' + displayName + '</option>');
                    console.log('NexusAI Debug: Added model:', modelId, '→', displayName);
                    modelCount++;
                });
                
                console.log('NexusAI Debug: Total models added:', modelCount);
            } else {
                console.log('NexusAI Debug: Failed to get models or no models in response');
                console.log('NexusAI Debug: Response success:', response.success);
                console.log('NexusAI Debug: Response models:', response.models);
                console.log('NexusAI Debug: Full response object:', response);
                
                // Show error message instead of default models
                modelSelect.empty();
                modelSelect.append('<option value="">Failed to load models - check API key</option>');
            }
        })
        .fail(function(xhr, status, error) {
            console.log('NexusAI Debug: Get models AJAX request failed:', error);
            console.log('NexusAI Debug: XHR status:', status);
            console.log('NexusAI Debug: XHR status code:', xhr.status);
            console.log('NexusAI Debug: XHR response:', xhr.responseText);
            console.log('NexusAI Debug: XHR status code:', xhr.status);
            console.log('NexusAI Debug: Full XHR object:', xhr);
            
            // Show network error instead of default models
            modelSelect.empty();
            modelSelect.append('<option value="">Network error - check connection</option>');
        })
        .always(function() {
            console.log('NexusAI Debug: API test request completed');
            console.log('NexusAI Debug: Load models request completed');
            if (callback) callback();
        });
    },
    
    
    /**
     * Initialize settings save functionality
     */
    initSettingsSave: function() {
        var $ = jQuery; // Ensure $ is available within this method
        
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
        var $ = jQuery; // Ensure $ is available within this method
        
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

        // Add to queue trigger
        $(document).on('click', '.add-to-queue-btn', function() {
            console.log('NexusAI Debug: Add to queue button clicked');

            var button = $(this);
            var postId = button.data('post-id');
            var postTitle = button.data('post-title');

            console.log('NexusAI Debug: Add to queue for post ID:', postId);

            NexusAIWPTranslatorAdmin.showAddToQueueDialog(postId, postTitle);
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
        var $ = jQuery; // Ensure $ is available within this method
        
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
        var $ = jQuery; // Ensure $ is available within this method
        
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
        var $ = jQuery; // Ensure $ is available within this method
        
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

        console.log('NexusAI Debug: Starting AJAX translation request with real-time progress');

        // Generate unique progress ID
        var progressId = 'trans_' + postId + '_' + targetLanguages.join('_') + '_' + Date.now() + '_' + Math.floor(Math.random() * 10000);

        // Start translation
        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_translate_post',
            post_id: postId,
            target_languages: targetLanguages,
            progress_id: progressId,
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

        // Start real-time progress tracking
        this.startRealTimeProgressTracking(progressId);
    },

    /**
     * Start real-time progress tracking
     */
    startRealTimeProgressTracking: function(progressId) {
        var self = this;
        console.log('NexusAI Debug: Starting real-time progress tracking for:', progressId);

        // Poll for progress updates every 500ms
        var progressInterval = setInterval(function() {
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_get_progress',
                progress_id: progressId,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    self.updateRealTimeProgress(response.data);

                    // Stop polling if translation is complete or failed
                    if (response.data.status === 'completed' || response.data.status === 'failed') {
                        clearInterval(progressInterval);
                        console.log('NexusAI Debug: Progress tracking completed:', response.data.status);
                    }
                } else {
                    console.log('NexusAI Debug: Progress tracking error:', response.message);
                }
            })
            .fail(function() {
                console.log('NexusAI Debug: Progress tracking request failed');
            });
        }, 500);

        // Store interval for cleanup
        this.progressInterval = progressInterval;

        // Cleanup after 5 minutes (safety)
        setTimeout(function() {
            if (self.progressInterval) {
                clearInterval(self.progressInterval);
                console.log('NexusAI Debug: Progress tracking timeout - cleaning up');
            }
        }, 300000);
    },

    /**
     * Update progress with real-time data
     */
    updateRealTimeProgress: function(progressData) {
        console.log('NexusAI Debug: Updating real-time progress:', progressData);

        // Update progress bar
        var percentage = progressData.progress_percentage || 0;
        $('#nexus-ai-wp-progress-bar').css('width', percentage + '%');
        $('#nexus-ai-wp-progress-percentage').text(Math.round(percentage) + '%');

        // Update current step status
        if (progressData.current_step) {
            // Reset all steps to pending first
            $('.nexus-ai-wp-progress-step').removeClass('processing completed failed').addClass('pending');
            $('.nexus-ai-wp-progress-step .nexus-ai-wp-progress-step-icon').text('⏳');

            // Update steps based on progress history
            if (progressData.steps && progressData.steps.length > 0) {
                var stepMap = {
                    'title': 'title',
                    'content_block': 'content',
                    'excerpt': 'excerpt',
                    'categories': 'categories',
                    'tags': 'tags'
                };

                var completedSteps = {};
                var currentStep = null;

                // Process all steps to determine status
                progressData.steps.forEach(function(step) {
                    var mappedStep = stepMap[step.step] || step.step;

                    if (step.status === 'completed') {
                        completedSteps[mappedStep] = 'completed';
                    } else if (step.status === 'processing') {
                        currentStep = mappedStep;
                    } else if (step.status === 'failed') {
                        completedSteps[mappedStep] = 'failed';
                    }
                });

                // Update completed steps
                Object.keys(completedSteps).forEach(function(stepId) {
                    var status = completedSteps[stepId];
                    $('#step-' + stepId).removeClass('pending processing').addClass(status);
                    var icon = status === 'completed' ? '✓' : '✗';
                    $('#step-' + stepId + ' .nexus-ai-wp-progress-step-icon').text(icon);
                });

                // Update current processing step
                if (currentStep) {
                    $('#step-' + currentStep).removeClass('pending').addClass('processing');
                    $('#step-' + currentStep + ' .nexus-ai-wp-progress-step-icon').text('⚡');
                }
            }
        }

        // Update step descriptions with latest messages
        if (progressData.steps && progressData.steps.length > 0) {
            var latestStep = progressData.steps[progressData.steps.length - 1];
            if (latestStep && latestStep.message) {
                var stepElement = $('#step-' + (latestStep.step === 'content_block' ? 'content' : latestStep.step));
                if (stepElement.length > 0) {
                    stepElement.find('.nexus-ai-wp-progress-step-description').text(latestStep.message);
                }
            }
        }
    },

    /**
     * Simulate progress updates (fallback)
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
            NexusAIWPTranslatorAdmin.updateBulkActionButtons();
        });

        // Enable/disable apply button when action is selected
        $(document).on('change', '.nexus-ai-wp-bulk-action-select', function() {
            NexusAIWPTranslatorAdmin.updateBulkActionButtons();
        });

        // Handle bulk action apply
        $(document).on('click', '.nexus-ai-wp-bulk-action-apply', function() {
            var button = $(this);
            var postType = button.data('post-type');
            var action = button.siblings('.nexus-ai-wp-bulk-action-select').val();
            var selectedPosts = [];

            // Get selected posts
            button.closest('.nexus-ai-wp-bulk-actions-container').next('table').find('.select-post-checkbox:checked').each(function() {
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
                case 'set_language':
                    NexusAIWPTranslatorAdmin.handleBulkSetLanguage(selectedPosts);
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
            var selectedCount = container.next('table').find('.select-post-checkbox:checked').length;
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
     * Update bulk action buttons state
     */
    updateBulkActionButtons: function() {
        $('.nexus-ai-wp-bulk-actions-container').each(function() {
            var container = $(this);
            var selectedCount = container.next('table').find('.select-post-checkbox:checked').length;
            var applyButton = container.find('.nexus-ai-wp-bulk-action-apply');
            var actionSelect = container.find('.nexus-ai-wp-bulk-action-select');

            if (selectedCount > 0 && actionSelect.val()) {
                applyButton.prop('disabled', false);
            } else {
                applyButton.prop('disabled', true);
            }
        });
    },

    /**
     * Handle bulk set language action
     */
    handleBulkSetLanguage: function(selectedPosts) {
        console.log('NexusAI Debug: Handling bulk set language');

        // Show language selection dialog
        this.showBulkSetLanguageDialog(selectedPosts);
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
     * Show bulk link dialog
     */
    showBulkLinkDialog: function(selectedPosts) {
        var dialogHtml =
            '<div id="nexus-ai-wp-link-dialog" class="nexus-ai-wp-bulk-dialog-overlay">' +
                '<div class="nexus-ai-wp-bulk-dialog">' +
                    '<div class="nexus-ai-wp-bulk-dialog-header">' +
                        '<h3>Link Selected Posts</h3>' +
                        '<button type="button" class="nexus-ai-wp-bulk-dialog-close">&times;</button>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-bulk-dialog-body">' +
                        '<p><strong>Selected Posts:</strong> ' + selectedPosts.length + ' items</p>' +
                        '<div class="nexus-ai-wp-link-explanation">' +
                            '<p>This will create translation relationships between the selected posts. Choose one post as the source (original) and the others will be linked as translations of that post.</p>' +
                        '</div>' +
                        '<div class="nexus-ai-wp-source-selection">' +
                            '<label for="bulk-link-source">Select Source Post:</label>' +
                            '<select id="bulk-link-source" class="nexus-ai-wp-source-select">' +
                                '<option value="">Choose source post...</option>';

        selectedPosts.forEach(function(post) {
            dialogHtml += '<option value="' + post.id + '">' + post.title + ' (ID: ' + post.id + ')</option>';
        });

        dialogHtml +=
                            '</select>' +
                        '</div>' +
                        '<div class="nexus-ai-wp-link-note">' +
                            '<p><em>Note: The source post will be considered the original, and all other selected posts will be linked as translations of it.</em></p>' +
                        '</div>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-bulk-dialog-footer">' +
                        '<button type="button" class="button nexus-ai-wp-bulk-dialog-cancel">Cancel</button>' +
                        '<button type="button" class="button button-primary nexus-ai-wp-link-confirm" disabled>Link Posts</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $('body').append(dialogHtml);
        $('#nexus-ai-wp-link-dialog').css('display', 'flex');

        // Enable/disable confirm button based on source selection
        $('#bulk-link-source').on('change', function() {
            var confirmButton = $('.nexus-ai-wp-link-confirm');
            if ($(this).val()) {
                confirmButton.prop('disabled', false);
            } else {
                confirmButton.prop('disabled', true);
            }
        });

        // Handle dialog events
        var self = this;
        $(document).on('click', '#nexus-ai-wp-link-dialog .nexus-ai-wp-bulk-dialog-close, #nexus-ai-wp-link-dialog .nexus-ai-wp-bulk-dialog-cancel', function() {
            $('#nexus-ai-wp-link-dialog').remove();
        });

        $(document).on('click', '#nexus-ai-wp-link-dialog .nexus-ai-wp-link-confirm', function() {
            var sourcePostId = $('#bulk-link-source').val();

            if (!sourcePostId) {
                alert('Please select a source post.');
                return;
            }

            $('#nexus-ai-wp-link-dialog').remove();
            self.performBulkLink(selectedPosts, sourcePostId);
        });
    },

    /**
     * Perform bulk link
     */
    performBulkLink: function(selectedPosts, sourcePostId) {
        var postIds = selectedPosts.map(function(post) {
            return post.id;
        });

        console.log('NexusAI Debug: Linking posts:', postIds, 'with source:', sourcePostId);

        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_bulk_link_posts',
            post_ids: postIds,
            source_post_id: sourcePostId,
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                alert('Posts linked successfully.');
                location.reload(); // Refresh to show updated state
            } else {
                alert('Failed to link posts: ' + (response.message || 'Unknown error'));
            }
        })
        .fail(function() {
            alert('Network error occurred while linking posts.');
        });
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
    },

    /**
     * Initialize quality assessment interface
     */
    initQualityAssessmentInterface: function() {
        console.log('NexusAI Debug: Initializing quality assessment interface');

        // Handle quality details button clicks
        $(document).on('click', '.nexus-ai-wp-quality-details', function() {
            var button = $(this);
            var postId = button.data('post-id');

            console.log('NexusAI Debug: Quality details requested for post:', postId);

            NexusAIWPTranslatorAdmin.showQualityDetailsDialog(postId);
        });
    },

    /**
     * Show quality details dialog
     */
    showQualityDetailsDialog: function(postId) {
        var self = this;

        // Get quality assessment data
        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_get_quality_details',
            post_id: postId,
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                self.displayQualityDetailsDialog(response.data);
            } else {
                alert('Failed to load quality details: ' + (response.message || 'Unknown error'));
            }
        })
        .fail(function() {
            alert('Network error occurred while loading quality details.');
        });
    },

    /**
     * Display quality details dialog
     */
    displayQualityDetailsDialog: function(qualityData) {
        var dialogHtml =
            '<div id="nexus-ai-wp-quality-dialog" class="nexus-ai-wp-quality-dialog-overlay">' +
                '<div class="nexus-ai-wp-quality-dialog">' +
                    '<div class="nexus-ai-wp-quality-dialog-header">' +
                        '<h3>Translation Quality Assessment</h3>' +
                        '<button type="button" class="nexus-ai-wp-quality-dialog-close">&times;</button>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-quality-dialog-body">' +
                        '<div class="nexus-ai-wp-quality-overview">' +
                            '<div class="nexus-ai-wp-quality-metric">' +
                                '<div class="nexus-ai-wp-quality-metric-value">' + qualityData.grade + '</div>' +
                                '<div class="nexus-ai-wp-quality-metric-label">Overall Grade</div>' +
                            '</div>' +
                            '<div class="nexus-ai-wp-quality-metric">' +
                                '<div class="nexus-ai-wp-quality-metric-value">' + qualityData.overall_score + '%</div>' +
                                '<div class="nexus-ai-wp-quality-metric-label">Overall Score</div>' +
                            '</div>' +
                            '<div class="nexus-ai-wp-quality-metric">' +
                                '<div class="nexus-ai-wp-quality-metric-value">' + qualityData.completeness_score + '%</div>' +
                                '<div class="nexus-ai-wp-quality-metric-label">Completeness</div>' +
                            '</div>' +
                            '<div class="nexus-ai-wp-quality-metric">' +
                                '<div class="nexus-ai-wp-quality-metric-value">' + qualityData.consistency_score + '%</div>' +
                                '<div class="nexus-ai-wp-quality-metric-label">Consistency</div>' +
                            '</div>' +
                            '<div class="nexus-ai-wp-quality-metric">' +
                                '<div class="nexus-ai-wp-quality-metric-value">' + qualityData.structure_score + '%</div>' +
                                '<div class="nexus-ai-wp-quality-metric-label">Structure</div>' +
                            '</div>' +
                            '<div class="nexus-ai-wp-quality-metric">' +
                                '<div class="nexus-ai-wp-quality-metric-value">' + qualityData.length_score + '%</div>' +
                                '<div class="nexus-ai-wp-quality-metric-label">Length</div>' +
                            '</div>' +
                        '</div>';

        // Add issues section
        if (qualityData.issues && qualityData.issues.length > 0) {
            dialogHtml +=
                '<div class="nexus-ai-wp-quality-section">' +
                    '<h4>Issues Found</h4>' +
                    '<ul class="nexus-ai-wp-quality-issues">';

            qualityData.issues.forEach(function(issue) {
                dialogHtml += '<li>' + issue + '</li>';
            });

            dialogHtml += '</ul></div>';
        }

        // Add suggestions section
        if (qualityData.suggestions && qualityData.suggestions.length > 0) {
            dialogHtml +=
                '<div class="nexus-ai-wp-quality-section">' +
                    '<h4>Suggestions for Improvement</h4>' +
                    '<ul class="nexus-ai-wp-quality-suggestions">';

            qualityData.suggestions.forEach(function(suggestion) {
                dialogHtml += '<li>' + suggestion + '</li>';
            });

            dialogHtml += '</ul></div>';
        }

        // Add metrics section
        if (qualityData.metrics) {
            dialogHtml +=
                '<div class="nexus-ai-wp-quality-section">' +
                    '<h4>Translation Metrics</h4>' +
                    '<div class="nexus-ai-wp-quality-metrics-grid">' +
                        '<div class="nexus-ai-wp-quality-metrics-item">' +
                            '<strong>' + qualityData.metrics.original_word_count + '</strong>' +
                            '<span>Original Words</span>' +
                        '</div>' +
                        '<div class="nexus-ai-wp-quality-metrics-item">' +
                            '<strong>' + qualityData.metrics.translated_word_count + '</strong>' +
                            '<span>Translated Words</span>' +
                        '</div>' +
                        '<div class="nexus-ai-wp-quality-metrics-item">' +
                            '<strong>' + qualityData.metrics.original_char_count + '</strong>' +
                            '<span>Original Characters</span>' +
                        '</div>' +
                        '<div class="nexus-ai-wp-quality-metrics-item">' +
                            '<strong>' + qualityData.metrics.translated_char_count + '</strong>' +
                            '<span>Translated Characters</span>' +
                        '</div>' +
                        '<div class="nexus-ai-wp-quality-metrics-item">' +
                            '<strong>' + qualityData.metrics.html_tags_original + '</strong>' +
                            '<span>Original HTML Tags</span>' +
                        '</div>' +
                        '<div class="nexus-ai-wp-quality-metrics-item">' +
                            '<strong>' + qualityData.metrics.html_tags_translated + '</strong>' +
                            '<span>Translated HTML Tags</span>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }

        dialogHtml +=
                    '</div>' +
                '</div>' +
            '</div>';

        // Add dialog to page
        $('body').append(dialogHtml);
        $('#nexus-ai-wp-quality-dialog').css('display', 'flex');

        // Handle close events
        $(document).on('click', '#nexus-ai-wp-quality-dialog .nexus-ai-wp-quality-dialog-close, #nexus-ai-wp-quality-dialog', function(e) {
            if (e.target === this) {
                $('#nexus-ai-wp-quality-dialog').remove();
            }
        });
    },

    /**
     * Initialize translation queue interface
     */
    initTranslationQueueInterface: function() {
        console.log('NexusAI Debug: Initializing translation queue interface');

        // Auto-refresh queue when tab is active
        this.queueRefreshInterval = null;

        // Handle tab switching
        $(document).on('click', '.nav-tab[href="#queue-tab"]', function() {
            NexusAIWPTranslatorAdmin.loadQueueData();
            NexusAIWPTranslatorAdmin.startQueueAutoRefresh();
        });

        // Handle other tab switching (stop auto-refresh)
        $(document).on('click', '.nav-tab:not([href="#queue-tab"])', function() {
            NexusAIWPTranslatorAdmin.stopQueueAutoRefresh();
        });

        // Queue control buttons
        $(document).on('click', '#refresh-queue-btn', function() {
            NexusAIWPTranslatorAdmin.loadQueueData();
        });

        $(document).on('click', '#pause-queue-btn', function() {
            NexusAIWPTranslatorAdmin.pauseQueue();
        });

        $(document).on('click', '#resume-queue-btn', function() {
            NexusAIWPTranslatorAdmin.resumeQueue();
        });

        // Queue filter
        $(document).on('change', '#queue-status-filter', function() {
            NexusAIWPTranslatorAdmin.loadQueueData();
        });

        // Queue item actions
        $(document).on('click', '.remove-queue-item', function() {
            var queueId = $(this).data('queue-id');
            NexusAIWPTranslatorAdmin.removeQueueItem(queueId);
        });

        $(document).on('click', '.retry-queue-item', function() {
            var queueId = $(this).data('queue-id');
            NexusAIWPTranslatorAdmin.retryQueueItem(queueId);
        });
    },

    /**
     * Load queue data
     */
    loadQueueData: function() {
        var self = this;
        var status = $('#queue-status-filter').val();

        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_get_queue_status',
            status: status,
            limit: 100,
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                self.updateQueueDisplay(response.data);
            } else {
                console.log('NexusAI Debug: Failed to load queue data:', response.message);
            }
        })
        .fail(function() {
            console.log('NexusAI Debug: Network error loading queue data');
        });
    },

    /**
     * Update queue display
     */
    updateQueueDisplay: function(data) {
        // Update statistics
        $('#queue-pending-count').text(data.statistics.pending || 0);
        $('#queue-processing-count').text(data.statistics.processing || 0);
        $('#queue-completed-count').text(data.statistics.completed || 0);
        $('#queue-failed-count').text(data.statistics.failed || 0);

        // Update pause/resume button
        if (data.statistics.queue_paused) {
            $('#pause-queue-btn').hide();
            $('#resume-queue-btn').show();
        } else {
            $('#pause-queue-btn').show();
            $('#resume-queue-btn').hide();
        }

        // Update queue items table
        var tbody = $('#queue-items-tbody');
        tbody.empty();

        if (data.items && data.items.length > 0) {
            data.items.forEach(function(item) {
                var row = self.createQueueItemRow(item);
                tbody.append(row);
            });
        } else {
            tbody.append('<tr><td colspan="7" class="nexus-ai-wp-queue-empty"><h3>No queue items found</h3><p>The translation queue is empty.</p></td></tr>');
        }
    },

    /**
     * Create queue item row
     */
    createQueueItemRow: function(item) {
        var languages = JSON.parse(item.target_languages || '[]');
        var languageTags = languages.map(function(lang) {
            return '<span class="queue-language-tag">' + lang + '</span>';
        }).join('');

        var statusClass = item.status.toLowerCase();
        var statusBadge = '<span class="queue-status-badge ' + statusClass + '">' + item.status + '</span>';

        var priorityClass = item.priority >= 7 ? 'high' : (item.priority >= 4 ? 'medium' : 'low');
        var priorityIndicator = '<span class="queue-priority ' + priorityClass + '">' + item.priority + '</span>';

        var scheduledTime = item.scheduled_time ? new Date(item.scheduled_time).toLocaleString() : '-';
        var isOverdue = item.scheduled_time && new Date(item.scheduled_time) < new Date();
        var scheduledClass = isOverdue ? 'overdue' : '';

        var actions = '';
        if (item.status === 'pending' || item.status === 'failed') {
            actions += '<button type="button" class="button button-small remove-queue-item" data-queue-id="' + item.id + '">Remove</button>';
        }
        if (item.status === 'failed') {
            actions += '<button type="button" class="button button-small retry-queue-item" data-queue-id="' + item.id + '">Retry</button>';
        }

        var errorMessage = item.error_message ? '<div class="queue-error-message">' + item.error_message + '</div>' : '';

        return '<tr>' +
            '<td><strong>' + (item.post_title || 'Unknown') + '</strong><br><small>ID: ' + item.post_id + ' | Type: ' + (item.post_type || 'unknown') + '</small></td>' +
            '<td><div class="queue-languages">' + languageTags + '</div></td>' +
            '<td>' + priorityIndicator + '</td>' +
            '<td>' + statusBadge + errorMessage + '</td>' +
            '<td><span class="queue-scheduled-time ' + scheduledClass + '">' + scheduledTime + '</span></td>' +
            '<td>' + item.attempts + '/' + item.max_attempts + '</td>' +
            '<td><div class="queue-item-actions">' + actions + '</div></td>' +
        '</tr>';
    },

    /**
     * Start queue auto-refresh
     */
    startQueueAutoRefresh: function() {
        var self = this;

        if (this.queueRefreshInterval) {
            clearInterval(this.queueRefreshInterval);
        }

        this.queueRefreshInterval = setInterval(function() {
            self.loadQueueData();
        }, 10000); // Refresh every 10 seconds
    },

    /**
     * Stop queue auto-refresh
     */
    stopQueueAutoRefresh: function() {
        if (this.queueRefreshInterval) {
            clearInterval(this.queueRefreshInterval);
            this.queueRefreshInterval = null;
        }
    },

    /**
     * Pause queue
     */
    pauseQueue: function() {
        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_pause_queue',
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                $('#pause-queue-btn').hide();
                $('#resume-queue-btn').show();
                alert('Queue paused successfully.');
            } else {
                alert('Failed to pause queue: ' + (response.message || 'Unknown error'));
            }
        })
        .fail(function() {
            alert('Network error occurred while pausing queue.');
        });
    },

    /**
     * Resume queue
     */
    resumeQueue: function() {
        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_resume_queue',
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                $('#pause-queue-btn').show();
                $('#resume-queue-btn').hide();
                alert('Queue resumed successfully.');
            } else {
                alert('Failed to resume queue: ' + (response.message || 'Unknown error'));
            }
        })
        .fail(function() {
            alert('Network error occurred while resuming queue.');
        });
    },

    /**
     * Remove queue item
     */
    removeQueueItem: function(queueId) {
        if (!confirm('Are you sure you want to remove this item from the queue?')) {
            return;
        }

        var self = this;

        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_remove_from_queue',
            queue_id: queueId,
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                self.loadQueueData(); // Refresh the queue
            } else {
                alert('Failed to remove item: ' + (response.message || 'Unknown error'));
            }
        })
        .fail(function() {
            alert('Network error occurred while removing item.');
        });
    },

    /**
     * Retry queue item
     */
    retryQueueItem: function(queueId) {
        var self = this;

        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_retry_queue_item',
            queue_id: queueId,
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                self.loadQueueData(); // Refresh the queue
                alert('Item scheduled for retry.');
            } else {
                alert('Failed to retry item: ' + (response.message || 'Unknown error'));
            }
        })
        .fail(function() {
            alert('Network error occurred while retrying item.');
        });
    },

    /**
     * Show add to queue dialog
     */
    showAddToQueueDialog: function(postId, postTitle) {
        var dialogHtml =
            '<div id="nexus-ai-wp-add-to-queue-dialog" class="nexus-ai-wp-bulk-dialog-overlay">' +
                '<div class="nexus-ai-wp-bulk-dialog">' +
                    '<div class="nexus-ai-wp-bulk-dialog-header">' +
                        '<h3>Add to Translation Queue</h3>' +
                        '<button type="button" class="nexus-ai-wp-bulk-dialog-close">&times;</button>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-bulk-dialog-body">' +
                        '<p><strong>Post:</strong> ' + postTitle + '</p>' +
                        '<div class="nexus-ai-wp-queue-options">' +
                            '<div class="nexus-ai-wp-queue-option">' +
                                '<label>Target Languages:</label>' +
                                '<div class="nexus-ai-wp-language-checkboxes">' +
                                    '<label><input type="checkbox" value="es" checked> Spanish (es)</label>' +
                                    '<label><input type="checkbox" value="fr" checked> French (fr)</label>' +
                                    '<label><input type="checkbox" value="de" checked> German (de)</label>' +
                                    '<label><input type="checkbox" value="it"> Italian (it)</label>' +
                                    '<label><input type="checkbox" value="pt"> Portuguese (pt)</label>' +
                                    '<label><input type="checkbox" value="ru"> Russian (ru)</label>' +
                                '</div>' +
                            '</div>' +
                            '<div class="nexus-ai-wp-queue-option">' +
                                '<label for="queue-priority">Priority:</label>' +
                                '<select id="queue-priority">' +
                                    '<option value="1">Low (1)</option>' +
                                    '<option value="3">Low-Medium (3)</option>' +
                                    '<option value="5" selected>Medium (5)</option>' +
                                    '<option value="7">High (7)</option>' +
                                    '<option value="9">Urgent (9)</option>' +
                                '</select>' +
                            '</div>' +
                            '<div class="nexus-ai-wp-queue-option">' +
                                '<label for="queue-scheduled-time">Schedule for:</label>' +
                                '<select id="queue-schedule-type">' +
                                    '<option value="immediate">Immediate</option>' +
                                    '<option value="custom">Custom Date/Time</option>' +
                                '</select>' +
                                '<input type="datetime-local" id="queue-scheduled-time" style="display: none; margin-top: 5px;">' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-bulk-dialog-footer">' +
                        '<button type="button" class="button nexus-ai-wp-bulk-dialog-cancel">Cancel</button>' +
                        '<button type="button" class="button button-primary nexus-ai-wp-add-to-queue-confirm">Add to Queue</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $('body').append(dialogHtml);
        $('#nexus-ai-wp-add-to-queue-dialog').css('display', 'flex');

        // Handle schedule type change
        $('#queue-schedule-type').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#queue-scheduled-time').show();
            } else {
                $('#queue-scheduled-time').hide();
            }
        });

        // Handle dialog events
        var self = this;
        $(document).on('click', '#nexus-ai-wp-add-to-queue-dialog .nexus-ai-wp-bulk-dialog-close, #nexus-ai-wp-add-to-queue-dialog .nexus-ai-wp-bulk-dialog-cancel', function() {
            $('#nexus-ai-wp-add-to-queue-dialog').remove();
        });

        $(document).on('click', '#nexus-ai-wp-add-to-queue-dialog .nexus-ai-wp-add-to-queue-confirm', function() {
            var selectedLanguages = [];
            $('#nexus-ai-wp-add-to-queue-dialog .nexus-ai-wp-language-checkboxes input:checked').each(function() {
                selectedLanguages.push($(this).val());
            });

            if (selectedLanguages.length === 0) {
                alert('Please select at least one target language.');
                return;
            }

            var priority = $('#queue-priority').val();
            var scheduleType = $('#queue-schedule-type').val();
            var scheduledTime = scheduleType === 'custom' ? $('#queue-scheduled-time').val() : null;

            $('#nexus-ai-wp-add-to-queue-dialog').remove();
            self.addToQueue(postId, selectedLanguages, priority, scheduledTime);
        });
    },

    /**
     * Add post to queue
     */
    addToQueue: function(postId, targetLanguages, priority, scheduledTime) {
        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_add_to_queue',
            post_id: postId,
            target_languages: targetLanguages,
            priority: priority,
            scheduled_time: scheduledTime,
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                alert('Post added to translation queue successfully!');
            } else {
                alert('Failed to add to queue: ' + (response.message || 'Unknown error'));
            }
        })
        .fail(function() {
            alert('Network error occurred while adding to queue.');
        });
    },

    /**
     * Show bulk set language dialog
     */
    showBulkSetLanguageDialog: function(selectedPosts) {
        var dialogHtml =
            '<div id="nexus-ai-wp-set-language-dialog" class="nexus-ai-wp-bulk-dialog-overlay">' +
                '<div class="nexus-ai-wp-bulk-dialog">' +
                    '<div class="nexus-ai-wp-bulk-dialog-header">' +
                        '<h3>Set Language for Selected Posts</h3>' +
                        '<button type="button" class="nexus-ai-wp-bulk-dialog-close">&times;</button>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-bulk-dialog-body">' +
                        '<p><strong>Selected Posts:</strong> ' + selectedPosts.length + ' items</p>' +
                        '<div class="nexus-ai-wp-language-selection">' +
                            '<label for="bulk-set-language">Select Language:</label>' +
                            '<select id="bulk-set-language" class="nexus-ai-wp-language-select">' +
                                '<option value="">Choose a language...</option>' +
                                '<option value="en">English (en)</option>' +
                                '<option value="es">Spanish (es)</option>' +
                                '<option value="fr">French (fr)</option>' +
                                '<option value="de">German (de)</option>' +
                                '<option value="it">Italian (it)</option>' +
                                '<option value="pt">Portuguese (pt)</option>' +
                                '<option value="ru">Russian (ru)</option>' +
                                '<option value="ja">Japanese (ja)</option>' +
                                '<option value="ko">Korean (ko)</option>' +
                                '<option value="zh">Chinese (zh)</option>' +
                                '<option value="ar">Arabic (ar)</option>' +
                                '<option value="hi">Hindi (hi)</option>' +
                                '<option value="nl">Dutch (nl)</option>' +
                                '<option value="sv">Swedish (sv)</option>' +
                                '<option value="da">Danish (da)</option>' +
                                '<option value="no">Norwegian (no)</option>' +
                                '<option value="fi">Finnish (fi)</option>' +
                                '<option value="pl">Polish (pl)</option>' +
                                '<option value="tr">Turkish (tr)</option>' +
                                '<option value="cs">Czech (cs)</option>' +
                                '<option value="hu">Hungarian (hu)</option>' +
                                '<option value="ro">Romanian (ro)</option>' +
                                '<option value="bg">Bulgarian (bg)</option>' +
                                '<option value="hr">Croatian (hr)</option>' +
                                '<option value="sk">Slovak (sk)</option>' +
                                '<option value="sl">Slovenian (sl)</option>' +
                                '<option value="et">Estonian (et)</option>' +
                                '<option value="lv">Latvian (lv)</option>' +
                                '<option value="lt">Lithuanian (lt)</option>' +
                                '<option value="mt">Maltese (mt)</option>' +
                                '<option value="ga">Irish (ga)</option>' +
                                '<option value="cy">Welsh (cy)</option>' +
                            '</select>' +
                        '</div>' +
                        '<div class="nexus-ai-wp-language-note">' +
                            '<p><em>Note: This will set the source language for the selected posts. This helps the translation system understand what language the content is written in.</em></p>' +
                        '</div>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-bulk-dialog-footer">' +
                        '<button type="button" class="button nexus-ai-wp-bulk-dialog-cancel">Cancel</button>' +
                        '<button type="button" class="button button-primary nexus-ai-wp-set-language-confirm" disabled>Set Language</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $('body').append(dialogHtml);
        $('#nexus-ai-wp-set-language-dialog').css('display', 'flex');

        // Enable/disable confirm button based on language selection
        $('#bulk-set-language').on('change', function() {
            var confirmButton = $('.nexus-ai-wp-set-language-confirm');
            if ($(this).val()) {
                confirmButton.prop('disabled', false);
            } else {
                confirmButton.prop('disabled', true);
            }
        });

        // Handle dialog events
        var self = this;
        $(document).on('click', '#nexus-ai-wp-set-language-dialog .nexus-ai-wp-bulk-dialog-close, #nexus-ai-wp-set-language-dialog .nexus-ai-wp-bulk-dialog-cancel', function() {
            $('#nexus-ai-wp-set-language-dialog').remove();
        });

        $(document).on('click', '#nexus-ai-wp-set-language-dialog .nexus-ai-wp-set-language-confirm', function() {
            var selectedLanguage = $('#bulk-set-language').val();

            if (!selectedLanguage) {
                alert('Please select a language.');
                return;
            }

            $('#nexus-ai-wp-set-language-dialog').remove();
            self.performBulkSetLanguage(selectedPosts, selectedLanguage);
        });
    },

    /**
     * Perform bulk set language
     */
    performBulkSetLanguage: function(selectedPosts, language) {
        var postIds = selectedPosts.map(function(post) {
            return post.id;
        });

        console.log('NexusAI Debug: Setting language', language, 'for posts:', postIds);

        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_bulk_set_language',
            post_ids: postIds,
            language: language,
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                alert('Language set successfully for ' + postIds.length + ' posts.');
                location.reload(); // Refresh to show updated language
            } else {
                alert('Failed to set language: ' + (response.message || 'Unknown error'));
            }
        })
        .fail(function() {
            alert('Network error occurred while setting language.');
        });
    },

    /**
     * Perform bulk unlink
     */
    performBulkUnlink: function(selectedPosts) {
        var postIds = selectedPosts.map(function(post) {
            return post.id;
        });

        console.log('NexusAI Debug: Unlinking posts:', postIds);

        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_bulk_unlink_posts',
            post_ids: postIds,
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                alert('Posts unlinked successfully.');
                location.reload(); // Refresh to show updated state
            } else {
                alert('Failed to unlink posts: ' + (response.message || 'Unknown error'));
            }
        })
        .fail(function() {
            alert('Network error occurred while unlinking posts.');
        });
    },

    /**
     * Perform bulk delete
     */
    performBulkDelete: function(selectedPosts) {
        var postIds = selectedPosts.map(function(post) {
            return post.id;
        });

        console.log('NexusAI Debug: Deleting posts:', postIds);

        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_bulk_delete_posts',
            post_ids: postIds,
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                alert('Posts deleted successfully.');
                location.reload(); // Refresh to show updated state
            } else {
                alert('Failed to delete posts: ' + (response.message || 'Unknown error'));
            }
        })
        .fail(function() {
            alert('Network error occurred while deleting posts.');
        });
    },

    /**
     * Perform bulk clear cache
     */
    performBulkClearCache: function(selectedPosts) {
        var postIds = selectedPosts.map(function(post) {
            return post.id;
        });

        console.log('NexusAI Debug: Clearing cache for posts:', postIds);

        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_bulk_clear_cache_posts',
            post_ids: postIds,
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                alert('Translation cache cleared successfully.');
                // No need to reload for cache clear
            } else {
                alert('Failed to clear cache: ' + (response.message || 'Unknown error'));
            }
        })
        .fail(function() {
            alert('Network error occurred while clearing cache.');
        });
    }
};

// Make NexusAIWPTranslatorAdmin globally available immediately
window.NexusAIWPTranslatorAdmin = NexusAIWPTranslatorAdmin;
console.log('NexusAI Debug: NexusAIWPTranslatorAdmin made globally available');

// Initialize when document is ready with proper jQuery scoping
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('NexusAI Debug: JavaScript function wrapper started');
    console.log('NexusAI Debug: Document ready, initializing admin interface');
    
    if (window.NexusAIWPTranslatorAdmin) {
        window.NexusAIWPTranslatorAdmin.init();
    } else {
        console.error('NexusAI Debug: NexusAIWPTranslatorAdmin not available in document ready!');
    }
});

// Check for auto translation when everything is loaded
jQuery(window).on('load', function() {
    if (window.NexusAIWPTranslatorAdmin && typeof window.NexusAIWPTranslatorAdmin.checkAutoTranslation === 'function') {
        setTimeout(function() {
            try {
                window.NexusAIWPTranslatorAdmin.checkAutoTranslation();
            } catch(error) {
                console.log('NexusAI Debug: Auto translation check failed:', error);
            }
        }, 1500);
    }
});
