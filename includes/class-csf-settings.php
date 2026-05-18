<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CSF_Settings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=csf_form',
            __( 'Form Settings', 'cotlas-simple-forms' ),
            __( 'Settings', 'cotlas-simple-forms' ),
            'manage_options',
            'csf-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        // General Settings
        register_setting( 'csf_settings_group', 'csf_delete_data_uninstall' );
        
        // SMTP Settings
        register_setting( 'csf_settings_group', 'csf_enable_smtp' ); // Toggle
        register_setting( 'csf_settings_group', 'csf_use_global_smtp' ); // Use for all system emails
        register_setting( 'csf_settings_group', 'csf_smtp_host' );
        register_setting( 'csf_settings_group', 'csf_smtp_port' );
        register_setting( 'csf_settings_group', 'csf_smtp_user' );
        register_setting( 'csf_settings_group', 'csf_smtp_pass' );
        register_setting( 'csf_settings_group', 'csf_smtp_encryption' ); // tls or ssl
        register_setting( 'csf_settings_group', 'csf_from_email' );
        register_setting( 'csf_settings_group', 'csf_from_name' );
        register_setting( 'csf_settings_group', 'csf_to_email' ); // Default receiver

        // Security Settings
        register_setting( 'csf_settings_group', 'csf_turnstile_site_key' );
        register_setting( 'csf_settings_group', 'csf_turnstile_secret_key' );
        register_setting( 'csf_settings_group', 'csf_google_places_api_key' );
        register_setting( 'csf_settings_group', 'csf_enable_limit' );
        register_setting( 'csf_settings_group', 'csf_limit_duration' );

        // Uploads Settings
        register_setting( 'csf_settings_group', 'csf_allowed_file_types' );
        register_setting( 'csf_settings_group', 'csf_max_upload_size' );

        // Frontend Settings
        register_setting( 'csf_settings_group', 'csf_disable_frontend_css' );
        register_setting( 'csf_settings_group', 'csf_disable_frontend_js' );
    }

    public function render_settings_page() {
        $active_tab = isset( $_GET['csf_settings_tab'] ) ? sanitize_text_field( $_GET['csf_settings_tab'] ) : ( isset( $_GET['tab'] ) && $_GET['tab'] !== 'settings' ? sanitize_text_field( $_GET['tab'] ) : 'general' );
        $base_url = isset( $_GET['page'] ) && $_GET['page'] === 'csf-dashboard'
            ? admin_url( 'edit.php?post_type=csf_form&page=csf-dashboard&tab=settings' )
            : admin_url( 'edit.php?post_type=csf_form&page=csf-settings' );
        ?>
        <div class="wrap csf-admin-settings">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url( add_query_arg( 'csf_settings_tab', 'general', $base_url ) ); ?>" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e( 'General', 'cotlas-simple-forms' ); ?></a>
                <a href="<?php echo esc_url( add_query_arg( 'csf_settings_tab', 'email', $base_url ) ); ?>" class="nav-tab <?php echo $active_tab == 'email' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Email', 'cotlas-simple-forms' ); ?></a>
                <a href="<?php echo esc_url( add_query_arg( 'csf_settings_tab', 'security', $base_url ) ); ?>" class="nav-tab <?php echo $active_tab == 'security' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Security', 'cotlas-simple-forms' ); ?></a>
                <a href="<?php echo esc_url( add_query_arg( 'csf_settings_tab', 'uploads', $base_url ) ); ?>" class="nav-tab <?php echo $active_tab == 'uploads' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Uploads', 'cotlas-simple-forms' ); ?></a>
                <a href="<?php echo esc_url( add_query_arg( 'csf_settings_tab', 'frontend', $base_url ) ); ?>" class="nav-tab <?php echo $active_tab == 'frontend' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Frontend', 'cotlas-simple-forms' ); ?></a>
            </h2>

            <form action="options.php" method="post">
                <?php
                settings_fields( 'csf_settings_group' );
                do_settings_sections( 'csf_settings_group' );
                ?>
                <table class="form-table">
                    <?php if ( $active_tab == 'general' ) : ?>
                    <tr>
                        <th scope="row"><?php _e( 'Uninstall Behavior', 'cotlas-simple-forms' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="csf_delete_data_uninstall" value="1" <?php checked( 1, get_option( 'csf_delete_data_uninstall' ), true ); ?>>
                                <?php _e( 'Delete all forms and entries when plugin is uninstalled', 'cotlas-simple-forms' ); ?>
                            </label>
                        </td>
                    </tr>
                    <?php elseif ( $active_tab == 'email' ) : ?>
                    <tr>
                        <th scope="row"><?php _e( 'Default Recipient Email', 'cotlas-simple-forms' ); ?></th>
                        <td><input type="email" name="csf_to_email" value="<?php echo esc_attr( get_option( 'csf_to_email' ) ); ?>" class="regular-text"></td>
                    </tr>
                    
                    <tr><th colspan="2"><h3><?php _e( 'SMTP Settings', 'cotlas-simple-forms' ); ?></h3></th></tr>
                    <tr>
                        <th scope="row"><?php _e( 'Enable SMTP', 'cotlas-simple-forms' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="csf_enable_smtp" value="1" <?php checked( 1, get_option( 'csf_enable_smtp' ), true ); ?> onclick="toggleSmtpFields(this)">
                                <?php _e( 'Enable SMTP for sending emails', 'cotlas-simple-forms' ); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="csf_use_global_smtp" value="1" <?php checked( 1, get_option( 'csf_use_global_smtp' ), true ); ?>>
                                <?php _e( 'Use this SMTP for all system emails (Global)', 'cotlas-simple-forms' ); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tbody id="csf-smtp-fields" style="<?php echo get_option( 'csf_enable_smtp' ) ? '' : 'display:none;'; ?>">
                        <tr>
                            <th scope="row"><?php _e( 'SMTP Host', 'cotlas-simple-forms' ); ?></th>
                            <td><input type="text" name="csf_smtp_host" value="<?php echo esc_attr( get_option( 'csf_smtp_host' ) ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'SMTP Port', 'cotlas-simple-forms' ); ?></th>
                            <td><input type="text" name="csf_smtp_port" value="<?php echo esc_attr( get_option( 'csf_smtp_port' ) ); ?>" class="small-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'SMTP Username', 'cotlas-simple-forms' ); ?></th>
                            <td><input type="text" name="csf_smtp_user" value="<?php echo esc_attr( get_option( 'csf_smtp_user' ) ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'SMTP Password', 'cotlas-simple-forms' ); ?></th>
                            <td><input type="password" name="csf_smtp_pass" value="<?php echo esc_attr( get_option( 'csf_smtp_pass' ) ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Encryption', 'cotlas-simple-forms' ); ?></th>
                            <td>
                                <select name="csf_smtp_encryption">
                                    <option value="" <?php selected( get_option( 'csf_smtp_encryption' ), '' ); ?>><?php _e('None', 'cotlas-simple-forms'); ?></option>
                                    <option value="tls" <?php selected( get_option( 'csf_smtp_encryption' ), 'tls' ); ?>><?php _e('TLS', 'cotlas-simple-forms'); ?></option>
                                    <option value="ssl" <?php selected( get_option( 'csf_smtp_encryption' ), 'ssl' ); ?>><?php _e('SSL', 'cotlas-simple-forms'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'From Email', 'cotlas-simple-forms' ); ?></th>
                            <td><input type="email" name="csf_from_email" value="<?php echo esc_attr( get_option( 'csf_from_email' ) ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'From Name', 'cotlas-simple-forms' ); ?></th>
                            <td><input type="text" name="csf_from_name" value="<?php echo esc_attr( get_option( 'csf_from_name' ) ); ?>" class="regular-text"></td>
                        </tr>
                    </tbody>
                    <?php elseif ( $active_tab == 'security' ) : ?>
                    <tr><th colspan="2"><h3><?php _e( 'Cloudflare Turnstile', 'cotlas-simple-forms' ); ?></h3></th></tr>
                    <tr>
                        <th scope="row"><?php _e( 'Site Key', 'cotlas-simple-forms' ); ?></th>
                        <td><input type="text" name="csf_turnstile_site_key" value="<?php echo esc_attr( get_option( 'csf_turnstile_site_key' ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Turnstile Secret Key', 'cotlas-simple-forms' ); ?></th>
                        <td><input type="text" name="csf_turnstile_secret_key" value="<?php echo esc_attr( get_option( 'csf_turnstile_secret_key' ) ); ?>" class="regular-text"></td>
                    </tr>

                    <tr><th colspan="2"><h3><?php _e( 'Google Places (City Autocomplete)', 'cotlas-simple-forms' ); ?></h3></th></tr>
                    <tr>
                        <th scope="row"><?php _e( 'Google API Key', 'cotlas-simple-forms' ); ?></th>
                        <td><input type="text" name="csf_google_places_api_key" value="<?php echo esc_attr( get_option( 'csf_google_places_api_key' ) ); ?>" class="regular-text"></td>
                    </tr>

                    <tr><th colspan="2"><h3><?php _e( 'Rate Limiting (Anti-Spam)', 'cotlas-simple-forms' ); ?></h3></th></tr>
                    <tr>
                        <th scope="row"><?php _e( 'Limit Form Submission', 'cotlas-simple-forms' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="csf_enable_limit" value="1" <?php checked( 1, get_option( 'csf_enable_limit' ), true ); ?>>
                                <?php _e( 'Enable rate limiting per IP address', 'cotlas-simple-forms' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Limit Duration', 'cotlas-simple-forms' ); ?></th>
                        <td>
                            <select name="csf_limit_duration">
                                <option value="300" <?php selected( get_option( 'csf_limit_duration' ), '300' ); ?>><?php _e('5 Minutes', 'cotlas-simple-forms'); ?></option>
                                <option value="900" <?php selected( get_option( 'csf_limit_duration' ), '900' ); ?>><?php _e('15 Minutes', 'cotlas-simple-forms'); ?></option>
                                <option value="1800" <?php selected( get_option( 'csf_limit_duration' ), '1800' ); ?>><?php _e('30 Minutes', 'cotlas-simple-forms'); ?></option>
                                <option value="3600" <?php selected( get_option( 'csf_limit_duration' ), '3600' ); ?>><?php _e('1 Hour', 'cotlas-simple-forms'); ?></option>
                                <option value="43200" <?php selected( get_option( 'csf_limit_duration' ), '43200' ); ?>><?php _e('12 Hours', 'cotlas-simple-forms'); ?></option>
                                <option value="86400" <?php selected( get_option( 'csf_limit_duration' ), '86400' ); ?>><?php _e('1 Day', 'cotlas-simple-forms'); ?></option>
                            </select>
                            <p class="description"><?php _e( 'Time to wait before allowing another submission from the same IP.', 'cotlas-simple-forms' ); ?></p>
                        </td>
                    </tr>
                    <?php elseif ( $active_tab == 'uploads' ) : ?>
                    <tr>
                        <th scope="row"><?php _e( 'Allowed File Types', 'cotlas-simple-forms' ); ?></th>
                        <td>
                            <input type="text" name="csf_allowed_file_types" value="<?php echo esc_attr( get_option( 'csf_allowed_file_types', 'jpg,jpeg,png,pdf,doc,docx' ) ); ?>" class="regular-text">
                            <p class="description"><?php _e( 'Comma separated extensions (e.g. jpg,pdf,zip)', 'cotlas-simple-forms' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Max Upload Size (MB)', 'cotlas-simple-forms' ); ?></th>
                        <td>
                            <input type="number" name="csf_max_upload_size" value="<?php echo esc_attr( get_option( 'csf_max_upload_size', '5' ) ); ?>" class="small-text" min="1">
                        </td>
                    </tr>
                    <?php elseif ( $active_tab == 'frontend' ) : ?>
                    <tr>
                        <th scope="row"><?php _e( 'CSS Loading', 'cotlas-simple-forms' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="csf_disable_frontend_css" value="1" <?php checked( 1, get_option( 'csf_disable_frontend_css' ), true ); ?>>
                                <?php _e( 'Disable plugin frontend CSS (if you want to style forms via your theme)', 'cotlas-simple-forms' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'JS Loading', 'cotlas-simple-forms' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="csf_disable_frontend_js" value="1" <?php checked( 1, get_option( 'csf_disable_frontend_js' ), true ); ?>>
                                <?php _e( 'Disable plugin frontend JS (warning: breaks AJAX submission)', 'cotlas-simple-forms' ); ?>
                            </label>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
                <p class="submit">
                    <input type="hidden" name="csf_settings_tab" value="<?php echo esc_attr( $active_tab ); ?>" />
                    <?php submit_button( __( 'Save Changes', 'cotlas-simple-forms' ), 'primary', 'submit', false ); ?>
                </p>
            </form>
            <?php if ( $active_tab == 'email' ) : ?>
            <script>
                function toggleSmtpFields(checkbox) {
                    var fields = document.getElementById('csf-smtp-fields');
                    fields.style.display = checkbox.checked ? 'table-row-group' : 'none';
                }
            </script>
            <?php endif; ?>
        </div>
        <?php
    }
}
