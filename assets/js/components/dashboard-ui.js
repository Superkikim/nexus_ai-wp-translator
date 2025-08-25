/**
 * Dashboard UI Component
 * Handles translation popup and UI interactions
 */

(function($) {
    'use strict';

    // Dashboard UI Component
    window.NexusAIWPTranslatorDashboardUI = {
        
        /**
         * Show language selection popup for translation
         */
        showLanguageSelectionPopup: function(postId, postTitle) {
            // Get data from DOM attributes or global variables
            var targetLanguages = this.getTargetLanguages();
            var languageNames = this.getLanguageNames();
            var strings = this.getStrings();

            // Create popup HTML
            var popupHtml = '<div id="nexus-ai-wp-translate-popup" class="nexus-ai-wp-popup-overlay">' +
                '<div class="nexus-ai-wp-popup-content">' +
                    '<div class="nexus-ai-wp-popup-header">' +
                        '<h3>' + strings.selectLanguages + '</h3>' +
                        '<button type="button" class="nexus-ai-wp-popup-close">&times;</button>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-popup-body">' +
                        '<p><strong>' + postTitle + '</strong></p>' +
                        '<p>' + strings.chooseLanguages + '</p>' +
                        '<div class="nexus-ai-wp-language-selection">';

            // Add language checkboxes
            targetLanguages.forEach(function(langCode) {
                var langName = languageNames[langCode] || langCode.toUpperCase();
                popupHtml += '<label class="nexus-ai-wp-language-option">' +
                    '<input type="checkbox" value="' + langCode + '" class="nexus-ai-wp-target-language"> ' +
                    langName + ' (' + langCode + ')' +
                '</label>';
            });

            popupHtml += '</div>' +
                        '<div class="nexus-ai-wp-throttle-info">' +
                            '<p><small>' + strings.throttleNote + '</small></p>' +
                        '</div>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-popup-footer">' +
                        '<button type="button" class="button" id="nexus-ai-wp-cancel-translate">' + strings.cancel + '</button>' +
                        '<button type="button" class="button button-primary" id="nexus-ai-wp-start-translate">' + strings.startTranslation + '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

            // Add popup to page
            $('body').append(popupHtml);
            $('#nexus-ai-wp-translate-popup').fadeIn(200);

            // Handle popup events
            $('#nexus-ai-wp-cancel-translate, .nexus-ai-wp-popup-close').on('click', function() {
                NexusAIWPTranslatorDashboardUI.closeTranslatePopup();
            });

            $('#nexus-ai-wp-start-translate').on('click', function() {
                NexusAIWPTranslatorDashboardUI.startTranslation(postId, postTitle);
            });

            // Close on background click
            $('#nexus-ai-wp-translate-popup').on('click', function(e) {
                if (e.target === this) {
                    NexusAIWPTranslatorDashboardUI.closeTranslatePopup();
                }
            });
        },

        /**
         * Close translation popup
         */
        closeTranslatePopup: function() {
            $('#nexus-ai-wp-translate-popup').fadeOut(200, function() {
                $(this).remove();
            });
        },

        /**
         * Start translation process
         */
        startTranslation: function(postId, postTitle) {
            var selectedLanguages = [];
            $('.nexus-ai-wp-target-language:checked').each(function() {
                selectedLanguages.push($(this).val());
            });

            var strings = this.getStrings();

            if (selectedLanguages.length === 0) {
                alert(strings.selectAtLeastOne);
                return;
            }

            // Show progress
            $('#nexus-ai-wp-start-translate').prop('disabled', true).text(strings.translating);

            // Start translation
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_translate_post',
                post_id: postId,
                target_languages: selectedLanguages,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    alert(strings.translationCompleted);
                    location.reload();
                } else {
                    alert(strings.translationFailed + ' ' + (response.message || strings.unknownError));
                }
            })
            .fail(function() {
                alert(strings.networkError);
            })
            .always(function() {
                NexusAIWPTranslatorDashboardUI.closeTranslatePopup();
            });
        },

        /**
         * Get target languages from data attribute or global
         */
        getTargetLanguages: function() {
            // Try to get from data attribute first
            var languages = $('.wrap').data('target-languages');
            if (languages) {
                return languages;
            }

            // Fallback to default languages
            return ['es', 'fr', 'de'];
        },

        /**
         * Get language names mapping
         */
        getLanguageNames: function() {
            return {
                'en': 'English',
                'es': 'Spanish',
                'fr': 'French',
                'de': 'German',
                'it': 'Italian',
                'pt': 'Portuguese',
                'ru': 'Russian',
                'ja': 'Japanese',
                'ko': 'Korean',
                'zh': 'Chinese',
                'ar': 'Arabic',
                'hi': 'Hindi',
                'nl': 'Dutch',
                'sv': 'Swedish',
                'da': 'Danish',
                'no': 'Norwegian',
                'fi': 'Finnish',
                'pl': 'Polish',
                'cs': 'Czech',
                'hu': 'Hungarian'
            };
        },

        /**
         * Get localized strings from data attributes or defaults
         */
        getStrings: function() {
            var strings = $('.wrap').data('dashboard-strings');
            if (strings) {
                return strings;
            }

            // Fallback to English defaults
            return {
                selectLanguages: 'Select Languages to Translate',
                chooseLanguages: 'Choose which languages you want to translate this post to:',
                throttleNote: 'Note: Each language requires 2 API calls (title + content). Check your throttle limits in Settings.',
                cancel: 'Cancel',
                startTranslation: 'Start Translation',
                selectAtLeastOne: 'Please select at least one language.',
                translating: 'Translating...',
                translationCompleted: 'Translation completed successfully!',
                translationFailed: 'Translation failed:',
                unknownError: 'Unknown error',
                networkError: 'Network error occurred'
            };
        }
    };

})(jQuery);
