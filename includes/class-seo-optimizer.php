<?php
/**
 * SEO Optimizer for Multi-language Content
 * 
 * Handles hreflang tags, sitemaps, and SEO optimization for translated content
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_SEO_Optimizer {
    
    private static $instance = null;
    private $db;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = Nexus_AI_WP_Translator_Database::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Add hreflang tags to head
        add_action('wp_head', array($this, 'add_hreflang_tags'));
        
        // Modify sitemap generation
        add_action('init', array($this, 'init_sitemap_modifications'));
        
        // Add language-specific meta tags
        add_action('wp_head', array($this, 'add_language_meta_tags'));
        
        // Modify page titles for SEO
        add_filter('wp_title', array($this, 'modify_page_title'), 10, 3);
        add_filter('document_title_parts', array($this, 'modify_document_title_parts'));
        
        // Add canonical URLs
        add_action('wp_head', array($this, 'add_canonical_url'));
        
        // Modify robots meta
        add_action('wp_head', array($this, 'add_robots_meta'));
        
        // Add Open Graph tags
        add_action('wp_head', array($this, 'add_open_graph_tags'));
        
        // Add JSON-LD structured data
        add_action('wp_head', array($this, 'add_structured_data'));

        // Add query vars for sitemap
        add_filter('query_vars', array($this, 'add_query_vars'));
    }
    
    /**
     * Add hreflang tags to page head
     */
    public function add_hreflang_tags() {
        if (!is_singular()) {
            return;
        }
        
        global $post;
        $current_post_id = $post->ID;
        $current_language = get_post_meta($current_post_id, '_nexus_ai_wp_translator_language', true);
        
        if (!$current_language) {
            $current_language = get_option('nexus_ai_wp_translator_source_language', 'en');
        }
        
        // Get all translations for this post
        $translations = $this->db->get_post_translations($current_post_id);
        $hreflang_urls = array();
        
        // Add current post
        $hreflang_urls[$current_language] = get_permalink($current_post_id);
        
        // Add translations
        foreach ($translations as $translation) {
            $translated_post_id = ($translation->source_post_id == $current_post_id) 
                ? $translation->translated_post_id 
                : $translation->source_post_id;
            
            $translated_language = get_post_meta($translated_post_id, '_nexus_ai_wp_translator_language', true);
            
            if ($translated_language && $translated_language !== $current_language) {
                $hreflang_urls[$translated_language] = get_permalink($translated_post_id);
            }
        }
        
        // Output hreflang tags
        foreach ($hreflang_urls as $lang => $url) {
            echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url($url) . '" />' . "\n";
        }
        
        // Add x-default if we have multiple languages
        if (count($hreflang_urls) > 1) {
            $default_language = get_option('nexus_ai_wp_translator_source_language', 'en');
            if (isset($hreflang_urls[$default_language])) {
                echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($hreflang_urls[$default_language]) . '" />' . "\n";
            }
        }
    }
    
    /**
     * Initialize sitemap modifications
     */
    public function init_sitemap_modifications() {
        // Hook into WordPress sitemap generation
        add_filter('wp_sitemaps_posts_entry', array($this, 'modify_sitemap_entry'), 10, 3);
        add_filter('wp_sitemaps_posts_query_args', array($this, 'modify_sitemap_query_args'), 10, 2);
        
        // Add custom sitemap for language alternates
        add_action('init', array($this, 'add_language_sitemap_rewrite'));
        add_action('template_redirect', array($this, 'handle_language_sitemap_request'));
    }
    
    /**
     * Modify sitemap entry to include language information
     */
    public function modify_sitemap_entry($sitemap_entry, $post, $post_type) {
        $language = get_post_meta($post->ID, '_nexus_ai_wp_translator_language', true);
        
        if ($language) {
            // Add language-specific lastmod if translation is newer
            $translation_date = get_post_meta($post->ID, '_nexus_ai_wp_translator_translation_date', true);
            if ($translation_date && strtotime($translation_date) > strtotime($post->post_modified)) {
                $sitemap_entry['lastmod'] = $translation_date;
            }
        }
        
        return $sitemap_entry;
    }
    
    /**
     * Modify sitemap query to include all language versions
     */
    public function modify_sitemap_query_args($args, $post_type) {
        // Include all posts regardless of language
        return $args;
    }
    
    /**
     * Add language-specific meta tags
     */
    public function add_language_meta_tags() {
        if (!is_singular()) {
            return;
        }
        
        global $post;
        $language = get_post_meta($post->ID, '_nexus_ai_wp_translator_language', true);
        
        if (!$language) {
            $language = get_option('nexus_ai_wp_translator_source_language', 'en');
        }
        
        // Add language meta tag
        echo '<meta name="language" content="' . esc_attr($language) . '" />' . "\n";
        
        // Add content language
        echo '<meta http-equiv="content-language" content="' . esc_attr($language) . '" />' . "\n";
        
        // Add translation information
        $source_post_id = get_post_meta($post->ID, '_nexus_ai_wp_translator_source_post', true);
        if ($source_post_id) {
            echo '<meta name="translation-source" content="' . esc_attr($source_post_id) . '" />' . "\n";
            
            $translation_date = get_post_meta($post->ID, '_nexus_ai_wp_translator_translation_date', true);
            if ($translation_date) {
                echo '<meta name="translation-date" content="' . esc_attr($translation_date) . '" />' . "\n";
            }
        }
    }
    
    /**
     * Modify page title for SEO
     */
    public function modify_page_title($title, $sep, $seplocation) {
        if (!is_singular()) {
            return $title;
        }
        
        global $post;
        $language = get_post_meta($post->ID, '_nexus_ai_wp_translator_language', true);
        
        if ($language) {
            $language_name = $this->get_language_name($language);
            
            if ($seplocation === 'right') {
                $title = $title . ' ' . $sep . ' ' . $language_name;
            } else {
                $title = $language_name . ' ' . $sep . ' ' . $title;
            }
        }
        
        return $title;
    }
    
    /**
     * Modify document title parts
     */
    public function modify_document_title_parts($title_parts) {
        if (!is_singular()) {
            return $title_parts;
        }
        
        global $post;
        $language = get_post_meta($post->ID, '_nexus_ai_wp_translator_language', true);
        
        if ($language && $language !== get_option('nexus_ai_wp_translator_source_language', 'en')) {
            $language_name = $this->get_language_name($language);
            $title_parts['tagline'] = isset($title_parts['tagline']) 
                ? $title_parts['tagline'] . ' - ' . $language_name
                : $language_name;
        }
        
        return $title_parts;
    }
    
    /**
     * Add canonical URL
     */
    public function add_canonical_url() {
        if (!is_singular()) {
            return;
        }
        
        global $post;
        $canonical_url = get_permalink($post->ID);
        
        // For translated posts, canonical should point to the original
        $source_post_id = get_post_meta($post->ID, '_nexus_ai_wp_translator_source_post', true);
        if ($source_post_id) {
            $canonical_url = get_permalink($source_post_id);
        }
        
        echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
    }
    
    /**
     * Add robots meta tag
     */
    public function add_robots_meta() {
        if (!is_singular()) {
            return;
        }
        
        global $post;
        $language = get_post_meta($post->ID, '_nexus_ai_wp_translator_language', true);
        $source_language = get_option('nexus_ai_wp_translator_source_language', 'en');
        
        // For translated content, add noindex if configured
        if ($language && $language !== $source_language) {
            $noindex_translations = get_option('nexus_ai_wp_translator_noindex_translations', false);
            
            if ($noindex_translations) {
                echo '<meta name="robots" content="noindex, follow" />' . "\n";
            }
        }
    }
    
    /**
     * Add Open Graph tags for social media
     */
    public function add_open_graph_tags() {
        if (!is_singular()) {
            return;
        }
        
        global $post;
        $language = get_post_meta($post->ID, '_nexus_ai_wp_translator_language', true);
        
        if (!$language) {
            $language = get_option('nexus_ai_wp_translator_source_language', 'en');
        }
        
        // Add language-specific Open Graph tags
        echo '<meta property="og:locale" content="' . esc_attr($this->get_og_locale($language)) . '" />' . "\n";
        
        // Add alternate locales for other translations
        $translations = $this->db->get_post_translations($post->ID);
        foreach ($translations as $translation) {
            $translated_post_id = ($translation->source_post_id == $post->ID) 
                ? $translation->translated_post_id 
                : $translation->source_post_id;
            
            $translated_language = get_post_meta($translated_post_id, '_nexus_ai_wp_translator_language', true);
            
            if ($translated_language && $translated_language !== $language) {
                echo '<meta property="og:locale:alternate" content="' . esc_attr($this->get_og_locale($translated_language)) . '" />' . "\n";
            }
        }
    }
    
    /**
     * Add JSON-LD structured data
     */
    public function add_structured_data() {
        if (!is_singular()) {
            return;
        }
        
        global $post;
        $language = get_post_meta($post->ID, '_nexus_ai_wp_translator_language', true);
        
        if (!$language) {
            return;
        }
        
        $structured_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'inLanguage' => $language,
            'headline' => get_the_title($post->ID),
            'url' => get_permalink($post->ID),
            'datePublished' => get_the_date('c', $post->ID),
            'dateModified' => get_the_modified_date('c', $post->ID)
        );
        
        // Add translation information
        $source_post_id = get_post_meta($post->ID, '_nexus_ai_wp_translator_source_post', true);
        if ($source_post_id) {
            $structured_data['translationOfWork'] = array(
                '@type' => 'Article',
                'url' => get_permalink($source_post_id),
                'inLanguage' => get_post_meta($source_post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en')
            );
        }
        
        // Add alternate language versions
        $translations = $this->db->get_post_translations($post->ID);
        if (!empty($translations)) {
            $workTranslations = array();
            
            foreach ($translations as $translation) {
                $translated_post_id = ($translation->source_post_id == $post->ID) 
                    ? $translation->translated_post_id 
                    : $translation->source_post_id;
                
                $translated_language = get_post_meta($translated_post_id, '_nexus_ai_wp_translator_language', true);
                
                if ($translated_language && $translated_language !== $language) {
                    $workTranslations[] = array(
                        '@type' => 'Article',
                        'url' => get_permalink($translated_post_id),
                        'inLanguage' => $translated_language
                    );
                }
            }
            
            if (!empty($workTranslations)) {
                $structured_data['workTranslation'] = $workTranslations;
            }
        }
        
        echo '<script type="application/ld+json">' . wp_json_encode($structured_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
    
    /**
     * Get language name for display
     */
    private function get_language_name($language_code) {
        $language_names = array(
            'en' => 'English',
            'es' => 'Español',
            'fr' => 'Français',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
            'ru' => 'Русский',
            'ja' => '日本語',
            'ko' => '한국어',
            'zh' => '中文'
        );
        
        return isset($language_names[$language_code]) ? $language_names[$language_code] : $language_code;
    }
    
    /**
     * Get Open Graph locale format
     */
    private function get_og_locale($language_code) {
        $og_locales = array(
            'en' => 'en_US',
            'es' => 'es_ES',
            'fr' => 'fr_FR',
            'de' => 'de_DE',
            'it' => 'it_IT',
            'pt' => 'pt_PT',
            'ru' => 'ru_RU',
            'ja' => 'ja_JP',
            'ko' => 'ko_KR',
            'zh' => 'zh_CN'
        );
        
        return isset($og_locales[$language_code]) ? $og_locales[$language_code] : $language_code . '_' . strtoupper($language_code);
    }

    /**
     * Add language sitemap rewrite rules
     */
    public function add_language_sitemap_rewrite() {
        add_rewrite_rule(
            '^sitemap-languages\.xml$',
            'index.php?nexus_ai_language_sitemap=1',
            'top'
        );

        add_rewrite_rule(
            '^sitemap-language-([a-z]{2})\.xml$',
            'index.php?nexus_ai_language_sitemap=1&language=$matches[1]',
            'top'
        );
    }

    /**
     * Handle language sitemap requests
     */
    public function handle_language_sitemap_request() {
        if (!get_query_var('nexus_ai_language_sitemap')) {
            return;
        }

        $language = get_query_var('language');

        if ($language) {
            $this->generate_language_specific_sitemap($language);
        } else {
            $this->generate_language_index_sitemap();
        }

        exit;
    }

    /**
     * Generate language index sitemap
     */
    private function generate_language_index_sitemap() {
        header('Content-Type: application/xml; charset=utf-8');

        $languages = $this->get_active_languages();

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($languages as $language) {
            $sitemap_url = home_url('/sitemap-language-' . $language . '.xml');
            $lastmod = $this->get_language_lastmod($language);

            echo '<sitemap>' . "\n";
            echo '<loc>' . esc_url($sitemap_url) . '</loc>' . "\n";
            if ($lastmod) {
                echo '<lastmod>' . esc_html($lastmod) . '</lastmod>' . "\n";
            }
            echo '</sitemap>' . "\n";
        }

        echo '</sitemapindex>' . "\n";
    }

    /**
     * Generate language-specific sitemap
     */
    private function generate_language_specific_sitemap($language) {
        header('Content-Type: application/xml; charset=utf-8');

        $posts = $this->get_posts_by_language($language);

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        foreach ($posts as $post) {
            $url = get_permalink($post->ID);
            $lastmod = get_the_modified_date('c', $post->ID);

            // Check if translation is newer
            $translation_date = get_post_meta($post->ID, '_nexus_ai_wp_translator_translation_date', true);
            if ($translation_date && strtotime($translation_date) > strtotime($post->post_modified)) {
                $lastmod = date('c', strtotime($translation_date));
            }

            echo '<url>' . "\n";
            echo '<loc>' . esc_url($url) . '</loc>' . "\n";
            echo '<lastmod>' . esc_html($lastmod) . '</lastmod>' . "\n";

            // Add hreflang alternates
            $translations = $this->db->get_post_translations($post->ID);
            foreach ($translations as $translation) {
                $translated_post_id = ($translation->source_post_id == $post->ID)
                    ? $translation->translated_post_id
                    : $translation->source_post_id;

                $translated_language = get_post_meta($translated_post_id, '_nexus_ai_wp_translator_language', true);

                if ($translated_language) {
                    $translated_url = get_permalink($translated_post_id);
                    echo '<xhtml:link rel="alternate" hreflang="' . esc_attr($translated_language) . '" href="' . esc_url($translated_url) . '" />' . "\n";
                }
            }

            echo '</url>' . "\n";
        }

        echo '</urlset>' . "\n";
    }

    /**
     * Get active languages
     */
    private function get_active_languages() {
        global $wpdb;

        $languages = $wpdb->get_col(
            "SELECT DISTINCT meta_value
             FROM {$wpdb->postmeta}
             WHERE meta_key = '_nexus_ai_wp_translator_language'
             AND meta_value != ''"
        );

        // Add source language
        $source_language = get_option('nexus_ai_wp_translator_source_language', 'en');
        if (!in_array($source_language, $languages)) {
            $languages[] = $source_language;
        }

        return array_filter($languages);
    }

    /**
     * Get posts by language
     */
    private function get_posts_by_language($language) {
        $args = array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_nexus_ai_wp_translator_language',
                    'value' => $language,
                    'compare' => '='
                )
            )
        );

        // For source language, also include posts without language meta
        if ($language === get_option('nexus_ai_wp_translator_source_language', 'en')) {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_nexus_ai_wp_translator_language',
                    'value' => $language,
                    'compare' => '='
                ),
                array(
                    'key' => '_nexus_ai_wp_translator_language',
                    'compare' => 'NOT EXISTS'
                )
            );
        }

        return get_posts($args);
    }

    /**
     * Get last modification date for language
     */
    private function get_language_lastmod($language) {
        global $wpdb;

        $lastmod = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(p.post_modified)
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_status = 'publish'
             AND p.post_type IN ('post', 'page')
             AND (pm.meta_key = '_nexus_ai_wp_translator_language' AND pm.meta_value = %s)",
            $language
        ));

        return $lastmod ? date('c', strtotime($lastmod)) : null;
    }

    /**
     * Translate meta descriptions
     */
    public function translate_meta_descriptions($post_id, $target_language) {
        // Get common meta description fields
        $meta_fields = array(
            '_yoast_wpseo_metadesc',
            '_aioseop_description',
            'description',
            '_genesis_description'
        );

        $api_handler = Nexus_AI_WP_Translator_API_Handler::get_instance();
        $source_language = get_post_meta($post_id, '_nexus_ai_wp_translator_language', true) ?: get_option('nexus_ai_wp_translator_source_language', 'en');

        foreach ($meta_fields as $meta_key) {
            $meta_value = get_post_meta($post_id, $meta_key, true);

            if (!empty($meta_value)) {
                $context = array(
                    'content_type' => 'meta_description',
                    'post_id' => $post_id
                );

                $translation_result = $api_handler->translate_content($meta_value, $source_language, $target_language, $context);

                if ($translation_result['success']) {
                    return $translation_result['translated_content'];
                }
            }
        }

        return null;
    }

    /**
     * Add meta description translation to translated posts
     */
    public function add_translated_meta_description($translated_post_id, $source_post_id, $target_language) {
        $translated_meta_desc = $this->translate_meta_descriptions($source_post_id, $target_language);

        if ($translated_meta_desc) {
            // Update common meta description fields
            $meta_fields = array(
                '_yoast_wpseo_metadesc',
                '_aioseop_description',
                'description',
                '_genesis_description'
            );

            foreach ($meta_fields as $meta_key) {
                if (get_post_meta($source_post_id, $meta_key, true)) {
                    update_post_meta($translated_post_id, $meta_key, $translated_meta_desc);
                }
            }
        }
    }

    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'nexus_ai_language_sitemap';
        $vars[] = 'language';
        return $vars;
    }
}
