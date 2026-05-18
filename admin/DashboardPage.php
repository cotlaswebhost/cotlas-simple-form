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
		add_action( 'admin_menu', array( $this, 'order_submenu' ), 99 );
		add_action( 'admin_init', array( $this, 'redirect_plugin_pages_to_dashboard' ) );
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
	 * Keep Dashboard as the first submenu entry.
	 *
	 * @return void
	 */
	public function order_submenu() {
		global $submenu;

		$parent = 'edit.php?post_type=csf_form';
		if ( empty( $submenu[ $parent ] ) ) {
			return;
		}

		foreach ( $submenu[ $parent ] as &$item ) {
			if ( empty( $item[2] ) ) {
				continue;
			}
			if ( 'edit.php?post_type=csf_form' === $item[2] ) {
				$item[2] = add_query_arg(
					array(
						'post_type' => 'csf_form',
						'page'      => 'csf-dashboard',
						'tab'       => 'all_forms',
					),
					'edit.php'
				);
			}
			if ( 'post-new.php?post_type=csf_form' === $item[2] ) {
				$item[2] = add_query_arg(
					array(
						'post_type' => 'csf_form',
						'page'      => 'csf-dashboard',
						'tab'       => 'add_form',
					),
					'edit.php'
				);
			}
		}
		unset( $item );

		usort(
			$submenu[ $parent ],
			function ( $a, $b ) {
				$order = array(
					'csf-dashboard'    => 0,
					'edit.php?post_type=csf_form&page=csf-dashboard&tab=all_forms' => 1,
					'edit.php?post_type=csf_form&page=csf-dashboard&tab=add_form' => 2,
					'csf-settings'     => 3,
					'csf-submissions'  => 4,
					'csf-email-templates' => 5,
					'csf-activity-log' => 6,
				);
				$a_key = isset( $a[2] ) ? $a[2] : '';
				$b_key = isset( $b[2] ) ? $b[2] : '';
				$a_pos = isset( $order[ $a_key ] ) ? $order[ $a_key ] : 50;
				$b_pos = isset( $order[ $b_key ] ) ? $order[ $b_key ] : 50;

				return $a_pos - $b_pos;
			}
		);
	}

	/**
	 * Route plugin subpages into dashboard tabs.
	 *
	 * @return void
	 */
	public function redirect_plugin_pages_to_dashboard() {
		if ( ! is_admin() || wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_GET['post_type'], $_GET['page'] ) || 'csf_form' !== $_GET['post_type'] ) {
			return;
		}

		$page = sanitize_key( $_GET['page'] );
		$map  = array(
			'csf-settings'        => 'settings',
			'csf-submissions'     => 'submissions',
			'csf-email-templates' => 'email_templates',
			'csf-activity-log'    => 'activity_log',
		);

		if ( 'csf-dashboard' === $page || 'csf-view-submission' === $page || ! isset( $map[ $page ] ) ) {
			return;
		}

		$args = array(
			'post_type' => 'csf_form',
			'page'      => 'csf-dashboard',
			'tab'       => $map[ $page ],
		);

		if ( isset( $_GET['tab'] ) ) {
			$args['csf_settings_tab'] = sanitize_key( wp_unslash( $_GET['tab'] ) );
		}

		foreach ( array( 's', 'filter_form_id', 'paged', 'action', 'post' ) as $key ) {
			if ( isset( $_GET[ $key ] ) ) {
				$args[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
			}
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'edit.php' ) ) );
		exit;
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
			'total_payment_forms'   => $this->forms->count_by_type( 'payment' ),
			'total_donation_forms'  => $this->forms->count_by_type( 'donation' ),
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
