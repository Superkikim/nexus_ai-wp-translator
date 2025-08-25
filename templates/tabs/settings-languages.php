<?php
/**
 * Language Settings Tab Content
 */

if (!defined('ABSPATH')) {
    exit;
}

// Comprehensive ISO 639-1 language list
$all_languages = array(
    'en' => 'English',
    'es' => 'Spanish',
    'fr' => 'French',
    'de' => 'German',
    'it' => 'Italian',
    'pt' => 'Portuguese',
    'ru' => 'Russian',
    'zh' => 'Chinese',
    'ja' => 'Japanese',
    'ar' => 'Arabic',
    'hi' => 'Hindi',
    'nl' => 'Dutch',
    'sv' => 'Swedish',
    'da' => 'Danish',
    'no' => 'Norwegian',
    'fi' => 'Finnish',
    'pl' => 'Polish',
    'cs' => 'Czech',
    'hu' => 'Hungarian',
    'ko' => 'Korean',
    'tr' => 'Turkish',
    'uk' => 'Ukrainian',
    'ro' => 'Romanian',
    'bg' => 'Bulgarian',
    'hr' => 'Croatian',
    'sk' => 'Slovak',
    'sl' => 'Slovenian',
    'et' => 'Estonian',
    'lv' => 'Latvian',
    'lt' => 'Lithuanian',
    'mt' => 'Maltese',
    'el' => 'Greek',
    'cy' => 'Welsh',
    'ga' => 'Irish',
    'is' => 'Icelandic',
    'mk' => 'Macedonian',
    'sq' => 'Albanian',
    'sr' => 'Serbian',
    'bs' => 'Bosnian',
    'me' => 'Montenegrin',
    'he' => 'Hebrew',
    'th' => 'Thai',
    'vi' => 'Vietnamese',
    'id' => 'Indonesian',
    'ms' => 'Malay',
    'tl' => 'Filipino',
    'sw' => 'Swahili',
    'am' => 'Amharic',
    'bn' => 'Bengali',
    'gu' => 'Gujarati',
    'kn' => 'Kannada',
    'ml' => 'Malayalam',
    'mr' => 'Marathi',
    'ne' => 'Nepali',
    'or' => 'Odia',
    'pa' => 'Punjabi',
    'si' => 'Sinhala',
    'ta' => 'Tamil',
    'te' => 'Telugu',
    'ur' => 'Urdu',
    'my' => 'Myanmar',
    'km' => 'Khmer',
    'lo' => 'Lao',
    'ka' => 'Georgian',
    'hy' => 'Armenian',
    'az' => 'Azerbaijani',
    'kk' => 'Kazakh',
    'ky' => 'Kyrgyz',
    'mn' => 'Mongolian',
    'tg' => 'Tajik',
    'tk' => 'Turkmen',
    'uz' => 'Uzbek',
    'af' => 'Afrikaans',
    'zu' => 'Zulu',
    'xh' => 'Xhosa',
    'st' => 'Sesotho',
    'tn' => 'Setswana',
    'ss' => 'Swati',
    'nr' => 'Ndebele',
    've' => 'Venda',
    'ts' => 'Tsonga',
    'yo' => 'Yoruba',
    'ig' => 'Igbo',
    'ha' => 'Hausa',
    'eu' => 'Basque',
    'ca' => 'Catalan',
    'gl' => 'Galician',
    'be' => 'Belarusian',
    'lv' => 'Latvian',
    'lt' => 'Lithuanian',
    'et' => 'Estonian',
    'fo' => 'Faroese',
    'lb' => 'Luxembourgish',
    'rm' => 'Romansh',
    'br' => 'Breton',
    'co' => 'Corsican',
    'sc' => 'Sardinian',
    'fur' => 'Friulian',
    'lad' => 'Ladino',
    'an' => 'Aragonese',
    'ast' => 'Asturian',
    'ext' => 'Extremaduran',
    'mwl' => 'Mirandese',
    'oc' => 'Occitan',
    'wa' => 'Walloon',
    'li' => 'Limburgish',
    'fy' => 'Frisian',
    'gd' => 'Scottish Gaelic',
    'kw' => 'Cornish',
    'gv' => 'Manx',
    'se' => 'Northern Sami',
    'smj' => 'Lule Sami',
    'sma' => 'Southern Sami',
    'smn' => 'Inari Sami',
    'sms' => 'Skolt Sami'
);

// Popular languages (shown by default)
$popular_languages = array('en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'ar', 'hi', 'nl');
?>

<h2><?php _e('Available Translation Languages', 'nexus-ai-wp-translator'); ?></h2>

<div class="nexus-ai-wp-language-settings">
    <p class="description">
        <?php _e('Select languages to enable for translation. Posts can be translated into any of the selected languages.', 'nexus-ai-wp-translator'); ?>
    </p>

    <!-- Search Box -->
    <div class="nexus-ai-wp-language-search">
        <input type="text"
               id="nexus-ai-wp-language-search"
               placeholder="<?php _e('Search languages by name or code (e.g., "French" or "fr")...', 'nexus-ai-wp-translator'); ?>"
               class="regular-text" />
        <button type="button" id="nexus-ai-wp-clear-search" class="button"><?php _e('Clear', 'nexus-ai-wp-translator'); ?></button>
    </div>

    <!-- Show All Toggle -->
    <div class="nexus-ai-wp-language-toggle">
        <label>
            <input type="checkbox" id="nexus-ai-wp-show-all-languages" />
            <?php _e('Show all languages', 'nexus-ai-wp-translator'); ?>
            <span class="description">(<?php echo count($all_languages); ?> <?php _e('total', 'nexus-ai-wp-translator'); ?>)</span>
        </label>
    </div>

    <!-- Language Grid -->
    <div class="nexus-ai-wp-language-grid" id="nexus-ai-wp-language-grid">
        <?php foreach ($all_languages as $code => $name): ?>
            <?php
            $is_popular = in_array($code, $popular_languages);
            $is_selected = in_array($code, $target_languages);
            $display_class = $is_popular ? 'popular' : 'extended';
            ?>
            <div class="nexus-ai-wp-language-item <?php echo $display_class; ?>"
                 data-code="<?php echo esc_attr($code); ?>"
                 data-name="<?php echo esc_attr(strtolower($name)); ?>">
                <label>
                    <input type="checkbox"
                           name="nexus_ai_wp_translator_target_languages[]"
                           value="<?php echo esc_attr($code); ?>"
                           <?php checked($is_selected); ?> />
                    <span class="language-name"><?php echo esc_html($name); ?></span>
                    <span class="language-code"><?php echo esc_html($code); ?></span>
                </label>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Selection Summary -->
    <div class="nexus-ai-wp-language-summary">
        <span id="nexus-ai-wp-selected-count">0</span> <?php _e('languages selected', 'nexus-ai-wp-translator'); ?>
    </div>
</div>
