<?php
/**
 * Plugin Name: Cotlas Simple Forms
 * Description: Simple form builder plugin by Cotlas Web solution. Login / Registration form builder. Block based form builder to create forms.
 * Version: 1.1.2
 * Author: Cotlas
 * Text Domain: cotlas-simple-forms
 * Update URI: https://api.github.com/repos/cotlaswebhost/cotlas-simple-form/releases/latest
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CSF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CSF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CSF_PLUGIN_VERSION', '1.1.2' );
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
