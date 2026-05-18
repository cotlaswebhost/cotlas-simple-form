<?php
/**
 * Email Templates Admin Page.
 *
 * @package CotlasSimpleForms
 */

namespace Cotlas\SimpleForms\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the email templates settings page.
 */
class EmailTemplatesPage {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add submenu page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'edit.php?post_type=csf_form',
			__( 'Email Templates', 'cotlas-simple-forms' ),
			__( 'Email Templates', 'cotlas-simple-forms' ),
			'manage_options',
			'csf-email-templates',
			array( $this, 'render' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'csf_email_templates_group', 'csf_email_template_admin_notification' );
		register_setting( 'csf_email_templates_group', 'csf_email_template_user_confirmation' );
		register_setting( 'csf_email_templates_group', 'csf_email_template_frontend_post' );
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'cotlas-simple-forms' ) );
		}

		$template_file = CSF_PLUGIN_DIR . 'templates/admin/email-templates.php';
		if ( file_exists( $template_file ) ) {
			include $template_file;
		}
	}
}
