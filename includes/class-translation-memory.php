<?php
/**
 * Translation Memory System
 * 
 * Stores and reuses previously translated segments for consistency and efficiency
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_Translation_Memory {
    
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
        // Hook into translation process
        add_filter('nexus_ai_wp_translator_before_translate', array($this, 'check_translation_memory'), 10, 3);
        add_action('nexus_ai_wp_translator_after_translate', array($this, 'store_translation_memory'), 10, 4);
        
        // AJAX handlers
        add_action('wp_ajax_nexus_ai_wp_search_translation_memory', array($this, 'ajax_search_translation_memory'));
        add_action('wp_ajax_nexus_ai_wp_manage_translation_memory', array($this, 'ajax_manage_translation_memory'));
        add_action('wp_ajax_nexus_ai_wp_import_translation_memory', array($this, 'ajax_import_translation_memory'));
        add_action('wp_ajax_nexus_ai_wp_export_translation_memory', array($this, 'ajax_export_translation_memory'));
        
        // Create tables on activation
        add_action('nexus_ai_wp_translator_activate', array($this, 'create_translation_memory_tables'));
    }
    
    /**
     * Create translation memory tables
     */
    public function create_translation_memory_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Translation memory table
        $tm_table = $wpdb->prefix . 'nexus_ai_translation_memory';
        $tm_sql = "CREATE TABLE $tm_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_text text NOT NULL,
            target_text text NOT NULL,
            source_language varchar(10) NOT NULL,
            target_language varchar(10) NOT NULL,
            source_hash varchar(64) NOT NULL,
            context_type varchar(50) DEFAULT 'general',
            domain varchar(100) DEFAULT 'general',
            quality_score int(11) DEFAULT NULL,
            usage_count int(11) DEFAULT 1,
            last_used datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_translation (source_hash, target_language),
            KEY source_language (source_language),
            KEY target_language (target_language),
            KEY context_type (context_type),
            KEY domain (domain),
            KEY last_used (last_used),
            FULLTEXT KEY source_text_ft (source_text)
        ) $charset_collate;";
        
        // Translation segments table for fuzzy matching
        $segments_table = $wpdb->prefix . 'nexus_ai_translation_segments';
        $segments_sql = "CREATE TABLE $segments_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            memory_id bigint(20) NOT NULL,
            segment_text text NOT NULL,
            segment_hash varchar(64) NOT NULL,
            segment_position int(11) NOT NULL,
            word_count int(11) NOT NULL,
            PRIMARY KEY (id),
            KEY memory_id (memory_id),
            KEY segment_hash (segment_hash),
            KEY word_count (word_count),
            FULLTEXT KEY segment_text_ft (segment_text),
            FOREIGN KEY (memory_id) REFERENCES $tm_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($tm_sql);
        dbDelta($segments_sql);
    }
    
    /**
     * Check translation memory before translating
     */
    public function check_translation_memory($content, $source_lang, $target_lang) {
        // Check for exact match first
        $exact_match = $this->find_exact_match($content, $source_lang, $target_lang);
        if ($exact_match) {
            $this->update_usage_count($exact_match['id']);
            return array(
                'found' => true,
                'type' => 'exact',
                'translation' => $exact_match['target_text'],
                'quality_score' => $exact_match['quality_score'],
                'usage_count' => $exact_match['usage_count'] + 1
            );
        }
        
        // Check for fuzzy matches
        $fuzzy_matches = $this->find_fuzzy_matches($content, $source_lang, $target_lang);
        if (!empty($fuzzy_matches)) {
            return array(
                'found' => true,
                'type' => 'fuzzy',
                'matches' => $fuzzy_matches
            );
        }
        
        return array('found' => false);
    }
    
    /**
     * Store translation in memory after successful translation
     */
    public function store_translation_memory($source_text, $target_text, $source_lang, $target_lang, $context = array()) {
        global $wpdb;
        
        $tm_table = $wpdb->prefix . 'nexus_ai_translation_memory';
        $segments_table = $wpdb->prefix . 'nexus_ai_translation_segments';
        
        $source_hash = hash('sha256', $source_text . $source_lang . $target_lang);
        $context_type = isset($context['type']) ? $context['type'] : 'general';
        $domain = isset($context['domain']) ? $context['domain'] : 'general';
        $quality_score = isset($context['quality_score']) ? $context['quality_score'] : null;
        
        // Check if already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $tm_table WHERE source_hash = %s AND target_language = %s",
            $source_hash, $target_lang
        ));
        
        if ($existing) {
            // Update existing entry
            $wpdb->update(
                $tm_table,
                array(
                    'target_text' => $target_text,
                    'usage_count' => $wpdb->get_var($wpdb->prepare(
                        "SELECT usage_count + 1 FROM $tm_table WHERE id = %d",
                        $existing->id
                    )),
                    'last_used' => current_time('mysql'),
                    'quality_score' => $quality_score
                ),
                array('id' => $existing->id),
                array('%s', '%d', '%s', '%d'),
                array('%d')
            );
            
            $memory_id = $existing->id;
        } else {
            // Insert new entry
            $result = $wpdb->insert(
                $tm_table,
                array(
                    'source_text' => $source_text,
                    'target_text' => $target_text,
                    'source_language' => $source_lang,
                    'target_language' => $target_lang,
                    'source_hash' => $source_hash,
                    'context_type' => $context_type,
                    'domain' => $domain,
                    'quality_score' => $quality_score
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
            );
            
            if ($result === false) {
                return false;
            }
            
            $memory_id = $wpdb->insert_id;
        }
        
        // Store segments for fuzzy matching
        $this->store_translation_segments($memory_id, $source_text);
        
        return $memory_id;
    }
    
    /**
     * Find exact match in translation memory
     */
    private function find_exact_match($content, $source_lang, $target_lang) {
        global $wpdb;
        
        $tm_table = $wpdb->prefix . 'nexus_ai_translation_memory';
        $source_hash = hash('sha256', $content . $source_lang . $target_lang);
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tm_table 
             WHERE source_hash = %s AND target_language = %s",
            $source_hash, $target_lang
        ), ARRAY_A);
    }
    
    /**
     * Find fuzzy matches in translation memory
     */
    private function find_fuzzy_matches($content, $source_lang, $target_lang, $threshold = 0.7) {
        global $wpdb;
        
        $tm_table = $wpdb->prefix . 'nexus_ai_translation_memory';
        
        // Use FULLTEXT search for initial filtering
        $candidates = $wpdb->get_results($wpdb->prepare(
            "SELECT *, MATCH(source_text) AGAINST(%s IN NATURAL LANGUAGE MODE) as relevance
             FROM $tm_table 
             WHERE source_language = %s AND target_language = %s
             AND MATCH(source_text) AGAINST(%s IN NATURAL LANGUAGE MODE) > 0
             ORDER BY relevance DESC
             LIMIT 10",
            $content, $source_lang, $target_lang, $content
        ), ARRAY_A);
        
        $matches = array();
        
        foreach ($candidates as $candidate) {
            $similarity = $this->calculate_similarity($content, $candidate['source_text']);
            
            if ($similarity >= $threshold) {
                $matches[] = array(
                    'id' => $candidate['id'],
                    'source_text' => $candidate['source_text'],
                    'target_text' => $candidate['target_text'],
                    'similarity' => $similarity,
                    'quality_score' => $candidate['quality_score'],
                    'usage_count' => $candidate['usage_count'],
                    'context_type' => $candidate['context_type']
                );
            }
        }
        
        // Sort by similarity
        usort($matches, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        return array_slice($matches, 0, 5); // Return top 5 matches
    }
    
    /**
     * Store translation segments for fuzzy matching
     */
    private function store_translation_segments($memory_id, $source_text) {
        global $wpdb;
        
        $segments_table = $wpdb->prefix . 'nexus_ai_translation_segments';
        
        // Delete existing segments
        $wpdb->delete($segments_table, array('memory_id' => $memory_id), array('%d'));
        
        // Split text into segments (sentences)
        $segments = $this->split_into_segments($source_text);
        
        foreach ($segments as $position => $segment) {
            $segment_hash = hash('sha256', $segment);
            $word_count = str_word_count($segment);
            
            $wpdb->insert(
                $segments_table,
                array(
                    'memory_id' => $memory_id,
                    'segment_text' => $segment,
                    'segment_hash' => $segment_hash,
                    'segment_position' => $position,
                    'word_count' => $word_count
                ),
                array('%d', '%s', '%s', '%d', '%d')
            );
        }
    }
    
    /**
     * Split text into segments
     */
    private function split_into_segments($text) {
        // Remove HTML tags for segmentation
        $clean_text = strip_tags($text);
        
        // Split by sentence endings
        $segments = preg_split('/[.!?]+\s+/', $clean_text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Filter out very short segments
        $segments = array_filter($segments, function($segment) {
            return strlen(trim($segment)) > 10;
        });
        
        return array_values($segments);
    }
    
    /**
     * Calculate similarity between two texts
     */
    private function calculate_similarity($text1, $text2) {
        // Normalize texts
        $text1 = strtolower(trim($text1));
        $text2 = strtolower(trim($text2));
        
        // Calculate Levenshtein distance
        $distance = levenshtein($text1, $text2);
        $max_length = max(strlen($text1), strlen($text2));
        
        if ($max_length === 0) {
            return 1.0;
        }
        
        $similarity = 1 - ($distance / $max_length);
        
        // Also calculate word-based similarity
        $words1 = array_unique(explode(' ', $text1));
        $words2 = array_unique(explode(' ', $text2));
        
        $common_words = array_intersect($words1, $words2);
        $total_words = array_unique(array_merge($words1, $words2));
        
        $word_similarity = count($common_words) / count($total_words);
        
        // Combine both similarities
        return ($similarity * 0.6) + ($word_similarity * 0.4);
    }
    
    /**
     * Update usage count for a translation memory entry
     */
    private function update_usage_count($memory_id) {
        global $wpdb;
        
        $tm_table = $wpdb->prefix . 'nexus_ai_translation_memory';
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $tm_table 
             SET usage_count = usage_count + 1, last_used = %s 
             WHERE id = %d",
            current_time('mysql'), $memory_id
        ));
    }
    
    /**
     * Search translation memory
     */
    public function search_translation_memory($query, $source_lang = null, $target_lang = null, $limit = 50) {
        global $wpdb;
        
        $tm_table = $wpdb->prefix . 'nexus_ai_translation_memory';
        
        $where_conditions = array();
        $where_values = array();
        
        if ($source_lang) {
            $where_conditions[] = "source_language = %s";
            $where_values[] = $source_lang;
        }
        
        if ($target_lang) {
            $where_conditions[] = "target_language = %s";
            $where_values[] = $target_lang;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        if (!empty($query)) {
            $where_clause .= !empty($where_conditions) ? ' AND ' : 'WHERE ';
            $where_clause .= "MATCH(source_text) AGAINST(%s IN NATURAL LANGUAGE MODE)";
            $where_values[] = $query;
        }
        
        $where_values[] = $limit;
        
        $sql = "SELECT * FROM $tm_table 
                $where_clause 
                ORDER BY last_used DESC 
                LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, ...$where_values), ARRAY_A);
    }
    
    /**
     * Get translation memory statistics
     */
    public function get_translation_memory_stats() {
        global $wpdb;
        
        $tm_table = $wpdb->prefix . 'nexus_ai_translation_memory';
        
        $stats = array();
        
        // Total entries
        $stats['total_entries'] = $wpdb->get_var("SELECT COUNT(*) FROM $tm_table");
        
        // Entries by language pair
        $stats['language_pairs'] = $wpdb->get_results(
            "SELECT 
                CONCAT(source_language, ' → ', target_language) as language_pair,
                COUNT(*) as count
             FROM $tm_table 
             GROUP BY source_language, target_language 
             ORDER BY count DESC"
        );
        
        // Most used translations
        $stats['most_used'] = $wpdb->get_results(
            "SELECT source_text, target_text, usage_count, source_language, target_language
             FROM $tm_table 
             ORDER BY usage_count DESC 
             LIMIT 10"
        );
        
        // Recent additions
        $stats['recent_additions'] = $wpdb->get_results(
            "SELECT source_text, target_text, source_language, target_language, created_at
             FROM $tm_table 
             ORDER BY created_at DESC 
             LIMIT 10"
        );
        
        // Quality distribution
        $stats['quality_distribution'] = $wpdb->get_results(
            "SELECT 
                CASE 
                    WHEN quality_score >= 90 THEN 'Excellent (90-100)'
                    WHEN quality_score >= 80 THEN 'Good (80-89)'
                    WHEN quality_score >= 70 THEN 'Fair (70-79)'
                    WHEN quality_score >= 60 THEN 'Poor (60-69)'
                    ELSE 'Unrated'
                END as quality_range,
                COUNT(*) as count
             FROM $tm_table 
             GROUP BY quality_range 
             ORDER BY MIN(COALESCE(quality_score, 0)) DESC"
        );
        
        return $stats;
    }
    
    /**
     * Clean up old or unused translation memory entries
     */
    public function cleanup_translation_memory($days_old = 365, $min_usage = 1) {
        global $wpdb;
        
        $tm_table = $wpdb->prefix . 'nexus_ai_translation_memory';
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $tm_table 
             WHERE last_used < DATE_SUB(NOW(), INTERVAL %d DAY) 
             AND usage_count < %d",
            $days_old, $min_usage
        ));
        
        return $deleted;
    }

    /**
     * AJAX: Search translation memory
     */
    public function ajax_search_translation_memory() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $query = sanitize_text_field($_POST['query']);
        $source_lang = sanitize_text_field($_POST['source_lang']);
        $target_lang = sanitize_text_field($_POST['target_lang']);
        $limit = intval($_POST['limit']) ?: 50;

        $results = $this->search_translation_memory($query, $source_lang, $target_lang, $limit);

        wp_send_json_success($results);
    }

    /**
     * AJAX: Manage translation memory
     */
    public function ajax_manage_translation_memory() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $action = sanitize_text_field($_POST['tm_action']);

        switch ($action) {
            case 'get_stats':
                $stats = $this->get_translation_memory_stats();
                wp_send_json_success($stats);
                break;

            case 'cleanup':
                $days_old = intval($_POST['days_old']) ?: 365;
                $min_usage = intval($_POST['min_usage']) ?: 1;
                $deleted = $this->cleanup_translation_memory($days_old, $min_usage);
                wp_send_json_success(array('deleted' => $deleted));
                break;

            case 'delete_entry':
                $entry_id = intval($_POST['entry_id']);
                $result = $this->delete_translation_memory_entry($entry_id);
                wp_send_json_success(array('deleted' => $result));
                break;

            default:
                wp_send_json_error(__('Invalid action', 'nexus-ai-wp-translator'));
        }
    }

    /**
     * AJAX: Import translation memory
     */
    public function ajax_import_translation_memory() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        if (!isset($_FILES['tm_file']) || $_FILES['tm_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('File upload failed', 'nexus-ai-wp-translator'));
        }

        $file_path = $_FILES['tm_file']['tmp_name'];
        $file_type = sanitize_text_field($_POST['file_type']);

        $result = $this->import_translation_memory_file($file_path, $file_type);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX: Export translation memory
     */
    public function ajax_export_translation_memory() {
        check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nexus-ai-wp-translator'));
        }

        $format = sanitize_text_field($_POST['format']);
        $source_lang = sanitize_text_field($_POST['source_lang']);
        $target_lang = sanitize_text_field($_POST['target_lang']);

        $this->export_translation_memory($format, $source_lang, $target_lang);
    }

    /**
     * Delete translation memory entry
     */
    private function delete_translation_memory_entry($entry_id) {
        global $wpdb;

        $tm_table = $wpdb->prefix . 'nexus_ai_translation_memory';

        return $wpdb->delete($tm_table, array('id' => $entry_id), array('%d'));
    }

    /**
     * Import translation memory from file
     */
    private function import_translation_memory_file($file_path, $file_type) {
        if ($file_type === 'csv') {
            return $this->import_csv_translation_memory($file_path);
        } elseif ($file_type === 'tmx') {
            return $this->import_tmx_translation_memory($file_path);
        } else {
            return array('success' => false, 'message' => __('Unsupported file type', 'nexus-ai-wp-translator'));
        }
    }

    /**
     * Import CSV translation memory
     */
    private function import_csv_translation_memory($file_path) {
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return array('success' => false, 'message' => __('Could not open file', 'nexus-ai-wp-translator'));
        }

        $imported = 0;
        $errors = 0;

        // Skip header row
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 4) {
                $source_text = $data[0];
                $target_text = $data[1];
                $source_lang = $data[2];
                $target_lang = $data[3];

                $result = $this->store_translation_memory($source_text, $target_text, $source_lang, $target_lang);

                if ($result) {
                    $imported++;
                } else {
                    $errors++;
                }
            }
        }

        fclose($handle);

        return array(
            'success' => true,
            'imported' => $imported,
            'errors' => $errors,
            'message' => sprintf(__('Imported %d entries with %d errors', 'nexus-ai-wp-translator'), $imported, $errors)
        );
    }

    /**
     * Import TMX translation memory
     */
    private function import_tmx_translation_memory($file_path) {
        $xml = simplexml_load_file($file_path);
        if (!$xml) {
            return array('success' => false, 'message' => __('Invalid TMX file', 'nexus-ai-wp-translator'));
        }

        $imported = 0;
        $errors = 0;

        foreach ($xml->body->tu as $tu) {
            $segments = array();

            foreach ($tu->tuv as $tuv) {
                $lang = (string)$tuv['xml:lang'];
                $text = (string)$tuv->seg;
                $segments[$lang] = $text;
            }

            if (count($segments) >= 2) {
                $languages = array_keys($segments);
                $source_lang = $languages[0];
                $target_lang = $languages[1];

                $result = $this->store_translation_memory(
                    $segments[$source_lang],
                    $segments[$target_lang],
                    $source_lang,
                    $target_lang
                );

                if ($result) {
                    $imported++;
                } else {
                    $errors++;
                }
            }
        }

        return array(
            'success' => true,
            'imported' => $imported,
            'errors' => $errors,
            'message' => sprintf(__('Imported %d entries with %d errors', 'nexus-ai-wp-translator'), $imported, $errors)
        );
    }

    /**
     * Export translation memory
     */
    private function export_translation_memory($format, $source_lang = null, $target_lang = null) {
        $entries = $this->search_translation_memory('', $source_lang, $target_lang, 10000);

        if ($format === 'csv') {
            $this->export_csv_translation_memory($entries, $source_lang, $target_lang);
        } elseif ($format === 'tmx') {
            $this->export_tmx_translation_memory($entries, $source_lang, $target_lang);
        }
    }

    /**
     * Export CSV translation memory
     */
    private function export_csv_translation_memory($entries, $source_lang, $target_lang) {
        $filename = 'translation-memory-' . ($source_lang ?: 'all') . '-' . ($target_lang ?: 'all') . '-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Header
        fputcsv($output, array('Source Text', 'Target Text', 'Source Language', 'Target Language', 'Quality Score', 'Usage Count', 'Last Used'));

        foreach ($entries as $entry) {
            fputcsv($output, array(
                $entry['source_text'],
                $entry['target_text'],
                $entry['source_language'],
                $entry['target_language'],
                $entry['quality_score'],
                $entry['usage_count'],
                $entry['last_used']
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * Export TMX translation memory
     */
    private function export_tmx_translation_memory($entries, $source_lang, $target_lang) {
        $filename = 'translation-memory-' . ($source_lang ?: 'all') . '-' . ($target_lang ?: 'all') . '-' . date('Y-m-d') . '.tmx';

        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<tmx version="1.4">' . "\n";
        echo '<header>' . "\n";
        echo '<prop type="x-filename">' . $filename . '</prop>' . "\n";
        echo '</header>' . "\n";
        echo '<body>' . "\n";

        foreach ($entries as $entry) {
            echo '<tu>' . "\n";
            echo '<tuv xml:lang="' . esc_attr($entry['source_language']) . '">' . "\n";
            echo '<seg>' . esc_html($entry['source_text']) . '</seg>' . "\n";
            echo '</tuv>' . "\n";
            echo '<tuv xml:lang="' . esc_attr($entry['target_language']) . '">' . "\n";
            echo '<seg>' . esc_html($entry['target_text']) . '</seg>' . "\n";
            echo '</tuv>' . "\n";
            echo '</tu>' . "\n";
        }

        echo '</body>' . "\n";
        echo '</tmx>' . "\n";
        exit;
    }
}
