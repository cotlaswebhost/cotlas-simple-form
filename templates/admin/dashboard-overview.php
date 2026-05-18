<?php
/**
 * Dashboard overview tab.
 *
 * @package CotlasSimpleForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cards = array(
	array( 'label' => __( 'Total Forms', 'cotlas-simple-forms' ), 'value' => $data['total_forms'], 'icon' => 'dashicons-feedback' ),
	array( 'label' => __( 'Total Entries', 'cotlas-simple-forms' ), 'value' => $data['total_entries'], 'icon' => 'dashicons-list-view' ),
	array( 'label' => __( 'Login Forms', 'cotlas-simple-forms' ), 'value' => $data['total_login_forms'], 'icon' => 'dashicons-lock' ),
	array( 'label' => __( 'Registration Forms', 'cotlas-simple-forms' ), 'value' => $data['total_register_forms'], 'icon' => 'dashicons-admin-users' ),
	array( 'label' => __( 'Payment Forms', 'cotlas-simple-forms' ), 'value' => $data['total_payment_forms'], 'icon' => 'dashicons-money-alt' ),
	array( 'label' => __( 'Donation Forms', 'cotlas-simple-forms' ), 'value' => $data['total_donation_forms'], 'icon' => 'dashicons-heart' ),
	array( 'label' => __( 'Contact Forms', 'cotlas-simple-forms' ), 'value' => $data['total_contact_forms'], 'icon' => 'dashicons-email-alt' ),
	array( 'label' => __( 'Post Forms', 'cotlas-simple-forms' ), 'value' => $data['total_post_forms'], 'icon' => 'dashicons-edit-page' ),
	array( 'label' => __( 'Email Sent', 'cotlas-simple-forms' ), 'value' => $data['total_email_sent'], 'icon' => 'dashicons-email' ),
	array( 'label' => __( 'SMTP Status', 'cotlas-simple-forms' ), 'value' => $data['smtp_enabled'] ? __( 'Active', 'cotlas-simple-forms' ) : __( 'Inactive', 'cotlas-simple-forms' ), 'icon' => 'dashicons-cloud' ),
);
?>

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
						<span><?php echo esc_html( sprintf( __( 'Form #%s', 'cotlas-simple-forms' ), $form_id ? $form_id : '-' ) ); ?></span>
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
			<p class="csf-empty"><?php esc_html_e( 'No frontend posts yet.', 'cotlas-simple-forms' ); ?></p>
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
