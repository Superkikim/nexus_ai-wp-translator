<?php
/**
 * Performance Settings Tab Content
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<h2><?php _e('Performance & Rate Limiting', 'nexus-ai-wp-translator'); ?></h2>

<table class="form-table">
    <tr>
        <th scope="row">
            <label for="nexus_ai_wp_translator_throttle_limit"><?php _e('API Call Limit', 'nexus-ai-wp-translator'); ?></label>
        </th>
        <td>
            <input type="number" 
                   id="nexus_ai_wp_translator_throttle_limit" 
                   name="nexus_ai_wp_translator_throttle_limit" 
                   value="<?php echo esc_attr($throttle_limit); ?>" 
                   min="1" 
                   max="1000" 
                   class="small-text" />
            <p class="description">
                <?php _e('Maximum number of API calls allowed per time period.', 'nexus-ai-wp-translator'); ?>
            </p>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <label for="nexus_ai_wp_translator_throttle_period"><?php _e('Time Period (seconds)', 'nexus-ai-wp-translator'); ?></label>
        </th>
        <td>
            <input type="number" 
                   id="nexus_ai_wp_translator_throttle_period" 
                   name="nexus_ai_wp_translator_throttle_period" 
                   value="<?php echo esc_attr($throttle_period); ?>" 
                   min="60" 
                   max="86400" 
                   class="small-text" />
            <p class="description">
                <?php _e('Time period for the rate limit (minimum 60 seconds).', 'nexus-ai-wp-translator'); ?>
            </p>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <label for="nexus_ai_wp_translator_retry_attempts"><?php _e('Retry Attempts', 'nexus-ai-wp-translator'); ?></label>
        </th>
        <td>
            <input type="number" 
                   id="nexus_ai_wp_translator_retry_attempts" 
                   name="nexus_ai_wp_translator_retry_attempts" 
                   value="<?php echo esc_attr($retry_attempts); ?>" 
                   min="1" 
                   max="10" 
                   class="small-text" />
            <p class="description">
                <?php _e('Number of times to retry failed API calls.', 'nexus-ai-wp-translator'); ?>
            </p>
        </td>
    </tr>
    
    <tr>
        <th scope="row"><?php _e('Cache Translations', 'nexus-ai-wp-translator'); ?></th>
        <td>
            <fieldset>
                <label>
                    <input type="checkbox" 
                           id="nexus_ai_wp_translator_cache_translations" 
                           name="nexus_ai_wp_translator_cache_translations" 
                           value="1" 
                           <?php checked($cache_translations); ?> />
                    <?php _e('Cache translations to improve performance', 'nexus-ai-wp-translator'); ?>
                </label>
                <p class="description">
                    <?php _e('When enabled, translations are cached to reduce API calls for repeated content.', 'nexus-ai-wp-translator'); ?>
                    <br>
                    <button type="button" id="nexus-ai-wp-clear-cache" class="button button-secondary" style="margin-top: 10px;">
                        <?php _e('Clear Translation Cache', 'nexus-ai-wp-translator'); ?>
                    </button>
                    <span id="nexus-ai-wp-clear-cache-result" style="margin-left: 10px;"></span>
                </p>
            </fieldset>
        </td>
    </tr>
</table>
