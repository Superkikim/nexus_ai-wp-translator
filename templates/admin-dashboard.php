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
        </nav>
        
        <!-- Articles Tab -->
        <div id="articles-tab" class="tab-content active">
            <h2><?php _e('Articles to Translate', 'nexus-ai-wp-translator'); ?></h2>
            <div id="articles-list">
                <?php echo $this->render_posts_list('post'); ?>
            </div>
        </div>
        
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
    
    // Translate individual post
    $(document).on('click', '.translate-post-btn', function() {
        var button = $(this);
        var postId = button.data('post-id');
        var postTitle = button.data('post-title');
        
        if (!confirm('Translate "' + postTitle + '" to all target languages?')) {
            return;
        }
        
        button.prop('disabled', true).text('Translating...');
        
        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
            action: 'nexus_ai_wp_translate_post',
            post_id: postId,
            target_languages: [], // Will use all configured target languages
            nonce: nexus_ai_wp_translator_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                alert('Translation completed successfully!');
                location.reload();
            } else {
                alert('Translation failed: ' + (response.message || 'Unknown error'));
            }
        })
        .fail(function() {
            alert('Network error occurred');
        })
        .always(function() {
            button.prop('disabled', false).text('Translate');
        });
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
});
</script>