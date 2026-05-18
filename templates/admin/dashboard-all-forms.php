<?php
/**
 * Dashboard all forms tab.
 *
 * @package CotlasSimpleForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="csf-dashboard-tab-panel">
	<div class="csf-tab-header">
		<div>
			<h2><?php esc_html_e( 'All Forms', 'cotlas-simple-forms' ); ?></h2>
			<p><?php esc_html_e( 'Manage your Cotlas forms without leaving the dashboard.', 'cotlas-simple-forms' ); ?></p>
		</div>
		<a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'csf_form', 'page' => 'csf-dashboard', 'tab' => 'add_form' ), admin_url( 'edit.php' ) ) ); ?>" data-csf-dashboard-action data-tab="add_form">
			<?php esc_html_e( 'Add Form', 'cotlas-simple-forms' ); ?>
		</a>
	</div>

	<table class="wp-list-table widefat fixed striped csf-dashboard-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Title', 'cotlas-simple-forms' ); ?></th>
				<th><?php esc_html_e( 'Type', 'cotlas-simple-forms' ); ?></th>
				<th><?php esc_html_e( 'Shortcode', 'cotlas-simple-forms' ); ?></th>
				<th><?php esc_html_e( 'Status', 'cotlas-simple-forms' ); ?></th>
				<th><?php esc_html_e( 'Modified', 'cotlas-simple-forms' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $forms ) ) : ?>
				<?php foreach ( $forms as $form ) : ?>
					<?php
					$form_type = get_post_meta( $form->ID, 'csf_form_type', true );
					$form_type = $form_type ? $form_type : 'normal';
					$status    = get_post_status_object( $form->post_status );
					?>
					<tr>
						<td>
							<strong><a href="<?php echo esc_url( get_edit_post_link( $form->ID ) ); ?>"><?php echo esc_html( get_the_title( $form ) ); ?></a></strong>
							<div class="row-actions">
								<span><a href="<?php echo esc_url( get_edit_post_link( $form->ID ) ); ?>"><?php esc_html_e( 'Edit', 'cotlas-simple-forms' ); ?></a></span>
							</div>
						</td>
						<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $form_type ) ) ); ?></td>
						<td><input type="text" readonly value="<?php echo esc_attr( '[csf_form id="' . $form->ID . '"]' ); ?>" onclick="this.select();" class="regular-text"></td>
						<td><?php echo esc_html( $status ? $status->label : $form->post_status ); ?></td>
						<td><?php echo esc_html( get_the_modified_date( get_option( 'date_format' ), $form ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="5"><?php esc_html_e( 'No forms found.', 'cotlas-simple-forms' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
