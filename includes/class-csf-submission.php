<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CSF_Submission {

    public function __construct() {
        add_action( 'wp_ajax_csf_submit_form', array( $this, 'handle_submission' ) );
        add_action( 'wp_ajax_nopriv_csf_submit_form', array( $this, 'handle_submission' ) );
    }

    public function handle_submission() {
        check_ajax_referer( 'csf_submit_nonce', 'nonce' );

        // Rate Limiting Check
        if ( get_option( 'csf_enable_limit' ) ) {
            $duration = (int) get_option( 'csf_limit_duration', 300 );
            $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
            $transient_key = 'csf_limit_' . md5( $ip );
            
            if ( get_transient( $transient_key ) ) {
                wp_send_json_error( array( 'message' => __( 'Too many submissions. Please try again later.', 'cotlas-simple-forms' ) ) );
                return;
            }
            
            // Set transient
            set_transient( $transient_key, true, $duration );
        }

        $form_id = intval( $_POST['form_id'] );
        $form_data = array();
        $country_field_keys = array();
        $taxonomy_field_map = array();
        
        // Honeypot Check + collect country fields
        // We parse the form content to find honeypot fields and country dropdowns.
        $post = get_post( $form_id );
        if ( $post ) {
            $blocks = parse_blocks( $post->post_content );
            foreach ( $blocks as $block ) {
                if ( $block['blockName'] === 'csf/field' && ! empty( $block['attrs'] ) ) {
                    $attrs = $block['attrs'];
                    
                    // Honeypot
                    if ( isset( $attrs['isHidden'] ) && $attrs['isHidden'] ) {
                        $name = isset( $attrs['name'] ) ? $attrs['name'] : sanitize_title( $attrs['label'] );
                        if ( ! empty( $_POST[ $name ] ) ) {
                            wp_send_json_error( array( 'message' => 'Spam detected.' ) );
                        }
                        unset( $_POST[ $name ] );
                    }

                    // Country dropdown fields (type === country)
                    $type = isset( $attrs['type'] ) ? $attrs['type'] : '';
                    if ( $type === 'country' ) {
                        $name = ! empty( $attrs['name'] ) ? $attrs['name'] : sanitize_title( $attrs['label'] );
                        $country_field_keys[] = 'csf_' . $name;
                    }

                    // Taxonomy select2 fields (store taxonomy for later ID->name mapping)
                    if ( $type === 'taxonomy_select2' && ! empty( $attrs['taxonomy'] ) ) {
                        $name = ! empty( $attrs['name'] ) ? $attrs['name'] : sanitize_title( $attrs['label'] );
                        $taxonomy_field_map[ 'csf_' . $name ] = $attrs['taxonomy'];
                    }
                }
            }
        }

        foreach ( $_POST as $key => $value ) {
            // We only want to save fields that are NOT our internal keys (action, nonce, form_id, page_url, etc)
            // AND we want to save them cleanly.
            // Previously we checked for 'csf_' prefix, but blocks don't always have that prefix in name attribute unless user added it.
            // Let's save everything that isn't internal.
            
            if ( ! in_array( $key, array( 'action', 'nonce', 'form_id', 'page_url', 'page_title' ) ) ) {
                 // Add prefix for storage if you want, or just store as is.
                 // The display logic strips 'csf_', so we should probably prepend it for consistency
                 // OR we change display logic to not expect it.
                 // Let's store with 'csf_' prefix for consistency with existing code structure if that was the plan,
                 // BUT the fields from frontend come as `name="name"` not `name="csf_name"`.
                 
                // Fix: Store as `csf_name` in the array.
                // Also stripslashes to remove escaped quotes (e.g. \" -> ")
                if ( is_array( $value ) ) {
                    $clean = array();
                    foreach ( $value as $v ) {
                        $clean[] = stripslashes( sanitize_text_field( $v ) );
                    }
                    $form_data[ 'csf_' . $key ] = implode( ', ', array_filter( $clean ) );
                } else {
                    $form_data[ 'csf_' . $key ] = stripslashes( sanitize_text_field( $value ) );
                }
            }
        }

        foreach ( array_keys( $form_data ) as $stored_key ) {
            if ( preg_match( '/^(csf_([a-z0-9_-]+))_(line1|line2|city|state|postcode|country)$/i', $stored_key, $matches ) ) {
                $base_key = $matches[1];
                if ( isset( $form_data[ $base_key ] ) ) {
                    unset( $form_data[ $stored_key ] );
                }
            }
            if ( preg_match( '/^(csf_([a-z0-9_-]+))_(from|to)$/i', $stored_key, $matches ) ) {
                $base_key = $matches[1];
                if ( isset( $form_data[ $base_key ] ) ) {
                    unset( $form_data[ $stored_key ] );
                }
            }
        }

        foreach ( $form_data as $stored_key => $value ) {
            if ( preg_match( '/^(csf_(.+))_country_code$/i', $stored_key, $matches ) ) {
                $base_key = $matches[1];
                if ( isset( $form_data[ $base_key ] ) ) {
                    $number = $form_data[ $base_key ];
                    $dial = $value;
                    $combined = $dial !== '' ? trim( $dial . ' - ' . $number ) : $number;
                    $form_data[ $base_key ] = $combined;
                    unset( $form_data[ $stored_key ] );
                }
            }
        }

        // Convert country codes to full country names for dedicated country dropdown fields
        if ( ! empty( $country_field_keys ) && class_exists( 'CSF_Blocks' ) ) {
            $countries = CSF_Blocks::get_countries();
            foreach ( $country_field_keys as $country_key ) {
                if ( isset( $form_data[ $country_key ] ) ) {
                    $code = $form_data[ $country_key ];
                    if ( isset( $countries[ $code ] ) ) {
                        $form_data[ $country_key ] = $countries[ $code ];
                    }
                }
            }
        }

        // Convert taxonomy term IDs to term names for taxonomy_select2 fields
        if ( ! empty( $taxonomy_field_map ) ) {
            foreach ( $taxonomy_field_map as $field_key => $taxonomy ) {
                if ( isset( $form_data[ $field_key ] ) ) {
                    $raw = $form_data[ $field_key ];
                    if ( is_numeric( $raw ) ) {
                        $term = get_term( (int) $raw, $taxonomy );
                        if ( $term && ! is_wp_error( $term ) ) {
                            $form_data[ $field_key ] = $term->name;
                        }
                    }
                }
            }
        }

        // Handle File Uploads
        $files = array();
        if ( ! empty( $_FILES ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            foreach ( $_FILES as $key => $file ) {
                $upload = wp_handle_upload( $file, array( 'test_form' => false ) );
                if ( ! isset( $upload['error'] ) && isset( $upload['url'] ) ) {
                    $files[ $key ] = $upload['url'];
                    $form_data[ $key ] = $upload['url'];
                }
            }
        }

        $form_type = get_post_meta( $form_id, 'csf_form_type', true );
        if ( ! $form_type ) {
            $form_type = 'normal';
        }

        if ( $form_type === 'login' ) {
            $this->handle_login( $form_id, $form_data );
            return;
        }

        if ( $form_type === 'register' ) {
            $this->handle_registration( $form_id, $form_data );
            return;
        }

        if ( $form_type === 'frontend_post' ) {
            $this->handle_frontend_post( $form_id, $form_data, $files );
            return;
        }

        $submission_id = wp_insert_post( array(
            'post_type'   => 'csf_submission',
            'post_status' => 'publish',
            'post_title'  => 'Submission #' . time(),
        ) );

        if ( $submission_id ) {
            update_post_meta( $submission_id, 'csf_form_id', $form_id );
            update_post_meta( $submission_id, 'csf_data', $form_data );
            update_post_meta( $submission_id, 'csf_files', $files );
            update_post_meta( $submission_id, 'csf_page_url', esc_url( $_POST['page_url'] ) );
            if ( isset( $_POST['page_title'] ) ) {
                update_post_meta( $submission_id, 'csf_page_title', sanitize_text_field( $_POST['page_title'] ) );
            }
            
            $this->send_email( $form_id, $form_data, $files );
            $thank_heading = get_post_meta( $form_id, 'csf_form_thankyou_heading', true );
            $thank_message = get_post_meta( $form_id, 'csf_form_thankyou_message', true );
            $thank_referrer = get_post_meta( $form_id, 'csf_form_thankyou_referrer', true );
            $data = array(
                'message' => 'Form submitted successfully.',
            );
            if ( $thank_heading ) {
                $data['thankyou_heading'] = $thank_heading;
            }
            if ( $thank_message ) {
                $data['thankyou_message'] = $thank_message;
            }
            if ( $thank_referrer === '1' ) {
                $data['thankyou_use_referrer'] = true;
            }
            wp_send_json_success( $data );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to save submission.' ) );
        }
    }

    private function resolve_user_field( $form_id, $form_data, $target ) {
        $post = get_post( $form_id );
        if ( ! $post ) {
            return '';
        }
        $blocks = parse_blocks( $post->post_content );
        foreach ( $blocks as $block ) {
            if ( $block['blockName'] !== 'csf/field' || empty( $block['attrs'] ) ) {
                continue;
            }
            $attrs = $block['attrs'];
            if ( empty( $attrs['userMetaTarget'] ) || $attrs['userMetaTarget'] !== $target ) {
                continue;
            }
            $name = ! empty( $attrs['name'] ) ? $attrs['name'] : sanitize_title( $attrs['label'] );
            $key = 'csf_' . $name;
            if ( isset( $form_data[ $key ] ) && $form_data[ $key ] !== '' ) {
                return $form_data[ $key ];
            }
        }
        return '';
    }

    private function collect_user_meta_fields( $form_id, $form_data ) {
        $meta = array();
        $post = get_post( $form_id );
        if ( ! $post ) {
            return $meta;
        }
        $blocks = parse_blocks( $post->post_content );
        foreach ( $blocks as $block ) {
            if ( $block['blockName'] !== 'csf/field' || empty( $block['attrs'] ) ) {
                continue;
            }
            $attrs = $block['attrs'];
            if ( empty( $attrs['userMetaTarget'] ) ) {
                continue;
            }
            $target = $attrs['userMetaTarget'];
            $name = ! empty( $attrs['name'] ) ? $attrs['name'] : sanitize_title( $attrs['label'] );
            $key = 'csf_' . $name;
            if ( ! isset( $form_data[ $key ] ) ) {
                continue;
            }
            $value = $form_data[ $key ];

            if ( $target === 'meta' ) {
                if ( empty( $attrs['userMetaKey'] ) ) {
                    continue;
                }
                $meta[ $attrs['userMetaKey'] ] = $value;
            } elseif ( in_array( $target, array( 'first_name', 'last_name', 'description' ), true ) ) {
                $meta[ $target ] = $value;
            }
        }
        return $meta;
    }

    private function get_field_label_map( $form_id ) {
        $map = array();
        $post = get_post( $form_id );
        if ( ! $post ) {
            return $map;
        }
        $blocks = parse_blocks( $post->post_content );
        foreach ( $blocks as $block ) {
            if ( $block['blockName'] !== 'csf/field' || empty( $block['attrs'] ) ) {
                continue;
            }
            $attrs = $block['attrs'];
            if ( empty( $attrs['label'] ) ) {
                continue;
            }
            $label = $attrs['label'];
            $name = ! empty( $attrs['name'] ) ? $attrs['name'] : sanitize_title( $label );
            $map[ 'csf_' . $name ] = $label;
        }
        return $map;
    }

    private function format_range_value( $value ) {
        if ( strpos( $value, ' - ' ) === false ) {
            return $value;
        }
        list( $from_raw, $to_raw ) = explode( ' - ', $value, 2 );
        $from_raw = trim( $from_raw );
        $to_raw   = trim( $to_raw );
        if ( $from_raw === '' && $to_raw === '' ) {
            return $value;
        }
        $from_date = null;
        $to_date   = null;
        $from_has_time = false;
        $to_has_time   = false;
        if ( strpos( $from_raw, 'T' ) !== false ) {
            $from_date = date_create_from_format( 'Y-m-d\TH:i', $from_raw );
            if ( ! $from_date ) {
                $from_date = date_create( $from_raw );
            }
            $from_has_time = true;
        } else {
            $from_date = date_create_from_format( 'Y-m-d', $from_raw );
            if ( ! $from_date ) {
                $from_date = date_create( $from_raw );
            }
        }
        if ( strpos( $to_raw, 'T' ) !== false ) {
            $to_date = date_create_from_format( 'Y-m-d\TH:i', $to_raw );
            if ( ! $to_date ) {
                $to_date = date_create( $to_raw );
            }
            $to_has_time = true;
        } else {
            $to_date = date_create_from_format( 'Y-m-d', $to_raw );
            if ( ! $to_date ) {
                $to_date = date_create( $to_raw );
            }
        }
        $from_is_valid = $from_date instanceof DateTime;
        $to_is_valid   = $to_date instanceof DateTime;
        if ( ! $from_is_valid && ! $to_is_valid ) {
            return $value;
        }
        $from_str = '';
        $to_str   = '';
        if ( $from_is_valid ) {
            $from_str = $from_has_time
                ? date_i18n( 'j M Y, g:i A', $from_date->getTimestamp() )
                : date_i18n( 'j M Y', $from_date->getTimestamp() );
        }
        if ( $to_is_valid ) {
            $to_str = $to_has_time
                ? date_i18n( 'j M Y, g:i A', $to_date->getTimestamp() )
                : date_i18n( 'j M Y', $to_date->getTimestamp() );
        }
        if ( $from_str && $to_str ) {
            return $from_str . ' - ' . $to_str;
        }
        if ( $from_str ) {
            return $from_str;
        }
        if ( $to_str ) {
            return $to_str;
        }
        return $value;
    }

    private function handle_login( $form_id, $form_data ) {
        if ( is_user_logged_in() ) {
            wp_send_json_success( array( 'message' => __( 'You are already logged in.', 'cotlas-simple-forms' ) ) );
        }

        $username = $this->resolve_user_field( $form_id, $form_data, 'user_login' );
        if ( ! $username ) {
            $username = $this->resolve_user_field( $form_id, $form_data, 'user_email' );
        }
        $password = $this->resolve_user_field( $form_id, $form_data, 'user_pass' );

        if ( ! $username || ! $password ) {
            wp_send_json_error( array( 'message' => __( 'Login fields are not configured correctly.', 'cotlas-simple-forms' ) ) );
        }

        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true,
        );

        $user = wp_signon( $creds, is_ssl() );
        if ( is_wp_error( $user ) ) {
            wp_send_json_error( array( 'message' => $user->get_error_message() ) );
        }

        $redirect_page_id = (int) get_post_meta( $form_id, 'csf_form_login_redirect', true );
        $redirect_url = '';
        if ( $redirect_page_id ) {
            $redirect_url = get_permalink( $redirect_page_id );
        }

        $data = array(
            'message' => __( 'Login successful.', 'cotlas-simple-forms' ),
        );
        if ( $redirect_url ) {
            $data['redirect_url'] = esc_url_raw( $redirect_url );
        }

        wp_send_json_success( $data );
    }

    private function handle_registration( $form_id, $form_data ) {
        if ( is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You are already logged in.', 'cotlas-simple-forms' ) ) );
        }

        if ( ! get_option( 'users_can_register' ) ) {
            wp_send_json_error( array( 'message' => __( 'Registration is disabled.', 'cotlas-simple-forms' ) ) );
        }

        $user_login = $this->resolve_user_field( $form_id, $form_data, 'user_login' );
        $user_email = $this->resolve_user_field( $form_id, $form_data, 'user_email' );
        $user_pass  = $this->resolve_user_field( $form_id, $form_data, 'user_pass' );

        if ( ! $user_login || ! $user_email || ! $user_pass ) {
            wp_send_json_error( array( 'message' => __( 'Registration fields are not configured correctly.', 'cotlas-simple-forms' ) ) );
        }

        if ( username_exists( $user_login ) || email_exists( $user_email ) ) {
            wp_send_json_error( array( 'message' => __( 'An account with this username or email already exists.', 'cotlas-simple-forms' ) ) );
        }

        $userdata = array(
            'user_login' => $user_login,
            'user_email' => $user_email,
            'user_pass'  => $user_pass,
        );

        $display_name = $this->resolve_user_field( $form_id, $form_data, 'display_name' );
        if ( $display_name ) {
            $userdata['display_name'] = $display_name;
        }

        $website = $this->resolve_user_field( $form_id, $form_data, 'user_url' );
        if ( $website ) {
            $userdata['user_url'] = $website;
        }

        $user_id = wp_insert_user( $userdata );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
        }

        $meta = $this->collect_user_meta_fields( $form_id, $form_data );
        foreach ( $meta as $k => $v ) {
            update_user_meta( $user_id, $k, $v );
        }

        $redirect_page_id = (int) get_post_meta( $form_id, 'csf_form_register_redirect', true );
        $redirect_url = '';
        if ( $redirect_page_id ) {
            $redirect_url = get_permalink( $redirect_page_id );
        }

        $data = array(
            'message' => __( 'Registration successful. You can now log in.', 'cotlas-simple-forms' ),
        );
        if ( $redirect_url ) {
            $data['redirect_url'] = esc_url_raw( $redirect_url );
        }

        wp_send_json_success( $data );
    }

    private function handle_frontend_post( $form_id, $form_data, $files ) {
        $guest_submit = get_post_meta( $form_id, 'csf_form_guest_submit', true );
        if ( ! $guest_submit && ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in to submit a post.', 'cotlas-simple-forms' ) ) );
        }

        $post_type = get_post_meta( $form_id, 'csf_form_post_type', true );
        if ( ! $post_type ) {
            $post_type = 'post';
        }

        $post_status = get_post_meta( $form_id, 'csf_form_post_status', true );
        if ( ! $post_status ) {
            $post_status = 'pending';
        }

        $post_title = isset( $form_data['csf_post_title'] ) ? sanitize_text_field( $form_data['csf_post_title'] ) : '';
        if ( ! $post_title ) {
            $post_title = __( 'Frontend Submission', 'cotlas-simple-forms' ) . ' - ' . time();
        }

        $post_content = isset( $form_data['csf_post_content'] ) ? wp_kses_post( $form_data['csf_post_content'] ) : '';
        $post_excerpt = isset( $form_data['csf_post_excerpt'] ) ? sanitize_textarea_field( $form_data['csf_post_excerpt'] ) : '';

        $post_data = array(
            'post_title'   => $post_title,
            'post_content' => $post_content,
            'post_excerpt' => $post_excerpt,
            'post_status'  => $post_status,
            'post_type'    => $post_type,
        );

        if ( is_user_logged_in() ) {
            $post_data['post_author'] = get_current_user_id();
        }

        $new_post_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $new_post_id ) ) {
            wp_send_json_error( array( 'message' => $new_post_id->get_error_message() ) );
        }

        update_post_meta( $new_post_id, '_csf_frontend_post', '1' );

        $default_category = get_post_meta( $form_id, 'csf_form_default_category', true );
        if ( $default_category && $post_type === 'post' ) {
            wp_set_post_categories( $new_post_id, array( $default_category ), true );
        }

        if ( isset( $form_data['csf_post_category'] ) && $post_type === 'post' ) {
            $cat_ids = array_map( 'intval', explode( ',', $form_data['csf_post_category'] ) );
            wp_set_post_categories( $new_post_id, $cat_ids, true );
        }
        if ( isset( $form_data['csf_post_tags'] ) && $post_type === 'post' ) {
            wp_set_post_tags( $new_post_id, sanitize_text_field( $form_data['csf_post_tags'] ), true );
        }

        if ( isset( $files['featured_image'] ) ) {
            $this->set_featured_image( $new_post_id, $files['featured_image'] );
        }

        foreach ( $form_data as $key => $value ) {
            if ( in_array( $key, array( 'csf_post_title', 'csf_post_content', 'csf_post_excerpt', 'csf_post_category', 'csf_post_tags' ) ) ) {
                continue;
            }
            $meta_key = preg_replace( '/^csf_/', '', $key );
            update_post_meta( $new_post_id, $meta_key, $value );
        }

        $submission_id = wp_insert_post( array(
            'post_type'   => 'csf_submission',
            'post_status' => 'publish',
            'post_title'  => 'Submission #' . time(),
        ) );

        if ( $submission_id ) {
            update_post_meta( $submission_id, 'csf_form_id', $form_id );
            update_post_meta( $submission_id, 'csf_data', $form_data );
            update_post_meta( $submission_id, 'csf_files', $files );
            update_post_meta( $submission_id, 'csf_page_url', isset( $_POST['page_url'] ) ? esc_url( $_POST['page_url'] ) : '' );
            if ( isset( $_POST['page_title'] ) ) {
                update_post_meta( $submission_id, 'csf_page_title', sanitize_text_field( $_POST['page_title'] ) );
            }
        }

        $this->send_email( $form_id, $form_data, $files );

        $thank_heading = get_post_meta( $form_id, 'csf_form_thankyou_heading', true );
        $thank_message = get_post_meta( $form_id, 'csf_form_thankyou_message', true );
        $thank_referrer = get_post_meta( $form_id, 'csf_form_thankyou_referrer', true );
        $data = array(
            'message' => __( 'Post submitted successfully.', 'cotlas-simple-forms' ),
        );
        if ( $thank_heading ) {
            $data['thankyou_heading'] = $thank_heading;
        }
        if ( $thank_message ) {
            $data['thankyou_message'] = $thank_message;
        }
        if ( $thank_referrer === '1' ) {
            $data['thankyou_use_referrer'] = true;
        }
        wp_send_json_success( $data );
    }

    private function set_featured_image( $post_id, $file_url ) {
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );
        
        $attachment_id = media_sideload_image( $file_url, $post_id, null, 'id' );
        if ( ! is_wp_error( $attachment_id ) ) {
            set_post_thumbnail( $post_id, $attachment_id );
        }
    }

    private function send_email( $form_id, $data, $files ) {
        $to = get_post_meta( $form_id, 'csf_form_email_to', true );
        if ( ! $to ) {
            $to = get_option( 'csf_to_email', get_option( 'admin_email' ) );
        }

        $subject = get_post_meta( $form_id, 'csf_form_email_subject', true );
        if ( ! $subject ) {
            $subject = 'New Form Submission - ' . get_the_title( $form_id );
        }

        $site_name = get_bloginfo( 'name' );
        $page_title = isset( $data['csf_page_title'] ) ? $data['csf_page_title'] : ( isset( $_POST['page_title'] ) ? sanitize_text_field($_POST['page_title']) : '' );
        $page_url = isset( $data['csf_page_url'] ) ? $data['csf_page_url'] : ( isset( $_POST['page_url'] ) ? esc_url($_POST['page_url']) : '' );
        
        $field_labels = $this->get_field_label_map( $form_id );

        $all_fields = '';
        foreach ( $data as $key => $val ) {
            // Skip internal fields
            if ( in_array( $key, array( 'form_id', 'action', 'page_url', 'page_title', 'nonce' ) ) ) continue;

            if ( isset( $field_labels[ $key ] ) ) {
                $label = $field_labels[ $key ];
            } else {
                $label = ucfirst( str_replace( array( 'csf_', '_', '-' ), array( '', ' ', ' ' ), $key ) );
            }
            
            if ( is_string( $val ) && strpos( $val, ' - ' ) !== false ) {
                $val = $this->format_range_value( $val );
            }
            $val_html = nl2br( esc_html( is_array( $val ) ? implode( ', ', $val ) : $val ) );
            
            $all_fields .= '<div class="email-field" style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                            <div class="email-label" style="font-weight: bold; color: #555; font-size: 14px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">' . esc_html( $label ) . '</div>
                            <div class="email-value" style="color: #333; font-size: 16px; line-height: 1.5;">' . $val_html . '</div>
                         </div>';
        }

        $form_type = get_post_meta( $form_id, 'csf_form_type', true );
        
        // Build Admin Message
        $default_admin = 'You have received a new form submission from <strong>{site_name}</strong> on page "{page_title}".<br><br>{all_fields}';
        $default_post = 'A new frontend post has been submitted by {name}.<br><br>Title: {post_title}<br>Status: Pending Review<br><br>{all_fields}';
        
        $admin_template = get_option( 'csf_email_template_admin_notification', $default_admin );
        if ( $form_type === 'frontend_post' ) {
            $admin_template = get_option( 'csf_email_template_frontend_post', $default_post );
        }

        $admin_message = $this->prepare_email_content( $admin_template, $data, $all_fields, $site_name, $page_title, $page_url );
        $admin_body = $this->wrap_email_body( $admin_message );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        $meta_from_email = get_post_meta( $form_id, 'csf_form_email_from_email', true );
        $meta_from_name = get_post_meta( $form_id, 'csf_form_email_from_name', true );
        if ( $meta_from_email ) {
            $from_header = $meta_from_email;
            if ( $meta_from_name ) {
                $from_header = $meta_from_name . ' <' . $meta_from_email . '>';
            }
            $headers[] = 'From: ' . $from_header;
        }
        
        // SMTP Handling
        $use_global = get_option( 'csf_use_global_smtp' );
        $enable_smtp = get_option( 'csf_enable_smtp' );

        if ( $enable_smtp && ! $use_global ) {
            add_action( 'phpmailer_init', array( $this, 'configure_smtp' ) );
        }

        wp_mail( $to, $subject, $admin_body, $headers );

        if ( get_post_meta( $form_id, 'csf_form_mail2_enable', true ) === '1' ) {
            $user_email_field = get_post_meta( $form_id, 'csf_form_mail2_email_field', true );
            if ( $user_email_field ) {
                $user_key = 'csf_' . $user_email_field;
                if ( isset( $data[ $user_key ] ) && is_email( $data[ $user_key ] ) ) {
                    $user_to = $data[ $user_key ];
                    $mail2_subject = get_post_meta( $form_id, 'csf_form_mail2_subject', true );
                    if ( ! $mail2_subject ) {
                        $mail2_subject = __( 'Thank you for your submission', 'cotlas-simple-forms' );
                    }

                    $default_user = 'Hi {name},<br><br>Thank you for your submission. Here is a copy of what we received:<br><br>{all_fields}';
                    $user_template = get_option( 'csf_email_template_user_confirmation', $default_user );
                    $user_message = $this->prepare_email_content( $user_template, $data, $all_fields, $site_name, $page_title, $page_url );
                    $user_body = $this->wrap_email_body( $user_message );

                    wp_mail( $user_to, $mail2_subject, $user_body, $headers );
                }
            }
        }
        
        if ( $enable_smtp && ! $use_global ) {
            remove_action( 'phpmailer_init', array( $this, 'configure_smtp' ) );
        }
    }

    private function prepare_email_content( $template, $data, $all_fields, $site_name, $page_title, $page_url ) {
        $content = wpautop( wp_kses_post( $template ) );
        $content = str_replace( '{all_fields}', $all_fields, $content );
        $content = str_replace( '{site_name}', esc_html( $site_name ), $content );
        $content = str_replace( '{page_title}', esc_html( $page_title ), $content );
        $content = str_replace( '{page_url}', esc_url( $page_url ), $content );
        
        foreach ( $data as $key => $val ) {
            $clean_key = preg_replace( '/^csf_/', '', $key );
            $content = str_replace( '{' . $clean_key . '}', esc_html( is_array( $val ) ? implode( ', ', $val ) : $val ), $content );
        }
        
        // Remove any unmatched variables
        $content = preg_replace( '/{[a-zA-Z0-9_]+}/', '', $content );
        
        return $content;
    }

    private function wrap_email_body( $content ) {
        return '<!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                .email-wrapper { width: 100%; background-color: #f4f4f4; padding: 40px 0; }
                .email-card { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden; }
                .email-body { padding: 40px; }
                .email-field:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
                p { margin-top: 0; margin-bottom: 20px; color: #555; }
                a { color: #0073aa; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="email-wrapper">
                <div class="email-card">
                    <div class="email-body">
                        ' . $content . '
                    </div>
                </div>
            </div>
        </body>
        </html>';
    }

    public function configure_smtp( $phpmailer ) {
        $phpmailer->isSMTP();
        $phpmailer->Host = get_option( 'csf_smtp_host' );
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = get_option( 'csf_smtp_port' );
        $phpmailer->Username = get_option( 'csf_smtp_user' );
        $phpmailer->Password = get_option( 'csf_smtp_pass' );
        $phpmailer->SMTPSecure = get_option( 'csf_smtp_encryption' );
        
        $from_email = get_option( 'csf_from_email' );
        $from_name = get_option( 'csf_from_name' );
        
        if ( $from_email ) {
            $phpmailer->From = $from_email;
        }
        if ( $from_name ) {
            $phpmailer->FromName = $from_name;
        }
    }
}
