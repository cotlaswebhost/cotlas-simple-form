<?php
/**
 * Admin asset loader.
 *
 * @package CotlasSimpleForms
 */

namespace Cotlas\SimpleForms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads assets for the upgraded admin pages.
 */
class Assets {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue dashboard/admin styles only on plugin pages.
	 *
	 * @param string $hook_suffix Current admin hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( false === strpos( $hook_suffix, 'csf-' ) && false === strpos( $hook_suffix, 'csf_form' ) ) {
			return;
		}

		wp_enqueue_style(
			'csf-admin',
			CSF_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			CSF_PLUGIN_VERSION
		);
	}
}
