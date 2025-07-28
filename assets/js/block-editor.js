/**
 * Gutenberg Block for Nexus AI WP Translator Language Switcher
 */

(function() {
    'use strict';
    
    console.log('Nexus AI WP Translator: Block editor script loading...');
    
    // Check if required WordPress objects are available
    if (typeof wp === 'undefined') {
        console.error('Nexus AI WP Translator: WordPress wp object not available');
        return;
    }
    
    if (!wp.blocks) {
        console.error('Nexus AI WP Translator: wp.blocks not available');
        return;
    }
    
    console.log('Nexus AI WP Translator: WordPress objects available, registering block...');
    
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, BlockControls, AlignmentToolbar } = wp.blockEditor;
    const { PanelBody, SelectControl, ToggleControl } = wp.components;
    const { __ } = wp.i18n;
    const { createElement: el, Fragment } = wp.element;
    
    // Check if our localized data is available
    if (typeof nexusAiWpTranslatorBlock === 'undefined') {
        console.error('Nexus AI WP Translator: nexusAiWpTranslatorBlock not available');
        return;
    }
    
    console.log('Nexus AI WP Translator: Localized data available:', nexusAiWpTranslatorBlock);
    
    registerBlockType('nexus-ai-wp-translator/language-switcher', {
        title: nexusAiWpTranslatorBlock.title,
        description: nexusAiWpTranslatorBlock.description,
        category: nexusAiWpTranslatorBlock.category,
        icon: 'translation',
        keywords: nexusAiWpTranslatorBlock.keywords,
        
        attributes: {
            style: {
                type: 'string',
                default: 'dropdown'
            },
            showFlags: {
                type: 'boolean',
                default: false
            },
            alignment: {
                type: 'string',
                default: 'left'
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { style, showFlags, alignment } = attributes;
            
            function onChangeStyle(newStyle) {
                setAttributes({ style: newStyle });
            }
            
            function onChangeShowFlags(newShowFlags) {
                setAttributes({ showFlags: newShowFlags });
            }
            
            function onChangeAlignment(newAlignment) {
                setAttributes({ alignment: newAlignment });
            }
            
            // Preview component
            const preview = el('div', {
                className: 'nexus-ai-wp-block-language-switcher-preview has-text-align-' + alignment,
                style: {
                    padding: '10px',
                    border: '1px dashed #ccc',
                    borderRadius: '4px',
                    textAlign: alignment
                }
            }, [
                el('div', {
                    key: 'icon',
                    style: {
                        fontSize: '24px',
                        marginBottom: '8px'
                    }
                }, '🌐'),
                el('div', {
                    key: 'label',
                    style: {
                        fontSize: '14px',
                        color: '#666'
                    }
                }, __('Language Switcher', 'nexus-ai-wp-translator')),
                el('div', {
                    key: 'style-info',
                    style: {
                        fontSize: '12px',
                        color: '#999',
                        marginTop: '4px'
                    }
                }, __('Style: ', 'nexus-ai-wp-translator') + style)
            ]);
            
            return el(Fragment, {}, [
                // Block Controls (toolbar)
                el(BlockControls, { key: 'controls' }, [
                    el(AlignmentToolbar, {
                        value: alignment,
                        onChange: onChangeAlignment
                    })
                ]),
                
                // Inspector Controls (sidebar)
                el(InspectorControls, { key: 'inspector' }, [
                    el(PanelBody, {
                        title: __('Language Switcher Settings', 'nexus-ai-wp-translator'),
                        initialOpen: true
                    }, [
                        el(SelectControl, {
                            label: __('Display Style', 'nexus-ai-wp-translator'),
                            value: style,
                            options: [
                                { label: __('Dropdown', 'nexus-ai-wp-translator'), value: 'dropdown' },
                                { label: __('List', 'nexus-ai-wp-translator'), value: 'list' }
                            ],
                            onChange: onChangeStyle
                        }),
                        el(ToggleControl, {
                            label: __('Show Flags', 'nexus-ai-wp-translator'),
                            checked: showFlags,
                            onChange: onChangeShowFlags,
                            help: __('Display flag icons next to language names (if available)', 'nexus-ai-wp-translator')
                        })
                    ])
                ]),
                
                // Block preview
                preview
            ]);
        },
        
        save: function() {
            // Return null since this is a dynamic block rendered by PHP
            return null;
        }
    });
    
    console.log('Nexus AI WP Translator: Block registered successfully!');
})();