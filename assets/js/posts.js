/**
 * Nexus AI WP Translator Posts Management JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        NexusAIWPTranslatorPosts.init();
    });
    
    var NexusAIWPTranslatorPosts = {
        
        init: function() {
            this.interceptDeleteActions();
            this.interceptTrashActions();
        },
        
        /**
         * Intercept delete actions
         */
        interceptDeleteActions: function() {
            // Intercept delete links in post list
            $(document).on('click', '.submitdelete', function(e) {
                e.preventDefault();
                var link = $(this);
                var postId = NexusAIWPTranslatorPosts.extractPostId(link.attr('href'));
                
                if (postId) {
                    NexusAIWPTranslatorPosts.checkLinkedPosts(postId, 'delete', link);
                }
            });
            
            // Intercept delete button in edit screen
            $(document).on('click', '#delete-action a', function(e) {
                e.preventDefault();
                var link = $(this);
                var postId = $('#post_ID').val();
                
                if (postId) {
                    NexusAIWPTranslatorPosts.checkLinkedPosts(postId, 'delete', link);
                }
            });
        },
        
        /**
         * Intercept trash actions
         */
        interceptTrashActions: function() {
            // Intercept trash links in post list
            $(document).on('click', 'a[href*="action=trash"]', function(e) {
                var link = $(this);
                var href = link.attr('href');
                
                // Skip if it's not a post trash action
                if (!href.includes('post.php') && !href.includes('action=trash')) {
                    return;
                }
                
                e.preventDefault();
                var postId = NexusAIWPTranslatorPosts.extractPostId(href);
                
                if (postId) {
                    NexusAIWPTranslatorPosts.checkLinkedPosts(postId, 'trash', link);
                }
            });
            
            // Intercept move to trash button in edit screen
            $(document).on('click', '#delete-action a[href*="action=trash"]', function(e) {
                e.preventDefault();
                var link = $(this);
                var postId = $('#post_ID').val();
                
                if (postId) {
                    NexusAIWPTranslatorPosts.checkLinkedPosts(postId, 'trash', link);
                }
            });
        },
        
        /**
         * Extract post ID from URL
         */
        extractPostId: function(url) {
            var match = url.match(/[?&]post=(\d+)/);
            return match ? match[1] : null;
        },
        
        /**
         * Check for linked posts before action
         */
        checkLinkedPosts: function(postId, action, originalLink) {
            console.log('Checking linked posts for post ID:', postId, 'Action:', action);
            
            $.post(nexus_ai_wp_translator_posts.ajax_url, {
                action: 'nexus_ai_wp_get_linked_posts',
                post_id: postId,
                nonce: nexus_ai_wp_translator_posts.nonce
            })
            .done(function(response) {
                console.log('Linked posts response:', response);
                
                if (response.success && response.data.length > 0) {
                    // Show confirmation popup
                    NexusAIWPTranslatorPosts.showConfirmationPopup(postId, action, response.data, originalLink);
                } else {
                    // No linked posts, proceed with original action
                    NexusAIWPTranslatorPosts.proceedWithOriginalAction(originalLink);
                }
            })
            .fail(function(xhr, status, error) {
                console.log('Failed to check linked posts:', error);
                // On error, proceed with original action
                NexusAIWPTranslatorPosts.proceedWithOriginalAction(originalLink);
            });
        },
        
        /**
         * Show confirmation popup
         */
        showConfirmationPopup: function(postId, action, linkedPosts, originalLink) {
            var actionText = action === 'delete' ? 'delete' : 'move to trash';
            var linkedPostsList = '';
            
            $.each(linkedPosts, function(i, post) {
                linkedPostsList += '<div class="nexus-ai-wp-linked-post">' +
                    '<strong>' + post.title + '</strong> (' + post.language + ')' +
                    '<br><small>ID: ' + post.id + ' | Status: ' + post.status + '</small>' +
                    '</div>';
            });
            
            var popupHtml = '<div class="nexus-ai-wp-delete-popup" id="nexus-ai-wp-delete-popup">' +
                '<div class="nexus-ai-wp-delete-popup-content">' +
                    '<h3>' + nexus_ai_wp_translator_posts.strings.confirm_title + '</h3>' +
                    '<p>' + nexus_ai_wp_translator_posts.strings.confirm_message + '</p>' +
                    '<div class="nexus-ai-wp-linked-posts">' +
                        '<strong>Linked posts (' + linkedPosts.length + '):</strong>' +
                        linkedPostsList +
                    '</div>' +
                    '<div class="nexus-ai-wp-popup-buttons">' +
                        '<button type="button" class="button" id="nexus-ai-wp-cancel-action">' +
                            nexus_ai_wp_translator_posts.strings.cancel +
                        '</button>' +
                        '<button type="button" class="button" id="nexus-ai-wp-unlink-only">' +
                            nexus_ai_wp_translator_posts.strings.unlink_only +
                        '</button>' +
                        '<button type="button" class="button button-primary" id="nexus-ai-wp-delete-all">' +
                            nexus_ai_wp_translator_posts.strings.delete_all +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
            
            $('body').append(popupHtml);
            $('#nexus-ai-wp-delete-popup').fadeIn(200);
            
            // Handle popup buttons
            $('#nexus-ai-wp-cancel-action').on('click', function() {
                NexusAIWPTranslatorPosts.closePopup();
            });
            
            $('#nexus-ai-wp-unlink-only').on('click', function() {
                NexusAIWPTranslatorPosts.handlePostAction(postId, action, 'unlink_only', originalLink);
            });
            
            $('#nexus-ai-wp-delete-all').on('click', function() {
                NexusAIWPTranslatorPosts.handlePostAction(postId, action, 'delete_all', originalLink);
            });
            
            // Close on background click
            $('#nexus-ai-wp-delete-popup').on('click', function(e) {
                if (e.target === this) {
                    NexusAIWPTranslatorPosts.closePopup();
                }
            });
        },
        
        /**
         * Handle post action with user choice
         */
        handlePostAction: function(postId, action, choice, originalLink) {
            console.log('Handling post action:', postId, action, choice);
            
            // Show processing state
            $('.nexus-ai-wp-popup-buttons button').prop('disabled', true);
            $('#nexus-ai-wp-delete-all, #nexus-ai-wp-unlink-only').text(nexus_ai_wp_translator_posts.strings.processing);
            
            $.post(nexus_ai_wp_translator_posts.ajax_url, {
                action: 'nexus_ai_wp_handle_post_action',
                post_id: postId,
                post_action: action,
                user_choice: choice,
                nonce: nexus_ai_wp_translator_posts.nonce
            })
            .done(function(response) {
                console.log('NexusAI Debug: Post action response received');
                console.log('NexusAI Debug: Response:', response);
                console.log('NexusAI Debug: Response type:', typeof response);
                console.log('NexusAI Debug: Response is null/undefined:', response === null || response === undefined);
                
                if (response === null || response === undefined) {
                    console.error('NexusAI Debug: Response is null or undefined!');
                    NexusAIWPTranslatorPosts.closePopup();
                    alert('Error: No response received from server');
                    return;
                }
                
                console.log('NexusAI Debug: Response success property:', response.success);
                
                NexusAIWPTranslatorPosts.closePopup();
                
                if (response.success) {
                    console.log('NexusAI Debug: Action successful, redirecting...');
                    // Redirect or reload as appropriate
                    if (action === 'delete') {
                        // Redirect to post list
                        window.location.href = 'edit.php?post_type=post';
                    } else {
                        // Reload current page
                        window.location.reload();
                    }
                } else {
                    console.log('NexusAI Debug: Action failed, showing alert');
                    var errorMessage = 'Error: ' + (response.data ? response.data : 'Unknown error occurred');
                    console.log('NexusAI Debug: Error message:', errorMessage);
                    alert(errorMessage);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('NexusAI Debug: AJAX request failed completely');
                console.error('NexusAI Debug: Status:', status);
                console.error('NexusAI Debug: Error:', error);
                console.error('NexusAI Debug: XHR status:', xhr.status);
                console.error('NexusAI Debug: XHR response text:', xhr.responseText);
                console.error('NexusAI Debug: XHR ready state:', xhr.readyState);
                
                NexusAIWPTranslatorPosts.closePopup();
                alert('Network error occurred: ' + error + ' (Status: ' + xhr.status + ')');
            });
        },
        
        /**
         * Proceed with original action (no linked posts)
         */
        proceedWithOriginalAction: function(originalLink) {
            console.log('Proceeding with original action');
            // Remove our event handler temporarily and trigger the original action
            var href = originalLink.attr('href');
            window.location.href = href;
        },
        
        /**
         * Close popup
         */
        closePopup: function() {
            $('#nexus-ai-wp-delete-popup').fadeOut(200, function() {
                $(this).remove();
            });
        }
    };
    
    // Make globally available
    window.NexusAIWPTranslatorPosts = NexusAIWPTranslatorPosts;
    
})(jQuery);