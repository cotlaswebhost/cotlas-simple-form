<?php
/**
 * Dashboard template.
 *
 * @package CotlasSimpleForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
$tabs       = array(
	'overview'        => __( 'Dashboard', 'cotlas-simple-forms' ),
	'all_forms'       => __( 'All Forms', 'cotlas-simple-forms' ),
	'add_form'        => __( 'Add Form', 'cotlas-simple-forms' ),
	'settings'        => __( 'Settings', 'cotlas-simple-forms' ),
	'submissions'     => __( 'Submissions', 'cotlas-simple-forms' ),
	'email_templates' => __( 'Email Templates', 'cotlas-simple-forms' ),
	'activity_log'    => __( 'Activity Log', 'cotlas-simple-forms' ),
);

$cards = array(
	array(
		'label' => __( 'Total Forms', 'cotlas-simple-forms' ),
		'value' => $data['total_forms'],
		'icon'  => 'dashicons-feedback',
	),
	array(
		'label' => __( 'Total Entries', 'cotlas-simple-forms' ),
		'value' => $data['total_entries'],
		'icon'  => 'dashicons-list-view',
	),
	array(
		'label' => __( 'Login Forms', 'cotlas-simple-forms' ),
		'value' => $data['total_login_forms'],
		'icon'  => 'dashicons-lock',
	),
	array(
		'label' => __( 'Registration Forms', 'cotlas-simple-forms' ),
		'value' => $data['total_register_forms'],
		'icon'  => 'dashicons-admin-users',
	),
	array(
		'label' => __( 'Payment Forms', 'cotlas-simple-forms' ),
		'value' => $data['total_payment_forms'],
		'icon'  => 'dashicons-money-alt',
	),
	array(
		'label' => __( 'Donation Forms', 'cotlas-simple-forms' ),
		'value' => $data['total_donation_forms'],
		'icon'  => 'dashicons-heart',
	),
	array(
		'label' => __( 'Contact Forms', 'cotlas-simple-forms' ),
		'value' => $data['total_contact_forms'],
		'icon'  => 'dashicons-email-alt',
	),
	array(
		'label' => __( 'Post Forms', 'cotlas-simple-forms' ),
		'value' => $data['total_post_forms'],
		'icon'  => 'dashicons-edit-page',
	),
	array(
		'label' => __( 'Email Sent', 'cotlas-simple-forms' ),
		'value' => $data['total_email_sent'],
		'icon'  => 'dashicons-email',
	),
	array(
		'label' => __( 'SMTP Status', 'cotlas-simple-forms' ),
		'value' => $data['smtp_enabled'] ? __( 'Active', 'cotlas-simple-forms' ) : __( 'Inactive', 'cotlas-simple-forms' ),
		'icon'  => 'dashicons-cloud',
	),
);
?>

<div class="wrap csf-admin-dashboard">
	<div class="csf-dashboard-header">
		<div>
			<h1><?php esc_html_e( 'Cotlas Forms Dashboard', 'cotlas-simple-forms' ); ?></h1>
			<p><?php esc_html_e( 'Central overview for forms, entries, email, and upcoming frontend post modules.', 'cotlas-simple-forms' ); ?></p>
		</div>
		<span class="csf-version-badge">
			<?php echo esc_html( sprintf( __( 'Version %s', 'cotlas-simple-forms' ), $data['plugin_version'] ) ); ?>
		</span>
	</div>

	<div class="csf-quick-actions">
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=csf_form' ) ); ?>">
			<span class="dashicons dashicons-plus-alt2"></span>
			<?php esc_html_e( 'Create Form', 'cotlas-simple-forms' ); ?>
		</a>
		<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=csf_form&page=csf-submissions' ) ); ?>">
			<span class="dashicons dashicons-list-view"></span>
			<?php esc_html_e( 'View Entries', 'cotlas-simple-forms' ); ?>
		</a>
		<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=csf_form&page=csf-settings' ) ); ?>">
			<span class="dashicons dashicons-email-alt"></span>
			<?php esc_html_e( 'SMTP Settings', 'cotlas-simple-forms' ); ?>
		</a>
		<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=csf_form&csf_form_type=frontend_post' ) ); ?>">
			<span class="dashicons dashicons-edit-page"></span>
			<?php esc_html_e( 'Create Post Form', 'cotlas-simple-forms' ); ?>
		</a>
		<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=csf_form&page=csf-settings' ) ); ?>">
			<span class="dashicons dashicons-admin-generic"></span>
			<?php esc_html_e( 'Settings', 'cotlas-simple-forms' ); ?>
		</a>
	</div>

	<nav class="csf-dashboard-tabs" aria-label="<?php esc_attr_e( 'Cotlas Forms Sections', 'cotlas-simple-forms' ); ?>">
		<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
			<a class="<?php echo esc_attr( $active_tab === $tab_key ? 'is-active' : '' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'csf_form', 'page' => 'csf-dashboard', 'tab' => $tab_key ), admin_url( 'edit.php' ) ) ); ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php if ( 'all_forms' === $active_tab ) : ?>
		<iframe class="csf-dashboard-frame" src="<?php echo esc_url( admin_url( 'edit.php?post_type=csf_form&csf_dashboard_frame=1' ) ); ?>" title="<?php esc_attr_e( 'All Forms', 'cotlas-simple-forms' ); ?>"></iframe>
	<?php elseif ( 'add_form' === $active_tab ) : ?>
		<iframe class="csf-dashboard-frame" src="<?php echo esc_url( admin_url( 'post-new.php?post_type=csf_form&csf_dashboard_frame=1' ) ); ?>" title="<?php esc_attr_e( 'Add Form', 'cotlas-simple-forms' ); ?>"></iframe>
	<?php elseif ( 'settings' === $active_tab ) : ?>
		<div class="csf-dashboard-tab-panel">
			<?php ( new CSF_Settings() )->render_settings_page(); ?>
		</div>
	<?php elseif ( 'submissions' === $active_tab ) : ?>
		<div class="csf-dashboard-tab-panel">
			<?php ( new CSF_Admin_List() )->render_list_page(); ?>
		</div>
	<?php elseif ( 'email_templates' === $active_tab ) : ?>
		<div class="csf-dashboard-tab-panel">
			<?php include CSF_PLUGIN_DIR . 'templates/admin/email-templates.php'; ?>
		</div>
	<?php elseif ( 'activity_log' === $active_tab ) : ?>
		<div class="csf-dashboard-tab-panel">
			<?php $logs = isset( $this ) ? $this->activity_logger->latest( 100 ) : array(); ?>
			<?php include CSF_PLUGIN_DIR . 'templates/admin/activity-log.php'; ?>
		</div>
	<?php else : ?>

	<div class="csf-stats-grid">
		<?php foreach ( $cards as $card ) : ?>
			<div class="csf-stat-card">
				<div class="csf-stat-icon">
					<span class="dashicons <?php echo esc_attr( $card['icon'] ); ?>"></span>
				</div>
				<div>
					<div class="csf-stat-value"><?php echo esc_html( $card['value'] ); ?></div>
					<div class="csf-stat-label"><?php echo esc_html( $card['label'] ); ?></div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="csf-dashboard-grid">
		<div class="csf-panel">
			<h2><?php esc_html_e( 'Latest Submissions', 'cotlas-simple-forms' ); ?></h2>
			<?php if ( ! empty( $data['latest_submissions'] ) ) : ?>
				<ul class="csf-list">
					<?php foreach ( $data['latest_submissions'] as $submission ) : ?>
						<?php
						$form_id    = get_post_meta( $submission->ID, 'csf_form_id', true );
						$page_title = get_post_meta( $submission->ID, 'csf_page_title', true );
						?>
						<li>
							<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=csf_form&page=csf-view-submission&id=' . absint( $submission->ID ) ) ); ?>">
								<?php echo esc_html( $page_title ? $page_title : $submission->post_title ); ?>
							</a>
							<span>
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: form ID. */
										__( 'Form #%s', 'cotlas-simple-forms' ),
										$form_id ? $form_id : '-'
									)
								);
								?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="csf-empty"><?php esc_html_e( 'No submissions yet.', 'cotlas-simple-forms' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="csf-panel">
			<h2><?php esc_html_e( 'Latest Frontend Posts', 'cotlas-simple-forms' ); ?></h2>
			<?php if ( ! empty( $data['latest_frontend_posts'] ) ) : ?>
				<ul class="csf-list">
					<?php foreach ( $data['latest_frontend_posts'] as $post ) : ?>
						<?php $status_object = get_post_status_object( $post->post_status ); ?>
						<li>
							<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>">
								<?php echo esc_html( get_the_title( $post ) ); ?>
							</a>
							<span><?php echo esc_html( $status_object ? $status_object->label : $post->post_status ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="csf-empty"><?php esc_html_e( 'Frontend post module is ready for Phase 2.', 'cotlas-simple-forms' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="csf-panel csf-panel-wide">
			<h2><?php esc_html_e( 'Recent Activity', 'cotlas-simple-forms' ); ?></h2>
			<?php if ( ! empty( $data['latest_activity'] ) ) : ?>
				<ul class="csf-activity-list">
					<?php foreach ( $data['latest_activity'] as $activity ) : ?>
						<li>
							<span class="csf-activity-type"><?php echo esc_html( ucwords( str_replace( '_', ' ', $activity->event_type ) ) ); ?></span>
							<span class="csf-activity-message"><?php echo esc_html( $activity->message ); ?></span>
							<time><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $activity->created_at ) ); ?></time>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="csf-empty"><?php esc_html_e( 'Activity will appear here as the upgraded modules record events.', 'cotlas-simple-forms' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>
</div>
