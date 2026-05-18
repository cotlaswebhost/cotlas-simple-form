<?php
/**
 * Email Templates Admin Template.
 *
 * @package CotlasSimpleForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$default_admin = 'You have received a new form submission from <strong>{site_name}</strong> on page "{page_title}".<br><br>{all_fields}';
$default_user = 'Hi {name},<br><br>Thank you for your submission. Here is a copy of what we received:<br><br>{all_fields}';
$default_post = 'A new frontend post has been submitted by {name}.<br><br>Title: {post_title}<br>Status: Pending Review<br><br>{all_fields}';

$admin_template = get_option( 'csf_email_template_admin_notification', $default_admin );
$user_template = get_option( 'csf_email_template_user_confirmation', $default_user );
$post_template = get_option( 'csf_email_template_frontend_post', $default_post );
?>

<div class="wrap csf-admin-settings">
	<h1><?php esc_html_e( 'Email Templates', 'cotlas-simple-forms' ); ?></h1>
    <p><?php esc_html_e( 'Customize the emails sent by Cotlas Simple Forms. You can use HTML tags to format your emails.', 'cotlas-simple-forms' ); ?></p>
    <p>
        <strong><?php esc_html_e( 'Available Variables:', 'cotlas-simple-forms' ); ?></strong><br>
        <code>{all_fields}</code> - <?php esc_html_e( 'Outputs a table of all submitted form fields.', 'cotlas-simple-forms' ); ?><br>
        <code>{site_name}</code> - <?php esc_html_e( 'The name of your website.', 'cotlas-simple-forms' ); ?><br>
        <code>{page_title}</code> - <?php esc_html_e( 'The title of the page where the form was submitted.', 'cotlas-simple-forms' ); ?><br>
        <code>{page_url}</code> - <?php esc_html_e( 'The URL of the page where the form was submitted.', 'cotlas-simple-forms' ); ?><br>
        <code>{submission_id}</code> - <?php esc_html_e( 'The ID of the saved submission.', 'cotlas-simple-forms' ); ?><br>
        <code>{post_title}</code> - <?php esc_html_e( 'The title of the frontend post (if applicable).', 'cotlas-simple-forms' ); ?><br>
        <code>{your_field_name}</code> - <?php esc_html_e( 'Replace "your_field_name" with the exact Name of a field (e.g., {email}, {first_name}) to output its value.', 'cotlas-simple-forms' ); ?>
    </p>

	<form class="csf-dashboard-settings-form" data-csf-settings-form>
		<input type="hidden" name="section" value="email_templates">
		<table class="form-table">
            <tr>
				<th scope="row"><?php _e( 'Admin Notification Email', 'cotlas-simple-forms' ); ?></th>
				<td>
                    <?php
                    wp_editor( wp_kses_post( $admin_template ), 'csf_email_template_admin_notification', array(
                        'textarea_name' => 'csf_email_template_admin_notification',
                        'textarea_rows' => 10,
                    ) );
                    ?>
					<p class="description"><?php _e( 'Sent to the admin when a general form is submitted.', 'cotlas-simple-forms' ); ?></p>
				</td>
			</tr>
            <tr>
				<th scope="row"><?php _e( 'User Confirmation Email', 'cotlas-simple-forms' ); ?></th>
				<td>
                    <?php
                    wp_editor( wp_kses_post( $user_template ), 'csf_email_template_user_confirmation', array(
                        'textarea_name' => 'csf_email_template_user_confirmation',
                        'textarea_rows' => 10,
                    ) );
                    ?>
					<p class="description"><?php _e( 'Sent to the user when Mail 2 is enabled for a form.', 'cotlas-simple-forms' ); ?></p>
				</td>
			</tr>
            <tr>
				<th scope="row"><?php _e( 'Frontend Post Submission Email', 'cotlas-simple-forms' ); ?></th>
				<td>
                    <?php
                    wp_editor( wp_kses_post( $post_template ), 'csf_email_template_frontend_post', array(
                        'textarea_name' => 'csf_email_template_frontend_post',
                        'textarea_rows' => 10,
                    ) );
                    ?>
					<p class="description"><?php _e( 'Sent to the admin when a new frontend post is submitted.', 'cotlas-simple-forms' ); ?></p>
				</td>
			</tr>
		</table>
		<div style="padding: 1.5em 0;">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Templates', 'cotlas-simple-forms' ); ?></button>
			<div data-csf-settings-result style="margin-top: 10px;"></div>
		</div>
	</form>
</div>
