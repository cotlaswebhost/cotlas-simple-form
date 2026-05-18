<?php
/**
 * Dashboard shell template.
 *
 * @package CotlasSimpleForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
$default_type = isset( $_GET['csf_default_form_type'] ) ? sanitize_key( wp_unslash( $_GET['csf_default_form_type'] ) ) : '';
$settings_tab = isset( $_GET['csf_settings_tab'] ) ? sanitize_key( wp_unslash( $_GET['csf_settings_tab'] ) ) : '';
?>

<div class="wrap csf-admin-dashboard">
	<div class="csf-dashboard-header">
		<div>
			<h1><?php esc_html_e( 'Cotlas Forms Dashboard', 'cotlas-simple-forms' ); ?></h1>
			<p><?php esc_html_e( 'Central overview for forms, entries, email, and form management.', 'cotlas-simple-forms' ); ?></p>
		</div>
		<span class="csf-version-badge">
			<?php echo esc_html( sprintf( __( 'Version %s', 'cotlas-simple-forms' ), $data['plugin_version'] ) ); ?>
		</span>
	</div>

	<div class="csf-quick-actions" role="tablist" aria-label="<?php esc_attr_e( 'Cotlas Forms Sections', 'cotlas-simple-forms' ); ?>">
		<a class="button button-primary <?php echo esc_attr( 'add_form' === $active_tab && 'frontend_post' !== $default_type ? 'is-active' : '' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'csf_form', 'page' => 'csf-dashboard', 'tab' => 'add_form' ), admin_url( 'edit.php' ) ) ); ?>" data-csf-dashboard-tab data-tab="add_form" role="tab" aria-selected="<?php echo esc_attr( 'add_form' === $active_tab && 'frontend_post' !== $default_type ? 'true' : 'false' ); ?>">
			<span class="dashicons dashicons-plus-alt2"></span>
			<?php esc_html_e( 'Create Form', 'cotlas-simple-forms' ); ?>
		</a>
		<a class="button <?php echo esc_attr( 'all_forms' === $active_tab ? 'is-active' : '' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'csf_form', 'page' => 'csf-dashboard', 'tab' => 'all_forms' ), admin_url( 'edit.php' ) ) ); ?>" data-csf-dashboard-tab data-tab="all_forms" role="tab" aria-selected="<?php echo esc_attr( 'all_forms' === $active_tab ? 'true' : 'false' ); ?>">
			<span class="dashicons dashicons-feedback"></span>
			<?php esc_html_e( 'All Forms', 'cotlas-simple-forms' ); ?>
		</a>
		<a class="button <?php echo esc_attr( 'submissions' === $active_tab ? 'is-active' : '' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'csf_form', 'page' => 'csf-dashboard', 'tab' => 'submissions' ), admin_url( 'edit.php' ) ) ); ?>" data-csf-dashboard-tab data-tab="submissions" role="tab" aria-selected="<?php echo esc_attr( 'submissions' === $active_tab ? 'true' : 'false' ); ?>">
			<span class="dashicons dashicons-list-view"></span>
			<?php esc_html_e( 'View Entries', 'cotlas-simple-forms' ); ?>
		</a>
		<a class="button <?php echo esc_attr( 'smtp' === $active_tab ? 'is-active' : '' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'csf_form', 'page' => 'csf-dashboard', 'tab' => 'smtp' ), admin_url( 'edit.php' ) ) ); ?>" data-csf-dashboard-tab data-tab="smtp" role="tab" aria-selected="<?php echo esc_attr( 'smtp' === $active_tab ? 'true' : 'false' ); ?>">
			<span class="dashicons dashicons-email-alt"></span>
			<?php esc_html_e( 'SMTP Settings', 'cotlas-simple-forms' ); ?>
		</a>
		<a class="button <?php echo esc_attr( 'add_form' === $active_tab && 'frontend_post' === $default_type ? 'is-active' : '' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'csf_form', 'page' => 'csf-dashboard', 'tab' => 'add_form', 'csf_default_form_type' => 'frontend_post' ), admin_url( 'edit.php' ) ) ); ?>" data-csf-dashboard-tab data-tab="add_form" data-form-type="frontend_post" role="tab" aria-selected="<?php echo esc_attr( 'add_form' === $active_tab && 'frontend_post' === $default_type ? 'true' : 'false' ); ?>">
			<span class="dashicons dashicons-edit-page"></span>
			<?php esc_html_e( 'Create Post Form', 'cotlas-simple-forms' ); ?>
		</a>
		<a class="button <?php echo esc_attr( 'settings' === $active_tab && 'email' !== $settings_tab ? 'is-active' : '' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'csf_form', 'page' => 'csf-dashboard', 'tab' => 'settings' ), admin_url( 'edit.php' ) ) ); ?>" data-csf-dashboard-tab data-tab="settings" role="tab" aria-selected="<?php echo esc_attr( 'settings' === $active_tab && 'email' !== $settings_tab ? 'true' : 'false' ); ?>">
			<span class="dashicons dashicons-admin-generic"></span>
			<?php esc_html_e( 'Settings', 'cotlas-simple-forms' ); ?>
		</a>
		<a class="button <?php echo esc_attr( 'email_templates' === $active_tab ? 'is-active' : '' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'csf_form', 'page' => 'csf-dashboard', 'tab' => 'email_templates' ), admin_url( 'edit.php' ) ) ); ?>" data-csf-dashboard-tab data-tab="email_templates" role="tab" aria-selected="<?php echo esc_attr( 'email_templates' === $active_tab ? 'true' : 'false' ); ?>">
			<span class="dashicons dashicons-editor-table"></span>
			<?php esc_html_e( 'Email Templates', 'cotlas-simple-forms' ); ?>
		</a>
		<a class="button <?php echo esc_attr( 'activity_log' === $active_tab ? 'is-active' : '' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'csf_form', 'page' => 'csf-dashboard', 'tab' => 'activity_log' ), admin_url( 'edit.php' ) ) ); ?>" data-csf-dashboard-tab data-tab="activity_log" role="tab" aria-selected="<?php echo esc_attr( 'activity_log' === $active_tab ? 'true' : 'false' ); ?>">
			<span class="dashicons dashicons-clock"></span>
			<?php esc_html_e( 'Activity Log', 'cotlas-simple-forms' ); ?>
		</a>
		<a class="button <?php echo esc_attr( 'overview' === $active_tab ? 'is-active' : '' ); ?>" href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'csf_form', 'page' => 'csf-dashboard', 'tab' => 'overview' ), admin_url( 'edit.php' ) ) ); ?>" data-csf-dashboard-tab data-tab="overview" role="tab" aria-selected="<?php echo esc_attr( 'overview' === $active_tab ? 'true' : 'false' ); ?>">
			<span class="dashicons dashicons-dashboard"></span>
			<?php esc_html_e( 'Dashboard', 'cotlas-simple-forms' ); ?>
		</a>
	</div>

	<div class="csf-dashboard-content" data-csf-dashboard-panel>
		<?php echo $this->render_tab_content( $active_tab ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</div>
