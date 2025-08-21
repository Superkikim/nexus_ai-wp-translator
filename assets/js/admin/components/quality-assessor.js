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

            // Create comprehensive quality dashboard
            var contentHtml = this.createQualityDashboard(qualityData);

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
                    // Update dialog with new data
                    if (response.data && response.data.quality_data) {
                        NexusAIWPTranslatorQualityAssessor.displayQualityDetailsDialog(response.data.quality_data);
                    } else {
                        // Refresh the dialog with new data
                        NexusAIWPTranslatorQualityAssessor.showQualityDetailsDialog(postId);
                    }

                    // Show success message
                    var successMsg = response.data && response.data.message ? response.data.message : 'Quality reassessed successfully';
                    console.debug('[Nexus Translator]: ' + successMsg);
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
         * Create comprehensive quality dashboard
         */
        createQualityDashboard: function(qualityData) {
            var html = '';

            // Header with overall score and grade
            html += this.createQualityHeader(qualityData);

            // Key metrics overview
            html += this.createMetricsOverview(qualityData);

            // Detailed component scores
            html += this.createComponentScores(qualityData);

            // Content analysis
            html += this.createContentAnalysis(qualityData);

            // Issues and suggestions
            html += this.createIssuesAndSuggestions(qualityData);

            // Assessment metadata
            html += this.createAssessmentMetadata(qualityData);

            return html;
        },

        /**
         * Create quality header section
         */
        createQualityHeader: function(qualityData) {
            var score = qualityData.overall_score || 0;
            var grade = qualityData.grade || this.getQualityGrade(score);
            var level = this.getQualityLevel(score);

            var html = '<div class="nexus-quality-header">';
            html += '<div class="quality-score-circle quality-' + level + '">';
            html += '<div class="score-value">' + Math.round(score) + '<span class="score-percent">%</span></div>';
            html += '<div class="score-grade">' + grade + '</div>';
            html += '</div>';
            html += '<div class="quality-summary">';
            html += '<h3>Translation Quality Assessment</h3>';
            html += '<p class="quality-description">' + this.getQualityDescription(score) + '</p>';
            html += '</div>';
            html += '</div>';

            return html;
        },

        /**
         * Create metrics overview section
         */
        createMetricsOverview: function(qualityData) {
            if (!qualityData.metrics) return '';

            var metrics = qualityData.metrics;
            var html = '<div class="nexus-quality-metrics">';
            html += '<h4><span class="dashicons dashicons-chart-area"></span> Content Metrics</h4>';
            html += '<div class="metrics-grid">';

            // Word count comparison
            if (metrics.original_word_count && metrics.translated_word_count) {
                var wordRatio = ((metrics.translated_word_count / metrics.original_word_count) * 100).toFixed(1);
                html += '<div class="metric-card">';
                html += '<div class="metric-icon">📝</div>';
                html += '<div class="metric-content">';
                html += '<div class="metric-label">Word Count</div>';
                html += '<div class="metric-value">' + metrics.translated_word_count + ' / ' + metrics.original_word_count + '</div>';
                html += '<div class="metric-ratio">(' + wordRatio + '% of original)</div>';
                html += '</div>';
                html += '</div>';
            }

            // Character count comparison
            if (metrics.original_char_count && metrics.translated_char_count) {
                var charRatio = ((metrics.translated_char_count / metrics.original_char_count) * 100).toFixed(1);
                html += '<div class="metric-card">';
                html += '<div class="metric-icon">🔤</div>';
                html += '<div class="metric-content">';
                html += '<div class="metric-label">Characters</div>';
                html += '<div class="metric-value">' + metrics.translated_char_count + ' / ' + metrics.original_char_count + '</div>';
                html += '<div class="metric-ratio">(' + charRatio + '% of original)</div>';
                html += '</div>';
                html += '</div>';
            }

            // HTML structure preservation
            if (metrics.html_tags_original !== undefined && metrics.html_tags_translated !== undefined) {
                var tagMatch = metrics.html_tags_original === metrics.html_tags_translated;
                html += '<div class="metric-card">';
                html += '<div class="metric-icon">' + (tagMatch ? '✅' : '⚠️') + '</div>';
                html += '<div class="metric-content">';
                html += '<div class="metric-label">HTML Structure</div>';
                html += '<div class="metric-value">' + metrics.html_tags_translated + ' / ' + metrics.html_tags_original + ' tags</div>';
                html += '<div class="metric-ratio">' + (tagMatch ? 'Preserved' : 'Modified') + '</div>';
                html += '</div>';
                html += '</div>';
            }

            html += '</div></div>';
            return html;
        },

        /**
         * Create component scores section
         */
        createComponentScores: function(qualityData) {
            var html = '<div class="nexus-quality-components">';
            html += '<h4><span class="dashicons dashicons-analytics"></span> Quality Components</h4>';
            html += '<div class="components-grid">';

            var components = [
                { key: 'completeness_score', label: 'Completeness', icon: '📋', description: 'Translation covers all original content' },
                { key: 'consistency_score', label: 'Consistency', icon: '🔄', description: 'Formatting and structure preserved' },
                { key: 'structure_score', label: 'Structure', icon: '🏗️', description: 'Document organization maintained' },
                { key: 'length_score', label: 'Length', icon: '📏', description: 'Appropriate content length' }
            ];

            components.forEach(function(component) {
                var score = qualityData[component.key] || 0;
                var level = NexusAIWPTranslatorQualityAssessor.getQualityLevel(score);

                html += '<div class="component-card quality-' + level + '">';
                html += '<div class="component-header">';
                html += '<span class="component-icon">' + component.icon + '</span>';
                html += '<span class="component-label">' + component.label + '</span>';
                html += '<span class="component-score">' + Math.round(score) + '%</span>';
                html += '</div>';
                html += '<div class="component-bar">';
                html += '<div class="component-fill" style="width: ' + score + '%"></div>';
                html += '</div>';
                html += '<div class="component-description">' + component.description + '</div>';
                html += '</div>';
            });

            html += '</div></div>';
            return html;
        },

        /**
         * Create content analysis section
         */
        createContentAnalysis: function(qualityData) {
            if (!qualityData.metrics) return '';

            var metrics = qualityData.metrics;
            var html = '<div class="nexus-content-analysis">';
            html += '<h4><span class="dashicons dashicons-search"></span> Content Analysis</h4>';
            html += '<div class="analysis-grid">';

            // Length analysis
            if (metrics.original_word_count && metrics.translated_word_count) {
                var lengthDiff = metrics.translated_word_count - metrics.original_word_count;
                var lengthPercent = ((lengthDiff / metrics.original_word_count) * 100).toFixed(1);
                var lengthStatus = Math.abs(lengthPercent) < 20 ? 'good' : (Math.abs(lengthPercent) < 40 ? 'warning' : 'error');

                html += '<div class="analysis-item analysis-' + lengthStatus + '">';
                html += '<div class="analysis-icon">' + (lengthStatus === 'good' ? '✅' : (lengthStatus === 'warning' ? '⚠️' : '❌')) + '</div>';
                html += '<div class="analysis-content">';
                html += '<div class="analysis-title">Length Variation</div>';
                html += '<div class="analysis-value">' + (lengthDiff > 0 ? '+' : '') + lengthDiff + ' words (' + (lengthPercent > 0 ? '+' : '') + lengthPercent + '%)</div>';
                html += '<div class="analysis-note">' + this.getLengthAnalysisNote(lengthPercent) + '</div>';
                html += '</div>';
                html += '</div>';
            }

            // Structure analysis
            var structureScore = qualityData.structure_score || 0;
            var structureStatus = structureScore >= 90 ? 'good' : (structureScore >= 70 ? 'warning' : 'error');
            html += '<div class="analysis-item analysis-' + structureStatus + '">';
            html += '<div class="analysis-icon">' + (structureStatus === 'good' ? '🏗️' : (structureStatus === 'warning' ? '⚠️' : '🔧')) + '</div>';
            html += '<div class="analysis-content">';
            html += '<div class="analysis-title">Structure Integrity</div>';
            html += '<div class="analysis-value">' + Math.round(structureScore) + '% preserved</div>';
            html += '<div class="analysis-note">' + this.getStructureAnalysisNote(structureScore) + '</div>';
            html += '</div>';
            html += '</div>';

            html += '</div></div>';
            return html;
        },

        /**
         * Create issues and suggestions section
         */
        createIssuesAndSuggestions: function(qualityData) {
            var html = '';

            // Issues section
            if (qualityData.issues && qualityData.issues.length > 0) {
                html += '<div class="nexus-quality-issues">';
                html += '<h4><span class="dashicons dashicons-warning"></span> Issues Found (' + qualityData.issues.length + ')</h4>';
                html += '<div class="issues-list">';

                qualityData.issues.forEach(function(issue, index) {
                    var severity = issue.severity || 'medium';
                    var type = issue.type || 'General';
                    var description = issue.description || issue;

                    html += '<div class="issue-item issue-' + severity + '">';
                    html += '<div class="issue-header">';
                    html += '<span class="issue-severity-badge severity-' + severity + '">' + severity.toUpperCase() + '</span>';
                    html += '<span class="issue-type">' + NexusAIWPTranslatorCore.escapeHtml(type) + '</span>';
                    html += '</div>';
                    html += '<div class="issue-description">' + NexusAIWPTranslatorCore.escapeHtml(description) + '</div>';
                    html += '</div>';
                });

                html += '</div></div>';
            }

            // Suggestions section
            if (qualityData.suggestions && qualityData.suggestions.length > 0) {
                html += '<div class="nexus-quality-suggestions">';
                html += '<h4><span class="dashicons dashicons-lightbulb"></span> Improvement Suggestions (' + qualityData.suggestions.length + ')</h4>';
                html += '<div class="suggestions-list">';

                qualityData.suggestions.forEach(function(suggestion, index) {
                    html += '<div class="suggestion-item">';
                    html += '<div class="suggestion-icon">💡</div>';
                    html += '<div class="suggestion-text">' + NexusAIWPTranslatorCore.escapeHtml(suggestion) + '</div>';
                    html += '</div>';
                });

                html += '</div></div>';
            }

            return html;
        },

        /**
         * Create assessment metadata section
         */
        createAssessmentMetadata: function(qualityData) {
            var html = '<div class="nexus-quality-metadata">';
            html += '<h4><span class="dashicons dashicons-info"></span> Assessment Details</h4>';
            html += '<div class="metadata-grid">';

            // Assessment date
            if (qualityData.assessment_date || qualityData.metrics?.assessment_timestamp) {
                var date = qualityData.assessment_date || new Date(qualityData.metrics.assessment_timestamp * 1000);
                html += '<div class="metadata-item">';
                html += '<span class="metadata-label">Assessed:</span>';
                html += '<span class="metadata-value">' + NexusAIWPTranslatorCore.formatDate(date) + '</span>';
                html += '</div>';
            }

            // Post ID
            if (qualityData.post_id) {
                html += '<div class="metadata-item">';
                html += '<span class="metadata-label">Post ID:</span>';
                html += '<span class="metadata-value">#' + qualityData.post_id + '</span>';
                html += '</div>';
            }

            // Assessment version
            html += '<div class="metadata-item">';
            html += '<span class="metadata-label">Assessment Version:</span>';
            html += '<span class="metadata-value">v2.0</span>';
            html += '</div>';

            html += '</div></div>';
            return html;
        },

        /**
         * Get quality description based on score
         */
        getQualityDescription: function(score) {
            if (score >= 95) return 'Exceptional translation quality with minimal issues';
            if (score >= 90) return 'Excellent translation quality with minor improvements possible';
            if (score >= 80) return 'Good translation quality with some areas for improvement';
            if (score >= 70) return 'Acceptable translation quality with several issues to address';
            if (score >= 60) return 'Below average translation quality requiring significant improvements';
            if (score >= 40) return 'Poor translation quality with major issues that need attention';
            return 'Very poor translation quality requiring complete review and revision';
        },

        /**
         * Get length analysis note
         */
        getLengthAnalysisNote: function(lengthPercent) {
            var absPercent = Math.abs(lengthPercent);
            if (absPercent < 10) return 'Length is very close to original - excellent';
            if (absPercent < 20) return 'Length variation is within acceptable range';
            if (absPercent < 40) return 'Noticeable length difference - review recommended';
            return 'Significant length difference - may indicate translation issues';
        },

        /**
         * Get structure analysis note
         */
        getStructureAnalysisNote: function(structureScore) {
            if (structureScore >= 95) return 'Perfect structure preservation';
            if (structureScore >= 90) return 'Excellent structure preservation';
            if (structureScore >= 80) return 'Good structure preservation with minor differences';
            if (structureScore >= 70) return 'Acceptable structure with some modifications';
            return 'Structure significantly altered - review required';
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
