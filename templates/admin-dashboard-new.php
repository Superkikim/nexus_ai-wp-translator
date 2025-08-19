<?php
/**
 * New Admin Dashboard Template - Redesigned Interface
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>
        <?php _e('Nexus AI WP Translator Dashboard', 'nexus-ai-wp-translator'); ?>
        <div class="nexus-ai-wp-header-links">
            <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-settings'); ?>" class="button">
                <?php _e('Settings', 'nexus-ai-wp-translator'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-translation'); ?>" class="button button-primary">
                <?php _e('Translation', 'nexus-ai-wp-translator'); ?>
            </a>
        </div>
    </h1>
    
    <div class="nexus-ai-wp-dashboard-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#dashboard-tab" class="nav-tab nav-tab-active"><?php _e('Dashboard', 'nexus-ai-wp-translator'); ?></a>
            <a href="#analytics-tab" class="nav-tab"><?php _e('Analytics', 'nexus-ai-wp-translator'); ?></a>
            <a href="#queue-tab" class="nav-tab"><?php _e('Queue', 'nexus-ai-wp-translator'); ?></a>
            <a href="#logs-tab" class="nav-tab"><?php _e('Logs', 'nexus-ai-wp-translator'); ?></a>
        </nav>
        
        <!-- Dashboard Tab -->
        <div id="dashboard-tab" class="tab-content active">
            <div class="nexus-ai-wp-dashboard-content">
                
                <!-- Quick Stats Cards -->
                <div class="nexus-ai-wp-stats-cards">
                    <div class="nexus-ai-wp-stat-card">
                        <h3><?php _e('Total Translations', 'nexus-ai-wp-translator'); ?></h3>
                        <div class="stat-number" id="stat-total"><?php echo number_format(intval($stats['total'] ?? 0)); ?></div>
                        <div class="stat-description"><?php _e('All time translations', 'nexus-ai-wp-translator'); ?></div>
                    </div>
                    
                    <div class="nexus-ai-wp-stat-card">
                        <h3><?php _e('Recent (7 days)', 'nexus-ai-wp-translator'); ?></h3>
                        <div class="stat-number" id="stat-recent"><?php echo number_format(intval($stats['recent'] ?? 0)); ?></div>
                        <div class="stat-description"><?php _e('This week', 'nexus-ai-wp-translator'); ?></div>
                    </div>
                    
                    <div class="nexus-ai-wp-stat-card">
                        <h3><?php _e('Success Rate', 'nexus-ai-wp-translator'); ?></h3>
                        <div class="stat-number success-rate" id="stat-success"><?php echo number_format(floatval($stats['success_rate'] ?? 0), 1); ?>%</div>
                        <div class="stat-description"><?php _e('Translation success', 'nexus-ai-wp-translator'); ?></div>
                    </div>
                    
                    <div class="nexus-ai-wp-stat-card">
                        <h3><?php _e('API Calls (7 days)', 'nexus-ai-wp-translator'); ?></h3>
                        <div class="stat-number" id="stat-api-calls"><?php echo number_format(intval($stats['api_calls'] ?? 0)); ?></div>
                        <div class="stat-description"><?php _e('This week', 'nexus-ai-wp-translator'); ?></div>
                    </div>
                </div>
                
                <!-- Two Column Layout -->
                <div class="nexus-ai-wp-dashboard-columns">
                    
                    <!-- Left Column -->
                    <div class="nexus-ai-wp-dashboard-column">
                        
                        <!-- Recent Translation Activity -->
                        <div class="nexus-ai-wp-dashboard-widget">
                            <h2><?php _e('Recent Translation Activity', 'nexus-ai-wp-translator'); ?></h2>
                            
                            <?php if ($recent_logs): ?>
                                <div class="nexus-ai-wp-recent-logs">
                                    <?php foreach (array_slice($recent_logs, 0, 5) as $log): ?>
                                        <div class="nexus-ai-wp-log-item status-<?php echo esc_attr($log->status); ?>">
                                            <div class="log-title">
                                                <?php if ($log->post_title): ?>
                                                    <a href="<?php echo get_edit_post_link($log->post_id); ?>"><?php echo esc_html($log->post_title); ?></a>
                                                <?php else: ?>
                                                    <span class="deleted-post"><?php _e('Deleted Post', 'nexus-ai-wp-translator'); ?> (ID: <?php echo $log->post_id; ?>)</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="log-details">
                                                <span class="log-action"><?php echo esc_html($log->action); ?></span>
                                                <span class="log-status status-<?php echo esc_attr($log->status); ?>"><?php echo esc_html(ucfirst($log->status)); ?></span>
                                                <span class="log-time"><?php echo human_time_diff(strtotime($log->created_at), current_time('timestamp')); ?> <?php _e('ago', 'nexus-ai-wp-translator'); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <p class="nexus-ai-wp-view-all">
                                    <a href="#logs-tab" class="nexus-ai-wp-tab-link"><?php _e('View All Logs', 'nexus-ai-wp-translator'); ?></a>
                                </p>
                            <?php else: ?>
                                <p class="nexus-ai-wp-no-data"><?php _e('No translation activity yet.', 'nexus-ai-wp-translator'); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- System Status -->
                        <div class="nexus-ai-wp-dashboard-widget">
                            <h2><?php _e('System Status', 'nexus-ai-wp-translator'); ?></h2>
                            
                            <div class="nexus-ai-wp-system-checks">
                                <!-- API Key Status -->
                                <div class="system-check">
                                    <div class="check-label"><?php _e('API Configuration', 'nexus-ai-wp-translator'); ?></div>
                                    <div class="check-status">
                                        <?php if (get_option('nexus_ai_wp_translator_api_key')): ?>
                                            <span class="status-success"><?php _e('Configured', 'nexus-ai-wp-translator'); ?></span>
                                        <?php else: ?>
                                            <span class="status-error"><?php _e('Not Configured', 'nexus-ai-wp-translator'); ?></span>
                                            <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-settings'); ?>" class="button button-small">
                                                <?php _e('Configure', 'nexus-ai-wp-translator'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Language Configuration -->
                                <div class="system-check">
                                    <div class="check-label"><?php _e('Languages', 'nexus-ai-wp-translator'); ?></div>
                                    <div class="check-status">
                                        <?php 
                                        $source_lang = get_option('nexus_ai_wp_translator_source_language', '');
                                        $target_langs = get_option('nexus_ai_wp_translator_target_languages', array());
                                        if (!empty($source_lang) && !empty($target_langs)): 
                                        ?>
                                            <span class="status-success">
                                                <?php printf(__('%s → %d languages', 'nexus-ai-wp-translator'), strtoupper($source_lang), count($target_langs)); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-warning"><?php _e('Not Configured', 'nexus-ai-wp-translator'); ?></span>
                                            <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-settings#languages-tab'); ?>" class="button button-small">
                                                <?php _e('Configure', 'nexus-ai-wp-translator'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Rate Limiting Status -->
                                <div class="system-check">
                                    <div class="check-label"><?php _e('Rate Limiting', 'nexus-ai-wp-translator'); ?></div>
                                    <div class="check-status" id="throttle-status">
                                        <?php
                                        $throttle_limit = get_option('nexus_ai_wp_translator_throttle_limit', 10);
                                        $current_calls = $this->db->get_throttle_status(60);
                                        
                                        if ($current_calls < $throttle_limit):
                                        ?>
                                            <span class="status-success"><?php printf(__('%d/%d calls used', 'nexus-ai-wp-translator'), $current_calls, $throttle_limit); ?></span>
                                        <?php else: ?>
                                            <span class="status-error"><?php _e('Limit reached', 'nexus-ai-wp-translator'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="nexus-ai-wp-dashboard-column">
                        
                        <!-- Enabled Languages -->
                        <div class="nexus-ai-wp-dashboard-widget">
                            <h2><?php _e('Language Configuration', 'nexus-ai-wp-translator'); ?></h2>
                            
                            <?php 
                            $source_language = get_option('nexus_ai_wp_translator_source_language', '');
                            $target_languages = get_option('nexus_ai_wp_translator_target_languages', array());
                            $languages = $this->translation_manager->get_available_languages();
                            ?>
                            
                            <?php if (!empty($source_language) && !empty($target_languages)): ?>
                                <div class="nexus-ai-wp-language-config">
                                    <div class="source-language">
                                        <strong><?php _e('Source:', 'nexus-ai-wp-translator'); ?></strong>
                                        <span class="language-badge source">
                                            <?php echo esc_html($languages[$source_language] ?? strtoupper($source_language)); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="target-languages">
                                        <strong><?php _e('Targets:', 'nexus-ai-wp-translator'); ?></strong>
                                        <div class="language-list">
                                            <?php foreach ($target_languages as $lang): ?>
                                                <span class="language-badge target">
                                                    <?php echo esc_html($languages[$lang] ?? strtoupper($lang)); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="nexus-ai-wp-no-data">
                                    <?php _e('Languages not configured yet.', 'nexus-ai-wp-translator'); ?>
                                    <br><br>
                                    <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-settings#languages-tab'); ?>" class="button button-primary">
                                        <?php _e('Configure Languages', 'nexus-ai-wp-translator'); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="nexus-ai-wp-dashboard-widget">
                            <h2><?php _e('Quick Actions', 'nexus-ai-wp-translator'); ?></h2>
                            
                            <div class="nexus-ai-wp-quick-actions">
                                <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-settings'); ?>" class="button">
                                    <span class="dashicons dashicons-admin-settings"></span>
                                    <?php _e('Settings', 'nexus-ai-wp-translator'); ?>
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-translation'); ?>" class="button button-primary">
                                    <span class="dashicons dashicons-translation"></span>
                                    <?php _e('Manage Translations', 'nexus-ai-wp-translator'); ?>
                                </a>
                                <button id="nexus-ai-wp-refresh-stats" class="button">
                                    <span class="dashicons dashicons-update"></span>
                                    <?php _e('Refresh Stats', 'nexus-ai-wp-translator'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Analytics Tab -->
        <div id="analytics-tab" class="tab-content">
            <h2><?php _e('Translation Analytics', 'nexus-ai-wp-translator'); ?></h2>
            
            <!-- Analytics Controls -->
            <div class="nexus-ai-wp-analytics-controls">
                <div class="analytics-period-selector">
                    <label for="analytics-period"><?php _e('Time Period:', 'nexus-ai-wp-translator'); ?></label>
                    <select id="analytics-period">
                        <option value="1 day"><?php _e('Last 24 Hours', 'nexus-ai-wp-translator'); ?></option>
                        <option value="7 days" selected><?php _e('Last 7 Days', 'nexus-ai-wp-translator'); ?></option>
                        <option value="30 days"><?php _e('Last 30 Days', 'nexus-ai-wp-translator'); ?></option>
                    </select>
                    <button type="button" class="button" id="refresh-analytics-btn"><?php _e('Refresh', 'nexus-ai-wp-translator'); ?></button>
                </div>
            </div>
            
            <!-- Detailed Analytics Cards -->
            <div class="nexus-ai-wp-analytics-detailed">
                <div class="analytics-card">
                    <h3><?php _e('Translation Volume', 'nexus-ai-wp-translator'); ?></h3>
                    <div class="analytics-value" id="analytics-volume">-</div>
                    <div class="analytics-description"><?php _e('Translations completed', 'nexus-ai-wp-translator'); ?></div>
                </div>
                
                <div class="analytics-card">
                    <h3><?php _e('Average Processing Time', 'nexus-ai-wp-translator'); ?></h3>
                    <div class="analytics-value" id="analytics-avg-time">-</div>
                    <div class="analytics-description"><?php _e('Seconds per translation', 'nexus-ai-wp-translator'); ?></div>
                </div>
                
                <div class="analytics-card">
                    <h3><?php _e('Most Active Language', 'nexus-ai-wp-translator'); ?></h3>
                    <div class="analytics-value" id="analytics-top-lang">-</div>
                    <div class="analytics-description"><?php _e('Most translated to', 'nexus-ai-wp-translator'); ?></div>
                </div>
                
                <div class="analytics-card">
                    <h3><?php _e('Quality Average', 'nexus-ai-wp-translator'); ?></h3>
                    <div class="analytics-value" id="analytics-quality-avg">-</div>
                    <div class="analytics-description"><?php _e('Translation quality score', 'nexus-ai-wp-translator'); ?></div>
                </div>
            </div>
            
            <!-- Charts Placeholder -->
            <div class="nexus-ai-wp-analytics-charts">
                <div class="analytics-chart-placeholder">
                    <h3><?php _e('Translation Trends', 'nexus-ai-wp-translator'); ?></h3>
                    <p><?php _e('Chart functionality will be available in a future update.', 'nexus-ai-wp-translator'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Queue Tab -->
        <div id="queue-tab" class="tab-content">
            <h2><?php _e('Translation Queue', 'nexus-ai-wp-translator'); ?></h2>
            
            <!-- Queue Controls -->
            <div class="nexus-ai-wp-queue-controls">
                <div class="queue-stats">
                    <div class="queue-stat">
                        <span class="stat-label"><?php _e('Pending:', 'nexus-ai-wp-translator'); ?></span>
                        <span class="stat-value" id="queue-pending-count">0</span>
                    </div>
                    <div class="queue-stat">
                        <span class="stat-label"><?php _e('Processing:', 'nexus-ai-wp-translator'); ?></span>
                        <span class="stat-value" id="queue-processing-count">0</span>
                    </div>
                    <div class="queue-stat">
                        <span class="stat-label"><?php _e('Completed Today:', 'nexus-ai-wp-translator'); ?></span>
                        <span class="stat-value" id="queue-completed-count">0</span>
                    </div>
                </div>
                
                <div class="queue-actions">
                    <button type="button" class="button" id="refresh-queue-btn"><?php _e('Refresh', 'nexus-ai-wp-translator'); ?></button>
                    <button type="button" class="button button-secondary" id="clear-queue-btn"><?php _e('Clear Completed', 'nexus-ai-wp-translator'); ?></button>
                </div>
            </div>
            
            <!-- Queue Items -->
            <div class="nexus-ai-wp-queue-placeholder">
                <h3><?php _e('Queue Management', 'nexus-ai-wp-translator'); ?></h3>
                <p><?php _e('Background translation queue functionality will be available in a future update. Currently, all translations run immediately.', 'nexus-ai-wp-translator'); ?></p>
                <p><strong><?php _e('Current Translation Method:', 'nexus-ai-wp-translator'); ?></strong> <?php _e('Immediate processing with progress tracking', 'nexus-ai-wp-translator'); ?></p>
            </div>
        </div>
        
        <!-- Logs Tab -->
        <div id="logs-tab" class="tab-content">
            <h2><?php _e('Translation Logs', 'nexus-ai-wp-translator'); ?></h2>
            
            <!-- Logs Controls -->
            <div class="nexus-ai-wp-logs-controls">
                <div class="logs-filters">
                    <select id="logs-status-filter">
                        <option value=""><?php _e('All Statuses', 'nexus-ai-wp-translator'); ?></option>
                        <option value="success"><?php _e('Success', 'nexus-ai-wp-translator'); ?></option>
                        <option value="error"><?php _e('Error', 'nexus-ai-wp-translator'); ?></option>
                        <option value="warning"><?php _e('Warning', 'nexus-ai-wp-translator'); ?></option>
                    </select>
                    <button type="button" class="button" id="filter-logs-btn"><?php _e('Filter', 'nexus-ai-wp-translator'); ?></button>
                    <button type="button" class="button" id="refresh-logs-btn"><?php _e('Refresh', 'nexus-ai-wp-translator'); ?></button>
                </div>
            </div>
            
            <!-- Logs Table -->
            <div class="nexus-ai-wp-logs-table">
                <?php if ($recent_logs): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Post', 'nexus-ai-wp-translator'); ?></th>
                                <th><?php _e('Action', 'nexus-ai-wp-translator'); ?></th>
                                <th><?php _e('Status', 'nexus-ai-wp-translator'); ?></th>
                                <th><?php _e('Message', 'nexus-ai-wp-translator'); ?></th>
                                <th><?php _e('Time', 'nexus-ai-wp-translator'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="nexus-ai-wp-logs-tbody">
                            <?php foreach ($recent_logs as $log): ?>
                                <tr class="log-row status-<?php echo esc_attr($log->status); ?>">
                                    <td>
                                        <?php if ($log->post_title): ?>
                                            <a href="<?php echo get_edit_post_link($log->post_id); ?>"><?php echo esc_html($log->post_title); ?></a>
                                            <br><small>ID: <?php echo $log->post_id; ?></small>
                                        <?php else: ?>
                                            <span class="deleted-post"><?php _e('Deleted Post', 'nexus-ai-wp-translator'); ?> (ID: <?php echo $log->post_id; ?>)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($log->action); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($log->status); ?>">
                                            <?php echo esc_html(ucfirst($log->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($log->message)): ?>
                                            <div class="log-message" title="<?php echo esc_attr($log->message); ?>">
                                                <?php echo esc_html(wp_trim_words($log->message, 10)); ?>
                                            </div>
                                        <?php else: ?>
                                            <em><?php _e('No message', 'nexus-ai-wp-translator'); ?></em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <time datetime="<?php echo esc_attr($log->created_at); ?>" title="<?php echo esc_attr($log->created_at); ?>">
                                            <?php echo human_time_diff(strtotime($log->created_at), current_time('timestamp')); ?> <?php _e('ago', 'nexus-ai-wp-translator'); ?>
                                        </time>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="nexus-ai-wp-no-data"><?php _e('No translation logs yet.', 'nexus-ai-wp-translator'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Dashboard specific styles */
.nexus-ai-wp-header-links {
    float: right;
    margin-top: -5px;
}

.nexus-ai-wp-header-links .button {
    margin-left: 10px;
}

.nexus-ai-wp-dashboard-tabs .nav-tab-wrapper {
    margin-bottom: 20px;
    border-bottom: 1px solid #ccc;
}

.nexus-ai-wp-dashboard-tabs .tab-content {
    display: none;
    padding: 20px 0;
}

.nexus-ai-wp-dashboard-tabs .tab-content.active {
    display: block;
}

/* Stats Cards */
.nexus-ai-wp-stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.nexus-ai-wp-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.nexus-ai-wp-stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
    font-weight: 600;
}

.nexus-ai-wp-stat-card .stat-number {
    font-size: 32px;
    font-weight: 600;
    color: #0073aa;
    margin-bottom: 5px;
}

.nexus-ai-wp-stat-card .stat-number.success-rate {
    color: #28a745;
}

.nexus-ai-wp-stat-card .stat-description {
    font-size: 12px;
    color: #888;
}

/* Dashboard Columns */
.nexus-ai-wp-dashboard-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.nexus-ai-wp-dashboard-widget {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.nexus-ai-wp-dashboard-widget h2 {
    margin: 0 0 15px 0;
    font-size: 18px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

/* Recent Logs */
.nexus-ai-wp-recent-logs {
    max-height: 300px;
    overflow-y: auto;
}

.nexus-ai-wp-log-item {
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.nexus-ai-wp-log-item:last-child {
    border-bottom: none;
}

.nexus-ai-wp-log-item .log-title a {
    font-weight: 600;
    text-decoration: none;
}

.nexus-ai-wp-log-item .log-details {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.nexus-ai-wp-log-item .log-details span {
    margin-right: 10px;
}

.nexus-ai-wp-log-item .log-status {
    padding: 2px 6px;
    border-radius: 3px;
    color: #fff;
    font-weight: 600;
}

.nexus-ai-wp-log-item .log-status.status-success {
    background: #28a745;
}

.nexus-ai-wp-log-item .log-status.status-error {
    background: #dc3545;
}

.nexus-ai-wp-log-item .log-status.status-warning {
    background: #ffc107;
    color: #000;
}

/* System Status */
.nexus-ai-wp-system-checks .system-check {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.nexus-ai-wp-system-checks .system-check:last-child {
    border-bottom: none;
}

.nexus-ai-wp-system-checks .check-label {
    font-weight: 600;
}

.nexus-ai-wp-system-checks .status-success {
    color: #28a745;
    font-weight: 600;
}

.nexus-ai-wp-system-checks .status-error {
    color: #dc3545;
    font-weight: 600;
}

.nexus-ai-wp-system-checks .status-warning {
    color: #ffc107;
    font-weight: 600;
}

/* Language Configuration */
.nexus-ai-wp-language-config {
    margin: 15px 0;
}

.nexus-ai-wp-language-config .source-language,
.nexus-ai-wp-language-config .target-languages {
    margin-bottom: 15px;
}

.nexus-ai-wp-language-config .language-list {
    margin-top: 5px;
}

.language-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    margin-right: 5px;
    margin-bottom: 5px;
}

.language-badge.source {
    background: #0073aa;
    color: #fff;
}

.language-badge.target {
    background: #f0f0f0;
    color: #333;
    border: 1px solid #ddd;
}

/* Quick Actions */
.nexus-ai-wp-quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.nexus-ai-wp-quick-actions .button {
    display: flex;
    align-items
