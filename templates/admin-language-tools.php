<?php
/**
 * Language Tools Admin Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Language Tools', 'nexus-ai-wp-translator'); ?></h1>
    
    <div class="nexus-ai-wp-language-tools">
        
        <!-- Fix Undefined Languages -->
        <div class="nexus-ai-wp-tool-section">
            <h2><?php _e('Fix Language Detection Issues', 'nexus-ai-wp-translator'); ?></h2>
            <p class="description">
                <?php _e('Some posts may not have a language defined. Use this tool to assign a default language to all posts without language metadata.', 'nexus-ai-wp-translator'); ?>
            </p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="nexus-ai-wp-bulk-language-select"><?php _e('Assign Language:', 'nexus-ai-wp-translator'); ?></label>
                    </th>
                    <td>
                        <select id="nexus-ai-wp-bulk-language-select">
                            <?php
                            $available_languages = array(
                                'en' => 'English',
                                'es' => 'Spanish', 
                                'fr' => 'French',
                                'de' => 'German',
                                'it' => 'Italian',
                                'pt' => 'Portuguese',
                                'ru' => 'Russian',
                                'ja' => 'Japanese',
                                'ko' => 'Korean',
                                'zh' => 'Chinese',
                                'ar' => 'Arabic',
                                'hi' => 'Hindi',
                                'nl' => 'Dutch',
                                'sv' => 'Swedish',
                                'da' => 'Danish',
                                'no' => 'Norwegian',
                                'fi' => 'Finnish',
                                'pl' => 'Polish',
                                'cs' => 'Czech',
                                'hu' => 'Hungarian'
                            );
                            $default_language = get_option('nexus_ai_wp_translator_source_language', 'en');
                            foreach ($available_languages as $code => $name) {
                                $selected = ($code === $default_language) ? 'selected' : '';
                                echo "<option value='{$code}' {$selected}>{$name} ({$code})</option>";
                            }
                            ?>
                        </select>
                        <button type="button" id="nexus-ai-wp-fix-undefined-languages" class="button button-secondary">
                            <?php _e('Fix Undefined Languages', 'nexus-ai-wp-translator'); ?>
                        </button>
                        <p class="description">
                            <?php _e('This will assign the selected language to all posts that do not have a language defined.', 'nexus-ai-wp-translator'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <div id="nexus-ai-wp-language-fix-results" style="display: none; margin-top: 20px;"></div>
        </div>
        
        <!-- Language Statistics -->
        <div class="nexus-ai-wp-tool-section">
            <h2><?php _e('Language Distribution Statistics', 'nexus-ai-wp-translator'); ?></h2>
            <p class="description">
                <?php _e('View the distribution of languages across your posts and pages.', 'nexus-ai-wp-translator'); ?>
            </p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Statistics:', 'nexus-ai-wp-translator'); ?></th>
                    <td>
                        <button type="button" id="nexus-ai-wp-load-language-stats" class="button button-secondary">
                            <?php _e('Load Language Statistics', 'nexus-ai-wp-translator'); ?>
                        </button>
                        <p class="description">
                            <?php _e('Click to see how many posts and pages are in each language.', 'nexus-ai-wp-translator'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <div id="nexus-ai-wp-language-stats-results" style="display: none; margin-top: 20px;"></div>
        </div>
        
        <!-- Bulk Language Change -->
        <div class="nexus-ai-wp-tool-section">
            <h2><?php _e('Bulk Language Change', 'nexus-ai-wp-translator'); ?></h2>
            <p class="description">
                <?php _e('Change the language of multiple posts at once. Use with caution!', 'nexus-ai-wp-translator'); ?>
            </p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Change Languages:', 'nexus-ai-wp-translator'); ?></th>
                    <td>
                        <label for="nexus-ai-wp-bulk-from-language"><?php _e('From Language:', 'nexus-ai-wp-translator'); ?></label>
                        <select id="nexus-ai-wp-bulk-from-language" style="margin-right: 10px;">
                            <option value=""><?php _e('-- Select Source Language --', 'nexus-ai-wp-translator'); ?></option>
                            <?php
                            foreach ($available_languages as $code => $name) {
                                echo "<option value='{$code}'>{$name} ({$code})</option>";
                            }
                            ?>
                        </select>
                        
                        <label for="nexus-ai-wp-bulk-to-language"><?php _e('To Language:', 'nexus-ai-wp-translator'); ?></label>
                        <select id="nexus-ai-wp-bulk-to-language" style="margin-right: 10px;">
                            <option value=""><?php _e('-- Select Target Language --', 'nexus-ai-wp-translator'); ?></option>
                            <?php
                            foreach ($available_languages as $code => $name) {
                                echo "<option value='{$code}'>{$name} ({$code})</option>";
                            }
                            ?>
                        </select>
                        
                        <button type="button" id="nexus-ai-wp-bulk-change-language" class="button button-secondary">
                            <?php _e('Preview Changes', 'nexus-ai-wp-translator'); ?>
                        </button>
                        
                        <p class="description">
                            <?php _e('First preview the changes, then execute them. This action cannot be undone.', 'nexus-ai-wp-translator'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <div id="nexus-ai-wp-bulk-change-results" style="display: none; margin-top: 20px;"></div>
        </div>
        
    </div>
</div>

<style>
.nexus-ai-wp-tool-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 20px;
    margin-bottom: 20px;
}

.nexus-ai-wp-tool-section h2 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #23282d;
}

.nexus-ai-wp-tool-section .description {
    margin-bottom: 15px;
    color: #666;
}

#nexus-ai-wp-language-fix-results,
#nexus-ai-wp-language-stats-results,
#nexus-ai-wp-bulk-change-results {
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.nexus-ai-wp-language-stats-table table {
    margin-top: 10px;
}

.nexus-ai-wp-bulk-preview {
    background: #fff;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.nexus-ai-wp-bulk-preview h4 {
    margin-top: 0;
    color: #0073aa;
}
</style>

<script>
jQuery(document).ready(function($) {
    console.log('Language Tools page loaded');

    // Check if AJAX object is available
    if (typeof nexus_ai_wp_translator_ajax === 'undefined') {
        console.error('AJAX object not available - admin.js not loaded');
        alert('Error: Language Tools requires admin scripts to be loaded. Please refresh the page.');
        return;
    }

    console.log('AJAX object available, initializing language tools');
    initLanguageToolsDirect();

    function initLanguageToolsDirect() {
        // Fix undefined languages
        $('#nexus-ai-wp-fix-undefined-languages').on('click', function() {
            var language = $('#nexus-ai-wp-bulk-language-select').val();
            var button = $(this);
            var resultsDiv = $('#nexus-ai-wp-language-fix-results');
            
            button.prop('disabled', true).text('Processing...');
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_fix_undefined_languages',
                language: language,
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    var html = '<div class="notice notice-success"><p>' + response.data.message + '</p></div>';
                    resultsDiv.html(html).show();
                } else {
                    resultsDiv.html('<div class="notice notice-error"><p>' + response.data + '</p></div>').show();
                }
            })
            .fail(function() {
                resultsDiv.html('<div class="notice notice-error"><p>Network error occurred</p></div>').show();
            })
            .always(function() {
                button.prop('disabled', false).text('Fix Undefined Languages');
            });
        });
        
        // Load language statistics  
        $('#nexus-ai-wp-load-language-stats').on('click', function() {
            var button = $(this);
            var resultsDiv = $('#nexus-ai-wp-language-stats-results');
            
            button.prop('disabled', true).text('Loading...');
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_get_language_stats',
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    var html = '<h3>Language Distribution:</h3>';
                    html += '<table class="wp-list-table widefat fixed striped">';
                    html += '<thead><tr><th>Language</th><th>Posts</th><th>Pages</th><th>Total</th></tr></thead><tbody>';
                    
                    // Process stats
                    var languageGroups = {};
                    $.each(response.data.stats, function(i, stat) {
                        if (!languageGroups[stat.language]) {
                            languageGroups[stat.language] = {
                                name: stat.language_name,
                                posts: 0,
                                pages: 0,
                                is_default: stat.is_default
                            };
                        }
                        if (stat.post_type === 'post') {
                            languageGroups[stat.language].posts = stat.count;
                        } else if (stat.post_type === 'page') {
                            languageGroups[stat.language].pages = stat.count;
                        }
                    });
                    
                    $.each(languageGroups, function(code, data) {
                        var total = data.posts + data.pages;
                        var nameDisplay = data.name;
                        if (data.is_default) {
                            nameDisplay += ' <span style="color: #0073aa;">(default)</span>';
                        }
                        html += '<tr>';
                        html += '<td>' + nameDisplay + '</td>';
                        html += '<td>' + data.posts + '</td>';
                        html += '<td>' + data.pages + '</td>';
                        html += '<td><strong>' + total + '</strong></td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table>';
                    
                    if (response.data.undefined_count > 0) {
                        html += '<div class="notice notice-warning" style="margin-top: 15px;"><p>';
                        html += '<strong>Warning:</strong> ' + response.data.undefined_count + ' posts have no language defined.';
                        html += '</p></div>';
                    }
                    
                    resultsDiv.html(html).show();
                } else {
                    resultsDiv.html('<div class="notice notice-error"><p>' + response.data + '</p></div>').show();
                }
            })
            .fail(function() {
                resultsDiv.html('<div class="notice notice-error"><p>Network error occurred</p></div>').show();
            })
            .always(function() {
                button.prop('disabled', false).text('Load Language Statistics');
            });
        });
        
        // Bulk language change
        $('#nexus-ai-wp-bulk-change-language').on('click', function() {
            var fromLanguage = $('#nexus-ai-wp-bulk-from-language').val();
            var toLanguage = $('#nexus-ai-wp-bulk-to-language').val();
            var button = $(this);
            var resultsDiv = $('#nexus-ai-wp-bulk-change-results');
            
            if (!toLanguage) {
                alert('Please select a target language.');
                return;
            }
            
            if (fromLanguage === toLanguage) {
                alert('Source and target languages cannot be the same.');
                return;
            }
            
            button.prop('disabled', true).text('Loading Preview...');
            
            $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                action: 'nexus_ai_wp_bulk_change_language',
                from_language: fromLanguage,
                to_language: toLanguage,
                action_type: 'preview',
                nonce: nexus_ai_wp_translator_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    var html = '<div class="nexus-ai-wp-bulk-preview">';
                    html += '<h4>Preview: ' + response.data.posts_count + ' posts will be changed</h4>';
                    html += '<p>From: <strong>' + response.data.from_language_name + '</strong> → To: <strong>' + response.data.to_language_name + '</strong></p>';
                    
                    if (response.data.posts.length > 0) {
                        html += '<h5>Sample posts that will be affected:</h5><ul>';
                        $.each(response.data.posts, function(i, post) {
                            html += '<li>' + post.post_title + ' (' + post.post_type + ')</li>';
                        });
                        html += '</ul>';
                    }
                    
                    if (response.data.posts_count > 0) {
                        html += '<p><button type="button" id="nexus-ai-wp-execute-bulk-change" class="button button-primary" style="margin-right: 10px;">Execute Changes</button>';
                        html += '<button type="button" class="button" onclick="$(\'#nexus-ai-wp-bulk-change-results\').hide();">Cancel</button></p>';
                    } else {
                        html += '<p><em>No posts found matching the criteria.</em></p>';
                    }
                    
                    html += '</div>';
                    resultsDiv.html(html).show();
                    
                    // Handle execute button
                    $('#nexus-ai-wp-execute-bulk-change').on('click', function() {
                        if (!confirm('Are you sure you want to change the language of ' + response.data.posts_count + ' posts? This action cannot be undone.')) {
                            return;
                        }
                        
                        $(this).prop('disabled', true).text('Executing...');
                        
                        $.post(nexus_ai_wp_translator_ajax.ajax_url, {
                            action: 'nexus_ai_wp_bulk_change_language',
                            from_language: fromLanguage,
                            to_language: toLanguage,
                            action_type: 'execute',
                            nonce: nexus_ai_wp_translator_ajax.nonce
                        })
                        .done(function(executeResponse) {
                            if (executeResponse.success) {
                                resultsDiv.html('<div class="notice notice-success"><p>' + executeResponse.data.message + '</p></div>');
                            } else {
                                resultsDiv.html('<div class="notice notice-error"><p>' + executeResponse.data + '</p></div>');
                            }
                        })
                        .fail(function() {
                            resultsDiv.html('<div class="notice notice-error"><p>Network error occurred during execution</p></div>');
                        });
                    });
                } else {
                    resultsDiv.html('<div class="notice notice-error"><p>' + response.data + '</p></div>').show();
                }
            })
            .fail(function() {
                resultsDiv.html('<div class="notice notice-error"><p>Network error occurred</p></div>').show();
            })
            .always(function() {
                button.prop('disabled', false).text('Preview Changes');
            });
        });
    }
});
</script>
