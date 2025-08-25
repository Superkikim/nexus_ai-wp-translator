<?php
/**
 * Admin Settings Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Nexus AI WP Translator Settings', 'nexus-ai-wp-translator'); ?></h1>
    
    <form id="nexus-ai-wp-translator-settings-form" method="post" action="options.php">
        <?php settings_fields('nexus_ai_wp_translator_settings'); ?>
        
        <div class="nexus-ai-wp-settings-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#global-settings" class="nav-tab nav-tab-active"><?php _e('Global Settings', 'nexus-ai-wp-translator'); ?></a>
                <a href="#api-settings" class="nav-tab"><?php _e('API Settings', 'nexus-ai-wp-translator'); ?></a>
                <a href="#language-settings" class="nav-tab"><?php _e('Languages', 'nexus-ai-wp-translator'); ?></a>
                <a href="#performance-settings" class="nav-tab"><?php _e('Performance', 'nexus-ai-wp-translator'); ?></a>
            </nav>
            
            <!-- Global Settings Tab -->
            <div id="global-settings" class="tab-content active">
                <?php include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/tabs/settings-global.php'; ?>
            </div>

            <!-- API Settings Tab -->
            <div id="api-settings" class="tab-content">
                <?php include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/tabs/settings-api.php'; ?>
            </div>

            <!-- Language Settings Tab -->
            <div id="language-settings" class="tab-content">
                <?php include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/tabs/settings-languages.php'; ?>
            </div>

            <!-- Performance Settings Tab -->
            <div id="performance-settings" class="tab-content">
                <?php include NEXUS_AI_WP_TRANSLATOR_PLUGIN_DIR . 'templates/tabs/settings-performance.php'; ?>
            </div>
        </div>
        
    </form>
</div>

<!-- JavaScript moved to assets/js/pages/settings.js -->

<!-- Large JavaScript function removed and moved to assets/js/pages/settings.js -->



<script>
// Pass server-side values to JavaScript
// SECURITY: API key removed to prevent exposure in browser
window.nexusAiServerData = {
    selectedModel: '<?php echo esc_js($selected_model); ?>',
    hasApiKey: <?php echo !empty($api_key) ? 'true' : 'false'; ?>
};
</script>
