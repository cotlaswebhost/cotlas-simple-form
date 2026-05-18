<?php
/**
 * Dashboard add form tab.
 *
 * @package CotlasSimpleForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$default_type = isset( $_GET['csf_default_form_type'] ) ? sanitize_key( wp_unslash( $_GET['csf_default_form_type'] ) ) : 'normal';
?>

<div class="csf-dashboard-tab-panel">
	<div class="csf-tab-header">
		<div>
			<h2><?php esc_html_e( 'Add Form', 'cotlas-simple-forms' ); ?></h2>
			<p><?php esc_html_e( 'Create a draft form, then continue editing it in the WordPress block editor.', 'cotlas-simple-forms' ); ?></p>
		</div>
	</div>

	<form class="csf-create-form" data-csf-create-form>
		<div data-csf-create-result></div>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="csf_form_title"><?php esc_html_e( 'Form Title', 'cotlas-simple-forms' ); ?></label>
				</th>
				<td>
					<input type="text" name="csf_form_title" id="csf_form_title" class="regular-text" required>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="csf_form_type_create"><?php esc_html_e( 'Form Type', 'cotlas-simple-forms' ); ?></label>
				</th>
				<td>
					<select name="csf_form_type" id="csf_form_type_create">
						<option value="normal" <?php selected( $default_type, 'normal' ); ?>><?php esc_html_e( 'Normal Contact Form', 'cotlas-simple-forms' ); ?></option>
						<option value="login" <?php selected( $default_type, 'login' ); ?>><?php esc_html_e( 'Login Form', 'cotlas-simple-forms' ); ?></option>
						<option value="register" <?php selected( $default_type, 'register' ); ?>><?php esc_html_e( 'Registration Form', 'cotlas-simple-forms' ); ?></option>
						<option value="payment" <?php selected( $default_type, 'payment' ); ?>><?php esc_html_e( 'Payment Form', 'cotlas-simple-forms' ); ?></option>
						<option value="donation" <?php selected( $default_type, 'donation' ); ?>><?php esc_html_e( 'Donation Form', 'cotlas-simple-forms' ); ?></option>
						<option value="frontend_post" <?php selected( $default_type, 'frontend_post' ); ?>><?php esc_html_e( 'Frontend Add Post Form', 'cotlas-simple-forms' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Create and Edit Form', 'cotlas-simple-forms' ); ?></button>
			<a href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'csf_form', 'page' => 'csf-dashboard', 'tab' => 'all_forms' ), admin_url( 'edit.php' ) ) ); ?>" class="button" data-csf-dashboard-action data-tab="all_forms"><?php esc_html_e( 'View All Forms', 'cotlas-simple-forms' ); ?></a>
		</p>
	</form>
</div>
