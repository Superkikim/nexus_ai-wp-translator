<?php
/**
 * Global Settings Tab Content
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<h2><?php _e('Global Translation Settings', 'nexus-ai-wp-translator'); ?></h2>

<table class="form-table">

    <tr>
        <th scope="row"><?php _e('Quality Assessment Method', 'nexus-ai-wp-translator'); ?></th>
        <td>
            <fieldset>
                <label>
                    <input type="checkbox"
                           id="nexus_ai_wp_translator_use_llm_quality_assessment"
                           name="nexus_ai_wp_translator_use_llm_quality_assessment"
                           value="1"
                           <?php checked(get_option('nexus_ai_wp_translator_use_llm_quality_assessment', true)); ?> />
                    <?php _e('Use LLM Quality Assessment (Recommended)', 'nexus-ai-wp-translator'); ?>
                </label>
                <p class="description">
                    <?php _e('During translation, an assesment of the quality of the translation is performed, allowing you to decide if review is required before publishing.<br><br>Beware this adds ~8% to API costs but eliminates false positives and provides better quality metrics.', 'nexus-ai-wp-translator'); ?>
                </p>
            </fieldset>
        </td>
    </tr>

    <tr>
        <th scope="row"><?php _e('Save as draft', 'nexus-ai-wp-translator'); ?></th>
        <td>
            <fieldset>
                <label>
                    <input type="checkbox"
                           id="nexus_ai_wp_translator_save_as_draft"
                           name="nexus_ai_wp_translator_save_as_draft"
                           value="1"
                           <?php checked(get_option('nexus_ai_wp_translator_save_as_draft', false)); ?> />
                    <?php _e('Save translations as drafts instead of publishing immediately', 'nexus-ai-wp-translator'); ?>
                </label>
                <p class="description">
                    <?php _e('When enabled, translated posts will be saved as drafts for review before publishing. When disabled, translations will be published immediately with the same status as the source post.', 'nexus-ai-wp-translator'); ?>
                </p>
            </fieldset>
        </td>
    </tr>
</table>
