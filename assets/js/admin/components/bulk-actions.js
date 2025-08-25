/**
 * Nexus AI WP Translator Bulk Actions
 * Bulk operations functionality with container scoping
 */

(function() {
    'use strict';

    // Bulk actions object
    var NexusAIWPTranslatorBulkActions = {

        // Store initialized containers to prevent duplicate initialization
        initializedContainers: new Set(),

        /**
         * Initialize bulk actions
         */
        init: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initBulkActions')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Initializing bulk actions');

            // Note: Bulk actions are handled via button click, not form submission
        },

        /**
         * Initialize bulk actions interface (legacy method for backward compatibility)
         */
        initInterface: function() {
            console.debug('[Nexus Translator]: Legacy initInterface called - initializing for all tab containers');

            // Initialize for all known tab containers
            var tabContainers = ['#articles-tab', '#pages-tab', '#events-tab'];

            tabContainers.forEach(function(containerId) {
                if (jQuery(containerId).length > 0) {
                    NexusAIWPTranslatorBulkActions.initForContainer(containerId);
                }
            });
        },

        /**
         * Initialize bulk actions for a specific container
         * @param {string} containerSelector - CSS selector for the container (e.g., '#articles-tab')
         */
        initForContainer: function(containerSelector) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initBulkActionsForContainer')) return;
            var $ = jQuery;

            // Prevent duplicate initialization
            if (this.initializedContainers.has(containerSelector)) {
                console.debug('[Nexus Translator]: Container already initialized:', containerSelector);
                return;
            }

            console.debug('[Nexus Translator]: Initializing bulk actions for container:', containerSelector);

            var container = $(containerSelector);
            if (container.length === 0) {
                console.warn('[Nexus Translator]: Container not found:', containerSelector);
                return;
            }

            // Clean up any existing event handlers for this container
            this.cleanupContainerEvents(containerSelector);

            // Initialize container-scoped event handlers
            this.initContainerCheckboxHandlers(containerSelector);
            this.initContainerBulkActionHandlers(containerSelector);

            // Mark container as initialized
            this.initializedContainers.add(containerSelector);

            // Update initial state
            this.updateBulkSelectionCount(containerSelector);
            this.updateBulkActionButtons(containerSelector);
        },

        /**
         * Clean up event handlers for a specific container
         * @param {string} containerSelector - CSS selector for the container
         */
        cleanupContainerEvents: function(containerSelector) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('cleanupContainerEvents')) return;
            var $ = jQuery;

            var container = $(containerSelector);

            // Remove namespaced event handlers
            container.off('.bulkactions');

            console.debug('[Nexus Translator]: Cleaned up events for container:', containerSelector);
        },

        /**
         * Initialize checkbox event handlers for a specific container
         * @param {string} containerSelector - CSS selector for the container
         */
        initContainerCheckboxHandlers: function(containerSelector) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initContainerCheckboxHandlers')) return;
            var $ = jQuery;

            var container = $(containerSelector);

            // Handle individual checkbox changes (scoped to container)
            container.on('change.bulkactions', '.select-post-checkbox', function() {
                NexusAIWPTranslatorBulkActions.updateBulkSelectionCount(containerSelector);
                NexusAIWPTranslatorBulkActions.updateBulkActionButtons(containerSelector);
                NexusAIWPTranslatorBulkActions.updateSelectAllCheckbox(containerSelector);
            });

            // Handle select all checkbox (scoped to container)
            container.on('change.bulkactions', '.select-all-checkbox', function() {
                var isChecked = $(this).is(':checked');
                container.find('.select-post-checkbox').prop('checked', isChecked);
                NexusAIWPTranslatorBulkActions.updateBulkSelectionCount(containerSelector);
                NexusAIWPTranslatorBulkActions.updateBulkActionButtons(containerSelector);
            });

            console.debug('[Nexus Translator]: Initialized checkbox handlers for:', containerSelector);
        },

        /**
         * Initialize bulk action button handlers for a specific container
         * @param {string} containerSelector - CSS selector for the container
         */
        initContainerBulkActionHandlers: function(containerSelector) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initContainerBulkActionHandlers')) return;
            var $ = jQuery;

            var container = $(containerSelector);

            // Handle bulk action button click (scoped to container)
            container.on('click.bulkactions', '.nexus-ai-wp-bulk-action-apply', function() {
                var button = $(this);
                var postType = button.data('post-type');
                var actionSelect = container.find('.nexus-ai-wp-bulk-action-select');
                var action = actionSelect.val();
                var selectedPosts = [];

                // Get selected posts from this container only
                container.find('.select-post-checkbox:checked').each(function() {
                    selectedPosts.push($(this).data('post-id'));
                });

                if (selectedPosts.length === 0) {
                    alert('Please select at least one post.');
                    return;
                }

                if (!action) {
                    alert('Please select an action.');
                    return;
                }

                console.debug('[Nexus Translator]: Bulk action:', action, 'for posts:', selectedPosts, 'in container:', containerSelector);

                // Handle different actions
                NexusAIWPTranslatorBulkActions.handleBulkAction(containerSelector, action, selectedPosts);
            });

            console.debug('[Nexus Translator]: Initialized bulk action handlers for:', containerSelector);
        },

        /**
         * Handle bulk action execution
         * @param {string} containerSelector - CSS selector for the container
         * @param {string} action - The action to perform
         * @param {Array} selectedPosts - Array of selected post IDs
         */
        handleBulkAction: function(containerSelector, action, selectedPosts) {
            switch(action) {
                case 'translate':
                    this.handleBulkTranslate(selectedPosts);
                    break;
                case 'set_language':
                    this.handleBulkSetLanguage(selectedPosts);
                    break;
                case 'detect_language':
                    this.handleBulkDetectLanguage(selectedPosts);
                    break;
                case 'link':
                    this.handleBulkLink(selectedPosts);
                    break;
                case 'unlink':
                    this.handleBulkUnlink(selectedPosts);
                    break;
                case 'delete':
                    this.handleBulkDelete(selectedPosts);
                    break;
                case 'clear_cache':
                    this.handleBulkClearCache(selectedPosts);
                    break;
                default:
                    alert('Please select an action.');
            }
        },

        /**
         * Update bulk selection count for a specific container
         * @param {string} containerSelector - CSS selector for the container
         */
        updateBulkSelectionCount: function(containerSelector) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('updateBulkSelectionCount')) return;
            var $ = jQuery;

            // Default to global scope for backward compatibility
            if (!containerSelector) {
                containerSelector = 'body';
            }

            var container = $(containerSelector);
            var selectedCount = container.find('.select-post-checkbox:checked').length;
            var totalCount = container.find('.select-post-checkbox').length;

            console.debug('[Nexus Translator]: Selected posts in', containerSelector + ':', selectedCount, 'of', totalCount);

            // Update selection count display within this container
            container.find('.nexus-ai-wp-bulk-selection-count').text(selectedCount + ' ' + 'items selected');
        },

        /**
         * Update select all checkbox state for a specific container
         * @param {string} containerSelector - CSS selector for the container
         */
        updateSelectAllCheckbox: function(containerSelector) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('updateSelectAllCheckbox')) return;
            var $ = jQuery;

            // Default to global scope for backward compatibility
            if (!containerSelector) {
                containerSelector = 'body';
            }

            var container = $(containerSelector);
            var selectedCount = container.find('.select-post-checkbox:checked').length;
            var totalCount = container.find('.select-post-checkbox').length;
            var selectAllCheckbox = container.find('.select-all-checkbox');

            if (selectedCount === 0) {
                selectAllCheckbox.prop('indeterminate', false).prop('checked', false);
            } else if (selectedCount === totalCount) {
                selectAllCheckbox.prop('indeterminate', false).prop('checked', true);
            } else {
                selectAllCheckbox.prop('indeterminate', true).prop('checked', false);
            }
        },

        /**
         * Update bulk action buttons state for a specific container
         * @param {string} containerSelector - CSS selector for the container
         */
        updateBulkActionButtons: function(containerSelector) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('updateBulkActionButtons')) return;
            var $ = jQuery;

            // Default to global scope for backward compatibility
            if (!containerSelector) {
                containerSelector = 'body';
            }

            var container = $(containerSelector);
            var selectedCount = container.find('.select-post-checkbox:checked').length;
            var bulkActionButtons = container.find('.nexus-ai-wp-bulk-action-apply');
            var bulkActionSelects = container.find('.nexus-ai-wp-bulk-action-select');

            if (selectedCount > 0) {
                bulkActionButtons.prop('disabled', false);
                bulkActionSelects.prop('disabled', false);
            } else {
                bulkActionButtons.prop('disabled', true);
                bulkActionSelects.prop('disabled', true);
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
         * Handle bulk detect language action
         */
        handleBulkDetectLanguage: function(selectedPosts) {
            console.debug('[Nexus Translator]: Handling bulk detect language');

            if (!confirm('Detect language for ' + selectedPosts.length + ' selected posts? This will use the AI to automatically detect the language of each post.')) {
                return;
            }

            this.performBulkDetectLanguage(selectedPosts);
        },

        /**
         * Perform bulk detect language
         */
        performBulkDetectLanguage: function(selectedPosts) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('performBulkDetectLanguage')) return;
            var $ = jQuery;

            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_bulk_detect_language',
                post_ids: selectedPosts,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    var message = 'Language detection completed for ' + selectedPosts.length + ' posts!';
                    if (response.data && response.data.detected_count) {
                        message = 'Successfully detected language for ' + response.data.detected_count + ' out of ' + selectedPosts.length + ' posts.';
                    }
                    NexusAIWPTranslatorCore.showGlobalNotice('success', message);
                    location.reload();
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                    alert('Failed to detect language: ' + errorMsg);
                }
            })
            .fail(function() {
                alert('Network error occurred while detecting language.');
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
