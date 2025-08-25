/**
 * Dashboard Page JavaScript
 * Handles dashboard-specific functionality including tab switching and translation UI
 */

(function($) {
    'use strict';

    // Dashboard Page Controller
    window.NexusAIWPTranslatorDashboardPage = {
        
        /**
         * Initialize dashboard page
         */
        init: function() {
            this.initTabSwitching();
            this.initLogsHandlers();
            this.initTranslationHandlers();
            this.initStatsRefresh();
            this.initBulkActionsForAllTabs();
            this.restoreActiveTab();
        },

        /**
         * Initialize tab switching functionality
         */
        initTabSwitching: function() {
            $('.nexus-ai-wp-content-tabs .nav-tab').on('click', function(e) {
                e.preventDefault();

                var target = $(this).attr('href');

                // Update nav tabs
                $('.nexus-ai-wp-content-tabs .nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');

                // Update tab content
                $('.nexus-ai-wp-content-tabs .tab-content').removeClass('active');
                $(target).addClass('active');

                // Save active tab
                localStorage.setItem('nexus_ai_wp_translator_content_tab', target);

                // Load tab-specific content using dashboard module
                if (window.NexusAIWPTranslatorDashboard && typeof NexusAIWPTranslatorDashboard.loadTabContent === 'function') {
                    NexusAIWPTranslatorDashboard.loadTabContent(target);
                }

                // Ensure bulk actions are initialized for the active tab after content loads
                setTimeout(function() {
                    NexusAIWPTranslatorDashboardPage.ensureBulkActionsForTab(target);
                }, 300);
            });
        },

        /**
         * Restore active tab or default to dashboard
         */
        restoreActiveTab: function() {
            var activeContentTab = localStorage.getItem('nexus_ai_wp_translator_content_tab');
            if (activeContentTab && $(activeContentTab).length) {
                $('.nexus-ai-wp-content-tabs .nav-tab[href="' + activeContentTab + '"]').click();
            } else {
                // Default to dashboard tab
                $('.nexus-ai-wp-content-tabs .nav-tab[href="#dashboard-tab"]').click();
            }
        },

        /**
         * Initialize bulk actions for all content tabs
         * This is called after the page loads to ensure static content has bulk actions
         */
        initBulkActionsForAllTabs: function() {
            if (!window.NexusAIWPTranslatorBulkActions) {
                console.warn('[Nexus Translator]: Bulk actions not available');
                return;
            }

            console.debug('[Nexus Translator]: Dashboard page initializing bulk actions for static content');

            var contentTabs = ['#articles-tab', '#pages-tab', '#events-tab'];

            // Wait for admin-main.js to finish its initialization first
            setTimeout(function() {
                contentTabs.forEach(function(tabId) {
                    if (jQuery(tabId).length > 0) {
                        // Check if this tab has content (static content from PHP)
                        var hasContent = jQuery(tabId).find('.select-post-checkbox').length > 0;
                        if (hasContent) {
                            console.debug('[Nexus Translator]: Found static content in', tabId, '- ensuring bulk actions');
                            if (typeof NexusAIWPTranslatorBulkActions.reinitForContainer === 'function') {
                                NexusAIWPTranslatorBulkActions.reinitForContainer(tabId);
                            }
                        } else {
                            console.debug('[Nexus Translator]: No static content in', tabId, '- will initialize after AJAX load');
                        }
                    }
                });
            }, 500); // Wait for admin-main.js initialization to complete
        },

        /**
         * Ensure bulk actions are properly initialized for a tab
         * @param {string} tabId - The tab ID (e.g., '#articles-tab')
         */
        ensureBulkActionsForTab: function(tabId) {
            // Only initialize bulk actions for content tabs that have posts lists
            var contentTabs = ['#articles-tab', '#pages-tab', '#events-tab'];

            if (contentTabs.includes(tabId) && window.NexusAIWPTranslatorBulkActions) {
                console.debug('[Nexus Translator]: Ensuring bulk actions for tab:', tabId);

                // Use a timeout to ensure the tab content is fully loaded
                setTimeout(function() {
                    if (typeof NexusAIWPTranslatorBulkActions.reinitForContainer === 'function') {
                        NexusAIWPTranslatorBulkActions.reinitForContainer(tabId);
                    } else if (typeof NexusAIWPTranslatorBulkActions.initForContainer === 'function') {
                        NexusAIWPTranslatorBulkActions.initForContainer(tabId);
                    }
                }, 200);
            }
        },

        /**
         * Initialize logs handlers
         */
        initLogsHandlers: function() {
            // Logs refresh functionality
            $(document).on('click', '#refresh-logs', function() {
                if (window.NexusAIWPTranslatorDashboard && typeof NexusAIWPTranslatorDashboard.loadLogsData === 'function') {
                    NexusAIWPTranslatorDashboard.loadLogsData();
                }
            });

            // Logs filter clear functionality
            $(document).on('click', '#clear-logs-filters', function() {
                $('#logs-status-filter').val('');
                $('#logs-action-filter').val('');
                $('#logs-search').val('');
                if (window.NexusAIWPTranslatorDashboard && typeof NexusAIWPTranslatorDashboard.loadLogsData === 'function') {
                    NexusAIWPTranslatorDashboard.loadLogsData();
                }
            });
        },

        /**
         * Initialize translation handlers
         */
        initTranslationHandlers: function() {
            // Translate individual post - show language selection popup
            $(document).on('click', '.translate-post-btn', function() {
                var button = $(this);
                var postId = button.data('post-id');
                var postTitle = button.data('post-title');

                if (window.NexusAIWPTranslatorDashboardUI) {
                    NexusAIWPTranslatorDashboardUI.showLanguageSelectionPopup(postId, postTitle);
                }
            });
        },

        /**
         * Initialize stats refresh functionality
         */
        initStatsRefresh: function() {
            $('#nexus-ai-wp-refresh-stats').on('click', function() {
                var button = $(this);
                var originalText = button.text();
                var refreshingText = button.data('refreshing-text') || 'Refreshing...';
                var errorText = button.data('error-text') || 'Error refreshing stats';
                
                button.prop('disabled', true).text(refreshingText);
                
                $.post(ajaxurl, {
                    action: 'nexus_ai_wp_get_stats',
                    period: '7 days',
                    nonce: nexus_ai_wp_translator_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(errorText);
                    }
                }).always(function() {
                    button.prop('disabled', false).text(originalText);
                });
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        NexusAIWPTranslatorDashboardPage.init();
    });

})(jQuery);
