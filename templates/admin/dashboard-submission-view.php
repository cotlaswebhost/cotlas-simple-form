<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="csf-submission-view">
	<h3><?php echo esc_html( sprintf( __( 'Submission #%d', 'cotlas-simple-forms' ), $submission_id ) ); ?></h3>
	<p>
		<?php echo esc_html( sprintf( __( 'Form ID: %s', 'cotlas-simple-forms' ), $form_id ? $form_id : '-' ) ); ?>
		<br>
		<?php echo esc_html( get_the_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $post ) ); ?>
	</p>

	<?php if ( ! empty( $form_data ) && is_array( $form_data ) ) : ?>
		<table class="widefat striped">
			<tbody>
				<?php foreach ( $form_data as $key => $value ) : ?>
					<tr>
						<th><?php echo esc_html( ucwords( str_replace( array( 'csf_', '_' ), array( '', ' ' ), $key ) ) ); ?></th>
						<td>
							<?php
							if ( is_array( $value ) ) {
								echo esc_html( implode( ', ', array_map( 'sanitize_text_field', $value ) ) );
							} else {
								echo esc_html( $value );
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p class="csf-empty"><?php esc_html_e( 'This submission does not contain saved form data.', 'cotlas-simple-forms' ); ?></p>
	<?php endif; ?>

	<?php if ( ! empty( $files ) && is_array( $files ) ) : ?>
		<h4><?php esc_html_e( 'Files', 'cotlas-simple-forms' ); ?></h4>
		<ul>
			<?php foreach ( $files as $file_url ) : ?>
				<li><a href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( basename( $file_url ) ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
