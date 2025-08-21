/**
 * Nexus AI WP Translator Translation Manager
 * Translation interface and functionality
 */

(function() {
    'use strict';

    // Translation manager object
    var NexusAIWPTranslatorTranslationManager = {
        
        /**
         * Initialize translation actions
         */
        init: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initTranslationActions')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Initializing translation actions');
            
            // Handle translate button clicks
            $(document).on('click', '.nexus-ai-wp-translate-btn', function() {
                var button = $(this);
                var postId = button.data('post-id');
                var postTitle = button.data('post-title') || 'Post #' + postId;
                var targetLanguages = button.data('target-languages');
                
                console.debug('[Nexus Translator]: Translate button clicked for post:', postId);
                
                if (!targetLanguages || targetLanguages.length === 0) {
                    alert('No target languages configured. Please check your settings.');
                    return;
                }
                
                // Start translation with progress dialog
                NexusAIWPTranslatorProgressDialog.startTranslationWithProgress(postId, postTitle, targetLanguages, button);
            });
            
            // Handle resume translation button clicks
            $(document).on('click', '.nexus-ai-wp-resume-btn', function() {
                var button = $(this);
                var postId = button.data('post-id');
                var postTitle = button.data('post-title') || 'Post #' + postId;
                var resumableLanguages = button.data('resumable-languages');
                
                console.debug('[Nexus Translator]: Resume button clicked for post:', postId);
                
                if (!resumableLanguages || resumableLanguages.length === 0) {
                    alert('No resumable translations found.');
                    return;
                }
                
                // Start resume translation with progress dialog
                this.startResumeTranslation(postId, postTitle, resumableLanguages, button);
            });
            
            // Handle unlink translation button clicks
            $(document).on('click', '.nexus-ai-wp-unlink-btn', function() {
                var button = $(this);
                var postId = button.data('post-id');
                var translationId = button.data('translation-id');
                
                if (!confirm(nexus_ai_wp_translator_ajax.strings.confirm_unlink)) {
                    return;
                }
                
                console.debug('[Nexus Translator]: Unlink button clicked for post:', postId, 'translation:', translationId);
                
                button.prop('disabled', true).text('Unlinking...');
                
                $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                    action: 'nexus_ai_wp_unlink_translation',
                    post_id: postId,
                    translation_id: translationId,
                    nonce: nexus_ai_wp_translator_ajax.nonce
                })
                .done(function(response) {
                    if (response.success) {
                        // Reload the page to reflect changes
                        location.reload();
                    } else {
                        alert('Failed to unlink translation: ' + (response.data ? response.data.message : 'Unknown error'));
                    }
                })
                .fail(function() {
                    alert('Network error occurred while unlinking translation.');
                })
                .always(function() {
                    button.prop('disabled', false).text('Unlink');
                });
            });
            
            // Check for auto translation on post edit pages
            if ($('#post_ID').length > 0) {
                console.debug('[Nexus Translator]: Post edit page detected, checking for auto translation');
                var self = this;
                setTimeout(function() {
                    self.checkAutoTranslation();
                }, 1000); // Wait 1 second for page to fully load
            }
        },

        /**
         * Check for auto translation status
         */
        checkAutoTranslation: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('checkAutoTranslation')) return;
            var $ = jQuery;

            var postId = NexusAIWPTranslatorCore.getPostId();
            if (!postId) {
                console.debug('[Nexus Translator]: No post ID found for auto translation check');
                return;
            }
            
            console.debug('[Nexus Translator]: Checking auto translation status for post:', postId);
            
            NexusAIWPTranslatorAjax.getTranslationStatus(postId, function(response) {
                console.debug('[Nexus Translator]: Auto translation status response:', response);
                
                if (response.success && response.data && response.data.auto_translation_in_progress) {
                    console.debug('[Nexus Translator]: Auto translation in progress detected');
                    NexusAIWPTranslatorTranslationManager.showAutoTranslationProgress(response.data);
                }
            });
        },

        /**
         * Show auto translation progress popup
         */
        showAutoTranslationProgress: function(data) {
            console.debug('[Nexus Translator]: Showing auto translation progress:', data);
            
            var languageNames = NexusAIWPTranslatorCore.getLanguageNames();
            
            // Create progress popup HTML
            var progressHtml = '<div id="nexus-ai-wp-auto-translation-popup" style="' +
                'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); ' +
                'background: white; border: 1px solid #ccc; border-radius: 8px; ' +
                'padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); z-index: 10000; ' +
                'min-width: 400px; max-width: 600px;">' +
                '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">' +
                '<h3 style="margin: 0; color: #0073aa;">🔄 Auto Translation in Progress</h3>' +
                '<button id="nexus-ai-wp-dismiss-auto-popup" style="background: none; border: none; font-size: 18px; cursor: pointer;">×</button>' +
                '</div>' +
                '<p style="margin-bottom: 15px;">Your post is being automatically translated. You can continue editing while the translation runs in the background.</p>';
            
            // Add target languages info
            if (data.target_languages && data.target_languages.length > 0) {
                progressHtml += '<div style="margin-bottom: 15px;">' +
                    '<strong>Target Languages:</strong><br>';
                
                data.target_languages.forEach(function(lang) {
                    var langName = languageNames[lang] || lang.toUpperCase();
                    var status = data.language_status && data.language_status[lang] ? data.language_status[lang] : 'pending';
                    var statusIcon = status === 'completed' ? '✅' : status === 'in_progress' ? '🔄' : '⏳';
                    
                    progressHtml += '<div style="margin: 5px 0; padding: 5px; background: #f9f9f9; border-radius: 4px;">' +
                        statusIcon + ' ' + langName + ' (' + lang + ') - ' + status.replace('_', ' ') +
                        '</div>';
                });
                
                progressHtml += '</div>';
            }
            
            // Add progress info
            if (data.progress_percentage !== undefined) {
                progressHtml += '<div style="margin-bottom: 15px;">' +
                    '<div style="background: #f0f0f0; border-radius: 10px; height: 20px; overflow: hidden;">' +
                    '<div style="background: #0073aa; height: 100%; width: ' + data.progress_percentage + '%; transition: width 0.3s;"></div>' +
                    '</div>' +
                    '<div style="text-align: center; margin-top: 5px; font-size: 12px; color: #666;">' +
                    Math.round(data.progress_percentage) + '% Complete' +
                    '</div>' +
                    '</div>';
            }
            
            progressHtml += '<div style="text-align: center;">' +
                '<button id="nexus-ai-wp-view-progress" class="button button-primary" style="margin-right: 10px;">View Detailed Progress</button>' +
                '<button id="nexus-ai-wp-dismiss-auto-popup-btn" class="button">Dismiss</button>' +
                '</div>' +
                '</div>';
            
            // Add overlay
            var overlayHtml = '<div id="nexus-ai-wp-auto-translation-overlay" style="' +
                'position: fixed; top: 0; left: 0; width: 100%; height: 100%; ' +
                'background: rgba(0,0,0,0.5); z-index: 9999;"></div>';
            
            // Remove existing popup if any
            $('#nexus-ai-wp-auto-translation-popup, #nexus-ai-wp-auto-translation-overlay').remove();
            
            // Add to page
            $('body').append(overlayHtml + progressHtml);
            
            // Bind events
            $('#nexus-ai-wp-dismiss-auto-popup, #nexus-ai-wp-dismiss-auto-popup-btn, #nexus-ai-wp-auto-translation-overlay').on('click', function() {
                NexusAIWPTranslatorTranslationManager.dismissAutoTranslation();
            });
            
            $('#nexus-ai-wp-view-progress').on('click', function() {
                // Switch to dashboard and show progress
                window.location.href = admin_url + 'admin.php?page=nexus-ai-wp-translator-dashboard#queue-tab';
            });
            
            // Auto-refresh progress every 5 seconds
            var refreshInterval = setInterval(function() {
                var postId = NexusAIWPTranslatorCore.getPostId();
                if (postId) {
                    NexusAIWPTranslatorAjax.getTranslationStatus(postId, function(response) {
                        if (response.success && response.data && response.data.auto_translation_in_progress) {
                            // Update the popup with new data
                            NexusAIWPTranslatorTranslationManager.showAutoTranslationProgress(response.data);
                        } else {
                            // Translation completed or stopped
                            clearInterval(refreshInterval);
                            NexusAIWPTranslatorTranslationManager.dismissAutoTranslation();
                            
                            if (response.success && response.data && response.data.translation_completed) {
                                NexusAIWPTranslatorCore.showGlobalNotice('success', 'Auto translation completed successfully!');
                            }
                        }
                    });
                }
            }, 5000);
            
            // Store interval ID for cleanup
            window.nexusAutoTranslationInterval = refreshInterval;
        },

        /**
         * Dismiss auto translation popup
         */
        dismissAutoTranslation: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('dismissAutoTranslation')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Dismissing auto translation popup');
            
            // Clear refresh interval
            if (window.nexusAutoTranslationInterval) {
                clearInterval(window.nexusAutoTranslationInterval);
                window.nexusAutoTranslationInterval = null;
            }
            
            // Remove popup and overlay
            $('#nexus-ai-wp-auto-translation-popup, #nexus-ai-wp-auto-translation-overlay').fadeOut(300, function() {
                $(this).remove();
            });
        },

        /**
         * Start resume translation with progress dialog
         */
        startResumeTranslation: function(postId, postTitle, resumableLanguages, button) {
            console.debug('[Nexus Translator]: Starting resume translation');

            // Show progress dialog
            NexusAIWPTranslatorProgressDialog.showProgressDialog(postTitle + ' (Resume)', resumableLanguages);

            // Perform resume translation
            this.performResumeTranslation(postId, resumableLanguages, button);
        },

        /**
         * Perform resume translation
         */
        performResumeTranslation: function(postId, resumableLanguages, button) {
            var self = this;

            console.debug('[Nexus Translator]: Starting AJAX resume translation request');

            NexusAIWPTranslatorAjax.resumeTranslation(postId, resumableLanguages, function(response) {
                console.debug('[Nexus Translator]: Resume translation response:', response);

                if (response.success) {
                    // Start progress simulation
                    self.simulateResumeProgress();
                    
                    // Handle success after delay
                    setTimeout(function() {
                        NexusAIWPTranslatorProgressDialog.handleTranslationSuccess(response, button);
                    }, 3000);
                } else {
                    var errorMessage = response.data && response.data.message ? response.data.message : 'Unknown error occurred';
                    NexusAIWPTranslatorProgressDialog.handleTranslationError(errorMessage, button);
                }
            });
        },

        /**
         * Simulate resume progress updates
         */
        simulateResumeProgress: function() {
            var self = this;
            var progress = 25; // Start at 25% since we're resuming
            var steps = ['title', 'content', 'excerpt', 'categories', 'tags', 'creating'];
            var currentStep = 1; // Start from content since title might be done

            var progressInterval = setInterval(function() {
                progress += Math.random() * 15 + 5; // Random increment between 5-20%
                
                if (progress > 100) {
                    progress = 100;
                    clearInterval(progressInterval);
                }
                
                // Update progress bar
                $('#nexus-ai-wp-progress-bar').css('width', progress + '%');
                $('#nexus-ai-wp-progress-percentage').text(Math.round(progress) + '%');
                
                // Update current step
                if (currentStep < steps.length && progress > (currentStep + 1) * 15) {
                    NexusAIWPTranslatorProgressDialog.updateStepStatus(steps[currentStep], 'completed');
                    currentStep++;
                    if (currentStep < steps.length) {
                        NexusAIWPTranslatorProgressDialog.updateStepStatus(steps[currentStep], 'in-progress');
                    }
                }
                
            }, 500);
            
            // Store interval for cleanup
            window.nexusResumeProgressInterval = progressInterval;
        }
    };

    // Make translation manager globally available
    window.NexusAIWPTranslatorTranslationManager = NexusAIWPTranslatorTranslationManager;
    console.debug('[Nexus Translator]: NexusAIWPTranslatorTranslationManager made globally available');

})();
