<?php
/**
 * Language Settings Tab Content
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<h2><?php _e('Available Translation Languages', 'nexus-ai-wp-translator'); ?></h2>

<table class="form-table">
    <tr>
        <th scope="row">
            <label><?php _e('Available Languages', 'nexus-ai-wp-translator'); ?></label>
        </th>
        <td>
            <p class="description">
                <?php _e('Select languages to enable for translation. Posts can be translated into any of the selected languages.', 'nexus-ai-wp-translator'); ?>
            </p>
            <fieldset>
                <?php foreach ($languages as $code => $name): ?>
                    <label>
                        <input type="checkbox" 
                               name="nexus_ai_wp_translator_target_languages[]" 
                               value="<?php echo esc_attr($code); ?>" 
                               <?php checked(in_array($code, $target_languages)); ?> />
                        <?php echo esc_html($name); ?>
                    </label><br>
                <?php endforeach; ?>
            </fieldset>
        </td>
    </tr>
</table>
