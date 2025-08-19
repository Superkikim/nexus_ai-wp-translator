<?php
/**
 * Admin Translation Template - New Design
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get available post types
$post_types = get_post_types(array('public' => true), 'objects');
$allowed_types = array('post', 'page');

// Check for custom post types like events
if (post_type_exists('event')) {
    $allowed_types[] = 'event';
}

$languages = $this->translation_manager->get_available_languages();
?>

<div class="wrap">
    <h1>
        <?php _e('Translation Management', 'nexus-ai-wp-translator'); ?>
        <a href="<?php echo admin_url('admin.php?page=nexus-ai-wp-translator-settings'); ?>" class="page-title-action">
            <?php _e('Settings', 'nexus-ai-wp-translator'); ?>
        </a>
    </h1>
    
    <div class="nexus-ai-wp-translation-tabs">
        <nav class="nav-tab-wrapper">
            <?php 
            $first = true;
            foreach ($allowed_types as $post_type): 
                $post_type_obj = get_post_type_object($post_type);
                if ($post_type_obj):
            ?>
                <a href="#<?php echo esc_attr($post_type); ?>-tab" class="nav-tab <?php echo $first ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html($post_type_obj->labels->name); ?>
                </a>
                <?php $first = false; ?>
            <?php 
                endif;
            endforeach; 
            ?>
        </nav>
        
        <?php 
        $first = true;
        foreach ($allowed_types as $post_type): 
            $post_type_obj = get_post_type_object($post_type);
            if ($post_type_obj):
        ?>
            <div id="<?php echo esc_attr($post_type); ?>-tab" class="tab-content <?php echo $first ? 'active' : ''; ?>">
                <div class="nexus-ai-wp-translation-controls">
                    
                    <!-- Search and Filters -->
                    <div class="nexus-ai-wp-filters-row">
                        <div class="nexus-ai-wp-search-filter">
                            <input type="text" 
                                   id="search-<?php echo esc_attr($post_type); ?>" 
                                   class="nexus-ai-wp-search-field" 
                                   placeholder="<?php esc_attr_e('Search titles...', 'nexus-ai-wp-translator'); ?>"
                                   data-post-type="<?php echo esc_attr($post_type); ?>" />
                                   
                            <select id="category-filter-<?php echo esc_attr($post_type); ?>" class="nexus-ai-wp-category-filter" data-post-type="<?php echo esc_attr($post_type); ?>">
                                <option value=""><?php _e('All Categories', 'nexus-ai-wp-translator'); ?></option>
                                <?php
                                $categories = get_categories(array('hide_empty' => false));
                                foreach ($categories as $category):
                                ?>
                                    <option value="<?php echo esc_attr($category->term_id); ?>">
                                        <?php echo esc_html($category->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select id="language-filter-<?php echo esc_attr($post_type); ?>" class="nexus-ai-wp-language-filter" data-post-type="<?php echo esc_attr($post_type); ?>">
                                <option value=""><?php _e('All Languages', 'nexus-ai-wp-translator'); ?></option>
                                <?php foreach ($languages as $code => $name): ?>
                                    <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select id="translation-status-filter-<?php echo esc_attr($post_type); ?>" class="nexus-ai-wp-translation-filter" data-post-type="<?php echo esc_attr($post_type); ?>">
                                <option value=""><?php _e('All Items', 'nexus-ai-wp-translator'); ?></option>
                                <option value="linked"><?php _e('Linked Items', 'nexus-ai-wp-translator'); ?></option>
                                <option value="unlinked"><?php _e('Unlinked Items', 'nexus-ai-wp-translator'); ?></option>
                            </select>
                            
                            <button type="button" class="button nexus-ai-wp-reset-filters" data-post-type="<?php echo esc_attr($post_type); ?>">
                                <?php _e('Reset Filters', 'nexus-ai-wp-translator'); ?>
                            </button>
                        </div>
                        
                        <div class="nexus-ai-wp-items-per-page">
                            <label for="items-per-page-<?php echo esc_attr($post_type); ?>">
                                <?php _e('Items per page:', 'nexus-ai-wp-translator'); ?>
                            </label>
                            <select id="items-per-page-<?php echo esc_attr($post_type); ?>" class="nexus-ai-wp-items-per-page" data-post-type="<?php echo esc_attr($post_type); ?>">
                                <option value="10">10</option>
                                <option value="20" selected>20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Bulk Actions -->
                    <div class="nexus-ai-wp-bulk-actions-row">
                        <div class="nexus-ai-wp-bulk-actions">
                            <select id="bulk-action-<?php echo esc_attr($post_type); ?>" class="nexus-ai-wp-bulk-action-select" data-post-type="<?php echo esc_attr($post_type); ?>">
                                <option value=""><?php _e('Select Bulk Action', 'nexus-ai-wp-translator'); ?></option>
                                <option value="set_language"><?php _e('Set Language', 'nexus-ai-wp-translator'); ?></option>
                                <option value="translate"><?php _e('Translate', 'nexus-ai-wp-translator'); ?></option>
                                <option value="link"><?php _e('Link', 'nexus-ai-wp-translator'); ?></option>
                                <option value="unlink"><?php _e('Unlink', 'nexus-ai-wp-translator'); ?></option>
                                <option value="delete"><?php _e('Delete', 'nexus-ai-wp-translator'); ?></option>
                            </select>
                            
                            <button type="button" class="button nexus-ai-wp-bulk-apply" data-post-type="<?php echo esc_attr($post_type); ?>" disabled>
                                <?php _e('Apply', 'nexus-ai-wp-translator'); ?>
                            </button>
                            
                            <span class="nexus-ai-wp-selection-count" data-post-type="<?php echo esc_attr($post_type); ?>">
                                0 <?php _e('items selected', 'nexus-ai-wp-translator'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Items Table -->
                <div class="nexus-ai-wp-items-table-container" data-post-type="<?php echo esc_attr($post_type); ?>">
                    <table class="wp-list-table widefat fixed striped nexus-ai-wp-translation-table">
                        <thead>
                            <tr>
                                <th scope="col" class="column-select">
                                    <input type="checkbox" id="select-all-<?php echo esc_attr($post_type); ?>" class="nexus-ai-wp-select-all" data-post-type="<?php echo esc_attr($post_type); ?>">
                                </th>
                                <th scope="col" class="column-title"><?php _e('Title', 'nexus-ai-wp-translator'); ?></th>
                                <th scope="col" class="column-language"><?php _e('Language', 'nexus-ai-wp-translator'); ?></th>
                                <th scope="col" class="column-linked"><?php _e('Linked', 'nexus-ai-wp-translator'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="<?php echo esc_attr($post_type); ?>-tbody" class="nexus-ai-wp-items-tbody">
                            <tr>
                                <td colspan="4" class="nexus-ai-wp-loading">
                                    <?php _e('Loading items...', 'nexus-ai-wp-translator'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <div class="nexus-ai-wp-pagination" id="pagination-<?php echo esc_attr($post_type); ?>">
                        <!-- Pagination will be populated by JavaScript -->
                    </div>
                </div>
            </div>
            <?php $first = false; ?>
        <?php 
            endif;
        endforeach; 
        ?>
    </div>
</div>

<!-- Language Selection Modal -->
<div id="nexus-ai-wp-language-modal" class="nexus-ai-wp-modal" style="display: none;">
    <div class="nexus-ai-wp-modal-content">
        <div class="nexus-ai-wp-modal-header">
            <h3><?php _e('Set Language', 'nexus-ai-wp-translator'); ?></h3>
            <button type="button" class="nexus-ai-wp-modal-close">&times;</button>
        </div>
        <div class="nexus-ai-wp-modal-body">
            <p><?php _e('Select the language for the selected items:', 'nexus-ai-wp-translator'); ?></p>
            <select id="nexus-ai-wp-language-select" class="large-text">
                <option value=""><?php _e('Select Language', 'nexus-ai-wp-translator'); ?></option>
                <?php foreach ($languages as $code => $name): ?>
                    <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description">
                <?php _e('This will set the language for all selected items. Make sure the language is correct before applying.', 'nexus-ai-wp-translator'); ?>
            </p>
        </div>
        <div class="nexus-ai-wp-modal-footer">
            <button type="button" class="button button-secondary nexus-ai-wp-modal-cancel"><?php _e('Cancel', 'nexus-ai-wp-translator'); ?></button>
            <button type="button" class="button button-primary" id="nexus-ai-wp-apply-language"><?php _e('Apply Language', 'nexus-ai-wp-translator'); ?></button>
        </div>
    </div>
</div>

<!-- Translation Modal -->
<div id="nexus-ai-wp-translation-modal" class="nexus-ai-wp-modal" style="display: none;">
    <div class="nexus-ai-wp-modal-content">
        <div class="nexus-ai-wp-modal-header">
            <h3><?php _e('Translate Items', 'nexus-ai-wp-translator'); ?></h3>
            <button type="button" class="nexus-ai-wp-modal-close">&times;</button>
        </div>
        <div class="nexus-ai-wp-modal-body">
            <p><?php _e('Select target languages for translation:', 'nexus-ai-wp-translator'); ?></p>
            <div class="nexus-ai-wp-target-languages">
                <?php 
                $target_languages = get_option('nexus_ai_wp_translator_target_languages', array());
                foreach ($target_languages as $lang): 
                    if (isset($languages[$lang])):
                ?>
                    <label class="nexus-ai-wp-language-checkbox">
                        <input type="checkbox" value="<?php echo esc_attr($lang); ?>" class="nexus-ai-wp-target-lang">
                        <?php echo esc_html($languages[$lang]); ?>
                    </label>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
            <p class="description">
                <?php _e('Translation will create new posts in the selected languages. This may take several minutes for multiple items.', 'nexus-ai-wp-translator'); ?>
            </p>
        </div>
        <div class="nexus-ai-wp-modal-footer">
            <button type="button" class="button button-secondary nexus-ai-wp-modal-cancel"><?php _e('Cancel', 'nexus-ai-wp-translator'); ?></button>
            <button type="button" class="button button-primary" id="nexus-ai-wp-start-translation"><?php _e('Start Translation', 'nexus-ai-wp-translator'); ?></button>
        </div>
    </div>
</div>

<style>
/* Translation page styles */
.nexus-ai-wp-translation-tabs .nav-tab-wrapper {
    margin-bottom: 20px;
    border-bottom: 1px solid #ccc;
}

.nexus-ai-wp-translation-tabs .tab-content {
    display: none;
}

.nexus-ai-wp-translation-tabs .tab-content.active {
    display: block;
}

.nexus-ai-wp-translation-controls {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
}

.nexus-ai-wp-filters-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
}

.nexus-ai-wp-search-filter {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.nexus-ai-wp-search-field {
    min-width: 200px;
}

.nexus-ai-wp-items-per-page {
    display: flex;
    align-items: center;
    gap: 5px;
}

.nexus-ai-wp-bulk-actions-row {
    border-top: 1px solid #eee;
    padding-top: 15px;
}

.nexus-ai-wp-bulk-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.nexus-ai-wp-selection-count {
    font-style: italic;
    color: #666;
}

.nexus-ai-wp-items-table-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.nexus-ai-wp-translation-table {
    margin: 0;
}

.nexus-ai-wp-translation-table .column-select {
    width: 50px;
}

.nexus-ai-wp-translation-table .column-language {
    width: 100px;
}

.nexus-ai-wp-translation-table .column-linked {
    width: 120px;
}

.nexus-ai-wp-loading {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.nexus-ai-wp-pagination {
    padding: 15px;
    border-top: 1px solid #eee;
    text-align: center;
}

/* Modal styles */
.nexus-ai-wp-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nexus-ai-wp-modal-content {
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.nexus-ai-wp-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    position: relative;
}

.nexus-ai-wp-modal-header h3 {
    margin: 0;
    font-size: 18px;
}

.nexus-ai-wp-modal-close {
    position: absolute;
    top: 15px;
    right: 20px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.nexus-ai-wp-modal-close:hover {
    color: #000;
}

.nexus-ai-wp-modal-body {
    padding: 20px;
}

.nexus-ai-wp-target-languages {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    margin: 15px 0;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f9f9f9;
}

.nexus-ai-wp-language-checkbox {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.nexus-ai-wp-language-checkbox input {
    margin-right: 8px;
}

.nexus-ai-wp-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.nexus-ai-wp-modal-footer .button {
    margin-left: 10px;
}

/* Language badges */
.nexus-ai-wp-language-badge {
    display: inline-block;
    padding: 3px 8px;
    background: #f0f0f0;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.nexus-ai-wp-linked-count {
    color: #0073aa;
    font-weight: 600;
}

.nexus-ai-wp-no-data {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-style: italic;
}
</style>

<script>
jQuery(document).ready(function($) {
    var NexusAITranslation = {
        
        currentPostType: null,
        selectedItems: {},
        
        init: function() {
            this.initTabs();
            this.initFilters();
            this.initBulkActions();
            this.initModals();
            this.loadInitialData();
        },
        
        initTabs: function() {
            var self = this;
            
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                var postType = target.replace('#', '').replace('-tab', '');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').removeClass('active');
                $(target).addClass('active');
                
                self.currentPostType = postType;
                self.selectedItems[postType] = [];
                self.updateSelectionCount(postType);
                
                // Load data for this tab
                self.loadPostTypeData(postType);
                
                // Save active tab
                localStorage.setItem('nexus_ai_wp_translation_active_tab', target);
            });
            
            // Restore active tab
            var activeTab = localStorage.getItem('nexus_ai_wp_translation_active_tab');
            if (activeTab && $(activeTab).length) {
                $('.nav-tab[href="' + activeTab + '"]').click();
            } else {
                // Default to first tab
                $('.nav-tab').first().click();
            }
        },
        
        initFilters: function() {
            var self = this;
            
            // Search field
            $('.nexus-ai-wp-search-field').on('input', function() {
                var postType = $(this).data('post-type');
                self.debounce(function() {
                    self.loadPostTypeData(postType);
                }, 500);
            });
            
            // Filter selects
            $('.nexus-ai-wp-category-filter, .nexus-ai-wp-language-filter, .nexus-ai-wp-translation-filter').on('change', function() {
                var postType = $(this).data('post-type');
                self.loadPostTypeData(postType);
            });
            
            // Items per page
            $('.nexus-ai-wp-items-per-page').on('change', function() {
                var postType = $(this).data('post-type');
                self.loadPostTypeData(postType);
            });
            
            // Reset filters
            $('.nexus-ai-wp-reset-filters').on('click', function() {
                var postType = $(this).data('post-type');
                $('#search-' + postType).val('');
                $('#category-filter-' + postType).val('');
                $('#language-filter-' + postType).val('');
                $('#translation-status-filter-' + postType).val('');
                $('#items-per-page-' + postType).val('20');
                self.loadPostTypeData(postType);
            });
        },
        
        initBulkActions: function() {
            var self = this;
            
            // Select all checkbox
            $('.nexus-ai-wp-select-all').on('change', function() {
                var postType = $(this).data('post-type');
                var isChecked = $(this).is(':checked');
                
                $('#' + postType + '-tbody input[type="checkbox"]').prop('checked', isChecked);
                self.updateSelectedItems(postType);
            });
            
            // Bulk apply button
            $('.nexus-ai-wp-bulk-apply').on('click', function() {
                var postType = $(this).data('post-type');
                var action = $('#bulk-action-' + postType).val();
                
                if (!action) {
                    alert('<?php _e('Please select an action', 'nexus-ai-wp-translator'); ?>');
                    return;
                }
                
                var selectedIds = self.selectedItems[postType] || [];
                if (selectedIds.length === 0) {
                    alert('<?php _e('Please select items first', 'nexus-ai-wp-translator'); ?>');
                    return;
                }
                
                self.handleBulkAction(postType, action, selectedIds);
            });
        },
        
        loadInitialData: function() {
            // Load data for the first tab
            var firstTab = $('.nav-tab').first();
            if (firstTab.length) {
                var postType = firstTab.attr('href').replace('#', '').replace('-tab', '');
                this.currentPostType = postType;
                this.loadPostTypeData(postType);
            }
        },
        
        loadPostTypeData: function(postType) {
            var self = this;
            var tbody = $('#' + postType + '-tbody');
            
            // Show loading
            tbody.html('<tr><td colspan="4" class="nexus-ai-wp-loading"><?php _e('Loading items...', 'nexus-ai-wp-translator'); ?></td></tr>');
            
            // Simulate loading data (in a real implementation, this would be an AJAX call)
            setTimeout(function() {
                self.renderMockData(postType);
            }, 500);
        },
        
        renderMockData: function(postType) {
            var tbody = $('#' + postType + '-tbody');
            var mockData = this.getMockData(postType);
            
            if (mockData.length === 0) {
                tbody.html('<tr><td colspan="4" class="nexus-ai-wp-no-data"><?php _e('No items found', 'nexus-ai-wp-translator'); ?></td></tr>');
                return;
            }
            
            var html = '';
            mockData.forEach(function(item) {
                html += '<tr>';
                html += '<td><input type="checkbox" value="' + item.id + '" class="nexus-ai-wp-item-checkbox" data-post-type="' + postType + '"></td>';
                html += '<td><strong>' + item.title + '</strong><br><small>ID: ' + item.id + '</small></td>';
                html += '<td><span class="nexus-ai-wp-language-badge">' + (item.language || 'Not Set') + '</span></td>';
                html += '<td><span class="nexus-ai-wp-linked-count">' + item.linked + '</span></td>';
                html += '</tr>';
            });
            
            tbody.html(html);
            
            // Re-bind checkbox events
            $('.nexus-ai-wp-item-checkbox').on('change', function() {
                var postType = $(this).data('post-type');
                self.updateSelectedItems(postType);
            });
        },
        
        getMockData: function(postType) {
            // Mock data for demonstration
            var data = [];
            var titles = {
                'post': ['Sample Article', 'Another Post', 'Test Content'],
                'page': ['Home Page', 'About Us', 'Contact'],
                'event': ['Conference 2024', 'Workshop', 'Meetup']
            };
            
            for (var i = 1; i <= 5; i++) {
                data.push({
                    id: i,
                    title: (titles[postType] && titles[postType][i-1]) || (postType + ' ' + i),
                    language: i === 1 ? 'EN' : (Math.random() > 0.5 ? 'EN' : ''),
                    linked: Math.floor(Math.random() * 4)
                });
            }
            
            return data;
        },
        
        updateSelectedItems: function(postType) {
            var selectedIds = [];
            $('#' + postType + '-tbody input[type="checkbox"]:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            this.selectedItems[postType] = selectedIds;
            this.updateSelectionCount(postType);
            this.updateBulkActionButton(postType);
        },
        
        updateSelectionCount: function(postType) {
            var count = (this.selectedItems[postType] || []).length;
            $('.nexus-ai-wp-selection-count[data-post-type="' + postType + '"]').text(count + ' <?php _e('items selected', 'nexus-ai-wp-translator'); ?>');
        },
        
        updateBulkActionButton: function(postType) {
            var hasSelection = (this.selectedItems[postType] || []).length > 0;
            $('.nexus-ai-wp-bulk-apply[data-post-type="' + postType + '"]').prop('disabled', !hasSelection);
        },
        
        handleBulkAction: function(postType, action, selectedIds) {
            console.log('Bulk action:', action, 'for items:', selectedIds, 'of type:', postType);
            
            switch (action) {
                case 'set_language':
                    this.showLanguageModal(postType, selectedIds);
                    break;
                case 'translate':
                    this.showTranslationModal(postType, selectedIds);
                    break;
                default:
                    alert('Action "' + action + '" is not yet implemented');
            }
        },
        
        initModals: function() {
            var self = this;
            
            // Close modal events
            $('.nexus-ai-wp-modal-close, .nexus-ai-wp-modal-cancel').on('click', function() {
                $('.nexus-ai-wp-modal').hide();
            });
            
            // Close on background click
            $('.nexus-ai-wp-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
        },
        
        showLanguageModal: function(postType, selectedIds) {
            $('#nexus-ai-wp-language-modal').show();
            
            $('#nexus-ai-wp-apply-language').off('click').on('click', function() {
                var language = $('#nexus-ai-wp-language-select').val();
                if (!language) {
                    alert('<?php _e('Please select a language', 'nexus-ai-wp-translator'); ?>');
                    return;
                }
                
                // Here you would make the AJAX call to set language
                console.log('Setting language', language, 'for items', selectedIds);
                $('#nexus-ai-wp-language-modal').hide();
                alert('<?php _e('Language setting functionality will be implemented', 'nexus-ai-wp-translator'); ?>');
            });
        },
        
        showTranslationModal: function(postType, selectedIds) {
            $('#nexus-ai-wp-translation-modal').show();
            
            $('#nexus-ai-wp-start-translation').off('click').on('click', function() {
                var targetLangs = [];
                $('.nexus-ai-wp-target-lang:checked').each(function() {
                    targetLangs.push($(this).val());
                });
                
                if (targetLangs.length === 0) {
                    alert('<?php _e('Please select target languages', 'nexus-ai-wp-translator'); ?>');
                    return;
                }
                
                // Here you would make the AJAX call to start translation
                console.log('Starting translation to', targetLangs, 'for items', selectedIds);
                $('#nexus-ai-wp-translation-modal').hide();
                alert('<?php _e('Translation functionality will be implemented', 'nexus-ai-wp-translator'); ?>');
            });
        },
        
        debounce: function(func, delay) {
            var timeoutId;
            clearTimeout(timeoutId);
            timeoutId = setTimeout(func, delay);
        }
    };
    
    // Initialize
    NexusAITranslation.init();
});
