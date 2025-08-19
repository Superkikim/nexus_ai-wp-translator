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
            <a href="#articles-tab" class="nav-tab nav-tab-active"><?php _e('Articles', 'nexus-ai-wp-translator'); ?></a>
            <a href="#pages-tab" class="nav-tab"><?php _e('Pages', 'nexus-ai-wp-translator'); ?></a>
            <a href="#events-tab" class="nav-tab"><?php _e('Events', 'nexus-ai-wp-translator'); ?></a>
            <a href="#queue-tab" class="nav-tab"><?php _e('Translation Queue', 'nexus-ai-wp-translator'); ?></a>
            <a href="#analytics-tab" class="nav-tab"><?php _e('Analytics', 'nexus-ai-wp-translator'); ?></a>
        </nav>
        
        <!-- Articles Tab -->
        <div id="articles-tab" class="tab-content active">
            <h2><?php _e('Articles to Translate', 'nexus-ai-wp-translator'); ?></h2>

            <!-- Action Buttons Explanation -->
            <div class="nexus-ai-wp-action-explanation">
                <h3><?php _e('Action Buttons Explained', 'nexus-ai-wp-translator'); ?></h3>
                <div class="nexus-ai-wp-action-buttons-info">
                    <div class="nexus-ai-wp-action-info">
                        <span class="nexus-ai-wp-action-button-demo button button-primary"><?php _e('Translate', 'nexus-ai-wp-translator'); ?></span>
                        <div class="nexus-ai-wp-action-description">
                            <strong><?php _e('Immediate Translation', 'nexus-ai-wp-translator'); ?></strong>
                            <p><?php _e('Starts translation immediately in your browser. You can see real-time progress and the translation completes right away. Best for single posts or when you want to monitor the process.', 'nexus-ai-wp-translator'); ?></p>
                        </div>
                    </div>
                    <div class="nexus-ai-wp-action-info">
                        <span class="nexus-ai-wp-action-button-demo button"><?php _e('Queue', 'nexus-ai-wp-translator'); ?></span>
                        <div class="nexus-ai-wp-action-description">
                            <strong><?php _e('Background Translation', 'nexus-ai-wp-translator'); ?></strong>
                            <p><?php _e('Adds the post to the translation queue for background processing. Translations happen automatically via cron jobs. Best for bulk operations or when you want to schedule translations for later.', 'nexus-ai-wp-translator'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="articles-list">
                <?php echo $this->render_posts_list('post'); ?>
            </div>
        </div>
        
        <!-- Add a link button above the table -->
        <script>
        jQuery(document).ready(function($) {
            // Initialize checkbox functionality
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
                updateLinkButtonState();
            });
            
            function updateLinkButtonState() {
                // This function is kept for compatibility but no longer used
                // The bulk actions interface handles all actions now
            }
            
            function linkSelectedItems() {
                // This function is implemented in assets/js/admin.js
                console.log('Link selected items - functionality implemented in admin.js');
            }
        });
        </script>
        
        <!-- Pages Tab -->
        <div id="pages-tab" class="tab-content">
            <h2><?php _e('Pages to Translate', 'nexus-ai-wp-translator'); ?></h2>
            <div id="pages-list">
                <?php echo $this->render_posts_list('page'); ?>
            </div>
        </div>
        
        <!-- Events Tab -->
        <div id="events-tab" class="tab-content">
            <h2><?php _e('Events to Translate', 'nexus-ai-wp-translator'); ?></h2>
            <div id="events-list">
                <?php echo $this->render_posts_list('event'); ?>
            </div>
        </div>

        <!-- Translation Queue Tab -->
        <div id="queue-tab" class="tab-content">
            <h2><?php _e('Translation Queue', 'nexus-ai-wp-translator'); ?></h2>

            <!-- Queue Controls -->
            <div class="nexus-ai-wp-queue-controls">
                <div class="nexus-ai-wp-queue-stats">
                    <div class="queue-stat">
                        <span class="stat-label"><?php _e('Pending:', 'nexus-ai-wp-translator'); ?></span>
                        <span class="stat-value" id="queue-pending-count">-</span>
                    </div>
                    <div class="queue-stat">
                        <span class="stat-label"><?php _e('Processing:', 'nexus-ai-wp-translator'); ?></span>
                        <span class="stat-value" id="queue-processing-count">-</span>
                    </div>
                    <div class="queue-stat">
                        <span class="stat-label"><?php _e('Completed:', 'nexus-ai-wp-translator'); ?></span>
                        <span class="stat-value" id="queue-completed-count">-</span>
                    </div>
                    <div class="queue-stat">
                        <span class="stat-label"><?php _e('Failed:', 'nexus-ai-wp-translator'); ?></span>
                        <span class="stat-value" id="queue-failed-count">-</span>
                    </div>
                </div>

                <div class="nexus-ai-wp-queue-actions">
                    <button type="button" class="button" id="refresh-queue-btn"><?php _e('Refresh', 'nexus-ai-wp-translator'); ?></button>
                    <button type="button" class="button" id="pause-queue-btn"><?php _e('Pause Queue', 'nexus-ai-wp-translator'); ?></button>
                    <button type="button" class="button" id="resume-queue-btn" style="display: none;"><?php _e('Resume Queue', 'nexus-ai-wp-translator'); ?></button>
                </div>
            </div>

            <!-- Queue Filters -->
            <div class="nexus-ai-wp-queue-filters">
                <label for="queue-status-filter"><?php _e('Filter by Status:', 'nexus-ai-wp-translator'); ?></label>
                <select id="queue-status-filter">
                    <option value=""><?php _e('All', 'nexus-ai-wp-translator'); ?></option>
                    <option value="pending"><?php _e('Pending', 'nexus-ai-wp-translator'); ?></option>
                    <option value="processing"><?php _e('Processing', 'nexus-ai-wp-translator'); ?></option>
                    <option value="completed"><?php _e('Completed', 'nexus-ai-wp-translator'); ?></option>
                    <option value="failed"><?php _e('Failed', 'nexus-ai-wp-translator'); ?></option>
                </select>
            </div>

            <!-- Queue Items Table -->
            <div class="nexus-ai-wp-queue-table-container">
                <table class="wp-list-table widefat fixed striped" id="queue-items-table">
                    <thead>
                        <tr>
                            <th><?php _e('Post', 'nexus-ai-wp-translator'); ?></th>
                            <th><?php _e('Languages', 'nexus-ai-wp-translator'); ?></th>
                            <th><?php _e('Priority', 'nexus-ai-wp-translator'); ?></th>
                            <th><?php _e('Status', 'nexus-ai-wp-translator'); ?></th>
                            <th><?php _e('Scheduled', 'nexus-ai-wp-translator'); ?></th>
                            <th><?php _e('Attempts', 'nexus-ai-wp-translator'); ?></th>
                            <th><?php _e('Actions', 'nexus-ai-wp-translator'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="queue-items-tbody">
                        <tr>
                            <td colspan="7" class="nexus-ai-wp-loading"><?php _e('Loading queue items...', 'nexus-ai-wp-translator'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
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
    </div>

    <div class="nexus-ai-wp-translator-dashboard">
        <!-- Statistics Cards -->
        <div class="nexus-ai-wp-stats-cards">
            <div class="nexus-ai-wp-stat-card">
                <h3><?php _e('Total Translations', 'nexus-ai-wp-translator'); ?></h3>
                <div class="stat-number"><?php echo number_format(intval($stats['total'] ?? 0)); ?></div>
            </div>
            
            <div class="nexus-ai-wp-stat-card">
                <h3><?php _e('Recent (7 days)', 'nexus-ai-wp-translator'); ?></h3>
                <div class="stat-number"><?php echo number_format(intval($stats['recent'] ?? 0)); ?></div>
            </div>
            
            <div class="nexus-ai-wp-stat-card">
                <h3><?php _e('Success Rate', 'nexus-ai-wp-translator'); ?></h3>
                <div class="stat-number"><?php echo number_format(floatval($stats['success_rate'] ?? 0), 1); ?>%</div>
            </div>
            
            <div class="nexus-ai-wp-stat-card">
                <h3><?php _e('API Calls (7 days)', 'nexus-ai-wp-translator'); ?></h3>
                <div class="stat-number"><?php echo number_format(intval($stats['api_calls'] ?? 0)); ?></div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="nexus-ai-wp-quick-actions">
            <h2><?php _e('Quick Actions', 'nexus-ai-wp-translator'); ?></h2>
            <div class="action-buttons">
                <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-settings'); ?>" class="button button-primary">
                    <?php _e('Settings', 'nexus-ai-wp-translator'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-logs'); ?>" class="button">
                    <?php _e('View Logs', 'nexus-ai-wp-translator'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-relationships'); ?>" class="button">
                    <?php _e('Manage Relationships', 'nexus-ai-wp-translator'); ?>
                </a>
                <button id="nexus-ai-wp-refresh-stats" class="button">
                    <?php _e('Refresh Stats', 'nexus-ai-wp-translator'); ?>
                </button>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="nexus-ai-wp-recent-activity">
            <h2><?php _e('Recent Translation Activity', 'nexus-ai-wp-translator'); ?></h2>
            
            <?php if ($recent_logs): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Post', 'nexus-ai-wp-translator'); ?></th>
                            <th><?php _e('Action', 'nexus-ai-wp-translator'); ?></th>
                            <th><?php _e('Status', 'nexus-ai-wp-translator'); ?></th>
                            <th><?php _e('Time', 'nexus-ai-wp-translator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td>
                                    <?php if ($log->post_title): ?>
                                        <a href="<?php echo get_edit_post_link($log->post_id); ?>"><?php echo esc_html($log->post_title); ?></a>
                                    <?php else: ?>
                                        <span class="deleted-post"><?php _e('Deleted Post', 'nexus-ai-wp-translator'); ?> (ID: <?php echo $log->post_id; ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($log->action); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($log->status); ?>">
                                        <?php echo esc_html(ucfirst($log->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo human_time_diff(strtotime($log->created_at), current_time('timestamp')) . ' ' . __('ago', 'nexus-ai-wp-translator'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p>
                    <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-logs'); ?>" class="button">
                        <?php _e('View All Logs', 'nexus-ai-wp-translator'); ?>
                    </a>
                </p>
            <?php else: ?>
                <p><?php _e('No translation activity yet.', 'nexus-ai-wp-translator'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- System Status -->
        <div class="nexus-ai-wp-system-status">
            <h2><?php _e('System Status', 'nexus-ai-wp-translator'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><?php _e('API Key Status', 'nexus-ai-wp-translator'); ?></th>
                    <td>
                        <?php if (get_option('nexus_ai_wp_translator_api_key')): ?>
                            <span class="status-success"><?php _e('Configured', 'nexus-ai-wp-translator'); ?></span>
                        <?php else: ?>
                            <span class="status-error"><?php _e('Not Configured', 'nexus-ai-wp-translator'); ?></span>
                            <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-settings'); ?>" class="button button-small">
                                <?php _e('Configure', 'nexus-ai-wp-translator'); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th><?php _e('Auto Translation', 'nexus-ai-wp-translator'); ?></th>
                    <td>
                        <?php if (get_option('nexus_ai_wp_translator_auto_translate', true)): ?>
                            <span class="status-success"><?php _e('Enabled', 'nexus-ai-wp-translator'); ?></span>
                        <?php else: ?>
                            <span class="status-warning"><?php _e('Disabled', 'nexus-ai-wp-translator'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th><?php _e('Throttle Status', 'nexus-ai-wp-translator'); ?></th>
                    <td>
                        <?php
                        $db = Nexus_AI_WP_Translator_Database::get_instance();
                        $throttle_limit = get_option('nexus_ai_wp_translator_throttle_limit', 10);
                        $current_calls = $db->get_throttle_status(60);
                        
                        if ($current_calls < $throttle_limit):
                        ?>
                            <span class="status-success"><?php printf(__('%d/%d calls used', 'nexus-ai-wp-translator'), $current_calls, $throttle_limit); ?></span>
                        <?php else: ?>
                            <span class="status-error"><?php _e('Limit reached', 'nexus-ai-wp-translator'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
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
    });
    
    // Restore active tab
    var activeContentTab = localStorage.getItem('nexus_ai_wp_translator_content_tab');
    if (activeContentTab && $(activeContentTab).length) {
        $('.nexus-ai-wp-content-tabs .nav-tab[href="' + activeContentTab + '"]').click();
    }
    
    // Translate individual post - show language selection popup
    $(document).on('click', '.translate-post-btn', function() {
        var button = $(this);
        var postId = button.data('post-id');
        var postTitle = button.data('post-title');

        NexusAIWPTranslatorDashboard.showLanguageSelectionPopup(postId, postTitle);
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

    // Dashboard-specific translation functions
    var NexusAIWPTranslatorDashboard = {

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
                NexusAIWPTranslatorDashboard.closeTranslatePopup();
            });

            $('#nexus-ai-wp-start-translate').on('click', function() {
                NexusAIWPTranslatorDashboard.startTranslation(postId, postTitle);
            });

            // Close on background click
            $('#nexus-ai-wp-translate-popup').on('click', function(e) {
                if (e.target === this) {
                    NexusAIWPTranslatorDashboard.closeTranslatePopup();
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
                NexusAIWPTranslatorDashboard.closeTranslatePopup();
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
