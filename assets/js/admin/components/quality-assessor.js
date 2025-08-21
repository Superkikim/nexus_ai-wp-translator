/**
 * Nexus AI WP Translator Quality Assessor
 * Quality assessment UI and functionality
 */

(function() {
    'use strict';

    // Quality assessor object
    var NexusAIWPTranslatorQualityAssessor = {
        
        /**
         * Initialize quality assessment interface
         */
        init: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('initQualityAssessmentInterface')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Initializing quality assessment interface');

            // Handle quality score clicks
            $(document).on('click', '.nexus-ai-wp-quality-score', function(e) {
                e.preventDefault();
                var postId = $(this).data('post-id');
                console.debug('[Nexus Translator]: Quality score clicked for post:', postId);

                if (postId) {
                    NexusAIWPTranslatorQualityAssessor.showQualityDetailsDialog(postId);
                }
            });

            // Handle quality details button clicks
            $(document).on('click', '.nexus-ai-wp-quality-details', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var postId = $(this).data('post-id');
                console.debug('[Nexus Translator]: Quality details button clicked for post:', postId);

                if (postId) {
                    NexusAIWPTranslatorQualityAssessor.showQualityDetailsDialog(postId);
                }
            });
        },

        /**
         * Test dialog display (temporary)
         */
        testDialog: function() {
            if (!NexusAIWPTranslatorCore.ensureJQuery('testDialog')) return;
            var $ = jQuery;

            var testHtml = '<div id="nexus-test-dialog" class="nexus-ai-wp-dialog-overlay">' +
                '<div class="nexus-ai-wp-dialog">' +
                '<div class="nexus-ai-wp-dialog-header">' +
                '<h3>Test Dialog</h3>' +
                '<button class="nexus-ai-wp-dialog-close">×</button>' +
                '</div>' +
                '<div class="nexus-ai-wp-dialog-content">' +
                '<p>This is a test dialog to verify CSS is working.</p>' +
                '</div>' +
                '</div>' +
                '</div>';

            $('body').append(testHtml);

            $('#nexus-test-dialog .nexus-ai-wp-dialog-close').on('click', function() {
                $('#nexus-test-dialog').remove();
            });

            console.debug('[Nexus Translator]: Test dialog created');
        },

        /**
         * Show quality details dialog
         */
        showQualityDetailsDialog: function(postId) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('showQualityDetailsDialog')) return;
            var $ = jQuery;

            console.debug('[Nexus Translator]: Showing quality details for post:', postId);

            // Temporary test - remove this line after testing
            // this.testDialog(); return;

            // Show loading dialog first
            var dialogId = 'nexus-ai-wp-quality-dialog';
            var loadingHtml = '<div id="' + dialogId + '" class="nexus-ai-wp-dialog-overlay">' +
                '<div class="nexus-ai-wp-dialog">' +
                '<div class="nexus-ai-wp-dialog-header">' +
                '<h3>Translation Quality Details</h3>' +
                '<button class="nexus-ai-wp-dialog-close">×</button>' +
                '</div>' +
                '<div class="nexus-ai-wp-dialog-content">' +
                '<div class="nexus-ai-wp-loading">Loading quality data...</div>' +
                '</div>' +
                '</div>' +
                '</div>';

            $('#' + dialogId).remove();
            $('body').append(loadingHtml);

            console.debug('[Nexus Translator]: Loading dialog should now be visible');

            // Fetch quality data
            console.debug('[Nexus Translator]: Making AJAX request for quality details');
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_get_quality_details',
                post_id: postId,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                console.debug('[Nexus Translator]: Quality details AJAX response:', response);
                if (response.success && response.data) {
                    NexusAIWPTranslatorQualityAssessor.displayQualityDetailsDialog(response.data);
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Failed to load quality data';
                    console.debug('[Nexus Translator]: Quality details error:', errorMsg);
                    $('#' + dialogId + ' .nexus-ai-wp-dialog-content').html('<div class="notice notice-error"><p>' + errorMsg + '</p></div>');
                }
            })
            .fail(function(xhr, status, error) {
                console.debug('[Nexus Translator]: Quality details AJAX failed:', status, error);
                $('#' + dialogId + ' .nexus-ai-wp-dialog-content').html('<div class="notice notice-error"><p>Network error occurred while loading quality data.</p></div>');
            });

            // Close dialog event
            $(document).on('click', '#' + dialogId + ' .nexus-ai-wp-dialog-close', function() {
                $('#' + dialogId).remove();
            });
        },

        /**
         * Display quality details dialog
         */
        displayQualityDetailsDialog: function(qualityData) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('displayQualityDetailsDialog')) return;
            var $ = jQuery;

            var dialogId = 'nexus-ai-wp-quality-dialog';
            var languageNames = NexusAIWPTranslatorCore.getLanguageNames();

            var contentHtml = '<div class="nexus-ai-wp-quality-overview">' +
                '<h4>Overall Quality Score: <span class="quality-score-badge quality-' + this.getQualityLevel(qualityData.overall_score) + '">' +
                Math.round(qualityData.overall_score) + '%</span></h4>' +
                '</div>';

            if (qualityData.translations && qualityData.translations.length > 0) {
                contentHtml += '<div class="nexus-ai-wp-quality-translations">' +
                    '<h4>Translation Quality by Language</h4>' +
                    '<div class="quality-translations-grid">';

                qualityData.translations.forEach(function(translation) {
                    var langName = languageNames[translation.language] || translation.language.toUpperCase();
                    var qualityLevel = NexusAIWPTranslatorQualityAssessor.getQualityLevel(translation.quality_score);
                    
                    contentHtml += '<div class="quality-translation-item">' +
                        '<div class="quality-translation-header">' +
                        '<span class="language-name">' + langName + ' (' + translation.language + ')</span>' +
                        '<span class="quality-score-badge quality-' + qualityLevel + '">' + Math.round(translation.quality_score) + '%</span>' +
                        '</div>';

                    if (translation.quality_details) {
                        contentHtml += '<div class="quality-details">';
                        
                        if (translation.quality_details.fluency) {
                            contentHtml += '<div class="quality-metric">' +
                                '<span class="metric-label">Fluency:</span>' +
                                '<span class="metric-value">' + Math.round(translation.quality_details.fluency) + '%</span>' +
                                '</div>';
                        }
                        
                        if (translation.quality_details.accuracy) {
                            contentHtml += '<div class="quality-metric">' +
                                '<span class="metric-label">Accuracy:</span>' +
                                '<span class="metric-value">' + Math.round(translation.quality_details.accuracy) + '%</span>' +
                                '</div>';
                        }
                        
                        if (translation.quality_details.consistency) {
                            contentHtml += '<div class="quality-metric">' +
                                '<span class="metric-label">Consistency:</span>' +
                                '<span class="metric-value">' + Math.round(translation.quality_details.consistency) + '%</span>' +
                                '</div>';
                        }
                        
                        contentHtml += '</div>';
                    }

                    if (translation.issues && translation.issues.length > 0) {
                        contentHtml += '<div class="quality-issues">' +
                            '<h5>Issues Found:</h5>' +
                            '<ul>';
                        
                        translation.issues.forEach(function(issue) {
                            var severityClass = 'issue-' + (issue.severity || 'medium');
                            contentHtml += '<li class="quality-issue ' + severityClass + '">' +
                                '<span class="issue-type">' + (issue.type || 'General') + ':</span> ' +
                                '<span class="issue-description">' + NexusAIWPTranslatorCore.escapeHtml(issue.description) + '</span>' +
                                '</li>';
                        });
                        
                        contentHtml += '</ul></div>';
                    }

                    if (translation.suggestions && translation.suggestions.length > 0) {
                        contentHtml += '<div class="quality-suggestions">' +
                            '<h5>Suggestions:</h5>' +
                            '<ul>';
                        
                        translation.suggestions.forEach(function(suggestion) {
                            contentHtml += '<li class="quality-suggestion">' +
                                NexusAIWPTranslatorCore.escapeHtml(suggestion) +
                                '</li>';
                        });
                        
                        contentHtml += '</ul></div>';
                    }

                    contentHtml += '</div>';
                });

                contentHtml += '</div></div>';
            }

            if (qualityData.assessment_date) {
                contentHtml += '<div class="nexus-ai-wp-quality-meta">' +
                    '<p><small>Last assessed: ' + NexusAIWPTranslatorCore.formatDate(qualityData.assessment_date) + '</small></p>' +
                    '</div>';
            }

            // Add action buttons
            contentHtml += '<div class="nexus-ai-wp-quality-actions">' +
                '<button id="reassess-quality" class="button button-secondary">Reassess Quality</button>' +
                '<button id="export-quality-report" class="button">Export Report</button>' +
                '</div>';

            // Update dialog content
            $('#' + dialogId + ' .nexus-ai-wp-dialog-content').html(contentHtml);

            // Bind action events
            $('#reassess-quality').on('click', function() {
                NexusAIWPTranslatorQualityAssessor.reassessQuality(qualityData.post_id);
            });

            $('#export-quality-report').on('click', function() {
                NexusAIWPTranslatorQualityAssessor.exportQualityReport(qualityData);
            });
        },

        /**
         * Get quality level based on score
         */
        getQualityLevel: function(score) {
            if (score >= 90) return 'excellent';
            if (score >= 80) return 'good';
            if (score >= 70) return 'fair';
            if (score >= 60) return 'poor';
            return 'very-poor';
        },

        /**
         * Get quality grade letter based on score
         */
        getQualityGrade: function(score) {
            if (score >= 90) return 'A+';
            if (score >= 85) return 'A';
            if (score >= 80) return 'A-';
            if (score >= 75) return 'B+';
            if (score >= 70) return 'B';
            if (score >= 65) return 'B-';
            if (score >= 60) return 'C+';
            if (score >= 55) return 'C';
            if (score >= 50) return 'C-';
            if (score >= 40) return 'D';
            return 'F';
        },

        /**
         * Create enhanced quality display HTML
         */
        createQualityDisplay: function(score, postId, showDetails) {
            showDetails = showDetails !== false; // Default to true

            var grade = this.getQualityGrade(score);
            var level = this.getQualityLevel(score);

            var html = '<div class="nexus-ai-wp-quality-display nexus-ai-wp-quality-' + level + '">';

            // Grade letter (prominent)
            html += '<span class="nexus-ai-wp-quality-grade-letter">' + grade + '</span>';

            // Score percentage
            html += '<span class="nexus-ai-wp-quality-score" data-post-id="' + postId + '">' + Math.round(score) + '%</span>';

            // Details button/link
            if (showDetails) {
                html += '<button type="button" class="nexus-ai-wp-quality-details button-link" data-post-id="' + postId + '" title="View detailed quality assessment">';
                html += '<span class="dashicons dashicons-chart-bar"></span>';
                html += '</button>';
            }

            html += '</div>';

            return html;
        },

        /**
         * Reassess quality
         */
        reassessQuality: function(postId) {
            if (!NexusAIWPTranslatorCore.ensureJQuery('reassessQuality')) return;
            var $ = jQuery;

            var button = $('#reassess-quality');
            button.prop('disabled', true).text('Reassessing...');

            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_reassess_quality',
                post_id: postId,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    // Refresh the dialog with new data
                    NexusAIWPTranslatorQualityAssessor.showQualityDetailsDialog(postId);
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Failed to reassess quality';
                    alert('Error: ' + errorMsg);
                }
            })
            .fail(function() {
                alert('Network error occurred while reassessing quality.');
            })
            .always(function() {
                button.prop('disabled', false).text('Reassess Quality');
            });
        },

        /**
         * Export quality report
         */
        exportQualityReport: function(qualityData) {
            var reportContent = this.generateQualityReport(qualityData);
            var blob = new Blob([reportContent], { type: 'text/plain' });
            var url = window.URL.createObjectURL(blob);
            
            var a = document.createElement('a');
            a.href = url;
            a.download = 'quality-report-post-' + qualityData.post_id + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        },

        /**
         * Generate quality report text
         */
        generateQualityReport: function(qualityData) {
            var languageNames = NexusAIWPTranslatorCore.getLanguageNames();
            var report = 'Translation Quality Report\n';
            report += '==========================\n\n';
            report += 'Post ID: ' + qualityData.post_id + '\n';
            report += 'Overall Quality Score: ' + Math.round(qualityData.overall_score) + '%\n';
            report += 'Assessment Date: ' + (qualityData.assessment_date || 'Unknown') + '\n\n';

            if (qualityData.translations && qualityData.translations.length > 0) {
                report += 'Translation Details:\n';
                report += '-------------------\n\n';

                qualityData.translations.forEach(function(translation) {
                    var langName = languageNames[translation.language] || translation.language.toUpperCase();
                    report += 'Language: ' + langName + ' (' + translation.language + ')\n';
                    report += 'Quality Score: ' + Math.round(translation.quality_score) + '%\n';

                    if (translation.quality_details) {
                        if (translation.quality_details.fluency) {
                            report += 'Fluency: ' + Math.round(translation.quality_details.fluency) + '%\n';
                        }
                        if (translation.quality_details.accuracy) {
                            report += 'Accuracy: ' + Math.round(translation.quality_details.accuracy) + '%\n';
                        }
                        if (translation.quality_details.consistency) {
                            report += 'Consistency: ' + Math.round(translation.quality_details.consistency) + '%\n';
                        }
                    }

                    if (translation.issues && translation.issues.length > 0) {
                        report += '\nIssues Found:\n';
                        translation.issues.forEach(function(issue) {
                            report += '- ' + (issue.type || 'General') + ': ' + issue.description + '\n';
                        });
                    }

                    if (translation.suggestions && translation.suggestions.length > 0) {
                        report += '\nSuggestions:\n';
                        translation.suggestions.forEach(function(suggestion) {
                            report += '- ' + suggestion + '\n';
                        });
                    }

                    report += '\n';
                });
            }

            return report;
        }
    };

    // Make quality assessor globally available
    window.NexusAIWPTranslatorQualityAssessor = NexusAIWPTranslatorQualityAssessor;
    console.debug('[Nexus Translator]: NexusAIWPTranslatorQualityAssessor made globally available');

})();
