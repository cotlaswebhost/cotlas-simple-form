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

            paragraph: {
                class: Paragraph,
                inlineToolbar: true
            },

            header: {
                class: Header,
                inlineToolbar: true,
                config:{
                    levels:[1,2,3,4],
                    defaultLevel:2
                }
            },

            list:{
                class: List,
                inlineToolbar:true
            },

            quote:{
                class: Quote,
                inlineToolbar:true
            },

            checklist:{
                class: Checklist,
                inlineToolbar:true
            },

            table:{
                class: Table,
                inlineToolbar:true
            },

            code:{
                class: CodeTool
            },

            delimiter:{
                class: Delimiter
            },

            image:{
                class: ImageTool,
                config:{
                    uploader:{
                        uploadByFile:file=>
                            window.csfEditorJS.uploadImage(file)
                    }
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
        this.editor=new EditorJS({
            holder:this.containerId,
            tools:tools,
            data:initialData,
            placeholder:'Start writing...',
            autofocus:false,
            inlineToolbar:true,
            onChange:async()=>{
                const data=await this.editor.save();
                document.getElementById(
                this.textareaId
                ).value=JSON.stringify(data);
            }
        });
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