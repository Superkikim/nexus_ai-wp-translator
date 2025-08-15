<?php
/**
 * Translation Debug Script
 * Place this file in your WordPress root directory and access via browser
 * URL: https://yoursite.com/debug-translation.php
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Translation Plugin Debug Report</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{color:red;} .success{color:green;} .warning{color:orange;} pre{background:#f5f5f5;padding:10px;border-radius:4px;}</style>";

// 1. Check if plugin is active
echo "<h2>1. Plugin Status</h2>";
if (is_plugin_active('nexus-ai-wp-translator/nexus-ai-wp-translator.php')) {
    echo "<span class='success'>✓ Plugin is active</span><br>";
} else {
    echo "<span class='error'>✗ Plugin is not active</span><br>";
}

// 2. Check database tables
echo "<h2>2. Database Tables</h2>";
global $wpdb;
$tables = array(
    'nexus_ai_wp_translations',
    'nexus_ai_wp_translation_logs',
    'nexus_ai_wp_user_preferences'
);

foreach ($tables as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$full_table_name}");
        echo "<span class='success'>✓ {$full_table_name} exists ({$count} records)</span><br>";
    } else {
        echo "<span class='error'>✗ {$full_table_name} missing</span><br>";
    }
}

// 3. Check plugin options
echo "<h2>3. Plugin Configuration</h2>";
$options = array(
    'nexus_ai_wp_translator_api_key' => 'API Key',
    'nexus_ai_wp_translator_model' => 'Model',
    'nexus_ai_wp_translator_source_language' => 'Source Language',
    'nexus_ai_wp_translator_target_languages' => 'Target Languages'
);

foreach ($options as $option => $label) {
    $value = get_option($option);
    if ($value) {
        if ($option === 'nexus_ai_wp_translator_api_key') {
            $display_value = substr($value, 0, 10) . '...' . substr($value, -4);
        } elseif (is_array($value)) {
            $display_value = implode(', ', $value);
        } else {
            $display_value = $value;
        }
        echo "<span class='success'>✓ {$label}: {$display_value}</span><br>";
    } else {
        echo "<span class='error'>✗ {$label}: Not configured</span><br>";
    }
}

// 4. Check class existence
echo "<h2>4. Class Availability</h2>";
$classes = array(
    'Nexus_AI_WP_Translator_Manager',
    'Nexus_AI_WP_Translator_Database',
    'Nexus_AI_WP_Translator_API_Handler',
    'Nexus_AI_WP_Translator_Admin',
    'Nexus_AI_WP_Translator_Frontend'
);

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "<span class='success'>✓ {$class} loaded</span><br>";
    } else {
        echo "<span class='error'>✗ {$class} not found</span><br>";
    }
}

// 5. Check AJAX actions
echo "<h2>5. AJAX Actions</h2>";
global $wp_filter;
$ajax_actions = array(
    'wp_ajax_nexus_ai_wp_translate_post',
    'wp_ajax_nexus_ai_wp_test_api',
    'wp_ajax_nexus_ai_wp_get_models'
);

foreach ($ajax_actions as $action) {
    if (isset($wp_filter[$action])) {
        echo "<span class='success'>✓ {$action} registered</span><br>";
    } else {
        echo "<span class='error'>✗ {$action} not registered</span><br>";
    }
}

// 6. Test API connection
echo "<h2>6. API Connection Test</h2>";
$api_key = get_option('nexus_ai_wp_translator_api_key');
if ($api_key) {
    $api_handler = Nexus_AI_WP_Translator_API_Handler::get_instance();
    $test_result = $api_handler->test_api_connection();
    if ($test_result['success']) {
        echo "<span class='success'>✓ API connection successful</span><br>";
    } else {
        echo "<span class='error'>✗ API connection failed: " . $test_result['message'] . "</span><br>";
    }
} else {
    echo "<span class='warning'>⚠ API key not configured</span><br>";
}

// 7. Check recent logs
echo "<h2>7. Recent Translation Logs</h2>";
if (class_exists('Nexus_AI_WP_Translator_Database')) {
    $db = Nexus_AI_WP_Translator_Database::get_instance();
    $logs = $db->get_translation_logs(5);
    if ($logs) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Date</th><th>Post ID</th><th>Action</th><th>Status</th><th>Message</th></tr>";
        foreach ($logs as $log) {
            $status_class = $log->status === 'success' ? 'success' : ($log->status === 'error' ? 'error' : 'warning');
            echo "<tr>";
            echo "<td>" . $log->created_at . "</td>";
            echo "<td>" . $log->post_id . "</td>";
            echo "<td>" . $log->action . "</td>";
            echo "<td class='{$status_class}'>" . $log->status . "</td>";
            echo "<td>" . substr($log->message, 0, 100) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<span class='warning'>⚠ No translation logs found</span><br>";
    }
}

// 8. JavaScript check
echo "<h2>8. JavaScript Debug</h2>";
echo "<p>Check browser console for JavaScript errors when trying to translate.</p>";
echo "<script>
console.log('Translation Debug: Script loaded');
if (typeof jQuery !== 'undefined') {
    console.log('Translation Debug: jQuery available');
} else {
    console.error('Translation Debug: jQuery not available');
}
if (typeof nexus_ai_wp_translator_ajax !== 'undefined') {
    console.log('Translation Debug: AJAX object available', nexus_ai_wp_translator_ajax);
} else {
    console.error('Translation Debug: AJAX object not available');
}
</script>";

echo "<h2>Debug Complete</h2>";
echo "<p>Check the results above and browser console for more information.</p>";
?>