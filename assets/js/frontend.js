/**
 * Claude Translator Frontend JavaScript
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        ClaudeTranslatorFrontend.init();
    });
    
    var ClaudeTranslatorFrontend = {
        
        init: function() {
            this.initLanguageSwitcher();
            this.initLanguageDetection();
            this.handleLanguageChange();
        },
        
        /**
         * Initialize language switcher functionality
         */
        initLanguageSwitcher: function() {
            // Handle dropdown language switcher
            $(document).on('change', '.claude-language-select', function() {
                var selectedLanguage = $(this).val();
                ClaudeTranslatorFrontend.switchLanguage(selectedLanguage);
            });
            
            // Handle list-style language switcher
            $(document).on('click', '.claude-language-list a', function(e) {
                e.preventDefault();
                var selectedLanguage = $(this).data('lang');
                ClaudeTranslatorFrontend.switchLanguage(selectedLanguage);
            });
        },
        
        /**
         * Initialize language detection
         */
        initLanguageDetection: function() {
            // Check if we need to redirect based on browser language
            if (this.shouldRedirectForLanguage()) {
                this.redirectToPreferredLanguage();
            }
        },
        
        /**
         * Handle language change
         */
        handleLanguageChange: function() {
            // Listen for browser language change (if supported)
            if ('onlanguagechange' in window) {
                window.addEventListener('languagechange', function() {
                    ClaudeTranslatorFrontend.detectAndRedirect();
                });
            }
        },
        
        /**
         * Switch to a specific language
         */
        switchLanguage: function(language) {
            // Show loading state
            this.showLoadingState();
            
            // Store language preference
            this.storeLanguagePreference(language);
            
            // Redirect to language-specific URL
            var currentUrl = window.location.href;
            var newUrl = this.buildLanguageUrl(currentUrl, language);
            
            // Add fade effect
            $('body').addClass('claude-content-fade switching');
            
            setTimeout(function() {
                window.location.href = newUrl;
            }, 300);
        },
        
        /**
         * Store language preference via AJAX
         */
        storeLanguagePreference: function(language) {
            $.post(claude_translator.ajax_url, {
                action: 'claude_set_language_preference',
                language: language,
                nonce: claude_translator.nonce
            });
        },
        
        /**
         * Build language-specific URL
         */
        buildLanguageUrl: function(currentUrl, language) {
            var url = new URL(currentUrl);
            
            // Add language parameter
            url.searchParams.set('lang', language);
            
            return url.toString();
        },
        
        /**
         * Show loading state
         */
        showLoadingState: function() {
            $('.claude-language-switcher').addClass('loading');
            
            // Add spinner to dropdown
            $('.claude-language-select').after('<span class="loading-spinner"></span>');
        },
        
        /**
         * Check if we should redirect for language
         */
        shouldRedirectForLanguage: function() {
            // Don't redirect if language is already set in URL
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('lang')) {
                return false;
            }
            
            // Don't redirect if user has already visited (check session storage)
            if (sessionStorage.getItem('claude_translator_visited')) {
                return false;
            }
            
            return true;
        },
        
        /**
         * Redirect to preferred language
         */
        redirectToPreferredLanguage: function() {
            var browserLanguage = this.detectBrowserLanguage();
            var supportedLanguages = this.getSupportedLanguages();
            
            if (browserLanguage && supportedLanguages.includes(browserLanguage)) {
                // Mark as visited
                sessionStorage.setItem('claude_translator_visited', 'true');
                
                // Redirect
                var newUrl = this.buildLanguageUrl(window.location.href, browserLanguage);
                window.location.href = newUrl;
            }
        },
        
        /**
         * Detect browser language
         */
        detectBrowserLanguage: function() {
            var language = navigator.language || navigator.userLanguage;
            
            if (language) {
                // Try full language code first (e.g., en-US)
                if (this.isLanguageSupported(language)) {
                    return language;
                }
                
                // Try just the language part (e.g., en from en-US)
                var shortLang = language.split('-')[0];
                if (this.isLanguageSupported(shortLang)) {
                    return shortLang;
                }
            }
            
            return null;
        },
        
        /**
         * Get supported languages
         */
        getSupportedLanguages: function() {
            var languages = [];
            
            // Get from dropdown options
            $('.claude-language-select option').each(function() {
                var lang = $(this).val();
                if (lang) {
                    languages.push(lang);
                }
            });
            
            // Get from list links
            $('.claude-language-list a').each(function() {
                var lang = $(this).data('lang');
                if (lang) {
                    languages.push(lang);
                }
            });
            
            return languages;
        },
        
        /**
         * Check if language is supported
         */
        isLanguageSupported: function(language) {
            return this.getSupportedLanguages().includes(language);
        },
        
        /**
         * Detect and redirect if needed
         */
        detectAndRedirect: function() {
            if (this.shouldRedirectForLanguage()) {
                this.redirectToPreferredLanguage();
            }
        },
        
        /**
         * Update language switcher state
         */
        updateLanguageSwitcherState: function(currentLanguage) {
            // Update dropdown
            $('.claude-language-select').val(currentLanguage);
            
            // Update list
            $('.claude-language-list li').removeClass('current');
            $('.claude-language-list a[data-lang="' + currentLanguage + '"]').closest('li').addClass('current');
        },
        
        /**
         * Add translation notice
         */
        addTranslationNotice: function(sourceLanguage, targetLanguage) {
            var notice = $('<div class="claude-translation-notice">' +
                '<p>This content has been automatically translated from ' + sourceLanguage + ' to ' + targetLanguage + '. ' +
                '<a href="#" id="claude-view-original">View original</a></p>' +
                '</div>');
            
            $('main, .content, article').first().prepend(notice);
            
            // Handle view original click
            $('#claude-view-original').on('click', function(e) {
                e.preventDefault();
                ClaudeTranslatorFrontend.switchLanguage(sourceLanguage);
            });
        },
        
        /**
         * Initialize accessibility features
         */
        initAccessibility: function() {
            // Add ARIA labels
            $('.claude-language-select').attr('aria-label', 'Select language');
            $('.claude-language-list').attr('role', 'navigation').attr('aria-label', 'Language selection');
            
            // Add keyboard navigation
            $('.claude-language-list a').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).click();
                }
            });
        },
        
        /**
         * Handle mobile-specific functionality
         */
        initMobile: function() {
            if (this.isMobile()) {
                // Prevent zoom on select focus (iOS)
                $('.claude-language-select').css('font-size', '16px');
                
                // Add touch-friendly styling
                $('.claude-language-list a').css('min-height', '44px');
            }
        },
        
        /**
         * Check if device is mobile
         */
        isMobile: function() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        },
        
        /**
         * Initialize RTL support
         */
        initRTL: function() {
            var rtlLanguages = ['ar', 'he', 'fa', 'ur'];
            var currentLanguage = claude_translator.current_language;
            
            if (rtlLanguages.includes(currentLanguage)) {
                $('html').attr('dir', 'rtl');
                $('body').addClass('claude-rtl');
            }
        },
        
        /**
         * Handle cookie consent (GDPR compliance)
         */
        handleCookieConsent: function() {
            // Check if cookies are accepted before storing preferences
            if (this.areCookiesAccepted()) {
                return true;
            }
            
            // Show cookie consent notice if not accepted
            this.showCookieConsent();
            return false;
        },
        
        /**
         * Check if cookies are accepted
         */
        areCookiesAccepted: function() {
            return localStorage.getItem('claude_translator_cookies_accepted') === 'true';
        },
        
        /**
         * Show cookie consent notice
         */
        showCookieConsent: function() {
            var notice = $('<div class="claude-cookie-consent">' +
                '<p>We use cookies to remember your language preference. ' +
                '<button id="claude-accept-cookies">Accept</button> ' +
                '<button id="claude-decline-cookies">Decline</button></p>' +
                '</div>');
            
            $('body').append(notice);
            
            $('#claude-accept-cookies').on('click', function() {
                localStorage.setItem('claude_translator_cookies_accepted', 'true');
                $('.claude-cookie-consent').fadeOut();
            });
            
            $('#claude-decline-cookies').on('click', function() {
                $('.claude-cookie-consent').fadeOut();
            });
        }
    };
    
    // Make ClaudeTranslatorFrontend globally available
    window.ClaudeTranslatorFrontend = ClaudeTranslatorFrontend;
    
    // Initialize additional features
    $(document).ready(function() {
        ClaudeTranslatorFrontend.initAccessibility();
        ClaudeTranslatorFrontend.initMobile();
        ClaudeTranslatorFrontend.initRTL();
    });
    
})(jQuery);