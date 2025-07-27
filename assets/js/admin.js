/**
 * Nexus AI WP Translator Admin JavaScript
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        NexusAIWPTranslatorAdmin.init();
    });
    
    var NexusAIWPTranslatorAdmin = {
        
        init: function() {
            this.initTabSwitching();
            this.initApiTesting();
            this.initSettingsSave();
            this.initTranslationActions();
            this.initStatusRefresh();
            this.initBulkActions();
        },
        
        /**
         * Initialize tab switching functionality
         */
        initTabSwitching: function() {
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                
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
                $('.nav-tab[href="' + activeTab + '"]').click();
            }
        },
        
        /**
         * Initialize API testing functionality
         */
        initApiTesting: function() {
            $('#nexus-ai-wp-test-api').on('click', function() {
                var button = $(this);
                var apiKey = $('#nexus_ai_wp_translator_api_key').val().trim();
                var resultDiv = $('#api-test-result');
                
                if (!apiKey) {
                    NexusAIWPTranslatorAdmin.showNotice(resultDiv, 'error', nexus_ai_wp_translator_ajax.strings.error + ' Please enter an API key first.');
                    return;
                }
                
                button.prop('disabled', true).text(nexus_ai_wp_translator_ajax.strings.testing);
                resultDiv.html('<div class="nexus-ai-wp-spinner"></div> Testing connection...');
                
                $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                    action: 'nexus_ai_wp_test_api',
                    api_key: apiKey,
                    nonce: nexus_ai_wp_translator_ajax.nonce
                })
                .done(function(response) {
                    var noticeClass = response.success ? 'success' : 'error';
                    NexusAIWPTranslatorAdmin.showNotice(resultDiv, noticeClass, response.message);
                })
                .fail(function() {
                    NexusAIWPTranslatorAdmin.showNotice(resultDiv, 'error', 'Connection failed. Please check your internet connection.');
                })
                .always(function() {
                    button.prop('disabled', false).text('Test Connection');
                });
            });
            
            // API key toggle visibility
            $('#nexus-ai-wp-toggle-api-key').on('click', function() {
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
         * Initialize settings save functionality
         */
        initSettingsSave: function() {
            $('#nexus-ai-wp-save-settings').on('click', function() {
                var button = $(this);
                var form = $('#nexus-ai-wp-translator-settings-form');
                
                button.prop('disabled', true).text('Saving...');
                
                var formData = form.serialize();
                formData += '&action=nexus_ai_wp_save_settings&nonce=' + nexus_ai_wp_translator_ajax.nonce;
                
                $.post(nexus_ai_wp_translator_ajax.ajax_url, formData)
                .done(function(response) {
                    var noticeClass = response.success ? 'success' : 'error';
                    NexusAIWPTranslatorAdmin.showGlobalNotice(noticeClass, response.data);
                })
                .fail(function() {
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
            // Manual translation trigger
            $(document).on('click', '#nexus-ai-wp-translate-post', function() {
                var button = $(this);
                var postId = NexusAIWPTranslatorAdmin.getPostId();
                var targetLanguages = [];
                
                $('.nexus-ai-wp-target-language:checked').each(function() {
                    targetLanguages.push($(this).val());
                });
                
                if (targetLanguages.length === 0) {
                    alert('Please select at least one target language.');
                    return;
                }
                
                button.prop('disabled', true).text(nexus_ai_wp_translator_ajax.strings.translating);
                $('#nexus-ai-wp-translation-status').html('<div class="notice notice-info"><p>Translation in progress...</p></div>');
                
                $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                    action: 'nexus_ai_wp_translate_post',
                    post_id: postId,
                    target_languages: targetLanguages,
                    nonce: nexus_ai_wp_translator_ajax.nonce
                })
                .done(function(response) {
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
                .fail(function() {
                    $('#nexus-ai-wp-translation-status').html('<div class="notice notice-error"><p>Network error occurred</p></div>');
                })
                .always(function() {
                    button.prop('disabled', false).text('Translate Now');
                });
            });
            
            // Translation status check
            $(document).on('click', '#nexus-ai-wp-get-translation-status', function() {
                var button = $(this);
                var postId = NexusAIWPTranslatorAdmin.getPostId();
                
                button.prop('disabled', true);
                
                $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                    action: 'nexus_ai_wp_get_translation_status',
                    post_id: postId,
                    nonce: nexus_ai_wp_translator_ajax.nonce
                })
                .done(function(response) {
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
                if (!confirm(nexus_ai_wp_translator_ajax.strings.confirm_unlink)) {
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
                    if (response.success) {
                        row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error: ' + response.data);
                        button.prop('disabled', false).text('Unlink');
                    }
                })
                .fail(function() {
                    alert('Network error occurred');
                    button.prop('disabled', false).text('Unlink');
                });
            });
        },
        
        /**
         * Initialize status refresh functionality
         */
        initStatusRefresh: function() {
            $('#nexus-ai-wp-refresh-stats').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Refreshing...');
                
                $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                    action: 'nexus_ai_wp_get_stats',
                    period: '7 days',
                    nonce: nexus_ai_wp_translator_ajax.nonce
                })
                .done(function(response) {
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
            $('#cleanup-orphaned').on('click', function() {
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
            var noticeClass = 'notice-' + type;
            container.html('<div class="notice ' + noticeClass + '"><p>' + message + '</p></div>');
        },
        
        /**
         * Show global notice after H1
         */
        showGlobalNotice: function(type, message) {
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
            return postId;
        },
        
        /**
         * Debounce function
         */
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    };
    
    // Make NexusAIWPTranslatorAdmin globally available
    window.NexusAIWPTranslatorAdmin = NexusAIWPTranslatorAdmin;
    
})(jQuery);