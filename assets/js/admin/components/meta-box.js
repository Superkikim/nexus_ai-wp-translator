/**
 * Nexus AI WP Translator Meta Box
 * Post edit meta box functionality
 */

(function() {
    'use strict';

    // Meta box object
    var NexusAIWPTranslatorMetaBox = {
        
        /**
         * Initialize meta box functionality
         */
        init: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initMetaBox')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Initializing meta box functionality');

            // Initialize translation controls in meta box
            this.initTranslationControls();
            
            // Initialize language selection
            this.initLanguageSelection();
            
            // Initialize translation status display
            this.initTranslationStatus();
            
            // Initialize quick actions
            this.initQuickActions();
        },

        /**
         * Initialize translation controls
         */
        initTranslationControls: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initTranslationControls')) return;
            var $ = jQuery;

            // Handle translate button in meta box
            $(document).on('click', '#nexus-ai-wp-meta-translate-btn', function() {
                var button = $(this);
                var postId = NexusAIWPTranslatorCore.getPostId();
                var postTitle = $('#title').val() || 'Current Post';
                var selectedLanguages = [];
                
                // Get selected target languages from checkboxes
                $('#nexus-ai-wp-meta-target-languages input:checked').each(function() {
                    selectedLanguages.push($(this).val());
                });
                
                if (selectedLanguages.length === 0) {
                    alert('Please select at least one target language.');
                    return;
                }
                
                if (!postId) {
                    alert('Please save the post first before translating.');
                    return;
                }
                
                console.debug('[Nexus Translator]: Meta box translate clicked for post:', postId, 'languages:', selectedLanguages);
                
                // Start translation with progress dialog
                NexusAIWPTranslatorProgressDialog.startTranslationWithProgress(postId, postTitle, selectedLanguages, button);
            });

            // Handle add to queue button
            $(document).on('click', '#nexus-ai-wp-meta-queue-btn', function() {
                var postId = NexusAIWPTranslatorCore.getPostId();
                var postTitle = $('#title').val() || 'Current Post';
                
                if (!postId) {
                    alert('Please save the post first before adding to queue.');
                    return;
                }
                
                console.debug('[Nexus Translator]: Meta box add to queue clicked for post:', postId);
                
                // Show add to queue dialog
                NexusAIWPTranslatorQueueManager.showAddToQueueDialog(postId, postTitle);
            });
        },

        /**
         * Initialize language selection
         */
        initLanguageSelection: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initLanguageSelection')) return;
            var $ = jQuery;

            // Handle source language selection
            $(document).on('change', '#nexus-ai-wp-meta-source-language', function() {
                var sourceLanguage = $(this).val();
                var postId = NexusAIWPTranslatorCore.getPostId();
                
                if (postId && sourceLanguage) {
                    // Auto-save source language
                    $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                        action: 'nexus_ai_wp_set_post_language',
                        post_id: postId,
                        language: sourceLanguage,
                        nonce: nexus_ai_wp_translator_ajax.nonce
                    })
                    .done(function(response) {
                        if (response.success) {
                            // Show subtle feedback
                            var feedback = $('<span class="language-saved-feedback" style="color: #46b450; margin-left: 10px;">✓</span>');
                            $('#nexus-ai-wp-meta-source-language').after(feedback);
                            setTimeout(function() {
                                feedback.fadeOut(function() { $(this).remove(); });
                            }, 2000);
                        }
                    });
                }
            });

            // Handle target language checkbox changes
            $(document).on('change', '#nexus-ai-wp-meta-target-languages input[type="checkbox"]', function() {
                var selectedCount = $('#nexus-ai-wp-meta-target-languages input:checked').length;
                var translateBtn = $('#nexus-ai-wp-meta-translate-btn');
                
                if (selectedCount > 0) {
                    translateBtn.prop('disabled', false);
                    translateBtn.text('Translate to ' + selectedCount + ' language' + (selectedCount > 1 ? 's' : ''));
                } else {
                    translateBtn.prop('disabled', true);
                    translateBtn.text('Select languages to translate');
                }
            });

            // Select all / deselect all functionality
            $(document).on('click', '#nexus-ai-wp-meta-select-all-languages', function() {
                var isChecked = $(this).is(':checked');
                $('#nexus-ai-wp-meta-target-languages input[type="checkbox"]').prop('checked', isChecked);
                $('#nexus-ai-wp-meta-target-languages input[type="checkbox"]').first().trigger('change');
            });
        },

        /**
         * Initialize translation status display
         */
        initTranslationStatus: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initTranslationStatus')) return;
            var $ = jQuery;

            var postId = NexusAIWPTranslatorCore.getPostId();
            if (!postId) {
                return;
            }

            // Load and display current translation status
            this.loadTranslationStatus(postId);

            // Refresh status button
            $(document).on('click', '#nexus-ai-wp-meta-refresh-status', function() {
                var button = $(this);
                button.prop('disabled', true).text('Refreshing...');
                
                NexusAIWPTranslatorMetaBox.loadTranslationStatus(postId, function() {
                    button.prop('disabled', false).text('Refresh');
                });
            });
        },

        /**
         * Load translation status
         */
        loadTranslationStatus: function(postId, callback) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('loadTranslationStatus')) return;
            var $ = jQuery;

            NexusAIWPTranslatorAjax.getTranslationStatus(postId, function(response) {
                if (response.success && response.data) {
                    NexusAIWPTranslatorMetaBox.displayTranslationStatus(response.data);
                } else {
                    $('#nexus-ai-wp-meta-translation-status').html('<p>No translation data available.</p>');
                }
                
                if (callback) callback();
            });
        },

        /**
         * Display translation status
         */
        displayTranslationStatus: function(statusData) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('displayTranslationStatus')) return;
            var $ = jQuery;

            var statusContainer = $('#nexus-ai-wp-meta-translation-status');
            var languageNames = NexusAIWPTranslatorCore.getLanguageNames();
            var html = '';

            if (statusData.source_language) {
                var sourceLangName = languageNames[statusData.source_language] || statusData.source_language.toUpperCase();
                html += '<div class="meta-status-item">' +
                    '<strong>Source Language:</strong> ' + sourceLangName + ' (' + statusData.source_language + ')' +
                    '</div>';
            }

            if (statusData.translations && statusData.translations.length > 0) {
                html += '<div class="meta-status-item">' +
                    '<strong>Translations:</strong>' +
                    '<ul class="translation-list">';

                statusData.translations.forEach(function(translation) {
                    var langName = languageNames[translation.language] || translation.language.toUpperCase();
                    var statusClass = 'status-' + (translation.status || 'unknown');
                    var statusText = translation.status ? translation.status.replace('_', ' ') : 'Unknown';
                    
                    html += '<li class="translation-item ' + statusClass + '">' +
                        '<span class="language">' + langName + ' (' + translation.language + ')</span>' +
                        '<span class="status">' + statusText + '</span>';
                    
                    if (translation.post_id) {
                        html += '<a href="' + admin_url + 'post.php?post=' + translation.post_id + '&action=edit" class="view-translation" target="_blank">View</a>';
                    }
                    
                    if (translation.quality_score) {
                        html += NexusAIWPTranslatorQualityAssessor.createQualityDisplay(
                            translation.quality_score,
                            translation.post_id || 0,
                            true
                        );
                    }
                    
                    html += '</li>';
                });

                html += '</ul></div>';
            }

            if (statusData.in_progress_languages && statusData.in_progress_languages.length > 0) {
                html += '<div class="meta-status-item in-progress">' +
                    '<strong>In Progress:</strong> ';
                
                var inProgressNames = statusData.in_progress_languages.map(function(lang) {
                    return languageNames[lang] || lang.toUpperCase();
                });
                
                html += inProgressNames.join(', ') + '</div>';
            }

            if (statusData.resumable_languages && statusData.resumable_languages.length > 0) {
                html += '<div class="meta-status-item resumable">' +
                    '<strong>Can Resume:</strong> ';
                
                var resumableNames = statusData.resumable_languages.map(function(lang) {
                    return languageNames[lang] || lang.toUpperCase();
                });
                
                html += resumableNames.join(', ') +
                    '<button id="nexus-ai-wp-meta-resume-btn" class="button button-small" style="margin-left: 10px;">Resume</button>' +
                    '</div>';
                
                // Bind resume button event
                setTimeout(function() {
                    $('#nexus-ai-wp-meta-resume-btn').on('click', function() {
                        var postId = NexusAIWPTranslatorCore.getPostId();
                        var postTitle = $('#title').val() || 'Current Post';
                        
                        NexusAIWPTranslatorTranslationManager.startResumeTranslation(
                            postId, 
                            postTitle, 
                            statusData.resumable_languages, 
                            $(this)
                        );
                    });
                }, 100);
            }

            if (html === '') {
                html = '<p>No translations found for this post.</p>';
            }

            statusContainer.html(html);
        },

        /**
         * Initialize quick actions
         */
        initQuickActions: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initQuickActions')) return;
            var $ = jQuery;

            // Handle unlink all translations
            $(document).on('click', '#nexus-ai-wp-meta-unlink-all', function() {
                if (!confirm('Are you sure you want to unlink all translations for this post?')) {
                    return;
                }
                
                var postId = NexusAIWPTranslatorCore.getPostId();
                var button = $(this);
                
                button.prop('disabled', true).text('Unlinking...');
                
                $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                    action: 'nexus_ai_wp_unlink_all_translations',
                    post_id: postId,
                    nonce: nexus_ai_wp_translator_ajax.nonce
                })
                .done(function(response) {
                    if (response.success) {
                        NexusAIWPTranslatorCore.showGlobalNotice('success', 'All translations unlinked successfully!');
                        NexusAIWPTranslatorMetaBox.loadTranslationStatus(postId);
                    } else {
                        var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                        alert('Failed to unlink translations: ' + errorMsg);
                    }
                })
                .fail(function() {
                    alert('Network error occurred while unlinking translations.');
                })
                .always(function() {
                    button.prop('disabled', false).text('Unlink All');
                });
            });

            // Handle clear cache for this post
            $(document).on('click', '#nexus-ai-wp-meta-clear-cache', function() {
                var postId = NexusAIWPTranslatorCore.getPostId();
                var button = $(this);
                
                button.prop('disabled', true).text('Clearing...');
                
                $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                    action: 'nexus_ai_wp_clear_post_cache',
                    post_id: postId,
                    nonce: nexus_ai_wp_translator_ajax.nonce
                })
                .done(function(response) {
                    if (response.success) {
                        NexusAIWPTranslatorCore.showGlobalNotice('success', 'Cache cleared successfully!');
                    } else {
                        var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                        alert('Failed to clear cache: ' + errorMsg);
                    }
                })
                .fail(function() {
                    alert('Network error occurred while clearing cache.');
                })
                .always(function() {
                    button.prop('disabled', false).text('Clear Cache');
                });
            });
        }
    };

    // Make meta box globally available
    window.NexusAIWPTranslatorMetaBox = NexusAIWPTranslatorMetaBox;
    console.debug('[Nexus Translator]: NexusAIWPTranslatorMetaBox made globally available');

})();
