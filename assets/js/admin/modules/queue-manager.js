/**
 * Nexus AI WP Translator Queue Manager
 * Translation queue functionality
 */

(function() {
    'use strict';

    // Queue manager object
    var NexusAIWPTranslatorQueueManager = {
        queueRefreshInterval: null,
        
        /**
         * Initialize translation queue interface
         */
        init: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initTranslationQueueInterface')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Initializing translation queue interface');

            // Auto-refresh queue when tab is active
            this.queueRefreshInterval = null;

            // Handle tab switching
            $(document).on('click', '.nav-tab[href="#queue-tab"]', function() {
                NexusAIWPTranslatorQueueManager.loadQueueData();
                NexusAIWPTranslatorQueueManager.startQueueAutoRefresh();
            });

            // Handle other tab switching (stop auto-refresh)
            $(document).on('click', '.nav-tab:not([href="#queue-tab"])', function() {
                NexusAIWPTranslatorQueueManager.stopQueueAutoRefresh();
            });

            // Queue control buttons
            $(document).on('click', '#refresh-queue-btn', function() {
                NexusAIWPTranslatorQueueManager.loadQueueData();
            });

            $(document).on('click', '#pause-queue-btn', function() {
                NexusAIWPTranslatorQueueManager.pauseQueue();
            });

            $(document).on('click', '#resume-queue-btn', function() {
                NexusAIWPTranslatorQueueManager.resumeQueue();
            });

            // Queue filter
            $(document).on('change', '#queue-status-filter', function() {
                NexusAIWPTranslatorQueueManager.loadQueueData();
            });

            // Queue item actions
            $(document).on('click', '.queue-item-remove', function() {
                var queueId = $(this).data('queue-id');
                NexusAIWPTranslatorQueueManager.removeQueueItem(queueId);
            });

            $(document).on('click', '.queue-item-retry', function() {
                var queueId = $(this).data('queue-id');
                NexusAIWPTranslatorQueueManager.retryQueueItem(queueId);
            });
        },

        /**
         * Load queue data
         */
        loadQueueData: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('loadQueueData')) return;
            var $ = jQuery;

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
                    console.debug('[Nexus Translator]: Failed to load queue data:', response.message);
                }
            })
            .fail(function() {
                console.debug('[Nexus Translator]: Network error loading queue data');
            });
        },

        /**
         * Update queue display
         */
        updateQueueDisplay: function(data) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('updateQueueDisplay')) return;
            var $ = jQuery;

            // Update statistics
            $('#queue-pending-count').text(data.statistics.pending || 0);
            $('#queue-processing-count').text(data.statistics.processing || 0);
            $('#queue-completed-count').text(data.statistics.completed || 0);
            $('#queue-failed-count').text(data.statistics.failed || 0);

            // Update queue items list
            var queueList = $('#queue-items-tbody');
            queueList.empty();

            if (data.items && data.items.length > 0) {
                data.items.forEach(function(item) {
                    var itemRow = NexusAIWPTranslatorQueueManager.createQueueItemRow(item);
                    queueList.append(itemRow);
                });
            } else {
                queueList.append('<tr><td colspan="7">No queue items found.</td></tr>');
            }

            // Update control buttons based on queue paused flag
            var isPaused = (data.statistics && data.statistics.queue_paused) ? true : false;
            if (isPaused) {
                $('#pause-queue-btn').hide();
                $('#resume-queue-btn').show();
            } else {
                $('#pause-queue-btn').show();
                $('#resume-queue-btn').hide();
            }
        },

        /**
         * Create queue item row
         */
        createQueueItemRow: function(item) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('createQueueItemRow')) return '';
            var $ = jQuery;

            var languages = [];
            try { languages = JSON.parse(item.target_languages || '[]'); } catch(e) { languages = []; }
            var languageTags = languages.map(function(lang) {
                return '<span class="queue-language-tag">' + NexusAIWPTranslatorCore.escapeHtml(lang) + '</span>';
            }).join('');

            var statusClass = 'status-' + (item.status || 'unknown');
            var priorityClass = 'priority-' + (item.priority || 'normal');

            var actionsHtml = '';
            if (item.status === 'failed') {
                actionsHtml += '<button class="button button-small queue-item-retry" data-queue-id="' + item.id + '">Retry</button> ';
            }
            actionsHtml += '<button class="button button-small queue-item-remove" data-queue-id="' + item.id + '">Remove</button>';

            // Build table columns to match header: Post | Languages | Priority | Status | Scheduled | Attempts | Actions
            var scheduled = item.scheduled_time ? NexusAIWPTranslatorCore.escapeHtml(item.scheduled_time) : '-';
            var attempts = (item.attempts !== undefined && item.max_attempts !== undefined)
                ? (parseInt(item.attempts, 10) + ' / ' + parseInt(item.max_attempts, 10)) : '-';

            return '<tr class="queue-item ' + statusClass + ' ' + priorityClass + '">' +
                '<td>' +
                '<strong>' + NexusAIWPTranslatorCore.escapeHtml(item.post_title || ('Post #' + item.post_id)) + '</strong>' +
                '<br><small>ID: ' + item.post_id + '</small>' +
                '</td>' +
                '<td>' + languageTags + '</td>' +
                '<td><span class="queue-priority-badge ' + priorityClass + '">' + (item.priority || 'Normal') + '</span></td>' +
                '<td><span class="queue-status-badge ' + statusClass + '">' + (item.status || 'Unknown').replace('_', ' ') + '</span></td>' +
                '<td>' + scheduled + '</td>' +
                '<td>' + attempts + '</td>' +
                '<td>' + actionsHtml + '</td>' +
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
            }, 5000); // Refresh every 5 seconds
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
            if (!NexusAIWPTranslatorCore.ensureJQuery('pauseQueue')) return;
            var $ = jQuery;

            var button = $('#pause-queue-btn');
            button.prop('disabled', true).text('Pausing...');

            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_pause_queue',
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    NexusAIWPTranslatorCore.showGlobalNotice('success', 'Queue paused successfully!');
                    NexusAIWPTranslatorQueueManager.loadQueueData();
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                    alert('Failed to pause queue: ' + errorMsg);
                }
            })
            .fail(function() {
                alert('Network error occurred while pausing queue.');
            })
            .always(function() {
                button.prop('disabled', false).text('Pause Queue');
            });
        },

        /**
         * Resume queue
         */
        resumeQueue: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('resumeQueue')) return;
            var $ = jQuery;

            var button = $('#resume-queue-btn');
            button.prop('disabled', true).text('Resuming...');

            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_resume_queue',
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    NexusAIWPTranslatorCore.showGlobalNotice('success', 'Queue resumed successfully!');
                    NexusAIWPTranslatorQueueManager.loadQueueData();
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                    alert('Failed to resume queue: ' + errorMsg);
                }
            })
            .fail(function() {
                alert('Network error occurred while resuming queue.');
            })
            .always(function() {
                button.prop('disabled', false).text('Resume Queue');
            });
        },

        /**
         * Remove queue item
         */
        removeQueueItem: function(queueId) {
            if (!confirm('Are you sure you want to remove this item from the queue?')) {
                return;
            }

            if (!NexusAIWPTranslatorCore.ensureJQuery('removeQueueItem')) return;
            var $ = jQuery;

            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_remove_from_queue',
                queue_id: queueId,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    NexusAIWPTranslatorCore.showGlobalNotice('success', 'Queue item removed successfully!');
                    NexusAIWPTranslatorQueueManager.loadQueueData();
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                    alert('Failed to remove queue item: ' + errorMsg);
                }
            })
            .fail(function() {
                alert('Network error occurred while removing queue item.');
            });
        },

        /**
         * Retry queue item
         */
        retryQueueItem: function(queueId) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('retryQueueItem')) return;
            var $ = jQuery;

            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_retry_queue_item',
                queue_id: queueId,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    NexusAIWPTranslatorCore.showGlobalNotice('success', 'Queue item retried successfully!');
                    NexusAIWPTranslatorQueueManager.loadQueueData();
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                    alert('Failed to retry queue item: ' + errorMsg);
                }
            })
            .fail(function() {
                alert('Network error occurred while retrying queue item.');
            });
        },

        /**
         * Show add to queue dialog
         */
        showAddToQueueDialog: function(postId, postTitle) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('showAddToQueueDialog')) return;
            var $ = jQuery;

            var dialogId = 'nexus-ai-wp-add-to-queue-dialog';
            
            var dialogHtml = '<div id="' + dialogId + '" class="nexus-ai-wp-dialog-overlay">' +
                '<div class="nexus-ai-wp-dialog">' +
                '<div class="nexus-ai-wp-dialog-header">' +
                '<h3>Add to Translation Queue</h3>' +
                '<button class="nexus-ai-wp-dialog-close">×</button>' +
                '</div>' +
                '<div class="nexus-ai-wp-dialog-content">' +
                '<p>Add <strong>' + NexusAIWPTranslatorCore.escapeHtml(postTitle) + '</strong> to the translation queue.</p>' +
                '<div class="nexus-ai-wp-form-group">' +
                '<label for="queue-target-languages">Target Languages:</label>' +
                '<select id="queue-target-languages" multiple size="5">' +
                '<option value="es">Spanish</option>' +
                '<option value="fr">French</option>' +
                '<option value="de">German</option>' +
                '<option value="it">Italian</option>' +
                '<option value="pt">Portuguese</option>' +
                '</select>' +
                '</div>' +
                '<div class="nexus-ai-wp-form-group">' +
                '<label for="queue-priority">Priority:</label>' +
                '<select id="queue-priority">' +
                '<option value="low">Low</option>' +
                '<option value="normal" selected>Normal</option>' +
                '<option value="high">High</option>' +
                '<option value="urgent">Urgent</option>' +
                '</select>' +
                '</div>' +
                '<div class="nexus-ai-wp-form-group">' +
                '<label for="queue-scheduled-time">Scheduled Time (optional):</label>' +
                '<input type="datetime-local" id="queue-scheduled-time">' +
                '</div>' +
                '</div>' +
                '<div class="nexus-ai-wp-dialog-footer">' +
                '<button id="add-to-queue-confirm" class="button button-primary">Add to Queue</button>' +
                '<button class="nexus-ai-wp-dialog-close button">Cancel</button>' +
                '</div>' +
                '</div>' +
                '</div>';
            
            $('#' + dialogId).remove();
            $('body').append(dialogHtml);
            
            var dialog = $('#' + dialogId);
            
            // Close dialog events
            dialog.find('.nexus-ai-wp-dialog-close').on('click', function() {
                dialog.remove();
            });
            
            // Confirm button
            dialog.find('#add-to-queue-confirm').on('click', function() {
                var targetLanguages = [];
                dialog.find('#queue-target-languages option:selected').each(function() {
                    targetLanguages.push($(this).val());
                });
                
                var priority = dialog.find('#queue-priority').val();
                var scheduledTime = dialog.find('#queue-scheduled-time').val();
                
                if (targetLanguages.length === 0) {
                    alert('Please select at least one target language.');
                    return;
                }
                
                dialog.remove();
                NexusAIWPTranslatorQueueManager.addToQueue(postId, targetLanguages, priority, scheduledTime);
            });
        },

        /**
         * Add post to queue
         */
        addToQueue: function(postId, targetLanguages, priority, scheduledTime) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('addToQueue')) return;
            var $ = jQuery;

            // Map priority string to numeric level expected by server (low=3, normal=5, high=7, urgent=9)
            var priorityMap = { low: 3, normal: 5, high: 7, urgent: 9 };
            var priorityValue = priorityMap[String(priority).toLowerCase()] || 5;

            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_add_to_queue',
                post_id: postId,
                target_languages: targetLanguages,
                priority: priorityValue,
                scheduled_time: scheduledTime,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    NexusAIWPTranslatorCore.showGlobalNotice('success', 'Post added to translation queue successfully!');
                    NexusAIWPTranslatorQueueManager.loadQueueData();
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                    alert('Failed to add to queue: ' + errorMsg);
                }
            })
            .fail(function() {
                alert('Network error occurred while adding to queue.');
            });
        }
    };

    // Make queue manager globally available
    window.NexusAIWPTranslatorQueueManager = NexusAIWPTranslatorQueueManager;
    console.debug('[Nexus Translator]: NexusAIWPTranslatorQueueManager made globally available');

})();
