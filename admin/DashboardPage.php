<?php
/**
 * Plugin dashboard page.
 *
 * @package CotlasSimpleForms
 */

namespace Cotlas\SimpleForms\Admin;

use Cotlas\SimpleForms\ActivityLogger;
use Cotlas\SimpleForms\FormRepository;
use Cotlas\SimpleForms\SubmissionRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a central dashboard for Cotlas Simple Forms.
 */
class DashboardPage {

	/**
	 * Forms repository.
	 *
	 * @var FormRepository
	 */
	private $forms;

	/**
	 * Submissions repository.
	 *
	 * @var SubmissionRepository
	 */
	private $submissions;

	/**
	 * Activity logger.
	 *
	 * @var ActivityLogger
	 */
	private $activity_logger;

	/**
	 * Constructor.
	 *
	 * @param FormRepository       $forms           Forms repository.
	 * @param SubmissionRepository $submissions     Submissions repository.
	 * @param ActivityLogger       $activity_logger Activity logger.
	 */
	public function __construct( FormRepository $forms, SubmissionRepository $submissions, ActivityLogger $activity_logger ) {
		$this->forms           = $forms;
		$this->submissions     = $submissions;
		$this->activity_logger = $activity_logger;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'add_page' ), 5 );
	}

	/**
	 * Add dashboard submenu.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'edit.php?post_type=csf_form',
			__( 'Cotlas Forms Dashboard', 'cotlas-simple-forms' ),
			__( 'Dashboard', 'cotlas-simple-forms' ),
			'manage_options',
			'csf-dashboard',
			array( $this, 'render' )
		);
	}

	/**
	 * Render dashboard.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'cotlas-simple-forms' ) );
		}

		$data = array(
			'total_forms'           => $this->forms->count_all(),
			'total_entries'         => $this->submissions->count_all(),
			'total_login_forms'     => $this->forms->count_by_type( 'login' ),
			'total_register_forms'  => $this->forms->count_by_type( 'register' ),
			'total_contact_forms'   => $this->forms->count_contact_forms(),
			'total_post_forms'      => $this->forms->count_by_type( 'frontend_post' ),
			'total_email_sent'      => (int) get_option( 'csf_email_sent_count', 0 ),
			'smtp_enabled'          => (bool) get_option( 'csf_enable_smtp' ),
			'latest_submissions'    => $this->submissions->latest( 5 ),
			'latest_frontend_posts' => $this->latest_frontend_posts(),
			'latest_activity'       => $this->activity_logger->latest( 8 ),
			'plugin_version'        => CSF_PLUGIN_VERSION,
		);

		$template = CSF_PLUGIN_DIR . 'templates/admin/dashboard.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Get latest frontend posts created by the future module.
	 *
	 * @return array
	 */
	private function latest_frontend_posts() {
		return get_posts(
			array(
				'post_type'              => 'any',
				'post_status'            => array( 'publish', 'pending', 'draft' ),
				'posts_per_page'         => 5,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'   => '_csf_frontend_post',
						'value' => '1',
					),
				),
			)
		);
	}
}
