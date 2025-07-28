<?php
/**
 * Nexus AI WP Translator Language Switcher Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexus_AI_WP_Translator_Language_Switcher_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'nexus_ai_wp_language_switcher',
            __('Nexus AI WP Language Switcher', 'nexus-ai-wp-translator'),
            array(
                'description' => __('Display a language switcher for Nexus AI WP Translator', 'nexus-ai-wp-translator')
            )
        );
    }
    
    /**
     * Widget output
     */
    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title']);
        $style = isset($instance['style']) ? $instance['style'] : 'dropdown';
        $show_flags = isset($instance['show_flags']) ? $instance['show_flags'] : false;
        
        echo $args['before_widget'];
        
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        // Check if frontend class exists before using it
        if (class_exists('Nexus_AI_WP_Translator_Frontend')) {
            $frontend = Nexus_AI_WP_Translator_Frontend::get_instance();
            $switcher_html = $frontend->render_language_switcher(array(
                'style' => $style,
                'show_flags' => $show_flags
            ));
            
            if (!empty($switcher_html)) {
                echo $switcher_html;
            } else {
                echo '<p>' . __('No languages available.', 'nexus-ai-wp-translator') . '</p>';
            }
        } else {
            echo '<p>' . __('Language switcher temporarily unavailable.', 'nexus-ai-wp-translator') . '</p>';
        }
        
        echo $args['after_widget'];
    }
    
    /**
     * Widget form
     */
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : __('Language', 'nexus-ai-wp-translator');
        $style = isset($instance['style']) ? $instance['style'] : 'dropdown';
        $show_flags = isset($instance['show_flags']) ? $instance['show_flags'] : false;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'nexus-ai-wp-translator'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('style'); ?>"><?php _e('Display Style:', 'nexus-ai-wp-translator'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('style'); ?>" name="<?php echo $this->get_field_name('style'); ?>">
                <option value="dropdown" <?php selected($style, 'dropdown'); ?>><?php _e('Dropdown', 'nexus-ai-wp-translator'); ?></option>
                <option value="list" <?php selected($style, 'list'); ?>><?php _e('List', 'nexus-ai-wp-translator'); ?></option>
            </select>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_flags); ?> id="<?php echo $this->get_field_id('show_flags'); ?>" name="<?php echo $this->get_field_name('show_flags'); ?>">
            <label for="<?php echo $this->get_field_id('show_flags'); ?>"><?php _e('Show flags', 'nexus-ai-wp-translator'); ?></label>
        </p>
        <?php
    }
    
    /**
     * Update widget
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['style'] = (!empty($new_instance['style'])) ? strip_tags($new_instance['style']) : 'dropdown';
        $instance['show_flags'] = isset($new_instance['show_flags']) ? (bool) $new_instance['show_flags'] : false;
        
        return $instance;
    }
}