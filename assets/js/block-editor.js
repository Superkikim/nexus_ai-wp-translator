/**
 * Gutenberg Block for Nexus AI WP Translator Language Switcher
 */

(function() {
    'use strict';
    
    console.log('*** Nexus AI WP Translator: Block editor script STARTING ***');
    console.log('Nexus AI WP Translator: Current URL:', window.location.href);
    console.log('Nexus AI WP Translator: Document ready state:', document.readyState);
    
    // Check if required WordPress objects are available
    if (typeof wp === 'undefined') {
        console.error('Nexus AI WP Translator: WordPress wp object not available');
        return;
    }
    
    console.log('Nexus AI WP Translator: wp object available:', Object.keys(wp));
    
    if (!wp.blocks) {
        console.error('Nexus AI WP Translator: wp.blocks not available');
        return;
    }
    
    console.log('Nexus AI WP Translator: wp.blocks available, version:', wp.blocks.registerBlockType ? 'NEW' : 'OLD');
    
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, BlockControls, AlignmentToolbar } = wp.blockEditor;
    const { PanelBody, SelectControl, ToggleControl } = wp.components;
    const { __ } = wp.i18n;
    const { createElement: el, Fragment } = wp.element;
    
    console.log('Nexus AI WP Translator: All WP components loaded successfully');
    
    // Check if our localized data is available
    if (typeof nexusAiWpTranslatorBlock === 'undefined') {
        console.error('Nexus AI WP Translator: nexusAiWpTranslatorBlock not available');
        console.log('Nexus AI WP Translator: Available global variables:', Object.keys(window));
        return;
    }
    
    console.log('Nexus AI WP Translator: Localized data available:', nexusAiWpTranslatorBlock);
    
    console.log('Nexus AI WP Translator: About to register block type...');
    
    const blockRegistration = {
        title: nexusAiWpTranslatorBlock.title,
        description: nexusAiWpTranslatorBlock.description,
        category: nexusAiWpTranslatorBlock.category,
        icon: 'translation',
        keywords: nexusAiWpTranslatorBlock.keywords,
        supports: {
            align: true,
            html: false
        },
        
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
            
            console.log('Nexus AI WP Translator: Block edit function called with attributes:', attributes);
            
            function onChangeStyle(newStyle) {
                setAttributes({ style: newStyle });
            }
            
            function onChangeShowFlags(newShowFlags) {
                setAttributes({ showFlags: newShowFlags });
            }
            
            function onChangeAlignment(newAlignment) {
                setAttributes({ alignment: newAlignment });
            }
            
            // Add some debug info to the preview
            const debugInfo = 'Debug: style=' + style + ', showFlags=' + showFlags + ', alignment=' + alignment;
            console.log('Nexus AI WP Translator: Block preview -', debugInfo);
            
            // Preview component
            const preview = el('div', {
                className: 'nexus-ai-wp-block-language-switcher-preview has-text-align-' + alignment,
                style: {
                    padding: '10px',
                    border: '1px dashed #ccc',
                    borderRadius: '4px',
                    textAlign: alignment,
                    minHeight: '60px'
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
                        marginTop: '4px',
                        fontFamily: 'monospace'
                    }
                }, debugInfo),
                el('div', {
                    key: 'status',
                    style: {
                        fontSize: '12px',
                        color: '#999'
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
    };
    
    console.log('Nexus AI WP Translator: Block registration object:', blockRegistration);
    
    try {
        const result = registerBlockType('nexus-ai-wp-translator/language-switcher', blockRegistration);
        console.log('*** Nexus AI WP Translator: Block registered successfully! ***', result);
    } catch (error) {
        console.error('*** Nexus AI WP Translator: Block registration FAILED ***', error);
    }
    
})();