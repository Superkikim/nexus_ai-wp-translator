/**
 * Nexus AI WP Translator Admin Core
 * Core utilities, global variables, and common functions
 */

(function() {
    'use strict';

    // Debug: Log when script file is loaded
    console.debug('[Nexus Translator]: admin-core.js file loaded');
    console.debug('[Nexus Translator]: Current URL:', window.location.href);
    console.debug('[Nexus Translator]: jQuery available:', typeof jQuery !== 'undefined');

    // Check if jQuery is available
    if (typeof jQuery === 'undefined') {
        console.error('NexusAI Debug: jQuery is not loaded!');
        return; // Exit if jQuery is not available
    } else {
        console.debug('[Nexus Translator]: jQuery is available');
    }

    // Check if our localized variables are available
    if (typeof nexus_ai_wp_translator_ajax === 'undefined') {
        console.error('NexusAI Debug: nexus_ai_wp_translator_ajax is not defined!');
        console.debug('[Nexus Translator]: Available global variables:', Object.keys(window));
    } else {
        // SECURITY: Don't log AJAX variables as they may contain sensitive data
        console.debug('[Nexus Translator]: AJAX variables loaded successfully');
    }

    // Core utilities object
    var NexusAIWPTranslatorCore = {
        initialized: false,
        
        /**
         * Initialize core functionality
         */
        init: function() {
            if (this.initialized) {
                console.debug('[Nexus Translator]: Core already initialized, skipping');
                return;
            }
            this.initialized = true;
            console.debug('[Nexus Translator]: Core initialized successfully');
        },

        /**
         * Show notice in a specific container
         */
        showNotice: function(container, type, message) {
            console.debug('[Nexus Translator]: Showing notice:', type, message);
            var noticeClass = 'notice-' + type;
            container.html('<div class="notice ' + noticeClass + '"><p>' + message + '</p></div>');
        },
        
        /**
         * Show global notice after H1
         */
        showGlobalNotice: function(type, message) {
            // Ensure jQuery is available
            if (typeof jQuery === 'undefined') {
                console.error('NexusAI Debug: jQuery not available in showGlobalNotice');
                return;
            }

            var $ = jQuery; // Ensure $ is available within this method

            console.debug('[Nexus Translator]: Showing global notice:', type, message);
            
            // Remove existing notices
            $('.nexus-ai-wp-notice').remove();
            
            // Create new notice
            var noticeClass = 'notice-' + type;
            var notice = $('<div class="notice ' + noticeClass + ' nexus-ai-wp-notice is-dismissible"><p>' + message + '</p></div>');
            
            // Insert after H1
            $('h1').first().after(notice);
            
            // Auto-dismiss after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(function() {
                    notice.fadeOut();
                }, 5000);
            }
        },
        
        /**
         * Get current post ID
         */
        getPostId: function() {
            // Ensure jQuery is available
            if (typeof jQuery === 'undefined') {
                console.error('NexusAI Debug: jQuery not available in getPostId');
                return null;
            }

            var $ = jQuery; // Ensure $ is available within this method

            var postId = $('#post_ID').val() || $('input[name="post_ID"]').val();
            console.debug('[Nexus Translator]: Current post ID:', postId);
            return postId;
        },

        /**
         * Ensure jQuery is available for a function
         */
        ensureJQuery: function(functionName) {
            if (typeof jQuery === 'undefined') {
                console.error('NexusAI Debug: jQuery not available in ' + functionName);
                return false;
            }
            return true;
        },

        /**
         * Ensure AJAX variables are available
         */
        ensureAjaxVars: function(functionName) {
            if (typeof nexus_ai_wp_translator_ajax === 'undefined') {
                console.error('NexusAI Debug: AJAX variables not available in ' + functionName);
                return false;
            }
            return true;
        },

        /**
         * Ensure both jQuery and AJAX variables are available
         */
        ensureRequirements: function(functionName) {
            return this.ensureJQuery(functionName) && this.ensureAjaxVars(functionName);
        },

        /**
         * Get language names mapping
         */
        getLanguageNames: function() {
            // Comprehensive ISO 639-1 language list matching PHP translation manager
            return {
                'en': 'English',
                'es': 'Spanish',
                'fr': 'French',
                'de': 'German',
                'it': 'Italian',
                'pt': 'Portuguese',
                'ru': 'Russian',
                'zh': 'Chinese',
                'ja': 'Japanese',
                'ar': 'Arabic',
                'hi': 'Hindi',
                'nl': 'Dutch',
                'sv': 'Swedish',
                'da': 'Danish',
                'no': 'Norwegian',
                'fi': 'Finnish',
                'pl': 'Polish',
                'cs': 'Czech',
                'hu': 'Hungarian',
                'ko': 'Korean',
                'tr': 'Turkish',
                'uk': 'Ukrainian',
                'ro': 'Romanian',
                'bg': 'Bulgarian',
                'hr': 'Croatian',
                'sk': 'Slovak',
                'sl': 'Slovenian',
                'et': 'Estonian',
                'lv': 'Latvian',
                'lt': 'Lithuanian',
                'mt': 'Maltese',
                'el': 'Greek',
                'cy': 'Welsh',
                'ga': 'Irish',
                'is': 'Icelandic',
                'mk': 'Macedonian',
                'sq': 'Albanian',
                'sr': 'Serbian',
                'bs': 'Bosnian',
                'he': 'Hebrew',
                'th': 'Thai',
                'vi': 'Vietnamese',
                'id': 'Indonesian',
                'ms': 'Malay',
                'tl': 'Filipino',
                'sw': 'Swahili',
                'am': 'Amharic',
                'bn': 'Bengali',
                'gu': 'Gujarati',
                'kn': 'Kannada',
                'ml': 'Malayalam',
                'mr': 'Marathi',
                'ne': 'Nepali',
                'or': 'Odia',
                'pa': 'Punjabi',
                'si': 'Sinhala',
                'ta': 'Tamil',
                'te': 'Telugu',
                'ur': 'Urdu',
                'my': 'Myanmar',
                'km': 'Khmer',
                'lo': 'Lao',
                'ka': 'Georgian',
                'hy': 'Armenian',
                'az': 'Azerbaijani',
                'kk': 'Kazakh',
                'ky': 'Kyrgyz',
                'mn': 'Mongolian',
                'tg': 'Tajik',
                'tk': 'Turkmen',
                'uz': 'Uzbek',
                'af': 'Afrikaans',
                'zu': 'Zulu',
                'xh': 'Xhosa',
                'yo': 'Yoruba',
                'ig': 'Igbo',
                'ha': 'Hausa',
                'eu': 'Basque',
                'ca': 'Catalan',
                'gl': 'Galician',
                'be': 'Belarusian',
                'fo': 'Faroese',
                'lb': 'Luxembourgish',
                'br': 'Breton',
                'co': 'Corsican',
                'oc': 'Occitan',
                'fy': 'Frisian',
                'gd': 'Scottish Gaelic',
                'kw': 'Cornish',
                'gv': 'Manx'
            };
        },

        /**
         * Format language name
         */
        formatLanguageName: function(langCode) {
            var languageNames = this.getLanguageNames();
            return languageNames[langCode] || langCode.toUpperCase();
        },

        /**
         * Generate unique ID
         */
        generateUniqueId: function(prefix) {
            prefix = prefix || 'nexus';
            return prefix + '_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },

        /**
         * Throttle function
         */
        throttle: function(func, limit) {
            var inThrottle;
            return function() {
                var args = arguments;
                var context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(function() { inThrottle = false; }, limit);
                }
            };
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        /**
         * Format file size
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        /**
         * Format date
         */
        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        }
    };

    // Make core utilities globally available
    window.NexusAIWPTranslatorCore = NexusAIWPTranslatorCore;
    console.debug('[Nexus Translator]: NexusAIWPTranslatorCore made globally available');

})();
