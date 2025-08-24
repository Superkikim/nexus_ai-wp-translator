/**
 * Nexus AI WP Translator Admin Main
 * Main coordinator for all admin modules
 */

(function() {
    'use strict';

    // Main admin object
    var NexusAIWPTranslatorAdmin = {
        initialized: false,
        
        /**
         * Initialize all admin functionality
         */
        init: function() {
            // Ensure jQuery is available
            if (typeof jQuery === 'undefined') {
                console.error('NexusAI Debug: jQuery not available in init function');
                return;
            }

            var $ = jQuery; // Ensure $ is available within this method

            if (this.initialized) {
                console.debug('[Nexus Translator]: Admin already initialized, skipping');
                return;
            }
            this.initialized = true;
            
            console.debug('[Nexus Translator]: Starting admin initialization');
            console.debug('[Nexus Translator]: Admin object initialized successfully');
            
            // Initialize core utilities first
            if (window.NexusAIWPTranslatorCore) {
                NexusAIWPTranslatorCore.init();
            }
            
            // Initialize components based on current page
            this.initializeComponents();
            
            // Initialize modules based on current page
            this.initializeModules();
            
            console.debug('[Nexus Translator]: Admin initialization completed');
        },

        /**
         * Initialize components based on current page
         */
        initializeComponents: function() {
            var currentPage = this.getCurrentPage();
            console.debug('[Nexus Translator]: Initializing components for page:', currentPage);

            // Always initialize these components
            if (window.NexusAIWPTranslatorProgressDialog) {
                NexusAIWPTranslatorProgressDialog.init();
            }

            if (window.NexusAIWPTranslatorTranslationManager) {
                NexusAIWPTranslatorTranslationManager.init();
            }

            if (window.NexusAIWPTranslatorQualityAssessor) {
                NexusAIWPTranslatorQualityAssessor.init();
            }

            // Page-specific component initialization
            switch(currentPage) {
                case 'settings':
                    if (window.NexusAIWPTranslatorSettingsTabs) {
                        NexusAIWPTranslatorSettingsTabs.initAll();
                    }
                    break;

                case 'dashboard':
                    if (window.NexusAIWPTranslatorBulkActions) {
                        NexusAIWPTranslatorBulkActions.init();
                        NexusAIWPTranslatorBulkActions.initInterface();
                    }
                    break;

                case 'posts':
                case 'pages':
                    // Bulk actions removed from WordPress admin pages per user request
                    break;

                case 'post-edit':
                case 'post-new':
                    // Meta box initialization removed per user request
                    break;
            }
        },

        /**
         * Initialize modules based on current page
         */
        initializeModules: function() {
            var currentPage = this.getCurrentPage();
            console.debug('[Nexus Translator]: Initializing modules for page:', currentPage);

            switch(currentPage) {
                case 'dashboard':
                    if (window.NexusAIWPTranslatorDashboard) {
                        NexusAIWPTranslatorDashboard.init();
                    }
                    if (window.NexusAIWPTranslatorQueueManager) {
                        NexusAIWPTranslatorQueueManager.init();
                    }
                    break;

                case 'queue':
                    if (window.NexusAIWPTranslatorQueueManager) {
                        NexusAIWPTranslatorQueueManager.init();
                        // Auto-load queue data
                        setTimeout(function() {
                            NexusAIWPTranslatorQueueManager.loadQueueData();
                            NexusAIWPTranslatorQueueManager.startQueueAutoRefresh();
                        }, 500);
                    }
                    break;
            }
        },

        /**
         * Get current page type
         */
        getCurrentPage: function() {
            var $ = jQuery;
            var url = window.location.href;
            var body = $('body');

            // Check for post edit pages
            if (body.hasClass('post-php') || body.hasClass('post-new-php')) {
                return body.hasClass('post-new-php') ? 'post-new' : 'post-edit';
            }

            // Check for admin pages
            if (url.indexOf('nexus-ai-wp-translator-settings') !== -1) {
                return 'settings';
            }
            
            if (url.indexOf('nexus-ai-wp-translator-dashboard') !== -1) {
                return 'dashboard';
            }
            
            if (url.indexOf('nexus-ai-wp-translator-queue') !== -1) {
                return 'queue';
            }
            
            if (url.indexOf('nexus-ai-wp-translator-logs') !== -1) {
                return 'logs';
            }
            
            if (url.indexOf('nexus-ai-wp-translator-relationships') !== -1) {
                return 'relationships';
            }

            // Check for WordPress admin pages
            if (body.hasClass('edit-php')) {
                return 'posts';
            }

            return 'unknown';
        },

        /**
         * Get current post ID (for post edit pages)
         */
        getPostId: function() {
            return NexusAIWPTranslatorCore.getPostId();
        },

        /**
         * Show global notice
         */
        showGlobalNotice: function(type, message) {
            return NexusAIWPTranslatorCore.showGlobalNotice(type, message);
        },

        /**
         * Check for auto translation on post edit pages
         */
        checkAutoTranslation: function() {
            if (window.NexusAIWPTranslatorTranslationManager) {
                return NexusAIWPTranslatorTranslationManager.checkAutoTranslation();
            }
        },

        /**
         * Legacy method compatibility - initTabSwitching
         */
        initTabSwitching: function() {
            if (window.NexusAIWPTranslatorSettingsTabs) {
                return NexusAIWPTranslatorSettingsTabs.init();
            }
        },

        /**
         * Legacy method compatibility - initApiTesting
         */
        initApiTesting: function() {
            if (window.NexusAIWPTranslatorSettingsTabs) {
                return NexusAIWPTranslatorSettingsTabs.initApiTesting();
            }
        },

        /**
         * Legacy method compatibility - initSettingsSave
         */
        initSettingsSave: function() {
            if (window.NexusAIWPTranslatorSettingsTabs) {
                return NexusAIWPTranslatorSettingsTabs.initSettingsSave();
            }
        },

        /**
         * Legacy method compatibility - initTranslationActions
         */
        initTranslationActions: function() {
            if (window.NexusAIWPTranslatorTranslationManager) {
                return NexusAIWPTranslatorTranslationManager.init();
            }
        },

        /**
         * Legacy method compatibility - initStatusRefresh
         */
        initStatusRefresh: function() {
            if (window.NexusAIWPTranslatorSettingsTabs) {
                return NexusAIWPTranslatorSettingsTabs.initStatusRefresh();
            }
        },

        /**
         * Legacy method compatibility - initBulkActions
         */
        initBulkActions: function() {
            if (window.NexusAIWPTranslatorBulkActions) {
                return NexusAIWPTranslatorBulkActions.init();
            }
        },

        /**
         * Legacy method compatibility - initProgressDialog
         */
        initProgressDialog: function() {
            if (window.NexusAIWPTranslatorProgressDialog) {
                return NexusAIWPTranslatorProgressDialog.init();
            }
        },

        /**
         * Legacy method compatibility - initBulkActionsInterface
         */
        initBulkActionsInterface: function() {
            if (window.NexusAIWPTranslatorBulkActions) {
                return NexusAIWPTranslatorBulkActions.initInterface();
            }
        },

        /**
         * Legacy method compatibility - initQualityAssessmentInterface
         */
        initQualityAssessmentInterface: function() {
            if (window.NexusAIWPTranslatorQualityAssessor) {
                return NexusAIWPTranslatorQualityAssessor.init();
            }
        },

        /**
         * Legacy method compatibility - initTranslationQueueInterface
         */
        initTranslationQueueInterface: function() {
            if (window.NexusAIWPTranslatorQueueManager) {
                return NexusAIWPTranslatorQueueManager.init();
            }
        },

        /**
         * Legacy method compatibility - loadAvailableModels
         */
        loadAvailableModels: function() {
            if (window.NexusAIWPTranslatorAjax) {
                return NexusAIWPTranslatorAjax.loadAvailableModels();
            }
        },

        /**
         * Legacy method compatibility - autoSaveModel
         */
        autoSaveModel: function(model) {
            if (window.NexusAIWPTranslatorAjax) {
                return NexusAIWPTranslatorAjax.autoSaveModel(model);
            }
        },

        /**
         * Legacy method compatibility - performApiTest
         */
        performApiTest: function(button, apiKey, resultDiv) {
            if (window.NexusAIWPTranslatorAjax) {
                return NexusAIWPTranslatorAjax.performApiTest(button, apiKey, resultDiv);
            }
        },

        /**
         * Legacy method compatibility - autoSaveApiKey
         */
        autoSaveApiKey: function(apiKey, callback) {
            if (window.NexusAIWPTranslatorAjax) {
                return NexusAIWPTranslatorAjax.autoSaveApiKey(apiKey, callback);
            }
        }
    };

    // Make NexusAIWPTranslatorAdmin globally available immediately
    window.NexusAIWPTranslatorAdmin = NexusAIWPTranslatorAdmin;
    console.debug('[Nexus Translator]: NexusAIWPTranslatorAdmin made globally available');

    // Initialize when document is ready with proper jQuery scoping
    jQuery(document).ready(function($) {
        'use strict';

        console.debug('[Nexus Translator]: JavaScript function wrapper started');
        console.debug('[Nexus Translator]: Document ready, initializing admin interface');

        // Wait a bit to ensure all scripts are loaded
        setTimeout(function() {
            if (window.NexusAIWPTranslatorAdmin) {
                window.NexusAIWPTranslatorAdmin.init();
            } else {
                console.error('NexusAI Debug: NexusAIWPTranslatorAdmin not available in document ready!');
            }
        }, 100);
    });

})();
