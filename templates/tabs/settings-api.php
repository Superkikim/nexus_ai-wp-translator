<?php
/**
 * API Settings Tab Content
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<h2><?php _e('Nexus AI API Configuration', 'nexus-ai-wp-translator'); ?></h2>

<table class="form-table">
    <tr>
        <th scope="row">
            <label for="nexus_ai_wp_translator_api_key"><?php _e('API Key', 'nexus-ai-wp-translator'); ?></label>
        </th>
        <td>
            <input type="password" 
                   id="nexus_ai_wp_translator_api_key" 
                   name="nexus_ai_wp_translator_api_key" 
                   value="<?php echo esc_attr($api_key); ?>" 
                   class="large-text" 
                   autocomplete="off" />
            <button type="button" id="nexus-ai-wp-test-api" class="button">
                <?php _e('Test Connection', 'nexus-ai-wp-translator'); ?>
            </button>
            <button type="button" id="nexus-ai-wp-toggle-api-key" class="button">
                <?php _e('Show', 'nexus-ai-wp-translator'); ?>
            </button>
            <p class="description">
                <?php _e('Enter your Claude AI API key. You can get one from the Anthropic Console.', 'nexus-ai-wp-translator'); ?>
            </p>
            <div id="api-test-result"></div>
        </td>
    </tr>
    
    <tr id="model-selection-row" style="display: none;">
        <th scope="row">
            <label for="nexus_ai_wp_translator_model"><?php _e('AI Model', 'nexus-ai-wp-translator'); ?></label>
        </th>
        <td>
            <select id="nexus_ai_wp_translator_model" name="nexus_ai_wp_translator_model">
                <?php if (empty($selected_model)): ?>
                    <option value=""><?php _e('Please test API connection to load models', 'nexus-ai-wp-translator'); ?></option>
                <?php else: ?>
                    <option value="<?php echo esc_attr($selected_model); ?>" selected>
                        <?php echo esc_html($selected_model); ?>
                    </option>
                <?php endif; ?>
            </select>
            <button type="button" id="nexus-ai-wp-refresh-models" class="button" style="margin-left: 10px;">
                <?php _e('Refresh Models', 'nexus-ai-wp-translator'); ?>
            </button>
            <p class="description">
                <?php _e('Select the Claude AI model to use for translations.', 'nexus-ai-wp-translator'); ?>
                <br><strong><?php _e('Recommendation: Claude Sonnet 4 provides the best translation quality.', 'nexus-ai-wp-translator'); ?></strong>
            </p>
        </td>
    </tr>
</table>
