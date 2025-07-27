/**
 * Claude Translator Admin JavaScript
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        ClaudeTranslatorAdmin.init();
    });
    
    var ClaudeTranslatorAdmin = {
        
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
                localStorage.setItem('claude_translator_active_tab', target);
            });
            
            // Restore active tab from localStorage
            var activeTab = localStorage.getItem('claude_translator_active_tab');
            if (activeTab && $(activeTab).length) {
                $('.nav-tab[href="' + activeTab + '"]').click();
            }
        },
        
        /**
         * Initialize API testing functionality
         */
        initApiTesting: function() {
            $('#claude-test-api').on('click', function() {
                var button = $(this);
                var apiKey = $('#claude_translator_api_key').val().trim();
                var resultDiv = $('#api-test-result');
                
                if (!apiKey) {
                    ClaudeTranslatorAdmin.showNotice(resultDiv, 'error', claude_translator_ajax.strings.error + ' Please enter an API key first.');
                    return;
                }
                
                button.prop('disabled', true).text(claude_translator_ajax.strings.testing);
                resultDiv.html('<div class="claude-spinner"></div> Testing connection...');
                
                $.post(claude_translator_ajax.ajax_url, {
                    action: 'claude_test_api',
                    api_key: apiKey,
                    nonce: claude_translator_ajax.nonce
                })
                .done(function(response) {
                    var noticeClass = response.success ? 'success' : 'error';
                    ClaudeTranslatorAdmin.showNotice(resultDiv, noticeClass, response.message);
                })
                .fail(function() {
                    ClaudeTranslatorAdmin.showNotice(resultDiv, 'error', 'Connection failed. Please check your internet connection.');
                })
                .always(function() {
                    button.prop('disabled', false).text('Test Connection');
                });
            });
            
            // API key toggle visibility
            $('#claude-toggle-api-key').on('click', function() {
                var input = $('#claude_translator_api_key');
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
            $('#claude-save-settings').on('click', function() {
                var button = $(this);
                var form = $('#claude-translator-settings-form');
                
                button.prop('disabled', true).text('Saving...');
                
                var formData = form.serialize();
                formData += '&action=claude_save_settings&nonce=' + claude_translator_ajax.nonce;
                
                $.post(claude_translator_ajax.ajax_url, formData)
                .done(function(response) {
                    var noticeClass = response.success ? 'success' : 'error';
                    ClaudeTranslatorAdmin.showGlobalNotice(noticeClass, response.data);
                })
                .fail(function() {
                    ClaudeTranslatorAdmin.showGlobalNotice('error', 'Failed to save settings. Please try again.');
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
            $(document).on('click', '#claude-translate-post', function() {
                var button = $(this);
                var postId = ClaudeTranslatorAdmin.getPostId();
                var targetLanguages = [];
                
                $('.claude-target-language:checked').each(function() {
                    targetLanguages.push($(this).val());
                });
                
                if (targetLanguages.length === 0) {
                    alert('Please select at least one target language.');
                    return;
                }
                
                button.prop('disabled', true).text(claude_translator_ajax.strings.translating);
                $('#claude-translation-status').html('<div class="notice notice-info"><p>Translation in progress...</p></div>');
                
                $.post(claude_translator_ajax.ajax_url, {
                    action: 'claude_translate_post',
                    post_id: postId,
                    target_languages: targetLanguages,
                    nonce: claude_translator_ajax.nonce
                })
                .done(function(response) {
                    var noticeClass = response.success ? 'notice-success' : 'notice-error';
                    var message = response.success ? 
                        'Translation completed successfully!' : 
                        ('Translation failed: ' + (response.message || 'Unknown error'));
                    
                    $('#claude-translation-status').html('<div class="notice ' + noticeClass + '"><p>' + message + '</p></div>');
                    
                    if (response.success) {
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                })
                .fail(function() {
                    $('#claude-translation-status').html('<div class="notice notice-error"><p>Network error occurred</p></div>');
                })
                .always(function() {
                    button.prop('disabled', false).text('Translate Now');
                });
            });
            
            // Translation status check
            $(document).on('click', '#claude-get-translation-status', function() {
                var button = $(this);
                var postId = ClaudeTranslatorAdmin.getPostId();
                
                button.prop('disabled', true);
                
                $.post(claude_translator_ajax.ajax_url, {
                    action: 'claude_get_translation_status',
                    post_id: postId,
                    nonce: claude_translator_ajax.nonce
                })
                .done(function(response) {
                    if (response.success && response.data.length > 0) {
                        var html = '<ul>';
                        $.each(response.data, function(i, translation) {
                            html += '<li>' + translation.target_language + ': ' + translation.status + '</li>';
                        });
                        html += '</ul>';
                        $('#claude-translation-status').html(html);
                    } else {
                        $('#claude-translation-status').html('<p>No translations found.</p>');
                    }
                })
                .always(function() {
                    button.prop('disabled', false);
                });
            });
            
            // Unlink translation
            $(document).on('click', '.claude-unlink-translation, .unlink-translation', function() {
                if (!confirm(claude_translator_ajax.strings.confirm_unlink)) {
                    return;
                }
                
                var button = $(this);
                var postId = button.data('post-id') || button.data('source-id');
                var relatedId = button.data('related-id') || button.data('translated-id');
                var row = button.closest('tr, li');
                
                button.prop('disabled', true).text('Unlinking...');
                
                $.post(claude_translator_ajax.ajax_url, {
                    action: 'claude_unlink_translation',
                    post_id: postId,
                    related_post_id: relatedId,
                    nonce: claude_translator_ajax.nonce
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
            $('#claude-refresh-stats').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Refreshing...');
                
                $.post(claude_translator_ajax.ajax_url, {
                    action: 'claude_get_stats',
                    period: '7 days',
                    nonce: claude_translator_ajax.nonce
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
                
                $.post(claude_translator_ajax.ajax_url, {
                    action: 'claude_cleanup_orphaned',
                    nonce: claude_translator_ajax.nonce
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
    
    // Make ClaudeTranslatorAdmin globally available
    window.ClaudeTranslatorAdmin = ClaudeTranslatorAdmin;
    
})(jQuery);