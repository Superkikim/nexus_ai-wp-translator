<?php
/**
 * Queue Status Tab Content
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

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
