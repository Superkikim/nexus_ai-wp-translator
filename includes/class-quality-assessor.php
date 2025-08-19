<?php
/**
 * Translation Quality Assessor
 * 
 * Analyzes translated content for quality, completeness, and consistency
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_Quality_Assessor {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Quality assessment is performed on-demand
    }
    
    /**
     * Assess translation quality
     */
    public function assess_translation_quality($original_content, $translated_content, $source_lang, $target_lang) {
        $assessment = array(
            'overall_score' => 0,
            'completeness_score' => 0,
            'consistency_score' => 0,
            'structure_score' => 0,
            'length_score' => 0,
            'issues' => array(),
            'suggestions' => array(),
            'metrics' => array(),
            'grade' => 'F'
        );
        
        // Perform various quality checks
        $completeness = $this->assess_completeness($original_content, $translated_content);
        $consistency = $this->assess_consistency($original_content, $translated_content);
        $structure = $this->assess_structure($original_content, $translated_content);
        $length = $this->assess_length($original_content, $translated_content, $source_lang, $target_lang);
        
        // Combine scores
        $assessment['completeness_score'] = $completeness['score'];
        $assessment['consistency_score'] = $consistency['score'];
        $assessment['structure_score'] = $structure['score'];
        $assessment['length_score'] = $length['score'];
        
        // Calculate overall score (weighted average)
        $assessment['overall_score'] = round(
            ($completeness['score'] * 0.3) +
            ($consistency['score'] * 0.25) +
            ($structure['score'] * 0.25) +
            ($length['score'] * 0.2)
        );
        
        // Combine issues and suggestions
        $assessment['issues'] = array_merge(
            $completeness['issues'],
            $consistency['issues'],
            $structure['issues'],
            $length['issues']
        );
        
        $assessment['suggestions'] = array_merge(
            $completeness['suggestions'],
            $consistency['suggestions'],
            $structure['suggestions'],
            $length['suggestions']
        );
        
        // Set grade based on overall score
        $assessment['grade'] = $this->calculate_grade($assessment['overall_score']);
        
        // Add metrics
        $assessment['metrics'] = array(
            'original_word_count' => $this->count_words($original_content),
            'translated_word_count' => $this->count_words($translated_content),
            'original_char_count' => mb_strlen(strip_tags($original_content)),
            'translated_char_count' => mb_strlen(strip_tags($translated_content)),
            'html_tags_original' => $this->count_html_tags($original_content),
            'html_tags_translated' => $this->count_html_tags($translated_content),
            'assessment_timestamp' => time()
        );
        
        return $assessment;
    }
    
    /**
     * Assess completeness of translation
     */
    private function assess_completeness($original, $translated) {
        $result = array(
            'score' => 100,
            'issues' => array(),
            'suggestions' => array()
        );
        
        // Check if translation is empty
        if (empty(trim(strip_tags($translated)))) {
            $result['score'] = 0;
            $result['issues'][] = __('Translation is empty', 'nexus-ai-wp-translator');
            $result['suggestions'][] = __('Ensure the translation process completed successfully', 'nexus-ai-wp-translator');
            return $result;
        }
        
        // Check for significantly shorter translation (might indicate incomplete translation)
        $original_length = mb_strlen(strip_tags($original));
        $translated_length = mb_strlen(strip_tags($translated));
        
        if ($translated_length < ($original_length * 0.3)) {
            $result['score'] -= 40;
            $result['issues'][] = __('Translation appears significantly shorter than original', 'nexus-ai-wp-translator');
            $result['suggestions'][] = __('Review translation for missing content', 'nexus-ai-wp-translator');
        } elseif ($translated_length < ($original_length * 0.5)) {
            $result['score'] -= 20;
            $result['issues'][] = __('Translation is notably shorter than original', 'nexus-ai-wp-translator');
            $result['suggestions'][] = __('Verify all content has been translated', 'nexus-ai-wp-translator');
        }
        
        // Check for untranslated segments (common patterns that might indicate incomplete translation)
        $untranslated_patterns = array(
            '/\[UNTRANSLATED\]/',
            '/\[TODO\]/',
            '/\[MISSING\]/',
            '/\.\.\.$/',
            '/\[continue\]/i',
            '/\[more\]/i'
        );
        
        foreach ($untranslated_patterns as $pattern) {
            if (preg_match($pattern, $translated)) {
                $result['score'] -= 30;
                $result['issues'][] = __('Translation contains untranslated or incomplete segments', 'nexus-ai-wp-translator');
                $result['suggestions'][] = __('Complete the translation or retry the translation process', 'nexus-ai-wp-translator');
                break;
            }
        }
        
        return $result;
    }
    
    /**
     * Assess consistency of translation
     */
    private function assess_consistency($original, $translated) {
        $result = array(
            'score' => 100,
            'issues' => array(),
            'suggestions' => array()
        );
        
        // Check HTML tag consistency
        $original_tags = $this->extract_html_tags($original);
        $translated_tags = $this->extract_html_tags($translated);
        
        $missing_tags = array_diff($original_tags, $translated_tags);
        $extra_tags = array_diff($translated_tags, $original_tags);
        
        if (!empty($missing_tags)) {
            $result['score'] -= 20;
            $result['issues'][] = sprintf(__('Missing HTML tags: %s', 'nexus-ai-wp-translator'), implode(', ', $missing_tags));
            $result['suggestions'][] = __('Ensure all HTML formatting is preserved in translation', 'nexus-ai-wp-translator');
        }
        
        if (!empty($extra_tags)) {
            $result['score'] -= 10;
            $result['issues'][] = sprintf(__('Extra HTML tags: %s', 'nexus-ai-wp-translator'), implode(', ', $extra_tags));
            $result['suggestions'][] = __('Remove unnecessary HTML tags from translation', 'nexus-ai-wp-translator');
        }
        
        // Check for mixed languages (basic detection)
        if ($this->contains_mixed_languages($translated)) {
            $result['score'] -= 25;
            $result['issues'][] = __('Translation appears to contain mixed languages', 'nexus-ai-wp-translator');
            $result['suggestions'][] = __('Ensure translation is consistent in target language', 'nexus-ai-wp-translator');
        }
        
        return $result;
    }
    
    /**
     * Assess structure preservation
     */
    private function assess_structure($original, $translated) {
        $result = array(
            'score' => 100,
            'issues' => array(),
            'suggestions' => array()
        );
        
        // Count paragraphs
        $original_paragraphs = substr_count($original, '</p>') + substr_count($original, "\n\n");
        $translated_paragraphs = substr_count($translated, '</p>') + substr_count($translated, "\n\n");
        
        if (abs($original_paragraphs - $translated_paragraphs) > 1) {
            $result['score'] -= 15;
            $result['issues'][] = __('Paragraph structure differs significantly from original', 'nexus-ai-wp-translator');
            $result['suggestions'][] = __('Maintain original paragraph structure in translation', 'nexus-ai-wp-translator');
        }
        
        // Check for preserved links
        $original_links = preg_match_all('/<a[^>]*href=["\']([^"\']*)["\'][^>]*>/', $original);
        $translated_links = preg_match_all('/<a[^>]*href=["\']([^"\']*)["\'][^>]*>/', $translated);
        
        if ($original_links !== $translated_links) {
            $result['score'] -= 20;
            $result['issues'][] = __('Number of links differs from original', 'nexus-ai-wp-translator');
            $result['suggestions'][] = __('Ensure all links are preserved in translation', 'nexus-ai-wp-translator');
        }
        
        return $result;
    }
    
    /**
     * Assess length appropriateness
     */
    private function assess_length($original, $translated, $source_lang, $target_lang) {
        $result = array(
            'score' => 100,
            'issues' => array(),
            'suggestions' => array()
        );
        
        $original_words = $this->count_words($original);
        $translated_words = $this->count_words($translated);
        
        if ($original_words === 0) {
            return $result; // Can't assess if original is empty
        }
        
        $ratio = $translated_words / $original_words;
        
        // Expected ratios for different language pairs (rough estimates)
        $expected_ratios = $this->get_expected_length_ratios($source_lang, $target_lang);
        
        if ($ratio < $expected_ratios['min']) {
            $result['score'] -= 25;
            $result['issues'][] = __('Translation is unusually short for this language pair', 'nexus-ai-wp-translator');
            $result['suggestions'][] = __('Review for missing content or incomplete translation', 'nexus-ai-wp-translator');
        } elseif ($ratio > $expected_ratios['max']) {
            $result['score'] -= 15;
            $result['issues'][] = __('Translation is unusually long for this language pair', 'nexus-ai-wp-translator');
            $result['suggestions'][] = __('Review for unnecessary additions or verbose translation', 'nexus-ai-wp-translator');
        }
        
        return $result;
    }
    
    /**
     * Calculate grade based on score
     */
    private function calculate_grade($score) {
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 75) return 'B+';
        if ($score >= 70) return 'B';
        if ($score >= 65) return 'B-';
        if ($score >= 60) return 'C+';
        if ($score >= 55) return 'C';
        if ($score >= 50) return 'C-';
        if ($score >= 40) return 'D';
        return 'F';
    }
    
    /**
     * Count words in text
     */
    private function count_words($text) {
        $clean_text = strip_tags($text);
        $clean_text = preg_replace('/\s+/', ' ', $clean_text);
        $words = explode(' ', trim($clean_text));
        return count(array_filter($words));
    }
    
    /**
     * Count HTML tags
     */
    private function count_html_tags($text) {
        return preg_match_all('/<[^>]+>/', $text);
    }
    
    /**
     * Extract HTML tags
     */
    private function extract_html_tags($text) {
        preg_match_all('/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/', $text, $matches);
        return array_unique($matches[1]);
    }
    
    /**
     * Check for mixed languages (basic detection)
     */
    private function contains_mixed_languages($text) {
        // Basic check for common mixed language patterns
        $patterns = array(
            '/[a-zA-Z]+\s+[^\x00-\x7F]+/',  // Latin + non-Latin
            '/[^\x00-\x7F]+\s+[a-zA-Z]+/',  // non-Latin + Latin
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get expected length ratios for language pairs
     */
    private function get_expected_length_ratios($source_lang, $target_lang) {
        // Rough estimates based on linguistic characteristics
        $ratios = array(
            'en_es' => array('min' => 0.8, 'max' => 1.3),  // English to Spanish
            'en_fr' => array('min' => 0.8, 'max' => 1.3),  // English to French
            'en_de' => array('min' => 0.7, 'max' => 1.2),  // English to German
            'en_it' => array('min' => 0.8, 'max' => 1.3),  // English to Italian
            'en_pt' => array('min' => 0.8, 'max' => 1.3),  // English to Portuguese
            'en_ru' => array('min' => 0.6, 'max' => 1.1),  // English to Russian
        );
        
        $key = $source_lang . '_' . $target_lang;
        
        return isset($ratios[$key]) ? $ratios[$key] : array('min' => 0.5, 'max' => 2.0);
    }
}
