<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="csf-dashboard-tab-panel">
	<div class="csf-tab-header">
		<div>
			<h2><?php esc_html_e( 'Form Submissions', 'cotlas-simple-forms' ); ?></h2>
			<p><?php esc_html_e( 'Only real submissions with saved form data are shown here.', 'cotlas-simple-forms' ); ?></p>
		</div>
	</div>

	<div data-csf-submission-result></div>

	<table class="wp-list-table widefat fixed striped csf-dashboard-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Date', 'cotlas-simple-forms' ); ?></th>
				<th><?php esc_html_e( 'Form', 'cotlas-simple-forms' ); ?></th>
				<th><?php esc_html_e( 'Page', 'cotlas-simple-forms' ); ?></th>
				<th><?php esc_html_e( 'Status', 'cotlas-simple-forms' ); ?></th>
				<th><?php esc_html_e( 'Data', 'cotlas-simple-forms' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'cotlas-simple-forms' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $submissions ) ) : ?>
				<?php foreach ( $submissions as $submission ) : ?>
					<?php
					$form_id  = get_post_meta( $submission->ID, 'csf_form_id', true );
					$page_url = get_post_meta( $submission->ID, 'csf_page_url', true );
					?>
					<tr data-submission-row="<?php echo esc_attr( $submission->ID ); ?>">
						<td><?php echo esc_html( get_the_date( 'Y-m-d H:i', $submission ) ); ?></td>
						<td><?php echo $form_id ? '<a href="' . esc_url( get_edit_post_link( $form_id ) ) . '">ID: ' . esc_html( $form_id ) . '</a>' : esc_html__( '(Deleted Form)', 'cotlas-simple-forms' ); ?></td>
						<td><?php echo $page_url ? '<a href="' . esc_url( $page_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'View Page', 'cotlas-simple-forms' ) . '</a>' : '-'; ?></td>
						<td><span class="csf-status-label"><?php esc_html_e( 'Received', 'cotlas-simple-forms' ); ?></span></td>
						<td><button type="button" class="button" data-csf-view-submission="<?php echo esc_attr( $submission->ID ); ?>"><?php esc_html_e( 'View Data', 'cotlas-simple-forms' ); ?></button></td>
						<td><button type="button" class="button button-small delete" data-csf-delete-submission="<?php echo esc_attr( $submission->ID ); ?>"><?php esc_html_e( 'Delete', 'cotlas-simple-forms' ); ?></button></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="6"><?php esc_html_e( 'No submissions found.', 'cotlas-simple-forms' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
