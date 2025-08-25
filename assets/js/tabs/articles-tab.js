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
         */
        initCheckboxFunctionality: function() {
            // Initialize select all checkbox functionality
            $('.select-all-checkbox').on('change', function() {
                var postType = $(this).attr('id').replace('select-all-', '');
                var isChecked = $(this).is(':checked');
                
                $('.select-post-checkbox[data-post-id]').each(function() {
                    if ($(this).closest('tr').find('td').length > 0) { // Only for visible rows
                        $(this).prop('checked', isChecked);
                    }
                });
            });
            
            // Individual checkbox selection
            $('.select-post-checkbox').on('change', function() {
                this.updateLinkButtonState();
            }.bind(this));
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
