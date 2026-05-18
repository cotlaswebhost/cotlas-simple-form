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
		add_action( 'wp_ajax_csf_dashboard_tab', array( $this, 'ajax_dashboard_tab' ) );
		add_action( 'wp_ajax_csf_dashboard_create_form', array( $this, 'ajax_create_form' ) );
		add_action( 'wp_ajax_csf_dashboard_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_csf_dashboard_view_submission', array( $this, 'ajax_view_submission' ) );
		add_action( 'wp_ajax_csf_dashboard_delete_submission', array( $this, 'ajax_delete_submission' ) );
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
	 * Save dashboard settings without a page reload.
	 *
	 * @return void
	 */
	public function ajax_save_settings() {
		check_ajax_referer( 'csf_dashboard_tabs', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'cotlas-simple-forms' ) ) );
		}

		$section = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : 'settings';

		if ( 'smtp' === $section ) {
			$this->save_option_values(
				array(
					'csf_to_email'       => 'email',
					'csf_enable_smtp'    => 'bool',
					'csf_use_global_smtp'=> 'bool',
					'csf_smtp_host'      => 'text',
					'csf_smtp_port'      => 'text',
					'csf_smtp_user'      => 'text',
					'csf_smtp_pass'      => 'text',
					'csf_smtp_encryption'=> 'key',
					'csf_from_email'     => 'email',
					'csf_from_name'      => 'text',
				)
			);
		} else {
			$this->save_option_values(
				array(
					'csf_delete_data_uninstall' => 'bool',
					'csf_turnstile_site_key'    => 'text',
					'csf_turnstile_secret_key'  => 'text',
					'csf_google_places_api_key' => 'text',
					'csf_enable_limit'          => 'bool',
					'csf_limit_duration'        => 'key',
					'csf_allowed_file_types'    => 'text',
					'csf_max_upload_size'       => 'absint',
					'csf_disable_frontend_css'  => 'bool',
					'csf_disable_frontend_js'   => 'bool',
				)
			);
		}

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'cotlas-simple-forms' ) ) );
	}

	/**
	 * View submission data in the dashboard.
	 *
	 * @return void
	 */
	public function ajax_view_submission() {
		check_ajax_referer( 'csf_dashboard_tabs', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'cotlas-simple-forms' ) ) );
		}

		$submission_id = isset( $_POST['submission_id'] ) ? absint( $_POST['submission_id'] ) : 0;
		$post          = get_post( $submission_id );
		if ( ! $post || 'csf_submission' !== $post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Submission not found.', 'cotlas-simple-forms' ) ) );
		}

		$form_data = get_post_meta( $submission_id, 'csf_data', true );
		$files     = get_post_meta( $submission_id, 'csf_files', true );
		$form_id   = get_post_meta( $submission_id, 'csf_form_id', true );

		ob_start();
		include CSF_PLUGIN_DIR . 'templates/admin/dashboard-submission-view.php';
		wp_send_json_success( array( 'html' => ob_get_clean() ) );
	}

	/**
	 * Delete a submission from the dashboard.
	 *
	 * @return void
	 */
	public function ajax_delete_submission() {
		check_ajax_referer( 'csf_dashboard_tabs', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'cotlas-simple-forms' ) ) );
		}

		$submission_id = isset( $_POST['submission_id'] ) ? absint( $_POST['submission_id'] ) : 0;
		$post          = get_post( $submission_id );
		if ( ! $post || 'csf_submission' !== $post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Submission not found.', 'cotlas-simple-forms' ) ) );
		}

		$deleted = wp_delete_post( $submission_id, true );
		if ( ! $deleted ) {
			wp_send_json_error( array( 'message' => __( 'Could not delete submission.', 'cotlas-simple-forms' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Submission deleted.', 'cotlas-simple-forms' ) ) );
	}

	/**
	 * Load a dashboard tab without refreshing the page.
	 *
	 * @return void
	 */
	public function ajax_dashboard_tab() {
		check_ajax_referer( 'csf_dashboard_tabs', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'cotlas-simple-forms' ) ) );
		}

		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'overview';
		if ( isset( $_POST['settings_tab'] ) && '' !== $_POST['settings_tab'] ) {
			$_GET['csf_settings_tab'] = sanitize_key( wp_unslash( $_POST['settings_tab'] ) );
		}
		if ( isset( $_POST['form_type'] ) && '' !== $_POST['form_type'] ) {
			$_GET['csf_default_form_type'] = sanitize_key( wp_unslash( $_POST['form_type'] ) );
		}

		$url_args = array(
			'post_type' => 'csf_form',
			'page'      => 'csf-dashboard',
			'tab'       => $tab,
		);
		if ( isset( $_GET['csf_settings_tab'] ) ) {
			$url_args['csf_settings_tab'] = sanitize_key( wp_unslash( $_GET['csf_settings_tab'] ) );
		}
		if ( isset( $_GET['csf_default_form_type'] ) ) {
			$url_args['csf_default_form_type'] = sanitize_key( wp_unslash( $_GET['csf_default_form_type'] ) );
		}

		wp_send_json_success(
			array(
				'html' => $this->render_tab_content( $tab ),
				'url'  => add_query_arg( $url_args, admin_url( 'edit.php' ) ),
			)
		);
	}

	/**
	 * Create a draft form from the dashboard Add Form tab.
	 *
	 * @return void
	 */
	public function ajax_create_form() {
		check_ajax_referer( 'csf_dashboard_tabs', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'cotlas-simple-forms' ) ) );
		}

		$title = isset( $_POST['csf_form_title'] ) ? sanitize_text_field( wp_unslash( $_POST['csf_form_title'] ) ) : '';
		$type  = isset( $_POST['csf_form_type'] ) ? sanitize_key( wp_unslash( $_POST['csf_form_type'] ) ) : 'normal';

		if ( '' === $title ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a form title.', 'cotlas-simple-forms' ) ) );
		}

		$allowed_types = array( 'normal', 'login', 'register', 'payment', 'donation', 'frontend_post' );
		if ( ! in_array( $type, $allowed_types, true ) ) {
			$type = 'normal';
		}

		$form_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_type'   => 'csf_form',
				'post_status' => 'draft',
			)
		);

		if ( is_wp_error( $form_id ) || ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Could not create the form.', 'cotlas-simple-forms' ) ) );
		}

		update_post_meta( $form_id, 'csf_form_type', $type );

		wp_send_json_success(
			array(
				'redirect' => get_edit_post_link( $form_id, 'raw' ),
			)
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
			'total_entries'         => count( $this->get_valid_submissions( -1 ) ),
			'total_login_forms'     => $this->forms->count_by_type( 'login' ),
			'total_register_forms'  => $this->forms->count_by_type( 'register' ),
			'total_payment_forms'   => $this->forms->count_by_type( 'payment' ),
			'total_donation_forms'  => $this->forms->count_by_type( 'donation' ),
			'total_contact_forms'   => $this->forms->count_contact_forms(),
			'total_post_forms'      => $this->forms->count_by_type( 'frontend_post' ),
			'total_email_sent'      => (int) get_option( 'csf_email_sent_count', 0 ),
			'smtp_enabled'          => (bool) get_option( 'csf_enable_smtp' ),
			'latest_submissions'    => $this->get_valid_submissions( 5 ),
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
	 * Render dashboard tab content.
	 *
	 * @param string $tab Active tab.
	 * @return string
	 */
	public function render_tab_content( $tab ) {
		ob_start();

		switch ( $tab ) {
			case 'all_forms':
				$this->render_all_forms_tab();
				break;
			case 'add_form':
				$this->render_add_form_tab();
				break;
			case 'settings':
				$this->render_settings_tab();
				break;
			case 'smtp':
				$this->render_smtp_tab();
				break;
			case 'submissions':
				$this->render_submissions_tab();
				break;
			case 'email_templates':
				echo '<div class="csf-dashboard-tab-panel">';
				include CSF_PLUGIN_DIR . 'templates/admin/email-templates.php';
				echo '</div>';
				break;
			case 'activity_log':
				$logs = $this->activity_logger->latest( 100 );
				echo '<div class="csf-dashboard-tab-panel">';
				include CSF_PLUGIN_DIR . 'templates/admin/activity-log.php';
				echo '</div>';
				break;
			case 'overview':
			default:
				$this->render_overview_tab();
				break;
		}

		return ob_get_clean();
	}

	/**
	 * Render the overview dashboard content.
	 *
	 * @return void
	 */
	private function render_overview_tab() {
		$data = $this->dashboard_data();
		include CSF_PLUGIN_DIR . 'templates/admin/dashboard-overview.php';
	}

	/**
	 * Render a clean forms table without WordPress admin chrome.
	 *
	 * @return void
	 */
	private function render_all_forms_tab() {
		$forms = get_posts(
			array(
				'post_type'      => 'csf_form',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 50,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		include CSF_PLUGIN_DIR . 'templates/admin/dashboard-all-forms.php';
	}

	/**
	 * Render dashboard form creator.
	 *
	 * @return void
	 */
	private function render_add_form_tab() {
		include CSF_PLUGIN_DIR . 'templates/admin/dashboard-add-form.php';
	}

	/**
	 * Render settings rows.
	 *
	 * @return void
	 */
	private function render_settings_tab() {
		include CSF_PLUGIN_DIR . 'templates/admin/dashboard-settings.php';
	}

	/**
	 * Render SMTP settings.
	 *
	 * @return void
	 */
	private function render_smtp_tab() {
		include CSF_PLUGIN_DIR . 'templates/admin/dashboard-smtp.php';
	}

	/**
	 * Render dashboard-native submissions.
	 *
	 * @return void
	 */
	private function render_submissions_tab() {
		$submissions = $this->get_valid_submissions( 50 );

		include CSF_PLUGIN_DIR . 'templates/admin/dashboard-submissions.php';
	}

	/**
	 * Save a typed list of options.
	 *
	 * @param array $options Option map.
	 * @return void
	 */
	private function save_option_values( $options ) {
		foreach ( $options as $option => $type ) {
			$value = isset( $_POST[ $option ] ) ? wp_unslash( $_POST[ $option ] ) : '';
			if ( 'bool' === $type ) {
				update_option( $option, isset( $_POST[ $option ] ) ? '1' : '' );
				continue;
			}
			if ( 'email' === $type ) {
				update_option( $option, sanitize_email( $value ) );
				continue;
			}
			if ( 'absint' === $type ) {
				update_option( $option, absint( $value ) );
				continue;
			}
			if ( 'key' === $type ) {
				update_option( $option, sanitize_key( $value ) );
				continue;
			}
			update_option( $option, sanitize_text_field( $value ) );
		}
	}

	/**
	 * Collect dashboard data.
	 *
	 * @return array
	 */
	private function dashboard_data() {
		return array(
			'total_forms'           => $this->forms->count_all(),
			'total_entries'         => count( $this->get_valid_submissions( -1 ) ),
			'total_login_forms'     => $this->forms->count_by_type( 'login' ),
			'total_register_forms'  => $this->forms->count_by_type( 'register' ),
			'total_payment_forms'   => $this->forms->count_by_type( 'payment' ),
			'total_donation_forms'  => $this->forms->count_by_type( 'donation' ),
			'total_contact_forms'   => $this->forms->count_contact_forms(),
			'total_post_forms'      => $this->forms->count_by_type( 'frontend_post' ),
			'total_email_sent'      => (int) get_option( 'csf_email_sent_count', 0 ),
			'smtp_enabled'          => (bool) get_option( 'csf_enable_smtp' ),
			'latest_submissions'    => $this->get_valid_submissions( 5 ),
			'latest_frontend_posts' => $this->latest_frontend_posts(),
			'latest_activity'       => $this->activity_logger->latest( 8 ),
			'plugin_version'        => CSF_PLUGIN_VERSION,
		);
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

	/**
	 * Get submissions that contain actual saved form data.
	 *
	 * @param int $limit Number of submissions, or -1 for all.
	 * @return array
	 */
	private function get_valid_submissions( $limit = 50 ) {
		$raw_submissions = get_posts(
			array(
				'post_type'      => 'csf_submission',
				'post_status'    => 'publish',
				'posts_per_page' => -1 === $limit ? -1 : max( $limit * 3, 20 ),
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$submissions = array();
		foreach ( $raw_submissions as $submission ) {
			$data = get_post_meta( $submission->ID, 'csf_data', true );
			if ( empty( $data ) || ! is_array( $data ) ) {
				continue;
			}
			$form_id = get_post_meta( $submission->ID, 'csf_form_id', true );
			if ( $form_id ) {
				$form_type = get_post_meta( $form_id, 'csf_form_type', true );
				if ( 'frontend_post' === $form_type ) {
					continue;
				}
			}
			$submissions[] = $submission;
			if ( -1 !== $limit && count( $submissions ) >= $limit ) {
				break;
			}
		}

		return $submissions;
	}
}
