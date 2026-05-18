<?php
/**
 * Activity Log Admin Page.
 *
 * @package CotlasSimpleForms
 */

namespace Cotlas\SimpleForms\Admin;

use Cotlas\SimpleForms\ActivityLogger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the activity log admin page.
 */
class ActivityLogPage {

	/**
	 * Activity logger.
	 *
	 * @var ActivityLogger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param ActivityLogger $logger Activity logger.
	 */
	public function __construct( ActivityLogger $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Add submenu page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'edit.php?post_type=csf_form',
			__( 'Activity Log', 'cotlas-simple-forms' ),
			__( 'Activity Log', 'cotlas-simple-forms' ),
			'manage_options',
			'csf-activity-log',
			array( $this, 'render' )
		);
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

        $logs = $this->logger->latest( 100 );

		$template_file = CSF_PLUGIN_DIR . 'templates/admin/activity-log.php';
		if ( file_exists( $template_file ) ) {
			include $template_file;
		}
	}
}
