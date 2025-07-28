# Nexus AI WP Translator WordPress Plugin

A comprehensive WordPress plugin that automatically translates posts using Claude AI API with advanced management features, user preferences, and SEO optimization.

## Features

### 🚀 Core Functionality
- **Automatic Translation**: Translate posts upon publication using Claude AI
- **Bidirectional Linking**: Maintain relationships between source and translated posts
- **Language Detection**: Automatic detection based on browser locale and user preferences
- **Status Synchronization**: Sync post status changes across translations
- **Infinite Loop Prevention**: Built-in throttling and safeguards to prevent costly API loops

### 🎛️ Admin Panel
- **API Key Management**: Secure configuration with connection testing
- **Language Configuration**: Set source and multiple target languages
- **Translation Dashboard**: Monitor queue status, success rates, and processing logs
- **Post Relationship Manager**: Visual interface for managing translation links
- **Advanced Settings**: Throttling, caching, retry mechanisms, and more

### 🎨 User Experience
- **Frontend Language Switcher**: Widget and shortcode support
- **Automatic Content Serving**: Based on browser Accept-Language header
- **User Preferences**: Store language choices for logged-in users
- **SEO-Friendly URLs**: Optional language-specific URL structure
- **Responsive Design**: Works perfectly on all devices

### 🔧 Technical Features
- **WordPress Standards**: Follows all WordPress coding standards and best practices
- **Object-Oriented Architecture**: Clean, maintainable code structure
- **Database Optimization**: Custom tables for translations, logs, and preferences
- **Caching System**: Reduces API calls and improves performance
- **Error Handling**: Comprehensive error handling with retry mechanisms
- **Security**: Proper sanitization, validation, and nonce verification

## Installation

### Automatic Installation (Recommended)

1. Upload the plugin files to `/wp-content/plugins/claude-translator/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to **Claude Translator** in your WordPress admin menu
4. Configure your Claude AI API key in **Settings**

### Manual Installation

1. Download the plugin files
2. Upload via WordPress admin: **Plugins** → **Add New** → **Upload Plugin**
3. Activate the plugin
4. Configure settings as described above

## Configuration

### 1. API Key Setup

1. Get your Nexus AI API key from [Anthropic Console](https://console.anthropic.com/)
2. Go to **Nexus AI WP Translator** → **Settings** → **API Settings**
3. Enter your API key and click **Test Connection**
4. Save settings once connection is verified

### 2. Language Configuration

1. Navigate to **Settings** → **Languages** tab
2. Set your **Source Language** (default content language)
3. Select **Target Languages** for automatic translation
4. Save settings

### 3. Performance Settings

Configure throttling to prevent excessive API usage:

- **API Call Limit**: Maximum calls per time period (default: 10)
- **Time Period**: Rate limit period in seconds (default: 3600)
- **Retry Attempts**: Failed request retries (default: 3)
- **Cache Translations**: Enable/disable translation caching

## Usage

### Automatic Translation

Once configured, posts will automatically translate to all target languages when published. You can:

- Monitor progress in the **Dashboard**
- View translation status in post edit screens
- Check logs for any issues

### Manual Translation

From any post edit screen:

1. Scroll to the **Nexus AI WP Translator** meta box
2. Select target languages
3. Click **Translate Now**
4. Monitor progress and view results

### Language Switching

#### Widget
Add the **Nexus AI WP Language Switcher** widget to any widget area.

#### Shortcode
Use `[nexus_ai_wp_language_switcher]` anywhere in your content.

Parameters:
- `style`: "dropdown" or "list" (default: dropdown)
- `show_current`: "yes" or "no" (default: yes)

#### Template Function
```php
<?php
if (function_exists('nexus_ai_wp_translator_language_switcher')) {
    nexus_ai_wp_translator_language_switcher();
}
?>
```

## Database Schema

The plugin creates three custom tables:

### wp_nexus_ai_wp_translations
Stores translation relationships between posts.

```sql
CREATE TABLE wp_nexus_ai_wp_translations (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    source_post_id bigint(20) NOT NULL,
    translated_post_id bigint(20) NOT NULL,
    source_language varchar(10) NOT NULL,
    target_language varchar(10) NOT NULL,
    status varchar(20) DEFAULT 'pending',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

### wp_nexus_ai_wp_translation_logs
Tracks all translation activities and errors.

```sql
CREATE TABLE wp_nexus_ai_wp_translation_logs (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    action varchar(50) NOT NULL,
    status varchar(20) NOT NULL,
    message text,
    api_calls_count int(11) DEFAULT 0,
    processing_time float DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

### wp_nexus_ai_wp_user_preferences
Stores user language preferences.

```sql
CREATE TABLE wp_nexus_ai_wp_user_preferences (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    preferred_language varchar(10) NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY user_id (user_id)
);
```

## Supported Languages

- English (en)
- Spanish (es)
- French (fr)
- German (de)
- Italian (it)
- Portuguese (pt)
- Russian (ru)
- Japanese (ja)
- Korean (ko)
- Chinese (zh)
- Arabic (ar)
- Hindi (hi)
- Dutch (nl)
- Swedish (sv)
- Danish (da)
- Norwegian (no)
- Finnish (fi)
- Polish (pl)
- Czech (cs)
- Hungarian (hu)

## Hooks and Filters

### Actions

```php
// Before translation starts
do_action('nexus_ai_wp_translator_before_translate', $post_id, $target_languages);

// After translation completes
do_action('nexus_ai_wp_translator_after_translate', $post_id, $results);

// Before post relationship is stored
do_action('nexus_ai_wp_translator_before_store_relationship', $source_id, $translated_id, $source_lang, $target_lang);
```

### Filters

```php
// Modify translation content before storing
$content = apply_filters('nexus_ai_wp_translator_translated_content', $content, $source_content, $target_language);

// Modify supported languages
$languages = apply_filters('nexus_ai_wp_translator_supported_languages', $languages);

// Modify API request parameters
$params = apply_filters('nexus_ai_wp_translator_api_params', $params, $content, $source_lang, $target_lang);
```

## Troubleshooting

### Common Issues

#### API Connection Failed
- Verify your API key is correct
- Check your server can make outbound HTTPS requests
- Ensure Claude AI API is accessible from your server

#### Translation Not Working
- Check API call limits in Dashboard
- Verify source and target languages are different
- Review Translation Logs for specific errors

#### Missing Translations
- Ensure auto-translation is enabled in Settings
- Check that target languages are properly configured
- Verify post type is not excluded from translation

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `/wp-content/debug.log` for Nexus AI WP Translator errors.

## Performance Optimization

### Best Practices

1. **Configure Appropriate Throttling**: Set reasonable API limits based on your usage
2. **Enable Caching**: Reduces redundant API calls
3. **Monitor API Usage**: Regular check dashboard statistics
4. **Use Selective Translation**: Don't translate every post automatically if not needed

### Server Requirements

- PHP 7.4 or higher
- WordPress 5.0 or higher
- cURL extension enabled
- Adequate memory limit (256MB recommended)
- Reliable internet connection for API calls

## Security

The plugin implements multiple security measures:

- **Nonce Verification**: All AJAX requests are protected
- **Capability Checks**: User permissions are verified
- **Data Sanitization**: All input is properly sanitized
- **SQL Injection Prevention**: Prepared statements used throughout
- **XSS Protection**: Output is properly escaped

## Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch
3. Follow WordPress coding standards
4. Add proper documentation
5. Include tests where applicable
6. Submit a pull request

## Support

For support and questions:

1. Check the **Translation Logs** in your admin panel
2. Review this documentation
3. Check WordPress debug logs
4. Contact support with specific error messages and steps to reproduce

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- Automatic translation with Claude AI
- Comprehensive admin interface
- Language switching functionality
- SEO-friendly URLs
- Translation relationship management
- Performance optimization and caching
- Security hardening
- Extensive logging and monitoring

---

**Made with ❤️ for the WordPress community**

For more information about Nexus AI, visit [Anthropic](https://www.anthropic.com/).