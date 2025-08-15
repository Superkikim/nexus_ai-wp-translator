# Translation Feature Debugging Guide

## Current Analysis of Translation Plugin

Based on the codebase examination, here are the critical areas where translation functionality could break:

## 1. Critical Translation Components

### Core Translation Files:
- `includes/class-translation-manager.php` - Main translation logic
- `includes/class-api-handler.php` - Anthropic API integration
- `includes/class-admin.php` - Admin interface and AJAX handlers
- `assets/js/admin.js` - Frontend JavaScript for translation buttons
- `includes/class-database.php` - Database operations

### Key Functions to Check:
1. `ajax_translate_post()` - AJAX handler for translation requests
2. `translate_post()` - Core translation logic
3. `translate_content()` - API communication
4. `create_translated_post()` - Post creation after translation

## 2. Common Breaking Points

### A. AJAX Handler Issues
**File**: `includes/class-translation-manager.php`
**Function**: `ajax_translate_post()`
**Common Issues**:
- Missing nonce verification
- Incorrect capability checks
- Malformed response handling

```php
// Check for these patterns:
public function ajax_translate_post() {
    check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce'); // Must be present
    
    if (!current_user_can('edit_posts')) { // Capability check
        wp_send_json_error(__('Permission denied', 'nexus-ai-wp-translator'));
        return;
    }
    
    $post_id = intval($_POST['post_id']); // Input validation
    $target_languages = isset($_POST['target_languages']) ? (array) $_POST['target_languages'] : null;
}
```

### B. JavaScript Event Binding
**File**: `assets/js/admin.js`
**Common Issues**:
- Event handlers not properly bound
- Missing jQuery dependency
- Incorrect AJAX URL or data

```javascript
// Check for these patterns:
$(document).on('click', '#nexus-ai-wp-translate-post', function() {
    // Translation logic
});

// Verify AJAX call structure:
$.post(nexus_ai_wp_translator_ajax.ajax_url, {
    action: 'nexus_ai_wp_translate_post',
    post_id: postId,
    target_languages: targetLanguages,
    nonce: nexus_ai_wp_translator_ajax.nonce
})
```

### C. API Handler Problems
**File**: `includes/class-api-handler.php`
**Function**: `translate_content()`
**Common Issues**:
- Missing or invalid API key
- Incorrect API endpoint
- Malformed request body

```php
// Check API configuration:
private $api_endpoint = 'https://api.anthropic.com/v1/messages';
private $api_key; // Must be populated

// Verify request structure:
$body = array(
    'model' => $model,
    'max_tokens' => 4000,
    'messages' => array(
        array(
            'role' => 'user',
            'content' => $prompt
        )
    )
);
```

### D. Database Connection Issues
**File**: `includes/class-database.php`
**Common Issues**:
- Missing table creation
- Incorrect table names
- Failed database operations

## 3. Debugging Steps

### Step 1: Check Browser Console
1. Open browser developer tools
2. Go to Console tab
3. Try to trigger translation
4. Look for JavaScript errors

**Common Errors to Look For**:
- `nexus_ai_wp_translator_ajax is not defined`
- `Uncaught ReferenceError: $ is not defined`
- `404 Not Found` for AJAX requests

### Step 2: Check WordPress Debug Log
Enable WordPress debugging in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check `/wp-content/debug.log` for errors.

### Step 3: Verify Database Tables
Check if these tables exist:
- `wp_nexus_ai_wp_translations`
- `wp_nexus_ai_wp_translation_logs`
- `wp_nexus_ai_wp_user_preferences`

### Step 4: Test API Connection
Go to plugin settings and test API connection.

## 4. Most Likely Causes of Sudden Failure

### A. Script Enqueuing Issues
**Check**: `includes/class-admin.php` - `enqueue_admin_scripts()`
```php
// Verify scripts are enqueued on correct pages
public function enqueue_admin_scripts($hook) {
    $load_on_hooks = array('post.php', 'post-new.php');
    $is_our_page = strpos($hook, 'nexus-ai-wp-translator') !== false;
    $is_post_page = in_array($hook, $load_on_hooks);

    if (!$is_our_page && !$is_post_page) {
        return; // Scripts not loaded!
    }
}
```

### B. Hook Registration Problems
**Check**: `includes/class-translation-manager.php` - `init_hooks()`
```php
// Verify AJAX hooks are registered
add_action('wp_ajax_nexus_ai_wp_translate_post', array($this, 'ajax_translate_post'));
```

### C. Nonce Issues
**Check**: Nonce generation and verification
```php
// In admin.php:
wp_localize_script('nexus-ai-wp-translator-admin', 'nexus_ai_wp_translator_ajax', array(
    'nonce' => wp_create_nonce('nexus_ai_wp_translator_nonce')
));

// In AJAX handler:
check_ajax_referer('nexus_ai_wp_translator_nonce', 'nonce');
```

## 5. Quick Fix Checklist

1. **Verify Plugin Activation**: Ensure plugin is active and tables are created
2. **Check API Key**: Verify Anthropic API key is configured
3. **Test JavaScript**: Check browser console for errors
4. **Verify Permissions**: Ensure user has `edit_posts` capability
5. **Check Hooks**: Verify all WordPress hooks are properly registered
6. **Database Check**: Confirm all required tables exist

## 6. Emergency Fixes

### Fix 1: Re-register AJAX Handlers
Add to `functions.php` temporarily:
```php
add_action('wp_ajax_nexus_ai_wp_translate_post', function() {
    error_log('AJAX handler called');
    // Debug output
});
```

### Fix 2: Force Script Loading
Add to admin pages:
```php
wp_enqueue_script('jquery');
wp_enqueue_script('nexus-ai-wp-translator-admin');
```

### Fix 3: Reset Plugin
1. Deactivate plugin
2. Reactivate plugin
3. Reconfigure settings

## 7. Code Comparison Strategy

When comparing commits, focus on these files in order of importance:

1. **includes/class-translation-manager.php** - Core logic
2. **assets/js/admin.js** - Frontend functionality
3. **includes/class-admin.php** - Admin interface
4. **includes/class-api-handler.php** - API integration
5. **nexus-ai-wp-translator.php** - Plugin initialization

Look for changes in:
- Function signatures
- Hook registrations
- Variable names
- Class instantiation
- Error handling
- Nonce handling
- Capability checks

## 8. Testing Commands

### Test AJAX Endpoint Directly:
```bash
curl -X POST "https://yoursite.com/wp-admin/admin-ajax.php" \
  -d "action=nexus_ai_wp_translate_post&post_id=123&nonce=YOUR_NONCE"
```

### Test Database Connection:
```sql
SELECT * FROM wp_nexus_ai_wp_translation_logs ORDER BY created_at DESC LIMIT 10;
```

This guide should help identify where the translation functionality broke between commits.