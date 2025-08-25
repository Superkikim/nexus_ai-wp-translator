<?php
/**
 * Dashboard Overview Tab Content
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<h2><?php _e('Dashboard Overview', 'nexus-ai-wp-translator'); ?></h2>

<!-- Dashboard Grid Layout -->
<div class="nexus-ai-wp-dashboard-grid">
    <!-- Row 1: Analytics -->
    <div class="nexus-ai-wp-dashboard-row">
        <!-- Col 1: Primary Analytics (2x2 sub-grid) -->
        <div class="nexus-ai-wp-dashboard-section" style="grid-column: 1 / 2;">
            <h3><?php _e('Primary Analytics', 'nexus-ai-wp-translator'); ?></h3>
            <div class="nexus-ai-wp-analytics-grid">
                <div class="nexus-ai-wp-analytics-card">
                    <div class="analytics-icon">📊</div>
                    <div class="analytics-content">
                        <div class="analytics-label"><?php _e('Translation Volume', 'nexus-ai-wp-translator'); ?></div>
                        <div class="analytics-value">[PLACEHOLDER: Translation count data will go here]</div>
                    </div>
                </div>
                <div class="nexus-ai-wp-analytics-card">
                    <div class="analytics-icon">⭐</div>
                    <div class="analytics-content">
                        <div class="analytics-label"><?php _e('Quality Score', 'nexus-ai-wp-translator'); ?></div>
                        <div class="analytics-value">[PLACEHOLDER: Average quality metrics will go here]</div>
                    </div>
                </div>
                <div class="nexus-ai-wp-analytics-card">
                    <div class="analytics-icon">🌍</div>
                    <div class="analytics-content">
                        <div class="analytics-label"><?php _e('Popular Languages', 'nexus-ai-wp-translator'); ?></div>
                        <div class="analytics-value">[PLACEHOLDER: Language pair statistics will go here]</div>
                    </div>
                </div>
                <div class="nexus-ai-wp-analytics-card">
                    <div class="analytics-icon">⚡</div>
                    <div class="analytics-content">
                        <div class="analytics-label"><?php _e('Processing Speed', 'nexus-ai-wp-translator'); ?></div>
                        <div class="analytics-value">[PLACEHOLDER: Performance metrics will go here]</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Col 2: Secondary Analytics (2x2 sub-grid) -->
        <div class="nexus-ai-wp-dashboard-section" style="grid-column: 2 / 3;">
            <h3><?php _e('Secondary Analytics', 'nexus-ai-wp-translator'); ?></h3>
            <div class="nexus-ai-wp-analytics-grid">
                <div class="nexus-ai-wp-analytics-card">
                    <div class="analytics-icon">⏳</div>
                    <div class="analytics-content">
                        <div class="analytics-label"><?php _e('Pending Queue', 'nexus-ai-wp-translator'); ?></div>
                        <div class="analytics-value"><a href="#queue-tab" id="dashboard-pending-queue">-</a></div>
                    </div>
                </div>
                <div class="nexus-ai-wp-analytics-card">
                    <div class="analytics-icon">💾</div>
                    <div class="analytics-content">
                        <div class="analytics-label"><?php _e('Storage Usage', 'nexus-ai-wp-translator'); ?></div>
                        <div class="analytics-value">[PLACEHOLDER: Storage metrics will go here]</div>
                    </div>
                </div>
                <div class="nexus-ai-wp-analytics-card">
                    <div class="analytics-icon">🔌</div>
                    <div class="analytics-content">
                        <div class="analytics-label"><?php _e('API Usage', 'nexus-ai-wp-translator'); ?></div>
                        <div class="analytics-value">[PLACEHOLDER: API usage data will go here]</div>
                    </div>
                </div>
                <div class="nexus-ai-wp-analytics-card">
                    <div class="analytics-icon">❌</div>
                    <div class="analytics-content">
                        <div class="analytics-label"><?php _e('Error Rate', 'nexus-ai-wp-translator'); ?></div>
                        <div class="analytics-value">[PLACEHOLDER: Error statistics will go here]</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: System Status & Quick Actions -->
    <div class="nexus-ai-wp-dashboard-row">
        <!-- Col 1: System Status Panel -->
        <div class="nexus-ai-wp-dashboard-section" style="grid-column: 1 / 2;">
            <h3><?php _e('System Status', 'nexus-ai-wp-translator'); ?></h3>
            <div class="nexus-ai-wp-status-panel">
                <div class="status-item" id="api-key-status">
                    <div class="status-icon">🔒</div>
                    <div class="status-content">
                        <div class="status-label"><?php _e('API Key', 'nexus-ai-wp-translator'); ?></div>
                        <div class="status-value"><?php _e('Checking...', 'nexus-ai-wp-translator'); ?></div>
                        <div class="status-actions"><a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-settings#api-settings'); ?>"><?php _e('Configure', 'nexus-ai-wp-translator'); ?></a></div>
                    </div>
                </div>
                <div class="status-item" id="anthropic-service-status">
                    <div class="status-icon">🌐</div>
                    <div class="status-content">
                        <div class="status-label"><?php _e('Anthropic Service Status', 'nexus-ai-wp-translator'); ?></div>
                        <div class="status-value"><?php _e('Checking...', 'nexus-ai-wp-translator'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Col 2: Quick Actions Panel -->
        <div class="nexus-ai-wp-dashboard-section" style="grid-column: 2 / 3;">
            <h3><?php _e('Quick Actions', 'nexus-ai-wp-translator'); ?></h3>
            <div class="nexus-ai-wp-quick-actions-grid">
                <button type="button" class="nexus-ai-wp-action-button placeholder-button" disabled>
                    <span class="action-icon">🚀</span>
                    <span class="action-text">[PLACEHOLDER BUTTON: Primary action will go here]</span>
                </button>
                <button type="button" id="process-queue-now" class="nexus-ai-wp-action-button">
                    <span class="action-icon">⚡</span>
                    <span class="action-text"><?php _e('Process Queue Now', 'nexus-ai-wp-translator'); ?></span>
                </button>
                <button type="button" class="nexus-ai-wp-action-button placeholder-button" disabled>
                    <span class="action-icon">📤</span>
                    <span class="action-text">[PLACEHOLDER BUTTON: Export action will go here]</span>
                </button>
                <button type="button" class="nexus-ai-wp-action-button placeholder-button" disabled>
                    <span class="action-icon">🗄️</span>
                    <span class="action-text">[PLACEHOLDER BUTTON: Cache action will go here]</span>
                </button>
                <button type="button" class="nexus-ai-wp-action-button placeholder-button" disabled>
                    <span class="action-icon">🚨</span>
                    <span class="action-text">[PLACEHOLDER BUTTON: Emergency action will go here]</span>
                </button>
            </div>
        </div>
    </div>
</div>
