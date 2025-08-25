<?php
/**
 * Basic Language Validation Tests
 * 
 * This file contains basic tests for the language validation system.
 * Run these tests to verify language functionality works correctly.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test Language Validation Functions
 */
class Nexus_AI_WP_Translator_Language_Tests {

    private $translation_manager;

    public function __construct() {
        global $nexus_ai_wp_translator;
        if ($nexus_ai_wp_translator && $nexus_ai_wp_translator->translation_manager) {
            $this->translation_manager = $nexus_ai_wp_translator->translation_manager;
        }
    }

    /**
     * Run all language validation tests
     */
    public function run_tests() {
        if (!$this->translation_manager) {
            echo "❌ Translation manager not available\n";
            return false;
        }

        $tests = array(
            'test_valid_languages',
            'test_invalid_languages', 
            'test_language_codes_array',
            'test_available_languages_structure',
            'test_popular_languages_included'
        );

        $passed = 0;
        $total = count($tests);

        echo "🧪 Running Language Validation Tests...\n\n";

        foreach ($tests as $test) {
            if (method_exists($this, $test)) {
                $result = $this->$test();
                if ($result) {
                    echo "✅ {$test}: PASSED\n";
                    $passed++;
                } else {
                    echo "❌ {$test}: FAILED\n";
                }
            }
        }

        echo "\n📊 Results: {$passed}/{$total} tests passed\n";
        return $passed === $total;
    }

    /**
     * Test valid language codes
     */
    private function test_valid_languages() {
        $valid_codes = array('en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'ar');
        
        foreach ($valid_codes as $code) {
            if (!$this->translation_manager->is_valid_language($code)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Test invalid language codes
     */
    private function test_invalid_languages() {
        $invalid_codes = array('xx', 'invalid', '123', '', null, 'auto', 'detect');
        
        foreach ($invalid_codes as $code) {
            if ($this->translation_manager->is_valid_language($code)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Test language codes array validation
     */
    private function test_language_codes_array() {
        // Valid array
        $valid_array = array('en', 'es', 'fr');
        if (!$this->translation_manager->validate_language_codes($valid_array)) {
            return false;
        }

        // Invalid array with bad code
        $invalid_array = array('en', 'xx', 'fr');
        if ($this->translation_manager->validate_language_codes($invalid_array)) {
            return false;
        }

        // Non-array input
        if ($this->translation_manager->validate_language_codes('en')) {
            return false;
        }

        return true;
    }

    /**
     * Test available languages structure
     */
    private function test_available_languages_structure() {
        $languages = $this->translation_manager->get_available_languages();
        
        // Should be an array
        if (!is_array($languages)) {
            return false;
        }

        // Should have at least 50 languages
        if (count($languages) < 50) {
            return false;
        }

        // Each key should be 2-letter code, each value should be string
        foreach ($languages as $code => $name) {
            if (!preg_match('/^[a-z]{2}$/', $code) || !is_string($name) || empty($name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Test that popular languages are included
     */
    private function test_popular_languages_included() {
        $languages = $this->translation_manager->get_available_languages();
        $popular = array('en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'ar', 'hi', 'nl');
        
        foreach ($popular as $code) {
            if (!array_key_exists($code, $languages)) {
                return false;
            }
        }

        return true;
    }
}

/**
 * Run tests if called directly (for CLI testing)
 */
if (defined('WP_CLI') && WP_CLI) {
    $tests = new Nexus_AI_WP_Translator_Language_Tests();
    $tests->run_tests();
}

/**
 * WordPress admin test runner
 */
function nexus_ai_wp_translator_run_language_tests() {
    if (!current_user_can('manage_options')) {
        wp_die('Permission denied');
    }

    $tests = new Nexus_AI_WP_Translator_Language_Tests();
    
    echo '<div class="wrap">';
    echo '<h1>Nexus AI WP Translator - Language Tests</h1>';
    echo '<pre>';
    
    $success = $tests->run_tests();
    
    echo '</pre>';
    
    if ($success) {
        echo '<div class="notice notice-success"><p><strong>All tests passed!</strong> Language validation system is working correctly.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p><strong>Some tests failed!</strong> Please check the language validation system.</p></div>';
    }
    
    echo '</div>';
}

// Add admin menu item for testing (only in debug mode)
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('admin_menu', function() {
        add_submenu_page(
            'nexus-ai-wp-translator-dashboard',
            'Language Tests',
            'Language Tests',
            'manage_options',
            'nexus-ai-wp-translator-tests',
            'nexus_ai_wp_translator_run_language_tests'
        );
    });
}
