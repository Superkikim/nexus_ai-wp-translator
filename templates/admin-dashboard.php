<?php
/**
 * Admin Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Nexus AI WP Translator Dashboard', 'nexus-ai-wp-translator'); ?></h1>
    
    <!-- Content Management Tabs -->
    <div class="nexus-ai-wp-content-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#dashboard-tab" class="nav-tab nav-tab-active"><?php _e('Dashboard', 'nexus-ai-wp-translator'); ?></a>
            <a href="#articles-tab" class="nav-tab"><?php _e('Articles', 'nexus-ai-wp-translator'); ?></a>
            <a href="#pages-tab" class="nav-tab"><?php _e('Pages', 'nexus-ai-wp-translator'); ?></a>
            <a href="#events-tab" class="nav-tab"><?php _e('Events', 'nexus-ai-wp-translator'); ?></a>
            <a href="#queue-tab" class="nav-tab"><?php _e('Translation Queue', 'nexus-ai-wp-translator'); ?></a>
            <a href="#logs-tab" class="nav-tab"><?php _e('Logs', 'nexus-ai-wp-translator'); ?></a>
        </nav>

        <!-- Dashboard Tab -->
        <div id="dashboard-tab" class="tab-content active">
            <?php include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/tabs/dashboard-overview.php'; ?>
        </div>

        <!-- Articles Tab -->
        <div id="articles-tab" class="tab-content">
            <?php include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/tabs/articles-management.php'; ?>
        </div>

        <!-- Pages Tab -->
        <div id="pages-tab" class="tab-content">
            <?php include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/tabs/pages-management.php'; ?>
        </div>

        <!-- Events Tab -->
        <div id="events-tab" class="tab-content">
            <?php include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/tabs/events-management.php'; ?>
        </div>

        <!-- Translation Queue Tab -->
        <div id="queue-tab" class="tab-content">
            <?php include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/tabs/queue-status.php'; ?>
        </div>

        <!-- Analytics Tab -->
        <div id="analytics-tab" class="tab-content">
            <h2><?php _e('Translation Analytics', 'nexus-ai-wp-translator'); ?></h2>

            <!-- Analytics Controls -->
            <div class="nexus-ai-wp-analytics-controls">
                <div class="nexus-ai-wp-analytics-filters">
                    <label for="analytics-period"><?php _e('Time Period:', 'nexus-ai-wp-translator'); ?></label>
                    <select id="analytics-period">
                        <option value="7_days"><?php _e('Last 7 Days', 'nexus-ai-wp-translator'); ?></option>
                        <option value="30_days" selected><?php _e('Last 30 Days', 'nexus-ai-wp-translator'); ?></option>
                        <option value="90_days"><?php _e('Last 90 Days', 'nexus-ai-wp-translator'); ?></option>
                        <option value="1_year"><?php _e('Last Year', 'nexus-ai-wp-translator'); ?></option>
                    </select>

                    <button type="button" class="button" id="refresh-analytics-btn"><?php _e('Refresh', 'nexus-ai-wp-translator'); ?></button>
                    <button type="button" class="button" id="export-analytics-btn"><?php _e('Export', 'nexus-ai-wp-translator'); ?></button>
                </div>
            </div>

            <!-- Analytics Overview Cards -->
            <div class="nexus-ai-wp-analytics-overview">
                <div class="analytics-card">
                    <h3><?php _e('Total Translations', 'nexus-ai-wp-translator'); ?></h3>
                    <div class="analytics-value" id="analytics-total-translations">-</div>
                    <div class="analytics-change" id="analytics-translations-change"></div>
                </div>

                <div class="analytics-card">
                    <h3><?php _e('Success Rate', 'nexus-ai-wp-translator'); ?></h3>
                    <div class="analytics-value" id="analytics-success-rate">-</div>
                    <div class="analytics-change" id="analytics-success-change"></div>
                </div>

                <div class="analytics-card">
                    <h3><?php _e('Average Quality', 'nexus-ai-wp-translator'); ?></h3>
                    <div class="analytics-value" id="analytics-avg-quality">-</div>
                    <div class="analytics-change" id="analytics-quality-change"></div>
                </div>

                <div class="analytics-card">
                    <h3><?php _e('Total Cost', 'nexus-ai-wp-translator'); ?></h3>
                    <div class="analytics-value" id="analytics-total-cost">-</div>
                    <div class="analytics-change" id="analytics-cost-change"></div>
                </div>

                <div class="analytics-card">
                    <h3><?php _e('API Calls', 'nexus-ai-wp-translator'); ?></h3>
                    <div class="analytics-value" id="analytics-api-calls">-</div>
                    <div class="analytics-change" id="analytics-calls-change"></div>
                </div>

                <div class="analytics-card">
                    <h3><?php _e('Tokens Used', 'nexus-ai-wp-translator'); ?></h3>
                    <div class="analytics-value" id="analytics-tokens-used">-</div>
                    <div class="analytics-change" id="analytics-tokens-change"></div>
                </div>
            </div>

            <!-- Analytics Charts -->
            <div class="nexus-ai-wp-analytics-charts">
                <div class="analytics-chart-container">
                    <h3><?php _e('Translation Trends', 'nexus-ai-wp-translator'); ?></h3>
                    <canvas id="translation-trends-chart" width="400" height="200"></canvas>
                </div>

                <div class="analytics-chart-container">
                    <h3><?php _e('Language Distribution', 'nexus-ai-wp-translator'); ?></h3>
                    <canvas id="language-distribution-chart" width="400" height="200"></canvas>
                </div>

                <div class="analytics-chart-container">
                    <h3><?php _e('Quality Metrics', 'nexus-ai-wp-translator'); ?></h3>
                    <canvas id="quality-metrics-chart" width="400" height="200"></canvas>
                </div>

                <div class="analytics-chart-container">
                    <h3><?php _e('Cost Analysis', 'nexus-ai-wp-translator'); ?></h3>
                    <canvas id="cost-analysis-chart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Analytics Tables -->
            <div class="nexus-ai-wp-analytics-tables">
                <div class="analytics-table-container">
                    <h3><?php _e('Top Languages', 'nexus-ai-wp-translator'); ?></h3>
                    <table class="wp-list-table widefat fixed striped" id="top-languages-table">
                        <thead>
                            <tr>
                                <th><?php _e('Language', 'nexus-ai-wp-translator'); ?></th>
                                <th><?php _e('Translations', 'nexus-ai-wp-translator'); ?></th>
                                <th><?php _e('Avg Quality', 'nexus-ai-wp-translator'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="top-languages-tbody">
                            <tr>
                                <td colspan="3" class="nexus-ai-wp-loading"><?php _e('Loading...', 'nexus-ai-wp-translator'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="analytics-table-container">
                    <h3><?php _e('Recent Activity', 'nexus-ai-wp-translator'); ?></h3>
                    <table class="wp-list-table widefat fixed striped" id="recent-activity-table">
                        <thead>
                            <tr>
                                <th><?php _e('Post', 'nexus-ai-wp-translator'); ?></th>
                                <th><?php _e('Language', 'nexus-ai-wp-translator'); ?></th>
                                <th><?php _e('Status', 'nexus-ai-wp-translator'); ?></th>
                                <th><?php _e('Date', 'nexus-ai-wp-translator'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="recent-activity-tbody">
                            <tr>
                                <td colspan="4" class="nexus-ai-wp-loading"><?php _e('Loading...', 'nexus-ai-wp-translator'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Logs Tab -->
        <div id="logs-tab" class="tab-content">
            <?php include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/tabs/logs-viewer.php'; ?>
        </div>
    </div>


</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching for content tabs
    $('.nexus-ai-wp-content-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();

        var target = $(this).attr('href');

        // Update nav tabs
        $('.nexus-ai-wp-content-tabs .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        // Update tab content
        $('.nexus-ai-wp-content-tabs .tab-content').removeClass('active');
        $(target).addClass('active');

        // Save active tab
        localStorage.setItem('nexus_ai_wp_translator_content_tab', target);

        // Load tab-specific content using dashboard module
        if (window.NexusAIWPTranslatorDashboard && typeof NexusAIWPTranslatorDashboard.loadTabContent === 'function') {
            NexusAIWPTranslatorDashboard.loadTabContent(target);
        }
    });

    // Restore active tab or default to dashboard
    var activeContentTab = localStorage.getItem('nexus_ai_wp_translator_content_tab');
    if (activeContentTab && $(activeContentTab).length) {
        $('.nexus-ai-wp-content-tabs .nav-tab[href="' + activeContentTab + '"]').click();
    } else {
        // Default to dashboard tab
        $('.nexus-ai-wp-content-tabs .nav-tab[href="#dashboard-tab"]').click();
    }

    // Add logs refresh functionality
    $(document).on('click', '#refresh-logs', function() {
        if (window.NexusAIWPTranslatorDashboard && typeof NexusAIWPTranslatorDashboard.loadLogsData === 'function') {
            NexusAIWPTranslatorDashboard.loadLogsData();
        }
    });

    // Add logs filter clear functionality
    $(document).on('click', '#clear-logs-filters', function() {
        $('#logs-status-filter').val('');
        $('#logs-action-filter').val('');
        $('#logs-search').val('');
        if (window.NexusAIWPTranslatorDashboard && typeof NexusAIWPTranslatorDashboard.loadLogsData === 'function') {
            NexusAIWPTranslatorDashboard.loadLogsData();
        }
    });
    
    // Translate individual post - show language selection popup
    $(document).on('click', '.translate-post-btn', function() {
        var button = $(this);
        var postId = button.data('post-id');
        var postTitle = button.data('post-title');

        if (window.NexusAIWPTranslatorDashboardUI) {
            NexusAIWPTranslatorDashboardUI.showLanguageSelectionPopup(postId, postTitle);
        }
    });
    
    $('#nexus-ai-wp-refresh-stats').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).text('<?php _e('Refreshing...', 'nexus-ai-wp-translator'); ?>');
        
        $.post(ajaxurl, {
            action: 'nexus_ai_wp_get_stats',
            period: '7 days',
            nonce: nexus_ai_wp_translator_ajax.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('<?php _e('Error refreshing stats', 'nexus-ai-wp-translator'); ?>');
            }
        }).always(function() {
            button.prop('disabled', false).text('<?php _e('Refresh Stats', 'nexus-ai-wp-translator'); ?>');
        });
    });

    // Dashboard-specific translation UI functions (avoid clashing with module global)
    var NexusAIWPTranslatorDashboardUI = {

        showLanguageSelectionPopup: function(postId, postTitle) {
            // Get available target languages
            var targetLanguages = <?php echo json_encode(get_option('nexus_ai_wp_translator_target_languages', array('es', 'fr', 'de'))); ?>;
            var languageNames = {
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

            // Create popup HTML
            var popupHtml = '<div id="nexus-ai-wp-translate-popup" class="nexus-ai-wp-popup-overlay">' +
                '<div class="nexus-ai-wp-popup-content">' +
                    '<div class="nexus-ai-wp-popup-header">' +
                        '<h3><?php _e('Select Languages to Translate', 'nexus-ai-wp-translator'); ?></h3>' +
                        '<button type="button" class="nexus-ai-wp-popup-close">&times;</button>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-popup-body">' +
                        '<p><strong>' + postTitle + '</strong></p>' +
                        '<p><?php _e('Choose which languages you want to translate this post to:', 'nexus-ai-wp-translator'); ?></p>' +
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
                            '<p><small><?php _e('Note: Each language requires 2 API calls (title + content). Check your throttle limits in Settings.', 'nexus-ai-wp-translator'); ?></small></p>' +
                        '</div>' +
                    '</div>' +
                    '<div class="nexus-ai-wp-popup-footer">' +
                        '<button type="button" class="button" id="nexus-ai-wp-cancel-translate"><?php _e('Cancel', 'nexus-ai-wp-translator'); ?></button>' +
                        '<button type="button" class="button button-primary" id="nexus-ai-wp-start-translate"><?php _e('Start Translation', 'nexus-ai-wp-translator'); ?></button>' +
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

        closeTranslatePopup: function() {
            $('#nexus-ai-wp-translate-popup').fadeOut(200, function() {
                $(this).remove();
            });
        },

        startTranslation: function(postId, postTitle) {
            var selectedLanguages = [];
            $('.nexus-ai-wp-target-language:checked').each(function() {
                selectedLanguages.push($(this).val());
            });

            if (selectedLanguages.length === 0) {
                alert('<?php _e('Please select at least one language.', 'nexus-ai-wp-translator'); ?>');
                return;
            }

            // Show progress
            $('#nexus-ai-wp-start-translate').prop('disabled', true).text('<?php _e('Translating...', 'nexus-ai-wp-translator'); ?>');

            // Start translation
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_translate_post',
                post_id: postId,
                target_languages: selectedLanguages,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    alert('<?php _e('Translation completed successfully!', 'nexus-ai-wp-translator'); ?>');
                    location.reload();
                } else {
                    alert('<?php _e('Translation failed:', 'nexus-ai-wp-translator'); ?> ' + (response.message || '<?php _e('Unknown error', 'nexus-ai-wp-translator'); ?>'));
                }
            })
            .fail(function() {
                alert('<?php _e('Network error occurred', 'nexus-ai-wp-translator'); ?>');
            })
            .always(function() {
                NexusAIWPTranslatorDashboardUI.closeTranslatePopup();
            });
        }
    };
});
</script>

<style>
.nexus-ai-wp-popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 100000;
    display: none;
}

.nexus-ai-wp-popup-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 4px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.nexus-ai-wp-popup-header {
    padding: 20px 20px 0;
    border-bottom: 1px solid #ddd;
    position: relative;
}

.nexus-ai-wp-popup-header h3 {
    margin: 0 0 15px 0;
    font-size: 18px;
}

.nexus-ai-wp-popup-close {
    position: absolute;
    top: 15px;
    right: 20px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.nexus-ai-wp-popup-close:hover {
    color: #000;
}

.nexus-ai-wp-popup-body {
    padding: 20px;
}

.nexus-ai-wp-language-selection {
    margin: 15px 0;
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 4px;
}

.nexus-ai-wp-language-option {
    display: block;
    margin: 8px 0;
    cursor: pointer;
}

.nexus-ai-wp-language-option input {
    margin-right: 8px;
}

.nexus-ai-wp-throttle-info {
    background: #f0f8ff;
    border: 1px solid #b3d9ff;
    border-radius: 4px;
    padding: 10px;
    margin-top: 15px;
}

.nexus-ai-wp-popup-footer {
    padding: 15px 20px 20px;
    text-align: right;
    border-top: 1px solid #ddd;
}

.nexus-ai-wp-popup-footer .button {
    margin-left: 10px;
}
</style>

<!-- Translation Progress Dialog -->
<div id="nexus-ai-wp-progress-overlay" class="nexus-ai-wp-progress-overlay">
    <div class="nexus-ai-wp-progress-dialog">
        <div class="nexus-ai-wp-progress-header">
            <h3><?php _e('Translating Content', 'nexus-ai-wp-translator'); ?></h3>
            <button type="button" class="nexus-ai-wp-progress-close" id="nexus-ai-wp-progress-close">&times;</button>
        </div>

        <div class="nexus-ai-wp-progress-body">
            <div class="nexus-ai-wp-progress-info">
                <div class="nexus-ai-wp-progress-post-title" id="nexus-ai-wp-progress-post-title">
                    <?php _e('Preparing translation...', 'nexus-ai-wp-translator'); ?>
                </div>
                <div class="nexus-ai-wp-progress-languages" id="nexus-ai-wp-progress-languages">
                    <?php _e('Target languages: ', 'nexus-ai-wp-translator'); ?><span id="nexus-ai-wp-progress-target-langs"></span>
                </div>
            </div>

            <div class="nexus-ai-wp-progress-bar-container">
                <div class="nexus-ai-wp-progress-bar" id="nexus-ai-wp-progress-bar"></div>
                <div class="nexus-ai-wp-progress-percentage" id="nexus-ai-wp-progress-percentage">0%</div>
            </div>

            <div class="nexus-ai-wp-progress-steps" id="nexus-ai-wp-progress-steps">
                <!-- Progress steps will be dynamically added here -->
            </div>
        </div>

        <div class="nexus-ai-wp-progress-footer">
            <button type="button" class="nexus-ai-wp-progress-cancel" id="nexus-ai-wp-progress-cancel">
                <?php _e('Cancel Translation', 'nexus-ai-wp-translator'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Translation Success Dialog -->
<div id="nexus-ai-wp-success-overlay" class="nexus-ai-wp-progress-overlay">
    <div class="nexus-ai-wp-progress-dialog">
        <div class="nexus-ai-wp-progress-header">
            <h3><?php _e('Translation Complete', 'nexus-ai-wp-translator'); ?></h3>
            <button type="button" class="nexus-ai-wp-progress-close" id="nexus-ai-wp-success-close">&times;</button>
        </div>

        <div class="nexus-ai-wp-progress-success">
            <div class="nexus-ai-wp-progress-success-icon">✓</div>
            <div class="nexus-ai-wp-progress-success-message" id="nexus-ai-wp-success-message">
                <?php _e('Translation completed successfully!', 'nexus-ai-wp-translator'); ?>
            </div>
            <div class="nexus-ai-wp-progress-success-details" id="nexus-ai-wp-success-details">
                <!-- Success details will be added here -->
            </div>
            <div class="nexus-ai-wp-progress-success-actions">
                <button type="button" class="button button-primary" id="nexus-ai-wp-success-view">
                    <?php _e('View Translations', 'nexus-ai-wp-translator'); ?>
                </button>
                <button type="button" class="button" id="nexus-ai-wp-success-close-btn">
                    <?php _e('Close', 'nexus-ai-wp-translator'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Link Dialog Template -->
<div id="nexus-ai-wp-bulk-link-dialog-template" style="display: none;">
    <div class="nexus-ai-wp-bulk-dialog-overlay">
        <div class="nexus-ai-wp-bulk-dialog">
            <div class="nexus-ai-wp-bulk-dialog-header">
                <h3><?php _e('Link Posts', 'nexus-ai-wp-translator'); ?></h3>
                <button type="button" class="nexus-ai-wp-bulk-dialog-close">&times;</button>
            </div>
            <div class="nexus-ai-wp-bulk-dialog-body">
                <p><?php _e('Select the source post that other posts should be linked to:', 'nexus-ai-wp-translator'); ?></p>
                <div class="nexus-ai-wp-selected-items" id="nexus-ai-wp-link-source-selection">
                    <!-- Source selection will be populated here -->
                </div>
                <p><small><?php _e('Note: Posts will be linked as translations of the selected source post. Make sure they have different languages.', 'nexus-ai-wp-translator'); ?></small></p>
            </div>
            <div class="nexus-ai-wp-bulk-dialog-footer">
                <button type="button" class="button nexus-ai-wp-bulk-dialog-cancel"><?php _e('Cancel', 'nexus-ai-wp-translator'); ?></button>
                <button type="button" class="button button-primary nexus-ai-wp-bulk-link-confirm"><?php _e('Link Posts', 'nexus-ai-wp-translator'); ?></button>
            </div>
        </div>
    </div>
</div>
