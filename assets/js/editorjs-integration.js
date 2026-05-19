// assets/js/editorjs-integration.js

class CSFEditorJS {
    constructor(containerId, textareaId, config = {}) {
        this.containerId = containerId;
        this.textareaId = textareaId;
        this.editor = null;
        this.config = config;
        
        this.init();
    }
    
    async init() {
        // Wait for Editor.js to be loaded
        if (typeof EditorJS === 'undefined') {
            console.error('Editor.js not loaded');
            return;
        }
        
        // Default tools configuration with enhanced settings
        const defaultTools = {
            header: {
                class: Header,
                inlineToolbar: true,  // Enable inline toolbar
                config: {
                    placeholder: 'Enter a header',
                    levels: [1, 2, 3, 4, 5, 6],
                    defaultLevel: 2
                },
                toolbox: {
                    title: 'Header',
                    icon: '<svg width="20" height="20" viewBox="0 0 20 20"><path d="M3 4h2v5h8V4h2v12h-2v-5H5v5H3V4z"/></svg>'
                }
            },
            list: {
                class: List,
                inlineToolbar: true,
                config: {
                    defaultStyle: 'unordered'
                },
                toolbox: {
                    title: 'List',
                    icon: '<svg width="20" height="20" viewBox="0 0 20 20"><path d="M7 5h10v2H7V5zm0 4h10v2H7V9zm0 4h10v2H7v-2zM3 5h2v2H3V5zm0 4h2v2H3V9zm0 4h2v2H3v-2z"/></svg>'
                }
            },
            paragraph: {
                class: Paragraph,
                inlineToolbar: true,
                config: {
                    placeholder: 'Start writing your content...'
                }
            },
            image: {
                class: ImageTool,
                inlineToolbar: true,  // Enable inline toolbar for image captions
                config: {
                    uploader: {
                        uploadByFile: window.csfEditorJS && window.csfEditorJS.uploadImage ? window.csfEditorJS.uploadImage : null
                    },
                    captionPlaceholder: 'Image caption...',
                    // Enable image settings
                    actions: ['stretch', 'border', 'background']
                }
            },
            embed: {
                class: Embed,
                inlineToolbar: false,
                config: {
                    services: {
                        youtube: true,
                        vimeo: true,
                        twitter: true,
                        instagram: true,
                        facebook: true,
                        codepen: true,
                        github: true,
                        spotify: true
                    }
                }
            },
            table: {
                class: Table,
                inlineToolbar: true,
                config: {
                    rows: 2,
                    cols: 3
                }
            },
            delimiter: {
                class: Delimiter,
                toolbox: {
                    title: 'Delimiter',
                    icon: '<svg width="20" height="20" viewBox="0 0 20 20"><path d="M2 10h16M2 10l4-4m-4 4l4 4m12-4l-4-4m4 4l-4 4"/></svg>'
                }
            },
            warning: {
                class: Warning,
                inlineToolbar: true,
                config: {
                    titlePlaceholder: 'Title',
                    messagePlaceholder: 'Message'
                }
            },
            code: {
                class: CodeTool,
                toolbox: {
                    title: 'Code',
                    icon: '<svg width="20" height="20" viewBox="0 0 20 20"><path d="M6 7l-5 5 5 5M14 7l5 5-5 5M8 3L12 17"/></svg>'
                }
            },
            raw: {
                class: RawTool,
                toolbox: {
                    title: 'HTML',
                    icon: '<svg width="20" height="20" viewBox="0 0 20 20"><path d="M5 5l-4 5 4 5M15 5l4 5-4 5M9 2L11 18"/></svg>'
                }
            },
            quote: {
                class: Quote,
                inlineToolbar: true,
                config: {
                    quotePlaceholder: 'Enter a quote',
                    captionPlaceholder: 'Quote\'s author'
                }
            },
            checklist: {
                class: Checklist,
                inlineToolbar: true
            },
            marker: {
                class: Marker,
                shortcut: 'CMD+SHIFT+M',
                inlineToolbar: true
            },
            // Add inline code tool
            inlineCode: {
                class: InlineCode,
                shortcut: 'CMD+SHIFT+C',
                inlineToolbar: true,
                toolbox: {
                    title: 'Inline Code',
                    icon: '<svg width="20" height="20" viewBox="0 0 20 20"><path d="M8 7l-5 5 5 5M12 7l5 5-5 5"/></svg>'
                }
            },
            // Add underline tool
            underline: {
                class: Underline,
                shortcut: 'CMD+U',
                inlineToolbar: true,
                toolbox: {
                    title: 'Underline',
                    icon: '<svg width="20" height="20" viewBox="0 0 20 20"><path d="M5 4v6c0 2.5 2 5 5 5s5-2.5 5-5V4h-2v6c0 1.5-1 3-3 3s-3-1.5-3-3V4H5zM4 15h12v2H4z"/></svg>'
                }
            },
            // Add text-color tool
            textColor: {
                class: ColorPlugin,
                config: {
                   colorCollections: ['#000000', '#FF1300', '#EC7878','#9C27B0','#673AB7','#3F51B5','#0070FF','#03A9F4','#00BCD4','#4CAF50','#8BC34A','#CDDC39', '#FFF'],
                   defaultColor: '#FF1300',
                   type: 'text', 
                   customPicker: true
                }
            }
        };
        
        // Merge with custom tools if provided
        const tools = { ...defaultTools, ...this.config.tools };
        
        // Get initial data from textarea
        let initialData = { blocks: [] };
        const textareaValue = document.getElementById(this.textareaId).value;
        
        if (textareaValue) {
            try {
                initialData = JSON.parse(textareaValue);
            } catch (e) {
                // If not JSON, treat as plain text
                initialData = {
                    blocks: [{
                        type: 'paragraph',
                        data: { text: textareaValue }
                    }]
                };
            }
        }
        
        // Initialize Editor.js with enhanced configuration
        this.editor = new EditorJS({
            holder: this.containerId,
            tools: tools,
            data: initialData,
            placeholder: this.config.placeholder || 'Start writing...',
            autofocus: this.config.autofocus || false,
            // Enable inline toolbar for all blocks that support it
            inlineToolbar: ['link', 'marker', 'bold', 'italic', 'inlineCode', 'underline', 'textColor'],
            // Enable block tunes menu
            tunes: ['stretch', 'border', 'background'],
            onChange: () => {
                this.saveToTextarea();
            },
            // Add log level to see what's happening
            logLevel: 'ERROR',
            // i18n for better UX
            i18n: {
                messages: {
                    ui: {
                        blockTunes: {
                            toggler: {
                                'Click to tune': 'Click to tune',
                                'or drag to move': 'or drag to move'
                            }
                        },
                        inlineToolbar: {
                            converter: {
                                'Convert to': 'Convert to'
                            }
                        },
                        toolbar: {
                            toolbox: {
                                'Add': 'Add'
                            }
                        }
                    },
                    toolNames: {
                        'Text': 'Text',
                        'Heading': 'Heading',
                        'List': 'List',
                        'Warning': 'Warning',
                        'Code': 'Code',
                        'Quote': 'Quote',
                        'Image': 'Image',
                        'Table': 'Table',
                        'Link': 'Link',
                        'Marker': 'Marker',
                        'Bold': 'Bold',
                        'Italic': 'Italic',
                        'Inline Code': 'Inline Code',
                        'Underline': 'Underline',
                        'textColor': 'Text Color'
                    }
                }
            }
        });
        
        // Setup tooltips for editor elements after initialization
        this.setupTooltips();
    }
    
    setupTooltips() {
        // Wait for editor DOM to be ready
        setTimeout(() => {
            const editorElement = document.getElementById(this.containerId);
            if (!editorElement) return;
            
            // Add tooltips to toolbar buttons
            const toolbarButtons = editorElement.querySelectorAll('.ce-toolbar__settings-btn, .ce-toolbar__plus, .ce-toolbar__actions-btn');
            
            toolbarButtons.forEach(btn => {
                // Store original title
                const originalTitle = btn.getAttribute('title') || 
                    (btn.classList.contains('ce-toolbar__settings-btn') ? 'Settings' :
                     btn.classList.contains('ce-toolbar__plus') ? 'Add block' : 'Actions');
                
                // Apply tooltip on hover using Editor.js API if available
                if (this.editor && this.editor.api && this.editor.api.tooltip) {
                    btn.addEventListener('mouseenter', (e) => {
                        this.editor.api.tooltip.show(btn, originalTitle, {
                            placement: 'top',
                            delay: 300
                        });
                    });
                    btn.addEventListener('mouseleave', () => {
                        if (this.editor && this.editor.api && this.editor.api.tooltip) {
                            this.editor.api.tooltip.hide();
                        }
                    });
                }
            });
            
            // Add tooltips to block tunes
            const tuneButtons = editorElement.querySelectorAll('.ce-popover-item, .ce-settings__button');
            tuneButtons.forEach(btn => {
                const tooltipText = btn.getAttribute('data-tune') || 
                    (btn.innerHTML.includes('stretch') ? 'Stretch block' :
                     btn.innerHTML.includes('border') ? 'Add border' :
                     btn.innerHTML.includes('background') ? 'Add background' : 'Toggle setting');
                
                if (this.editor && this.editor.api && this.editor.api.tooltip) {
                    btn.addEventListener('mouseenter', (e) => {
                        this.editor.api.tooltip.show(btn, tooltipText, {
                            placement: 'right',
                            delay: 200
                        });
                    });
                    btn.addEventListener('mouseleave', () => {
                        if (this.editor && this.editor.api && this.editor.api.tooltip) {
                            this.editor.api.tooltip.hide();
                        }
                    });
                }
            });
        }, 500);
    }
    
    async saveToTextarea() {
        if (!this.editor) return;
        
        try {
            const outputData = await this.editor.save();
            document.getElementById(this.textareaId).value = JSON.stringify(outputData);
            
            // Trigger change event for WordPress
            const textarea = document.getElementById(this.textareaId);
            if (textarea && typeof jQuery !== 'undefined') {
                jQuery(textarea).trigger('change');
            }
        } catch (error) {
            console.error('Editor.js save failed:', error);
        }
    }
    
    async getData() {
        if (!this.editor) return null;
        return await this.editor.save();
    }
    
    async insertBlock(blockType, data) {
        if (!this.editor) return;
        
        try {
            await this.editor.blocks.insert(blockType, data);
        } catch (error) {
            console.error('Failed to insert block:', error);
        }
    }
    
    clear() {
        if (!this.editor) return;
        
        this.editor.blocks.clear();
    }
    
    destroy() {
        if (this.editor && this.editor.destroy) {
            this.editor.destroy();
        }
    }
}

// Initialize Editor.js for all form fields
function initCSFEditorJS() {
    const editorContainers = document.querySelectorAll('.csf-editorjs-container:not(.csf-initialized)');
    
    editorContainers.forEach(container => {
        container.classList.add('csf-initialized');
        const textareaId = container.dataset.textareaId;
        const textarea = document.getElementById(textareaId);
        
        if (textarea && typeof EditorJS !== 'undefined') {
            // Store instance on container for later access
            const editorInstance = new CSFEditorJS(container.id, textareaId, {
                uploadEndpoint: typeof csfEditorJS !== 'undefined' ? csfEditorJS.uploadUrl : '',
                placeholder: textarea.placeholder || 'Start writing...',
                autofocus: false
            });
            container.csfEditorInstance = editorInstance;
        }
    });
    
    // Initialize custom media buttons
    const mediaBtns = document.querySelectorAll('.csf-editorjs-media-btn:not(.csf-initialized)');
    mediaBtns.forEach(btn => {
        btn.classList.add('csf-initialized');
        const editorId = btn.dataset.editor;
        const fileInput = document.querySelector('.csf-editorjs-media-input[data-editor="' + editorId + '"]');
        
        if (fileInput) {
            btn.addEventListener('click', () => {
                fileInput.click();
            });
            
            fileInput.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;
                
                // Show loading state
                const originalText = btn.innerHTML;
                btn.innerHTML = 'Uploading...';
                btn.disabled = true;
                
                try {
                    const result = await window.csfEditorJS.uploadImage(file);
                    
                    if (result.success === 1) {
                        const container = document.getElementById(editorId);
                        if (container && container.csfEditorInstance && container.csfEditorInstance.editor) {
                            // Insert image block
                            await container.csfEditorInstance.insertBlock('image', {
                                file: { url: result.file.url }
                            });
                        }
                    } else {
                        alert('Upload failed: ' + (result.message || 'Unknown error'));
                    }
                } catch (err) {
                    console.error('Upload error:', err);
                    alert('Upload error: ' + err.message);
                } finally {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    fileInput.value = ''; // Reset input
                }
            });
        }
    });
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCSFEditorJS);
} else {
    initCSFEditorJS();
}

// For WordPress block editor - handle dynamically added fields
if (typeof wp !== 'undefined' && wp.data) {
    let debounceTimer;
    wp.data.subscribe(function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            initCSFEditorJS();
        }, 300);
    });
}

// Optional: Add keyboard shortcuts documentation
if (typeof wp !== 'undefined' && wp.hooks) {
    wp.hooks.addAction('editorjs.ready', 'csf/editorjs', () => {
        console.log('Editor.js is ready! Available shortcuts:');
        console.log('- CMD+B: Bold');
        console.log('- CMD+I: Italic');
        console.log('- CMD+K: Link');
        console.log('- CMD+SHIFT+M: Marker');
        console.log('- CMD+SHIFT+C: Inline Code');
        console.log('- CMD+U: Underline');
    });
}