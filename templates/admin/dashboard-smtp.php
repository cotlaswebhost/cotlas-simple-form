<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="csf-dashboard-tab-panel">
	<div class="csf-tab-header">
		<div>
			<h2><?php esc_html_e( 'SMTP Settings', 'cotlas-simple-forms' ); ?></h2>
			<p><?php esc_html_e( 'Email delivery settings are separate from general plugin settings.', 'cotlas-simple-forms' ); ?></p>
		</div>
	</div>

	<form class="csf-dashboard-settings-form" data-csf-settings-form>
		<input type="hidden" name="section" value="smtp">
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Default Recipient Email', 'cotlas-simple-forms' ); ?></th>
				<td><input type="email" name="csf_to_email" value="<?php echo esc_attr( get_option( 'csf_to_email' ) ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable SMTP', 'cotlas-simple-forms' ); ?></th>
				<td>
					<label><input type="checkbox" name="csf_enable_smtp" value="1" <?php checked( 1, get_option( 'csf_enable_smtp' ) ); ?>> <?php esc_html_e( 'Enable SMTP for sending emails', 'cotlas-simple-forms' ); ?></label>
					<br>
					<label><input type="checkbox" name="csf_use_global_smtp" value="1" <?php checked( 1, get_option( 'csf_use_global_smtp' ) ); ?>> <?php esc_html_e( 'Use this SMTP for all system emails', 'cotlas-simple-forms' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'SMTP Host', 'cotlas-simple-forms' ); ?></th>
				<td><input type="text" name="csf_smtp_host" value="<?php echo esc_attr( get_option( 'csf_smtp_host' ) ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'SMTP Port', 'cotlas-simple-forms' ); ?></th>
				<td><input type="text" name="csf_smtp_port" value="<?php echo esc_attr( get_option( 'csf_smtp_port' ) ); ?>" class="small-text"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'SMTP Username', 'cotlas-simple-forms' ); ?></th>
				<td><input type="text" name="csf_smtp_user" value="<?php echo esc_attr( get_option( 'csf_smtp_user' ) ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'SMTP Password', 'cotlas-simple-forms' ); ?></th>
				<td><input type="password" name="csf_smtp_pass" value="<?php echo esc_attr( get_option( 'csf_smtp_pass' ) ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Encryption', 'cotlas-simple-forms' ); ?></th>
				<td>
					<select name="csf_smtp_encryption">
						<option value="" <?php selected( get_option( 'csf_smtp_encryption' ), '' ); ?>><?php esc_html_e( 'None', 'cotlas-simple-forms' ); ?></option>
						<option value="tls" <?php selected( get_option( 'csf_smtp_encryption' ), 'tls' ); ?>><?php esc_html_e( 'TLS', 'cotlas-simple-forms' ); ?></option>
						<option value="ssl" <?php selected( get_option( 'csf_smtp_encryption' ), 'ssl' ); ?>><?php esc_html_e( 'SSL', 'cotlas-simple-forms' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'From Email', 'cotlas-simple-forms' ); ?></th>
				<td><input type="email" name="csf_from_email" value="<?php echo esc_attr( get_option( 'csf_from_email' ) ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'From Name', 'cotlas-simple-forms' ); ?></th>
				<td><input type="text" name="csf_from_name" value="<?php echo esc_attr( get_option( 'csf_from_name' ) ); ?>" class="regular-text"></td>
			</tr>
		</table>
		<div style="padding: 1.5em 0;">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save SMTP Settings', 'cotlas-simple-forms' ); ?></button>
			<div data-csf-settings-result style="margin-top: 10px;"></div>
		</div>
	</form>
</div>
