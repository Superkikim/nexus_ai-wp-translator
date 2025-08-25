/**
 * Nexus AI WP Translator Bulk Actions
 * Bulk operations functionality
 */

(function() {
    'use strict';

    // Bulk actions object
    var NexusAIWPTranslatorBulkActions = {
        
        /**
         * Initialize bulk actions
         */
        init: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initBulkActions')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Initializing bulk actions');
            
            // Handle bulk action form submission
            $(document).on('submit', '#nexus-ai-wp-bulk-actions-form', function(e) {
                e.preventDefault();
                
                var form = $(this);
                var action = form.find('select[name="bulk_action"]').val();
                var selectedPosts = [];
                
                form.find('input[name="post_ids[]"]:checked').each(function() {
                    selectedPosts.push($(this).val());
                });
                
                if (selectedPosts.length === 0) {
                    alert('Please select at least one post.');
                    return;
                }
                
                if (!action) {
                    alert('Please select an action.');
                    return;
                }
                
                console.debug('[Nexus Translator]: Bulk action:', action, 'for posts:', selectedPosts);
                
                // Handle different actions
                switch(action) {
                    case 'translate':
                        NexusAIWPTranslatorBulkActions.handleBulkTranslate(selectedPosts);
                        break;
                    case 'set_language':
                        NexusAIWPTranslatorBulkActions.handleBulkSetLanguage(selectedPosts);
                        break;
                    case 'link':
                        NexusAIWPTranslatorBulkActions.handleBulkLink(selectedPosts);
                        break;
                    case 'unlink':
                        NexusAIWPTranslatorBulkActions.handleBulkUnlink(selectedPosts);
                        break;
                    case 'delete':
                        NexusAIWPTranslatorBulkActions.handleBulkDelete(selectedPosts);
                        break;
                    case 'clear_cache':
                        NexusAIWPTranslatorBulkActions.handleBulkClearCache(selectedPosts);
                        break;
                    default:
                        alert('Please select an action.');
                }
            });
        },

        /**
         * Initialize bulk actions interface
         */
        initInterface: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initBulkActionsInterface')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Initializing bulk actions interface');

            // Update selection count when checkboxes change
            $(document).on('change', '.select-post-checkbox, .select-all-checkbox', function() {
                NexusAIWPTranslatorBulkActions.updateBulkSelectionCount();
                NexusAIWPTranslatorBulkActions.updateBulkActionButtons();
            });

            // Handle select all checkbox
            $(document).on('change', '.select-all-checkbox', function() {
                var isChecked = $(this).is(':checked');
                $('.select-post-checkbox').prop('checked', isChecked);
                NexusAIWPTranslatorBulkActions.updateBulkSelectionCount();
                NexusAIWPTranslatorBulkActions.updateBulkActionButtons();
            });

            // Handle bulk action button click
            $(document).on('click', '#nexus-ai-wp-bulk-action-apply', function() {
                var action = $('#nexus-ai-wp-bulk-action-select').val();
                var selectedPosts = [];
                
                $('.select-post-checkbox:checked').each(function() {
                    selectedPosts.push($(this).val());
                });
                
                if (selectedPosts.length === 0) {
                    alert('Please select at least one post.');
                    return;
                }
                
                if (!action) {
                    alert('Please select an action.');
                    return;
                }

                console.debug('[Nexus Translator]: Bulk action:', action, 'for posts:', selectedPosts);

                // Handle different actions
                switch(action) {
                    case 'translate':
                        NexusAIWPTranslatorBulkActions.handleBulkTranslate(selectedPosts);
                        break;
                    case 'set_language':
                        NexusAIWPTranslatorBulkActions.handleBulkSetLanguage(selectedPosts);
                        break;
                    case 'link':
                        NexusAIWPTranslatorBulkActions.handleBulkLink(selectedPosts);
                        break;
                    case 'unlink':
                        NexusAIWPTranslatorBulkActions.handleBulkUnlink(selectedPosts);
                        break;
                    case 'delete':
                        NexusAIWPTranslatorBulkActions.handleBulkDelete(selectedPosts);
                        break;
                    case 'clear_cache':
                        NexusAIWPTranslatorBulkActions.handleBulkClearCache(selectedPosts);
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
            if (!NexusAIWPTranslatorCore.ensureJQuery('updateBulkSelectionCount')) return;
            var $ = jQuery;

            var selectedCount = $('.select-post-checkbox:checked').length;
            var totalCount = $('.select-post-checkbox').length;
            
            console.debug('[Nexus Translator]: Selected posts:', selectedCount, 'of', totalCount);
            
            // Update selection count display
            $('#nexus-ai-wp-selection-count').text(selectedCount);
            $('#nexus-ai-wp-total-count').text(totalCount);
            
            // Update select all checkbox state
            var selectAllCheckbox = $('.select-all-checkbox');
            if (selectedCount === 0) {
                selectAllCheckbox.prop('indeterminate', false).prop('checked', false);
            } else if (selectedCount === totalCount) {
                selectAllCheckbox.prop('indeterminate', false).prop('checked', true);
            } else {
                selectAllCheckbox.prop('indeterminate', true).prop('checked', false);
            }
        },

        /**
         * Update bulk action buttons state
         */
        updateBulkActionButtons: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('updateBulkActionButtons')) return;
            var $ = jQuery;

            var selectedCount = $('.select-post-checkbox:checked').length;
            var bulkActionButton = $('#nexus-ai-wp-bulk-action-apply');
            var bulkActionSelect = $('#nexus-ai-wp-bulk-action-select');
            
            if (selectedCount > 0) {
                bulkActionButton.prop('disabled', false);
                bulkActionSelect.prop('disabled', false);
            } else {
                bulkActionButton.prop('disabled', true);
                bulkActionSelect.prop('disabled', true);
            }
        },

        /**
         * Handle bulk set language action
         */
        handleBulkSetLanguage: function(selectedPosts) {
            console.debug('[Nexus Translator]: Handling bulk set language');

            // Show language selection dialog
            this.showBulkSetLanguageDialog(selectedPosts);
        },

        /**
         * Handle bulk translate action
         */
        handleBulkTranslate: function(selectedPosts) {
            console.debug('[Nexus Translator]: Handling bulk translate');

            // Check existing translations and show dialog
            this.showBulkTranslateDialog(selectedPosts);
        },

        /**
         * Show bulk translate dialog
         */
        showBulkTranslateDialog: function(selectedPosts) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('showBulkTranslateDialog')) return;
            var $ = jQuery;

            var dialogId = 'nexus-ai-wp-bulk-translate-dialog';
            
            // Create dialog HTML
            var dialogHtml = '<div id="' + dialogId + '" class="nexus-ai-wp-dialog-overlay">' +
                '<div class="nexus-ai-wp-dialog">' +
                '<div class="nexus-ai-wp-dialog-header">' +
                '<h3>Bulk Translate Posts</h3>' +
                '<button class="nexus-ai-wp-dialog-close">×</button>' +
                '</div>' +
                '<div class="nexus-ai-wp-dialog-content">' +
                '<p>You are about to translate <strong>' + selectedPosts.length + '</strong> posts.</p>' +
                '<div class="nexus-ai-wp-form-group">' +
                '<label for="bulk-translate-languages">Target Languages:</label>' +
                '<select id="bulk-translate-languages" multiple size="5">' +
                '<option value="es">Spanish</option>' +
                '<option value="fr">French</option>' +
                '<option value="de">German</option>' +
                '<option value="it">Italian</option>' +
                '<option value="pt">Portuguese</option>' +
                '</select>' +
                '<p class="description">Hold Ctrl/Cmd to select multiple languages.</p>' +
                '</div>' +
                '</div>' +
                '<div class="nexus-ai-wp-dialog-footer">' +
                '<button id="bulk-translate-confirm" class="button button-primary">Start Translation</button>' +
                '<button class="nexus-ai-wp-dialog-close button">Cancel</button>' +
                '</div>' +
                '</div>' +
                '</div>';
            
            // Remove existing dialog
            $('#' + dialogId).remove();
            
            // Add dialog to page
            $('body').append(dialogHtml);
            
            // Initialize dialog events
            this.initBulkDialogEvents(dialogId, selectedPosts);
        },

        /**
         * Initialize bulk dialog events
         */
        initBulkDialogEvents: function(dialogId, selectedPosts) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initBulkDialogEvents')) return;
            var $ = jQuery;

            var dialog = $('#' + dialogId);
            
            // Close dialog events
            dialog.find('.nexus-ai-wp-dialog-close').on('click', function() {
                dialog.remove();
            });
            
            // Click outside to close
            dialog.on('click', function(e) {
                if (e.target === this) {
                    dialog.remove();
                }
            });
            
            // Confirm button
            dialog.find('#bulk-translate-confirm').on('click', function() {
                var targetLanguages = [];
                dialog.find('#bulk-translate-languages option:selected').each(function() {
                    targetLanguages.push($(this).val());
                });
                
                if (targetLanguages.length === 0) {
                    alert('Please select at least one target language.');
                    return;
                }
                
                dialog.remove();
                NexusAIWPTranslatorBulkActions.performBulkTranslation(selectedPosts, targetLanguages);
            });
        },

        /**
         * Perform bulk translation
         */
        performBulkTranslation: function(selectedPosts, targetLanguages) {
            console.debug('[Nexus Translator]: Starting bulk translation for', selectedPosts.length, 'posts to', targetLanguages);

            // Show progress dialog for bulk translation
            this.showBulkTranslationProgress(selectedPosts, targetLanguages);

            // Start processing posts sequentially
            this.processBulkTranslation(selectedPosts, targetLanguages, 0);
        },

        /**
         * Show bulk translation progress
         */
        showBulkTranslationProgress: function(selectedPosts, targetLanguages) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('showBulkTranslationProgress')) return;
            var $ = jQuery;

            var dialogId = 'nexus-ai-wp-bulk-progress-dialog';
            
            var dialogHtml = '<div id="' + dialogId + '" class="nexus-ai-wp-dialog-overlay">' +
                '<div class="nexus-ai-wp-dialog">' +
                '<div class="nexus-ai-wp-dialog-header">' +
                '<h3>Bulk Translation Progress</h3>' +
                '</div>' +
                '<div class="nexus-ai-wp-dialog-content">' +
                '<p>Translating <strong>' + selectedPosts.length + '</strong> posts to <strong>' + targetLanguages.join(', ') + '</strong></p>' +
                '<div class="nexus-ai-wp-progress-container">' +
                '<div class="nexus-ai-wp-progress-bar-container">' +
                '<div id="bulk-progress-bar" class="nexus-ai-wp-progress-bar" style="width: 0%;"></div>' +
                '</div>' +
                '<div id="bulk-progress-text" class="nexus-ai-wp-progress-text">0%</div>' +
                '</div>' +
                '<div id="bulk-progress-status" class="nexus-ai-wp-progress-status">Starting...</div>' +
                '<div id="bulk-progress-log" class="nexus-ai-wp-progress-log"></div>' +
                '</div>' +
                '<div class="nexus-ai-wp-dialog-footer">' +
                '<button id="bulk-progress-close" class="button" disabled>Close</button>' +
                '</div>' +
                '</div>' +
                '</div>';
            
            // Remove existing dialog
            $('#' + dialogId).remove();
            
            // Add dialog to page
            $('body').append(dialogHtml);
            
            // Close button event
            $('#bulk-progress-close').on('click', function() {
                $('#' + dialogId).remove();
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

            var postId = selectedPosts[currentIndex];
            var progress = Math.round((currentIndex / selectedPosts.length) * 100);
            
            // Update progress
            $('#bulk-progress-bar').css('width', progress + '%');
            $('#bulk-progress-text').text(progress + '%');
            $('#bulk-progress-status').text('Translating post ' + (currentIndex + 1) + ' of ' + selectedPosts.length + '...');
            
            // Add log entry
            $('#bulk-progress-log').append('<div>Processing post ID: ' + postId + '</div>');
            
            // Translate current post
            NexusAIWPTranslatorAjax.translatePost(postId, targetLanguages, null, function(response) {
                if (response.success) {
                    $('#bulk-progress-log').append('<div style="color: green;">✓ Post ' + postId + ' translated successfully</div>');
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                    $('#bulk-progress-log').append('<div style="color: red;">✗ Post ' + postId + ' failed: ' + errorMsg + '</div>');
                }
                
                // Process next post after a short delay
                setTimeout(function() {
                    NexusAIWPTranslatorBulkActions.processBulkTranslation(selectedPosts, targetLanguages, currentIndex + 1);
                }, 1000);
            });
        },

        /**
         * Complete bulk translation
         */
        completeBulkTranslation: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('completeBulkTranslation')) return;
            var $ = jQuery;

            // Update progress to 100%
            $('#bulk-progress-bar').css('width', '100%');
            $('#bulk-progress-text').text('100%');
            $('#bulk-progress-status').text('Bulk translation completed!');
            $('#bulk-progress-log').append('<div style="color: green; font-weight: bold;">✓ All translations completed!</div>');
            
            // Enable close button
            $('#bulk-progress-close').prop('disabled', false);
            
            // Auto-close after 5 seconds
            setTimeout(function() {
                $('#nexus-ai-wp-bulk-progress-dialog').remove();
                location.reload(); // Refresh to show new translations
            }, 5000);
        },

        /**
         * Handle bulk link action
         */
        handleBulkLink: function(selectedPosts) {
            if (selectedPosts.length < 2) {
                alert('Please select at least 2 posts to link together.');
                return;
            }

            this.showBulkLinkDialog(selectedPosts);
        },

        /**
         * Handle bulk unlink action
         */
        handleBulkUnlink: function(selectedPosts) {
            if (!confirm('Are you sure you want to unlink the selected posts? This will remove all translation relationships between them.')) {
                return;
            }

            this.performBulkUnlink(selectedPosts);
        },

        /**
         * Handle bulk delete action
         */
        handleBulkDelete: function(selectedPosts) {
            if (!confirm('Are you sure you want to delete the selected posts? This action cannot be undone.')) {
                return;
            }

            this.performBulkDelete(selectedPosts);
        },

        /**
         * Handle bulk clear cache action
         */
        handleBulkClearCache: function(selectedPosts) {
            if (!confirm('Are you sure you want to clear translation cache for the selected posts?')) {
                return;
            }

            this.performBulkClearCache(selectedPosts);
        },

        /**
         * Show bulk set language dialog
         */
        showBulkSetLanguageDialog: function(selectedPosts) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('showBulkSetLanguageDialog')) return;
            var $ = jQuery;

            var dialogId = 'nexus-ai-wp-bulk-set-language-dialog';

            // Get available languages from core
            var languageNames = NexusAIWPTranslatorCore.getLanguageNames();
            var languageOptions = '<option value="">Select Language</option>';

            // Build language options dynamically
            for (var code in languageNames) {
                if (languageNames.hasOwnProperty(code)) {
                    languageOptions += '<option value="' + code + '">' + languageNames[code] + ' (' + code + ')</option>';
                }
            }

            var dialogHtml = '<div id="' + dialogId + '" class="nexus-ai-wp-dialog-overlay">' +
                '<div class="nexus-ai-wp-dialog">' +
                '<div class="nexus-ai-wp-dialog-header">' +
                '<h3>Set Language for Posts</h3>' +
                '<button class="nexus-ai-wp-dialog-close">×</button>' +
                '</div>' +
                '<div class="nexus-ai-wp-dialog-content">' +
                '<p>Set language for <strong>' + selectedPosts.length + '</strong> selected posts.</p>' +
                '<div class="nexus-ai-wp-form-group">' +
                '<label for="bulk-set-language">Language:</label>' +
                '<select id="bulk-set-language">' +
                languageOptions +
                '</select>' +
                '</div>' +
                '<p class="description">This will set the source language for the selected posts. The language indicates what language the post content is written in.</p>' +
                '</div>' +
                '<div class="nexus-ai-wp-dialog-footer">' +
                '<button id="bulk-set-language-confirm" class="button button-primary">Set Language</button>' +
                '<button class="nexus-ai-wp-dialog-close button">Cancel</button>' +
                '</div>' +
                '</div>' +
                '</div>';

            $('#' + dialogId).remove();
            $('body').append(dialogHtml);

            var dialog = $('#' + dialogId);
            dialog.find('.nexus-ai-wp-dialog-close').on('click', function() {
                dialog.remove();
            });

            dialog.find('#bulk-set-language-confirm').on('click', function() {
                var language = $('#bulk-set-language').val();
                if (!language) {
                    alert('Please select a language.');
                    return;
                }
                dialog.remove();
                NexusAIWPTranslatorBulkActions.performBulkSetLanguage(selectedPosts, language);
            });
        },

        /**
         * Perform bulk set language
         */
        performBulkSetLanguage: function(selectedPosts, language) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('performBulkSetLanguage')) return;
            var $ = jQuery;

            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_bulk_set_language',
                post_ids: selectedPosts,
                language: language,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    NexusAIWPTranslatorCore.showGlobalNotice('success', 'Language set successfully for ' + selectedPosts.length + ' posts!');
                    location.reload();
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                    alert('Failed to set language: ' + errorMsg);
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
            if (!NexusAIWPTranslatorCore.ensureJQuery('performBulkUnlink')) return;
            var $ = jQuery;

            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_bulk_unlink_posts',
                post_ids: selectedPosts,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    NexusAIWPTranslatorCore.showGlobalNotice('success', 'Posts unlinked successfully!');
                    location.reload();
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                    alert('Failed to unlink posts: ' + errorMsg);
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
            if (!NexusAIWPTranslatorCore.ensureJQuery('performBulkDelete')) return;
            var $ = jQuery;

            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_bulk_delete_posts',
                post_ids: selectedPosts,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    NexusAIWPTranslatorCore.showGlobalNotice('success', 'Posts deleted successfully!');
                    location.reload();
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                    alert('Failed to delete posts: ' + errorMsg);
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
            if (!NexusAIWPTranslatorCore.ensureJQuery('performBulkClearCache')) return;
            var $ = jQuery;

            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_bulk_clear_cache',
                post_ids: selectedPosts,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    NexusAIWPTranslatorCore.showGlobalNotice('success', 'Cache cleared successfully for ' + selectedPosts.length + ' posts!');
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                    alert('Failed to clear cache: ' + errorMsg);
                }
            })
            .fail(function() {
                alert('Network error occurred while clearing cache.');
            });
        }
    };

    // Make bulk actions globally available
    window.NexusAIWPTranslatorBulkActions = NexusAIWPTranslatorBulkActions;
    console.debug('[Nexus Translator]: NexusAIWPTranslatorBulkActions made globally available');

})();
