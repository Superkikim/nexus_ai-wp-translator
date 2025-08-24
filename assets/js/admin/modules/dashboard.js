/**
 * Nexus AI WP Translator Dashboard Module
 * Dashboard-specific functionality
 */

(function() {
    'use strict';

    // Dashboard module object
    var NexusAIWPTranslatorDashboard = {

        /**
         * Initialize dashboard functionality
         */
        init: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initDashboard')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Initializing dashboard functionality');

            // Initialize dashboard tabs
            this.initDashboardTabs();

            // Initialize post lists
            this.initPostLists();

            // Initialize dashboard stats
            this.initDashboardStats();

            // Initialize dashboard actions
            this.initDashboardActions();

            // Initialize queue actions
            this.initQueueActions();

            // Make Pending Queue clickable to open Queue tab
            jQuery(document).on('click', '#dashboard-pending-queue', function(e) {
                e.preventDefault();
                jQuery('.nexus-ai-wp-content-tabs .nav-tab[href="#queue-tab"]').trigger('click');
            });

            // Enhance quality displays
            setTimeout(function() {
                NexusAIWPTranslatorDashboard.enhanceQualityDisplays();
            }, 500);
            // Wire "Queue" buttons in posts tables to open Add to Queue dialog
            jQuery(document).on('click', '.add-to-queue-btn', function() {
                var $btn = jQuery(this);
                var postId = $btn.data('post-id');
                var postTitle = $btn.data('post-title');
                if (window.NexusAIWPTranslatorQueueManager && typeof NexusAIWPTranslatorQueueManager.showAddToQueueDialog === 'function') {
                    NexusAIWPTranslatorQueueManager.showAddToQueueDialog(postId, postTitle);
                }
            });
        },

        /**
         * Initialize dashboard tabs
         */
        initDashboardTabs: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initDashboardTabs')) return;
            var $ = jQuery;

            // Handle dashboard tab switching
            $(document).on('click', '.nexus-ai-wp-dashboard-tab', function(e) {
                e.preventDefault();

                var target = $(this).attr('href');
                console.debug('[Nexus Translator]: Dashboard tab clicked:', target);

                // Remove active class from all tabs and content
                $('.nexus-ai-wp-dashboard-tab').removeClass('nav-tab-active');
                $('.nexus-ai-wp-dashboard-content').removeClass('active').hide();

                // Add active class to clicked tab
                $(this).addClass('nav-tab-active');

                // Show corresponding content
                $(target).addClass('active').show();

                // Store active tab in localStorage
                localStorage.setItem('nexus_ai_wp_translator_dashboard_tab', target);

                // Load tab-specific content
                this.loadTabContent(target);
            }.bind(this));

            // Restore active tab from localStorage or URL hash
            var activeTab = window.location.hash || localStorage.getItem('nexus_ai_wp_translator_dashboard_tab');
            if (activeTab && $(activeTab).length > 0) {
                $('.nexus-ai-wp-dashboard-tab[href="' + activeTab + '"]').trigger('click');
            } else {
                // Default to first tab
                $('.nexus-ai-wp-dashboard-tab').first().trigger('click');
            }
        },

        /**
         * Load tab-specific content
         */
        loadTabContent: function(tabId) {
            switch(tabId) {
                case '#dashboard-tab':
                    this.loadDashboardOverview();
                    break;
                case '#articles-tab':
                    this.loadPostsList('post');
                    break;
                case '#pages-tab':
                    this.loadPostsList('page');
                    break;
                case '#events-tab':
                    this.loadPostsList('event');
                    break;
                case '#queue-tab':
                    if (window.NexusAIWPTranslatorQueueManager) {
                        NexusAIWPTranslatorQueueManager.loadQueueData();
                        NexusAIWPTranslatorQueueManager.startQueueAutoRefresh();
                    }
                    break;
                case '#logs-tab':
                    this.loadLogsData();
                    break;
                case '#analytics-tab':
                    this.loadDashboardStats();
                    break;
            }
        },

        /**
         * Initialize post lists
         */
        initPostLists: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initPostLists')) return;
            var $ = jQuery;

            // Handle post list filtering
            $(document).on('change', '.nexus-ai-wp-post-filter', function() {
                var postType = $(this).data('post-type');
                var filterValue = $(this).val();

                console.debug('[Nexus Translator]: Filtering posts:', postType, filterValue);

                NexusAIWPTranslatorDashboard.loadPostsList(postType, filterValue);
            });

            // Handle post list search
            $(document).on('input', '.nexus-ai-wp-post-search', NexusAIWPTranslatorCore.debounce(function() {
                var postType = $(this).data('post-type');
                var searchTerm = $(this).val();

                console.debug('[Nexus Translator]: Searching posts:', postType, searchTerm);

                NexusAIWPTranslatorDashboard.loadPostsList(postType, null, searchTerm);
            }, 500));

            // Handle post list pagination
            $(document).on('click', '.nexus-ai-wp-post-pagination a', function(e) {
                e.preventDefault();

                var postType = $(this).data('post-type');
                var page = $(this).data('page');

                console.debug('[Nexus Translator]: Loading page:', postType, page);

                NexusAIWPTranslatorDashboard.loadPostsList(postType, null, null, page);
            });
        },

        /**
         * Load posts list
         */
        loadPostsList: function(postType, filter, search, page) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('loadPostsList')) return;
            var $ = jQuery;

            filter = filter || '';
            search = search || '';
            page = page || 1;

            var listContainer = $('#' + postType + 's-list');
            listContainer.html('<div class="nexus-ai-wp-loading">Loading ' + postType + 's...</div>');

            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_get_posts_list',
                post_type: postType,
                filter: filter,
                search: search,
                page: page,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success && response.data) {
                    listContainer.html(response.data.html);

                    // Update pagination if provided
                    if (response.data.pagination) {
                        $('#' + postType + 's-pagination').html(response.data.pagination);
                    }

                    // Enhance quality displays in the new content
                    setTimeout(function() {
                        NexusAIWPTranslatorDashboard.enhanceQualityDisplays();
                    }, 100);
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Failed to load posts';
                    listContainer.html('<div class="notice notice-error"><p>' + errorMsg + '</p></div>');
                }
            })
            .fail(function() {
                listContainer.html('<div class="notice notice-error"><p>Network error occurred while loading posts.</p></div>');
            });
        },

        /**
         * Initialize queue actions
         */
        initQueueActions: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initQueueActions')) return;
            var $ = jQuery;

            // Process Queue Now button
            $(document).on('click', '#process-queue-now', function(e) {
                e.preventDefault();
                var $button = $(this);
                var originalText = $button.find('.action-text').text();

                $button.prop('disabled', true);
                $button.find('.action-text').text('Processing...');

                $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                    action: 'nexus_ai_wp_process_queue_now',
                    nonce: nexus_ai_wp_translator_ajax.nonce
                }).done(function(response) {
                    if (response.success) {
                        $button.find('.action-text').text('✓ Triggered');
                        setTimeout(function() {
                            $button.find('.action-text').text(originalText);
                            $button.prop('disabled', false);
                            // Refresh queue stats
                            if (typeof NexusAIWPTranslatorQueueManager !== 'undefined') {
                                NexusAIWPTranslatorQueueManager.loadQueueData();
                            }
                        }, 2000);
                    } else {
                        alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                        $button.find('.action-text').text(originalText);
                        $button.prop('disabled', false);
                    }
                }).fail(function() {
                    alert('Network error occurred');
                    $button.find('.action-text').text(originalText);
                    $button.prop('disabled', false);
                });
            });
        },

        /**
         * Initialize dashboard stats
         */
        initDashboardStats: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initDashboardStats')) return;
            var $ = jQuery;

            // Auto-refresh stats every 30 seconds
            this.statsRefreshInterval = setInterval(function() {
                NexusAIWPTranslatorDashboard.loadDashboardStats();
            }, 30000);

            // Handle stats period change
            $(document).on('change', '#nexus-ai-wp-stats-period', function() {
                var period = $(this).val();
                NexusAIWPTranslatorDashboard.loadDashboardStats(period);
            });

            // Handle manual stats refresh
            $(document).on('click', '#nexus-ai-wp-refresh-dashboard-stats', function() {
                var button = $(this);
                button.prop('disabled', true).text('Refreshing...');

                NexusAIWPTranslatorDashboard.loadDashboardStats(null, function() {
                    button.prop('disabled', false).text('Refresh');
                });
            });
        },

        /**
         * Load dashboard stats
         */
        loadDashboardStats: function(period, callback) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('loadDashboardStats')) return;
            var $ = jQuery;

            period = period || $('#nexus-ai-wp-stats-period').val() || '7 days';

            NexusAIWPTranslatorAjax.getStats(period, function(response) {
                if (response.success && response.data) {
                    NexusAIWPTranslatorDashboard.displayDashboardStats(response.data);
                    // Also refresh queue stats for dashboard tile
                    if (window.NexusAIWPTranslatorQueueManager) {
                        // Fetch minimal queue stats and update the Pending Queue link text
                        jQuery.post(nexus_ai_wp_translator_ajax.ajax_url, {
                            action: 'nexus_ai_wp_get_queue_status',
                            status: 'pending',
                            limit: 1,
                            nonce: nexus_ai_wp_translator_ajax.nonce
                        }).done(function(resp){
                            var pending = (resp && resp.success && resp.data && resp.data.statistics && resp.data.statistics.pending) ? resp.data.statistics.pending : 0;
                            jQuery('#dashboard-pending-queue').text(pending);
                        });
                    }
                } else {
                    console.debug('[Nexus Translator]: Failed to load dashboard stats');
                }

                if (callback) callback();
            });
        },

        /**
         * Display dashboard stats
         */
        displayDashboardStats: function(statsData) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('displayDashboardStats')) return;
            var $ = jQuery;

            // Update stat counters
            if (statsData.total_translations !== undefined) {
                $('#nexus-ai-wp-stat-total-translations').text(statsData.total_translations);
            }

            if (statsData.translations_this_period !== undefined) {
                $('#nexus-ai-wp-stat-period-translations').text(statsData.translations_this_period);
            }

            if (statsData.active_languages !== undefined) {
                $('#nexus-ai-wp-stat-active-languages').text(statsData.active_languages);
            }

            if (statsData.average_quality !== undefined) {
                $('#nexus-ai-wp-stat-average-quality').text(Math.round(statsData.average_quality) + '%');
            }

            // Update language breakdown
            if (statsData.language_breakdown) {
                var languageNames = NexusAIWPTranslatorCore.getLanguageNames();
                var breakdownHtml = '';

                Object.keys(statsData.language_breakdown).forEach(function(lang) {
                    var count = statsData.language_breakdown[lang];
                    var langName = languageNames[lang] || lang.toUpperCase();

                    breakdownHtml += '<div class="language-stat-item">' +
                        '<span class="language-name">' + langName + '</span>' +
                        '<span class="language-count">' + count + '</span>' +
                        '</div>';
                });

                $('#nexus-ai-wp-language-breakdown').html(breakdownHtml);
            }

            // Update recent activity
            if (statsData.recent_activity) {
                var activityHtml = '';

                statsData.recent_activity.forEach(function(activity) {
                    var timeAgo = NexusAIWPTranslatorCore.formatDate(activity.date);

                    activityHtml += '<div class="activity-item">' +
                        '<div class="activity-description">' + NexusAIWPTranslatorCore.escapeHtml(activity.description) + '</div>' +
                        '<div class="activity-time">' + timeAgo + '</div>' +
                        '</div>';
                });

                $('#nexus-ai-wp-recent-activity').html(activityHtml);
            }
        },

        /**
         * Initialize dashboard actions
         */
        initDashboardActions: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initDashboardActions')) return;
            var $ = jQuery;

            // Handle cleanup orphaned translations
            $(document).on('click', '#nexus-ai-wp-cleanup-orphaned', function() {
                if (!confirm('Are you sure you want to cleanup orphaned translations? This action cannot be undone.')) {
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
                        var cleanedCount = response.data && response.data.cleaned_count ? response.data.cleaned_count : 0;
                        NexusAIWPTranslatorCore.showGlobalNotice('success', 'Cleaned up ' + cleanedCount + ' orphaned translations!');

                        // Refresh stats
                        NexusAIWPTranslatorDashboard.loadDashboardStats();
                    } else {
                        var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                        alert('Failed to cleanup orphaned translations: ' + errorMsg);
                    }
                })
                .fail(function() {
                    alert('Network error occurred while cleaning up orphaned translations.');
                })
                .always(function() {
                    button.prop('disabled', false).text('Cleanup Orphaned');
                });
            });

            // Handle export data
            $(document).on('click', '#nexus-ai-wp-export-data', function() {
                var button = $(this);
                button.prop('disabled', true).text('Exporting...');

                $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                    action: 'nexus_ai_wp_export_data',
                    nonce: nexus_ai_wp_translator_ajax.nonce
                })
                .done(function(response) {
                    if (response.success && response.data && response.data.download_url) {
                        // Trigger download
                        var a = document.createElement('a');
                        a.href = response.data.download_url;
                        a.download = response.data.filename || 'nexus-translator-export.json';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);

                        NexusAIWPTranslatorCore.showGlobalNotice('success', 'Data exported successfully!');
                    } else {
                        var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                        alert('Failed to export data: ' + errorMsg);
                    }
                })
                .fail(function() {
                    alert('Network error occurred while exporting data.');
                })
                .always(function() {
                    button.prop('disabled', false).text('Export Data');
                });
            });
        },

        /**
         * Cleanup on page unload
         */
        cleanup: function() {
            if (this.statsRefreshInterval) {
                clearInterval(this.statsRefreshInterval);
                this.statsRefreshInterval = null;
            }
        },

        /**
         * Load dashboard overview
         */
        loadDashboardOverview: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('loadDashboardOverview')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Loading dashboard overview');

            // Check API status
            this.checkApiStatus();

            // Check Anthropic service status
            this.checkAnthropicStatus();
        },

        /**
         * Check API status using existing API test functionality
         */
        checkApiStatus: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('checkApiStatus')) return;
            var $ = jQuery;

            var statusElement = $('#api-key-status .status-value');
            var iconElement = $('#api-key-status .status-icon');

            // SECURITY: Don't use API key from JavaScript, let server handle it
            var apiKey = $('#nexus_ai_wp_translator_api_key').val() || '';

            if (!apiKey) {
                statusElement.removeClass('status-success status-error').addClass('status-error')
                    .text('Not Configured');
                iconElement.text('❌');
                return;
            }

            statusElement.text('Testing...');
            iconElement.text('🔄');

            // Use existing API test functionality
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_test_api',
                api_key: apiKey,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    statusElement.removeClass('status-error').addClass('status-success')
                        .text('Validated');
                    iconElement.text('✅');
                } else {
                    statusElement.removeClass('status-success').addClass('status-error')
                        .text('Connection Failed');
                    iconElement.text('❌');
                }
            })
            .fail(function() {
                statusElement.removeClass('status-success').addClass('status-error')
                    .text('Test Failed');
                iconElement.text('❌');
            });
        },

        /**
         * Check Anthropic service status
         */
        checkAnthropicStatus: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('checkAnthropicStatus')) return;
            var $ = jQuery;

            var statusElement = $('#anthropic-service-status .status-value');
            var iconElement = $('#anthropic-service-status .status-icon');

            // Retrieve live status from Anthropic Statuspage via server proxy
            statusElement.text('Checking...');
            iconElement.text('🔄');
            jQuery.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_get_anthropic_status',
                nonce: nexus_ai_wp_translator_ajax.nonce
            }).done(function(resp){
                if (resp && resp.success && resp.data) {
                    var level = resp.data.level || 'warning';
                    var label = resp.data.label || 'Unknown';
                    statusElement.removeClass('status-success status-warning status-error')
                        .addClass('status-' + level)
                        .text(label);
                    iconElement.text(level === 'success' ? '✅' : (level === 'warning' ? '⚠️' : '❌'));
                } else {
                    statusElement.removeClass('status-success').addClass('status-error').text('Unavailable');
                    iconElement.text('❌');
                }
            }).fail(function(){
                statusElement.removeClass('status-success').addClass('status-error').text('Unavailable');
                iconElement.text('❌');
            });
        },

        /**
         * Load logs data
         */
        loadLogsData: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('loadLogsData')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Loading logs data');

            var tbody = $('#logs-tbody');
            tbody.html('<tr><td colspan="7" class="nexus-ai-wp-loading">Loading logs...</td></tr>');

            // Load logs via AJAX
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_get_logs',
                nonce: nexus_ai_wp_translator_ajax.nonce,
                per_page: 20,
                page: 1
            })
            .done(function(response) {
                if (response.success && response.data.logs) {
                    var logsHtml = '';
                    response.data.logs.forEach(function(log) {
                        logsHtml += '<tr>' +
                            '<td><strong>' + log.created_at + '</strong></td>' +
                            '<td>' + (log.post_title || 'Deleted Post (ID: ' + log.post_id + ')') + '</td>' +
                            '<td>' + log.action + '</td>' +
                            '<td><span class="status-' + log.status + '">' + log.status.charAt(0).toUpperCase() + log.status.slice(1) + '</span></td>' +
                            '<td>' + (log.message.length > 100 ? log.message.substring(0, 100) + '...' : log.message) + '</td>' +
                            '<td>' + (log.api_calls_count || 0) + '</td>' +
                            '<td>' + (log.processing_time > 0 ? log.processing_time.toFixed(2) + 's' : '-') + '</td>' +
                        '</tr>';
                    });
                    tbody.html(logsHtml);
                } else {
                    tbody.html('<tr><td colspan="7">No logs found.</td></tr>');
                }
            })
            .fail(function() {
                tbody.html('<tr><td colspan="7" class="error">Failed to load logs.</td></tr>');
            });
        },

        /**
         * Enhance quality displays in post lists
         */
        enhanceQualityDisplays: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('enhanceQualityDisplays')) return;
            var $ = jQuery;

            // Find any old-style quality displays and enhance them
            $('.nexus-ai-wp-quality-score').each(function() {
                var $this = $(this);
                var postId = $this.data('post-id');
                var scoreText = $this.text();
                var score = parseInt(scoreText.replace('%', ''));

                if (!isNaN(score) && postId) {
                    var $parent = $this.closest('.nexus-ai-wp-quality-display');
                    if ($parent.length && !$parent.find('.nexus-ai-wp-quality-grade-letter').length) {
                        // This is an old-style display, enhance it
                        var enhancedHtml = NexusAIWPTranslatorQualityAssessor.createQualityDisplay(score, postId, true);
                        $parent.replaceWith(enhancedHtml);
                    }
                }
            });
        }
    };

    // Cleanup on page unload
    jQuery(window).on('beforeunload', function() {
        NexusAIWPTranslatorDashboard.cleanup();
    });

    // Make dashboard module globally available
    window.NexusAIWPTranslatorDashboard = NexusAIWPTranslatorDashboard;
    console.debug('[Nexus Translator]: NexusAIWPTranslatorDashboard made globally available');

})();
