/**
 * Language Settings Tab JavaScript
 * Handles language grid search and selection functionality
 */

(function($) {
    'use strict';

    // Language Settings Tab Controller
    window.NexusAIWPTranslatorLanguageSettings = {
        
        /**
         * Initialize language settings functionality
         */
        init: function() {
            this.initSearchFunctionality();
            this.initShowAllToggle();
            this.initSelectionCounter();
            this.updateSelectionCount();
        },

        /**
         * Initialize search functionality
         */
        initSearchFunctionality: function() {
            var searchInput = $('#nexus-ai-wp-language-search');
            var clearButton = $('#nexus-ai-wp-clear-search');
            
            // Search functionality
            searchInput.on('input', function() {
                var searchTerm = $(this).val().toLowerCase().trim();
                this.filterLanguages(searchTerm);
            }.bind(this));

            // Clear search
            clearButton.on('click', function() {
                searchInput.val('');
                this.filterLanguages('');
                searchInput.focus();
            }.bind(this));

            // Enter key handling
            searchInput.on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                }
            });
        },

        /**
         * Filter languages based on search term
         */
        filterLanguages: function(searchTerm) {
            var languageItems = $('.nexus-ai-wp-language-item');
            var hasResults = false;

            languageItems.each(function() {
                var item = $(this);
                var languageName = item.data('name');
                var languageCode = item.data('code');
                
                // Check if search term matches name or code
                var nameMatch = languageName.indexOf(searchTerm) !== -1;
                var codeMatch = languageCode.indexOf(searchTerm) !== -1;
                var matches = searchTerm === '' || nameMatch || codeMatch;

                if (matches) {
                    item.removeClass('hidden search-match');
                    if (searchTerm !== '') {
                        item.addClass('search-match');
                        hasResults = true;
                    }
                } else {
                    item.addClass('hidden').removeClass('search-match');
                }
            });

            // Show "no results" message if needed
            this.toggleNoResultsMessage(!hasResults && searchTerm !== '');
        },

        /**
         * Toggle no results message
         */
        toggleNoResultsMessage: function(show) {
            var grid = $('#nexus-ai-wp-language-grid');
            var existingMessage = grid.find('.no-results-message');

            if (show && existingMessage.length === 0) {
                var message = $('<div class="no-results-message" style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #666; font-style: italic;">No languages found matching your search.</div>');
                grid.append(message);
            } else if (!show && existingMessage.length > 0) {
                existingMessage.remove();
            }
        },

        /**
         * Initialize show all toggle
         */
        initShowAllToggle: function() {
            var showAllCheckbox = $('#nexus-ai-wp-show-all-languages');
            
            showAllCheckbox.on('change', function() {
                var isChecked = $(this).is(':checked');
                var extendedItems = $('.nexus-ai-wp-language-item.extended');
                
                if (isChecked) {
                    extendedItems.addClass('show-all');
                } else {
                    extendedItems.removeClass('show-all');
                }

                // Clear search when toggling
                $('#nexus-ai-wp-language-search').val('');
                this.filterLanguages('');
            }.bind(this));
        },

        /**
         * Initialize selection counter
         */
        initSelectionCounter: function() {
            // Update count when checkboxes change
            $(document).on('change', 'input[name="nexus_ai_wp_translator_target_languages[]"]', function() {
                this.updateSelectionCount();
            }.bind(this));
        },

        /**
         * Update selection count display
         */
        updateSelectionCount: function() {
            var selectedCount = $('input[name="nexus_ai_wp_translator_target_languages[]"]:checked').length;
            $('#nexus-ai-wp-selected-count').text(selectedCount);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if we're on the language settings tab
        if ($('.nexus-ai-wp-language-settings').length) {
            NexusAIWPTranslatorLanguageSettings.init();
        }
    });

})(jQuery);
