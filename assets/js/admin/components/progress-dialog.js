/**
 * Nexus AI WP Translator Progress Dialog
 * Progress dialog functionality
 */

(function() {
    'use strict';

    // Progress dialog object
    var NexusAIWPTranslatorProgressDialog = {
        progressInterval: null,
        
        /**
         * Initialize progress dialog event handlers
         */
        init: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initProgressDialog')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Initializing progress dialog');
            
            // Close dialog button
            $(document).on('click', '#nexus-ai-wp-progress-close', function() {
                $('#nexus-ai-wp-progress-dialog').hide();
                
                // Clear any running intervals
                if (NexusAIWPTranslatorProgressDialog.progressInterval) {
                    clearInterval(NexusAIWPTranslatorProgressDialog.progressInterval);
                    NexusAIWPTranslatorProgressDialog.progressInterval = null;
                }
            });
            
            // Success dialog close button
            $(document).on('click', '#nexus-ai-wp-success-close', function() {
                $('#nexus-ai-wp-success-dialog').hide();
            });
            
            // View translations button
            $(document).on('click', '#nexus-ai-wp-view-translations', function() {
                // Reload the page to show new translations
                location.reload();
            });
        },

        /**
         * Start translation with progress dialog
         */
        startTranslationWithProgress: function(postId, postTitle, targetLanguages, button) {
            console.debug('[Nexus Translator]: Starting translation with progress dialog');

            // Show progress dialog
            this.showProgressDialog(postTitle, targetLanguages);

            // Perform translation
            this.performTranslationWithProgress(postId, targetLanguages, button);
        },

        /**
         * Show progress dialog
         */
        showProgressDialog: function(postTitle, targetLanguages) {
            console.debug('[Nexus Translator]: Showing progress dialog');

            // Update dialog content
            $('#nexus-ai-wp-progress-post-title').text(postTitle);
            $('#nexus-ai-wp-progress-target-langs').text(targetLanguages.join(', '));
            
            // Reset progress
            $('#nexus-ai-wp-progress-bar').css('width', '0%');
            $('#nexus-ai-wp-progress-percentage').text('0%');
            
            // Initialize progress steps
            this.initializeProgressSteps(targetLanguages);
            
            // Show dialog
            $('#nexus-ai-wp-progress-dialog').show();
        },

        /**
         * Initialize progress steps
         */
        initializeProgressSteps: function(targetLanguages) {
            var stepsContainer = $('#nexus-ai-wp-progress-steps');
            var steps = [
                {id: 'title', title: 'Translating Title', description: 'Processing post title...'},
                {id: 'content', title: 'Translating Content', description: 'Processing content blocks...'},
                {id: 'excerpt', title: 'Translating Excerpt', description: 'Processing post excerpt...'},
                {id: 'categories', title: 'Processing Categories', description: 'Handling category translations...'},
                {id: 'tags', title: 'Processing Tags', description: 'Handling tag translations...'},
                {id: 'creating', title: 'Creating Posts', description: 'Creating translated posts...'}
            ];
            
            stepsContainer.empty();
            
            steps.forEach(function(step) {
                var stepHtml = '<div class="progress-step" data-step="' + step.id + '">' +
                    '<div class="step-icon">⏳</div>' +
                    '<div class="step-content">' +
                    '<div class="step-title">' + step.title + '</div>' +
                    '<div class="step-description">' + step.description + '</div>' +
                    '</div>' +
                    '</div>';
                stepsContainer.append(stepHtml);
            });
        },

        /**
         * Perform translation with progress updates
         */
        performTranslationWithProgress: function(postId, targetLanguages, button) {
            var self = this;

            console.debug('[Nexus Translator]: Starting AJAX translation request with real-time progress');

            // Generate unique progress ID
            var progressId = NexusAIWPTranslatorCore.generateUniqueId('progress');

            NexusAIWPTranslatorAjax.translatePost(postId, targetLanguages, progressId, function(response) {
                console.debug('[Nexus Translator]: Translation response:', response);

                if (response.success) {
                    // Start real-time progress tracking
                    self.startRealTimeProgressTracking(progressId);
                    
                    // Handle success after completion
                    setTimeout(function() {
                        self.handleTranslationSuccess(response, button);
                    }, 5000);
                } else {
                    var errorMessage = response.data && response.data.message ? response.data.message : 'Unknown error occurred';
                    self.handleTranslationError(errorMessage, button);
                }
            });
        },

        /**
         * Start real-time progress tracking
         */
        startRealTimeProgressTracking: function(progressId) {
            var self = this;
            console.debug('[Nexus Translator]: Starting real-time progress tracking for:', progressId);

            // Poll for progress updates every 500ms
            var progressInterval = setInterval(function() {
                NexusAIWPTranslatorAjax.getTranslationProgress(progressId, function(response) {
                    if (response.success && response.data) {
                        self.updateRealTimeProgress(response.data);
                        
                        // Stop polling if translation is complete
                        if (response.data.status === 'completed' || response.data.status === 'failed') {
                            clearInterval(progressInterval);
                            self.progressInterval = null;
                        }
                    }
                });
            }, 500);
            
            this.progressInterval = progressInterval;
        },

        /**
         * Update progress with real-time data
         */
        updateRealTimeProgress: function(progressData) {
            console.debug('[Nexus Translator]: Updating real-time progress:', progressData);

            // Update progress bar
            var percentage = progressData.progress_percentage || 0;
            $('#nexus-ai-wp-progress-bar').css('width', percentage + '%');
            $('#nexus-ai-wp-progress-percentage').text(Math.round(percentage) + '%');

            // Update current step
            if (progressData.current_step) {
                // Mark previous steps as completed
                $('.progress-step').each(function() {
                    var stepId = $(this).data('step');
                    if (stepId !== progressData.current_step) {
                        $(this).find('.step-icon').text('✅');
                        $(this).addClass('completed');
                    }
                });
                
                // Mark current step as in progress
                var currentStepElement = $('.progress-step[data-step="' + progressData.current_step + '"]');
                currentStepElement.find('.step-icon').text('🔄');
                currentStepElement.addClass('in-progress').removeClass('completed');
            }

            // Update step descriptions with specific progress
            if (progressData.step_details) {
                Object.keys(progressData.step_details).forEach(function(stepId) {
                    var stepElement = $('.progress-step[data-step="' + stepId + '"]');
                    var stepDetail = progressData.step_details[stepId];
                    
                    if (stepDetail.description) {
                        stepElement.find('.step-description').text(stepDetail.description);
                    }
                    
                    if (stepDetail.status === 'completed') {
                        stepElement.find('.step-icon').text('✅');
                        stepElement.addClass('completed').removeClass('in-progress');
                    } else if (stepDetail.status === 'in_progress') {
                        stepElement.find('.step-icon').text('🔄');
                        stepElement.addClass('in-progress').removeClass('completed');
                    }
                });
            }
        },

        /**
         * Simulate progress updates (fallback)
         */
        simulateProgress: function() {
            var self = this;
            var progress = 0;
            var steps = ['title', 'content', 'excerpt', 'categories', 'tags', 'creating'];
            var currentStep = 0;

            var progressInterval = setInterval(function() {
                progress += Math.random() * 10 + 5; // Random increment between 5-15%
                
                if (progress > 100) {
                    progress = 100;
                    clearInterval(progressInterval);
                }
                
                // Update progress bar
                $('#nexus-ai-wp-progress-bar').css('width', progress + '%');
                $('#nexus-ai-wp-progress-percentage').text(Math.round(progress) + '%');
                
                // Update current step
                if (currentStep < steps.length && progress > (currentStep + 1) * 16) {
                    self.updateStepStatus(steps[currentStep], 'completed');
                    currentStep++;
                    if (currentStep < steps.length) {
                        self.updateStepStatus(steps[currentStep], 'in-progress');
                    }
                }
                
            }, 300);
            
            this.progressInterval = progressInterval;
        },

        /**
         * Update step status
         */
        updateStepStatus: function(stepId, status) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('updateStepStatus')) return;
            var $ = jQuery;

            var stepElement = $('.progress-step[data-step="' + stepId + '"]');
            
            if (stepElement.length === 0) {
                console.debug('[Nexus Translator]: Step element not found:', stepId);
                return;
            }
            
            stepElement.removeClass('completed in-progress pending');
            
            switch(status) {
                case 'completed':
                    stepElement.addClass('completed');
                    stepElement.find('.step-icon').text('✅');
                    break;
                case 'in-progress':
                    stepElement.addClass('in-progress');
                    stepElement.find('.step-icon').text('🔄');
                    break;
                case 'pending':
                default:
                    stepElement.addClass('pending');
                    stepElement.find('.step-icon').text('⏳');
                    break;
            }
        },

        /**
         * Handle translation success
         */
        handleTranslationSuccess: function(response, button) {
            console.debug('[Nexus Translator]: Handling translation success');

            // Clear progress interval
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
                this.progressInterval = null;
            }

            // Complete all steps
            $('.progress-step').each(function() {
                $(this).find('.step-icon').text('✅');
                $(this).addClass('completed').removeClass('in-progress pending');
            });

            // Set progress to 100%
            $('#nexus-ai-wp-progress-bar').css('width', '100%');
            $('#nexus-ai-wp-progress-percentage').text('100%');

            // Show success dialog after a short delay
            setTimeout(function() {
                $('#nexus-ai-wp-progress-dialog').hide();
                NexusAIWPTranslatorProgressDialog.showSuccessDialog(response);
            }, 1000);

            // Re-enable button
            if (button) {
                button.prop('disabled', false).text('Translate');
            }
        },

        /**
         * Handle translation error
         */
        handleTranslationError: function(errorMessage, button) {
            console.debug('[Nexus Translator]: Handling translation error:', errorMessage);

            // Clear progress interval
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
                this.progressInterval = null;
            }

            // Hide progress dialog and show error
            $('#nexus-ai-wp-progress-dialog').hide();
            alert('Translation failed: ' + errorMessage);

            // Re-enable button
            if (button) {
                button.prop('disabled', false).text('Translate');
            }
        },

        /**
         * Show success dialog
         */
        showSuccessDialog: function(response) {
            console.debug('[Nexus Translator]: Showing success dialog');

            var successCount = response.success_count || 0;
            var errorCount = response.error_count || 0;
            var totalLanguages = successCount + errorCount;

            $('#nexus-ai-wp-success-count').text(successCount);
            $('#nexus-ai-wp-success-total').text(totalLanguages);

            if (errorCount > 0) {
                $('#nexus-ai-wp-success-errors').text(' (' + errorCount + ' failed)').show();
            } else {
                $('#nexus-ai-wp-success-errors').hide();
            }

            $('#nexus-ai-wp-success-dialog').show();
        }
    };

    // Make progress dialog globally available
    window.NexusAIWPTranslatorProgressDialog = NexusAIWPTranslatorProgressDialog;
    console.debug('[Nexus Translator]: NexusAIWPTranslatorProgressDialog made globally available');

})();
