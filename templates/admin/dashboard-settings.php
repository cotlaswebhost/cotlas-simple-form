<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="csf-dashboard-tab-panel">
	<div class="csf-tab-header">
		<div>
			<h2><?php esc_html_e( 'Settings', 'cotlas-simple-forms' ); ?></h2>
			<p><?php esc_html_e( 'General, security, upload, and frontend settings in one place.', 'cotlas-simple-forms' ); ?></p>
		</div>
	</div>

	<form class="csf-dashboard-settings-form" data-csf-settings-form>
		<input type="hidden" name="section" value="settings">
		<div data-csf-settings-result></div>

		<table class="form-table" role="presentation">
			<tr class="csf-settings-section-row">
				<th colspan="2"><?php esc_html_e( 'General', 'cotlas-simple-forms' ); ?></th>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Uninstall Behavior', 'cotlas-simple-forms' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="csf_delete_data_uninstall" value="1" <?php checked( 1, get_option( 'csf_delete_data_uninstall' ) ); ?>>
						<?php esc_html_e( 'Delete all forms and entries when plugin is uninstalled', 'cotlas-simple-forms' ); ?>
					</label>
				</td>
			</tr>

			<tr class="csf-settings-section-row">
				<th colspan="2"><?php esc_html_e( 'Security', 'cotlas-simple-forms' ); ?></th>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Turnstile Site Key', 'cotlas-simple-forms' ); ?></th>
				<td><input type="text" name="csf_turnstile_site_key" value="<?php echo esc_attr( get_option( 'csf_turnstile_site_key' ) ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Turnstile Secret Key', 'cotlas-simple-forms' ); ?></th>
				<td><input type="text" name="csf_turnstile_secret_key" value="<?php echo esc_attr( get_option( 'csf_turnstile_secret_key' ) ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Google Places API Key', 'cotlas-simple-forms' ); ?></th>
				<td><input type="text" name="csf_google_places_api_key" value="<?php echo esc_attr( get_option( 'csf_google_places_api_key' ) ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Rate Limiting', 'cotlas-simple-forms' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="csf_enable_limit" value="1" <?php checked( 1, get_option( 'csf_enable_limit' ) ); ?>>
						<?php esc_html_e( 'Enable rate limiting per IP address', 'cotlas-simple-forms' ); ?>
					</label>
					<br>
					<select name="csf_limit_duration">
						<option value="300" <?php selected( get_option( 'csf_limit_duration' ), '300' ); ?>><?php esc_html_e( '5 Minutes', 'cotlas-simple-forms' ); ?></option>
						<option value="900" <?php selected( get_option( 'csf_limit_duration' ), '900' ); ?>><?php esc_html_e( '15 Minutes', 'cotlas-simple-forms' ); ?></option>
						<option value="1800" <?php selected( get_option( 'csf_limit_duration' ), '1800' ); ?>><?php esc_html_e( '30 Minutes', 'cotlas-simple-forms' ); ?></option>
						<option value="3600" <?php selected( get_option( 'csf_limit_duration' ), '3600' ); ?>><?php esc_html_e( '1 Hour', 'cotlas-simple-forms' ); ?></option>
						<option value="43200" <?php selected( get_option( 'csf_limit_duration' ), '43200' ); ?>><?php esc_html_e( '12 Hours', 'cotlas-simple-forms' ); ?></option>
						<option value="86400" <?php selected( get_option( 'csf_limit_duration' ), '86400' ); ?>><?php esc_html_e( '1 Day', 'cotlas-simple-forms' ); ?></option>
					</select>
				</td>
			</tr>

			<tr class="csf-settings-section-row">
				<th colspan="2"><?php esc_html_e( 'Uploads', 'cotlas-simple-forms' ); ?></th>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Allowed File Types', 'cotlas-simple-forms' ); ?></th>
				<td>
					<input type="text" name="csf_allowed_file_types" value="<?php echo esc_attr( get_option( 'csf_allowed_file_types', 'jpg,jpeg,png,pdf,doc,docx' ) ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Comma separated extensions.', 'cotlas-simple-forms' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Max Upload Size (MB)', 'cotlas-simple-forms' ); ?></th>
				<td><input type="number" name="csf_max_upload_size" value="<?php echo esc_attr( get_option( 'csf_max_upload_size', '5' ) ); ?>" class="small-text" min="1"></td>
			</tr>

			<tr class="csf-settings-section-row">
				<th colspan="2"><?php esc_html_e( 'Frontend', 'cotlas-simple-forms' ); ?></th>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Asset Loading', 'cotlas-simple-forms' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="csf_disable_frontend_css" value="1" <?php checked( 1, get_option( 'csf_disable_frontend_css' ) ); ?>>
						<?php esc_html_e( 'Disable plugin frontend CSS', 'cotlas-simple-forms' ); ?>
					</label>
					<br>
					<label>
						<input type="checkbox" name="csf_disable_frontend_js" value="1" <?php checked( 1, get_option( 'csf_disable_frontend_js' ) ); ?>>
						<?php esc_html_e( 'Disable plugin frontend JS', 'cotlas-simple-forms' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'cotlas-simple-forms' ); ?></button>
		</p>
	</form>
</div>
