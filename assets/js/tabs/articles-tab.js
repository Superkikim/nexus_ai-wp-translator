/**
 * Articles Tab JavaScript
 * Handles articles tab functionality including checkbox management
 */

(function($) {
    'use strict';

    // Articles Tab Controller
    window.NexusAIWPTranslatorArticlesTab = {
        
        /**
         * Initialize articles tab
         */
        init: function() {
            this.initCheckboxFunctionality();
        },

        /**
         * Initialize checkbox functionality for articles
         * Note: Checkbox event handling is now managed by bulk-actions.js
         */
        initCheckboxFunctionality: function() {
            // Checkbox functionality is handled by bulk-actions.js
            // This prevents event handler conflicts
        },

        /**
         * Update link button state (compatibility function)
         */
        updateLinkButtonState: function() {
            // This function is kept for compatibility but no longer used
            // The bulk actions interface handles all actions now
        },

        /**
         * Link selected items (compatibility function)
         */
        linkSelectedItems: function() {
            // This function is implemented in assets/js/admin.js
            console.debug('[Nexus Translator]: Link selected items - functionality implemented in admin.js');
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if we're on the articles tab or dashboard page
        if ($('#articles-tab').length || $('.nexus-ai-wp-content-tabs').length) {
            NexusAIWPTranslatorArticlesTab.init();
        }
    });

})(jQuery);
