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
        
        // Default tools configuration
        const defaultTools = {
            header: {
                class: Header,
                config: {
                    placeholder: 'Enter a header',
                    levels: [1, 2, 3, 4],
                    defaultLevel: 2
                }
            },
            list: {
                class: List,
                inlineToolbar: true,
                config: {
                    defaultStyle: 'unordered'
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
                config: {
                    endpoints: {
                        byFile: this.config.uploadEndpoint || csfEditorJS.uploadUrl,
                    }
                }
            },
            embed: {
                class: Embed,
                config: {
                    services: {
                        youtube: true,
                        vimeo: true,
                        twitter: true,
                        instagram: true,
                        facebook: true,
                        codepen: true
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
                class: Delimiter
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
                class: CodeTool
            },
            raw: {
                class: RawTool
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
                shortcut: 'CMD+SHIFT+M'
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
        
        // Initialize Editor.js
        this.editor = new EditorJS({
            holder: this.containerId,
            tools: tools,
            data: initialData,
            placeholder: this.config.placeholder || 'Start writing...',
            autofocus: this.config.autofocus || false,
            onChange: () => {
                this.saveToTextarea();
            }
        });
    }
    
    async saveToTextarea() {
        if (!this.editor) return;
        
        try {
            const outputData = await this.editor.save();
            document.getElementById(this.textareaId).value = JSON.stringify(outputData);
        } catch (error) {
            console.error('Editor.js save failed:', error);
        }
    }
    
    async getData() {
        if (!this.editor) return null;
        return await this.editor.save();
    }
    
    destroy() {
        if (this.editor && this.editor.destroy) {
            this.editor.destroy();
        }
    }
}

// Initialize Editor.js for all form fields
document.addEventListener('DOMContentLoaded', function() {
    // For frontend forms
    const editorContainers = document.querySelectorAll('.csf-editorjs-container');
    editorContainers.forEach(container => {
        const textareaId = container.dataset.textareaId;
        const textarea = document.getElementById(textareaId);
        
        if (textarea && typeof EditorJS !== 'undefined') {
            new CSFEditorJS(container.id, textareaId, {
                uploadEndpoint: csfEditorJS.uploadUrl,
                placeholder: textarea.placeholder || 'Start writing...'
            });
        }
    });
});

// For block editor (Gutenberg) - handle dynamically added fields
if (typeof wp !== 'undefined' && wp.data) {
    wp.data.subscribe(function() {
        setTimeout(function() {
            const editorContainers = document.querySelectorAll('.csf-editorjs-container:not(.csf-initialized)');
            editorContainers.forEach(container => {
                container.classList.add('csf-initialized');
                const textareaId = container.dataset.textareaId;
                const textarea = document.getElementById(textareaId);
                
                if (textarea && typeof EditorJS !== 'undefined') {
                    new CSFEditorJS(container.id, textareaId, {
                        uploadEndpoint: csfEditorJS.uploadUrl,
                        placeholder: textarea.placeholder || 'Start writing...'
                    });
                }
            });
        }, 100);
    });
}