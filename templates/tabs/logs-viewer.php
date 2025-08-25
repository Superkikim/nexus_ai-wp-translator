<?php
/**
 * Logs Viewer Tab Content
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<h2><?php _e('Translation Logs', 'nexus-ai-wp-translator'); ?></h2>

<div class="nexus-ai-wp-logs-filters">
    <form method="get" action="" id="logs-filter-form">
        <input type="hidden" name="page" value="nexus-ai-wp-translator-dashboard" />
        <input type="hidden" name="tab" value="logs" />

        <select name="status" id="logs-status-filter">
            <option value=""><?php _e('All Statuses', 'nexus-ai-wp-translator'); ?></option>
            <option value="success"><?php _e('Success', 'nexus-ai-wp-translator'); ?></option>
            <option value="error"><?php _e('Error', 'nexus-ai-wp-translator'); ?></option>
            <option value="processing"><?php _e('Processing', 'nexus-ai-wp-translator'); ?></option>
        </select>

        <select name="action" id="logs-action-filter">
            <option value=""><?php _e('All Actions', 'nexus-ai-wp-translator'); ?></option>
            <option value="translate"><?php _e('Translation', 'nexus-ai-wp-translator'); ?></option>
            <option value="queue"><?php _e('Queue', 'nexus-ai-wp-translator'); ?></option>
            <option value="link"><?php _e('Link', 'nexus-ai-wp-translator'); ?></option>
        </select>

        <input type="text" name="search" id="logs-search" placeholder="<?php _e('Search logs...', 'nexus-ai-wp-translator'); ?>" />

        <input type="submit" class="button" value="<?php _e('Filter', 'nexus-ai-wp-translator'); ?>" />
        <button type="button" class="button" id="clear-logs-filters">
            <?php _e('Clear Filters', 'nexus-ai-wp-translator'); ?>
        </button>
        <button type="button" class="button" id="refresh-logs">
            <?php _e('Refresh', 'nexus-ai-wp-translator'); ?>
        </button>
    </form>
</div>

<div id="logs-table-container">
    <table class="wp-list-table widefat fixed striped" id="logs-table">
        <thead>
            <tr>
                <th><?php _e('Date/Time', 'nexus-ai-wp-translator'); ?></th>
                <th><?php _e('Post', 'nexus-ai-wp-translator'); ?></th>
                <th><?php _e('Action', 'nexus-ai-wp-translator'); ?></th>
                <th><?php _e('Status', 'nexus-ai-wp-translator'); ?></th>
                <th><?php _e('Message', 'nexus-ai-wp-translator'); ?></th>
                <th><?php _e('API Calls', 'nexus-ai-wp-translator'); ?></th>
                <th><?php _e('Processing Time', 'nexus-ai-wp-translator'); ?></th>
            </tr>
        </thead>
        <tbody id="logs-tbody">
            <tr>
                <td colspan="7" class="nexus-ai-wp-loading"><?php _e('Loading logs...', 'nexus-ai-wp-translator'); ?></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="tablenav" id="logs-pagination">
    <div class="tablenav-pages">
        <!-- Pagination will be loaded here -->
    </div>
</div>
