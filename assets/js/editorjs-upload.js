// assets/js/editorjs-upload.js

(function() {
    if (typeof window.csfEditorJS === 'undefined') {
        window.csfEditorJS = {};
    }
    
    window.csfEditorJS.uploadImage = async function(file) {
        const formData = new FormData();
        formData.append('action', 'csf_editorjs_upload');
        formData.append('file', file);
        formData.append('nonce', csfEditorJS.uploadNonce);
        
        try {
            const response = await fetch(csfEditorJS.ajaxUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                return {
                    success: 1,
                    file: {
                        url: result.data.url
                    }
                };
            } else {
                return {
                    success: 0,
                    message: result.data.message
                };
            }
        } catch (error) {
            return {
                success: 0,
                message: error.message
            };
        }
    };
})();