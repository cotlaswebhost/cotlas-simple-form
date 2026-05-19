<?php
/**
 * Plugin Name: Cotlas Simple Forms
 * Description: Simple form builder plugin by Cotlas Web solution. Login / Registration form builder. Block based form builder to create forms.
 * Version: 1.1.9
 * Author: Cotlas
 * Text Domain: cotlas-simple-forms
 * Update URI: https://api.github.com/repos/cotlaswebhost/cotlas-simple-form/releases/latest
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CSF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CSF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CSF_PLUGIN_VERSION', '1.1.4' );
define( 'CSF_PLUGIN_FILE', __FILE__ );

require_once CSF_PLUGIN_DIR . 'includes/autoload.php';
require_once CSF_PLUGIN_DIR . 'inc/github-updater.php';
require_once CSF_PLUGIN_DIR . 'includes/class-csf-post-types.php';
require_once CSF_PLUGIN_DIR . 'includes/class-csf-blocks.php';
require_once CSF_PLUGIN_DIR . 'includes/class-csf-settings.php';
require_once CSF_PLUGIN_DIR . 'includes/class-csf-submission.php';
require_once CSF_PLUGIN_DIR . 'includes/class-csf-render.php';
require_once CSF_PLUGIN_DIR . 'includes/class-csf-admin-list.php';

// Initialize
new CSF_Post_Types();
new CSF_Blocks();
new CSF_Settings();
$submission = new CSF_Submission();
new CSF_Render();
new CSF_Admin_List();
\Cotlas\SimpleForms\Plugin::instance()->boot();

// Frontend assets
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'csf-fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
        array(),
        '4.7.0'
    );
} );

// Global SMTP Hook
if ( get_option( 'csf_enable_smtp' ) && get_option( 'csf_use_global_smtp' ) ) {
    add_action( 'phpmailer_init', array( $submission, 'configure_smtp' ) );
}

// Activation Hook to flush rewrite rules
register_activation_hook( __FILE__, function() {
    CSF_Post_Types::register_post_types();
    \Cotlas\SimpleForms\Plugin::activate();
    flush_rewrite_rules();
});

// Add AJAX handler for Editor.js image uploads
add_action('wp_ajax_csf_editorjs_upload', 'csf_handle_editorjs_upload');
add_action('wp_ajax_nopriv_csf_editorjs_upload', 'csf_handle_editorjs_upload');

function csf_handle_editorjs_upload() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'csf_editorjs_upload')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(array('message' => 'File upload failed'));
    }
    
    $file = $_FILES['file'];
    
    // Validate file type
    $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    if (!in_array($file['type'], $allowed_types)) {
        wp_send_json_error(array('message' => 'Invalid file type. Only images are allowed.'));
    }
    
    // Handle file upload
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    $upload_overrides = array('test_form' => false);
    $upload = wp_handle_upload($file, $upload_overrides);
    
    if (isset($upload['error']) && $upload['error'] !== false) {
        wp_send_json_error(array('message' => $upload['error']));
    }
    
    // Create attachment
    $attachment = array(
        'post_mime_type' => $upload['type'],
        'post_title' => sanitize_file_name($file['name']),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    
    $attachment_id = wp_insert_attachment($attachment, $upload['file']);
    
    if (is_wp_error($attachment_id)) {
        wp_send_json_error(array('message' => 'Failed to create attachment'));
    }
    
    // Generate metadata
    $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    wp_update_attachment_metadata($attachment_id, $attachment_data);
    
    wp_send_json_success(array(
        'url' => $upload['url'],
        'attachment_id' => $attachment_id
    ));
}
