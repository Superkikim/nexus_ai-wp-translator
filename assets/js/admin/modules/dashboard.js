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
                case '#posts-tab':
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
                case '#stats-tab':
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
        }
    };

    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        NexusAIWPTranslatorDashboard.cleanup();
    });

    // Make dashboard module globally available
    window.NexusAIWPTranslatorDashboard = NexusAIWPTranslatorDashboard;
    console.debug('[Nexus Translator]: NexusAIWPTranslatorDashboard made globally available');

})();
