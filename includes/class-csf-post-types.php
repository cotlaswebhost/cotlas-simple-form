<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CSF_Post_Types {

    public function __construct() {
        add_action( 'init', array( __CLASS__, 'register_post_types' ) );
        add_filter( 'post_row_actions', array( __CLASS__, 'add_row_actions' ), 10, 2 );
        add_action( 'admin_action_csf_duplicate_form', array( __CLASS__, 'handle_duplicate_form' ) );
    }

    public static function register_post_types() {
        // Register Form Post Type
        $labels = array(
            'name'               => _x( 'Cotlas Simple Forms', 'post type general name', 'cotlas-simple-forms' ),
            'singular_name'      => _x( 'Form', 'post type singular name', 'cotlas-simple-forms' ),
            'menu_name'          => _x( 'Cotlas Forms', 'admin menu', 'cotlas-simple-forms' ),
            'name_admin_bar'     => _x( 'Form', 'add new on admin bar', 'cotlas-simple-forms' ),
            'add_new'            => _x( 'Add New Form', 'form', 'cotlas-simple-forms' ),
            'add_new_item'       => __( 'Add New Form', 'cotlas-simple-forms' ),
            'new_item'           => __( 'New Form', 'cotlas-simple-forms' ),
            'edit_item'          => __( 'Edit Form', 'cotlas-simple-forms' ),
            'view_item'          => __( 'View Form', 'cotlas-simple-forms' ),
            'all_items'          => __( 'All Forms', 'cotlas-simple-forms' ),
            'search_items'       => __( 'Search Forms', 'cotlas-simple-forms' ),
            'parent_item_colon'  => __( 'Parent Forms:', 'cotlas-simple-forms' ),
            'not_found'          => __( 'No forms found.', 'cotlas-simple-forms' ),
            'not_found_in_trash' => __( 'No forms found in Trash.', 'cotlas-simple-forms' )
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __( 'Custom Forms.', 'cotlas-simple-forms' ),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'csf-form' ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor', 'revisions' ),
            'show_in_rest'       => true,
        );

        register_post_type( 'csf_form', $args );

        // Add columns filter
        add_filter( 'manage_csf_form_posts_columns', array( __CLASS__, 'add_form_columns' ) );
        add_action( 'manage_csf_form_posts_custom_column', array( __CLASS__, 'render_form_columns' ), 10, 2 );
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_shortcode_metabox' ) );
        add_action( 'save_post_csf_form', array( __CLASS__, 'save_form_meta' ), 10, 2 );

        // Register Submission Post Type (Hidden from menu, managed separately)
        $labels_sub = array(
            'name'               => _x( 'Submissions', 'post type general name', 'cotlas-simple-forms' ),
            'singular_name'      => _x( 'Submission', 'post type singular name', 'cotlas-simple-forms' ),
            'menu_name'          => _x( 'Submissions', 'admin menu', 'cotlas-simple-forms' ),
        );

        $args_sub = array(
            'labels'             => $labels_sub,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false, // We will create a custom admin page
            'show_in_menu'       => false,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'supports'           => array( 'title', 'custom-fields' ),
        );

        register_post_type( 'csf_submission', $args_sub );
    }

    public static function add_form_columns( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;
            if ( $key === 'title' ) {
                $new_columns['shortcode'] = __( 'Shortcode', 'cotlas-simple-forms' );
            }
        }
        return $new_columns;
    }

    public static function render_form_columns( $column, $post_id ) {
        if ( $column === 'shortcode' ) {
            echo '<input type="text" readonly value="[csf_form id=&quot;' . $post_id . '&quot;]" class="widefat" style="width: 250px;" onclick="this.select()">';
        }
    }

    public static function add_row_actions( $actions, $post ) {
        if ( $post->post_type !== 'csf_form' ) {
            return $actions;
        }
        $url = wp_nonce_url(
            admin_url( 'admin.php?action=csf_duplicate_form&post=' . $post->ID ),
            'csf_duplicate_form_' . $post->ID
        );
        $actions['csf_duplicate'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplicate', 'cotlas-simple-forms' ) . '</a>';
        return $actions;
    }

    public static function add_shortcode_metabox() {
        add_meta_box(
            'csf_form_shortcode',
            __( 'Form Shortcode', 'cotlas-simple-forms' ),
            array( __CLASS__, 'render_shortcode_metabox' ),
            'csf_form',
            'side',
            'high'
        );
        add_meta_box(
            'csf_form_settings',
            __( 'Form Settings', 'cotlas-simple-forms' ),
            array( __CLASS__, 'render_settings_metabox' ),
            'csf_form',
            'side',
            'default'
        );
    }

    public static function render_shortcode_metabox( $post ) {
        echo '<p>' . __( 'Copy this shortcode to display the form:', 'cotlas-simple-forms' ) . '</p>';
        echo '<input type="text" readonly value="[csf_form id=&quot;' . $post->ID . '&quot;]" class="widefat" onclick="this.select()">';
    }

    public static function handle_duplicate_form() {
        if ( ! isset( $_GET['post'] ) ) {
            wp_redirect( admin_url( 'edit.php?post_type=csf_form' ) );
            exit;
        }

        $post_id = intval( $_GET['post'] );
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( __( 'You are not allowed to duplicate this form.', 'cotlas-simple-forms' ) );
        }

        check_admin_referer( 'csf_duplicate_form_' . $post_id );

        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'csf_form' ) {
            wp_die( __( 'Form not found.', 'cotlas-simple-forms' ) );
        }

        $new_post = array(
            'post_title'   => $post->post_title . ' (Copy)',
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'post_status'  => 'draft',
            'post_type'    => 'csf_form',
        );

        $new_post_id = wp_insert_post( $new_post );

        if ( $new_post_id && ! is_wp_error( $new_post_id ) ) {
            $meta = get_post_meta( $post_id );
            foreach ( $meta as $key => $values ) {
                if ( in_array( $key, array( '_edit_lock', '_edit_last' ), true ) ) {
                    continue;
                }
                foreach ( $values as $value ) {
                    add_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
                }
            }

            wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
            exit;
        }

        wp_redirect( admin_url( 'edit.php?post_type=csf_form' ) );
        exit;
    }

    public static function render_settings_metabox( $post ) {
        $form_class = get_post_meta( $post->ID, 'csf_form_class', true );
        $label_mode = get_post_meta( $post->ID, 'csf_form_label_mode', true );
        $form_type = get_post_meta( $post->ID, 'csf_form_type', true );
        $hide_help_when_label = get_post_meta( $post->ID, 'csf_form_hide_help_when_label', true );
        $hide_help_all = get_post_meta( $post->ID, 'csf_form_hide_help_all', true );
        $keep_session_data = get_post_meta( $post->ID, 'csf_form_keep_session_data', true );
        $show_page_heading = get_post_meta( $post->ID, 'csf_form_show_page_heading', true );
        $show_pagination = get_post_meta( $post->ID, 'csf_form_show_pagination', true );
        $conversational = get_post_meta( $post->ID, 'csf_form_conversational', true );
        $email_to = get_post_meta( $post->ID, 'csf_form_email_to', true );
        $email_from_name = get_post_meta( $post->ID, 'csf_form_email_from_name', true );
        $email_from_email = get_post_meta( $post->ID, 'csf_form_email_from_email', true );
        $email_subject = get_post_meta( $post->ID, 'csf_form_email_subject', true );
        $mail2_enable = get_post_meta( $post->ID, 'csf_form_mail2_enable', true );
        $mail2_email_field = get_post_meta( $post->ID, 'csf_form_mail2_email_field', true );
        $mail2_subject = get_post_meta( $post->ID, 'csf_form_mail2_subject', true );
        $turnstile_enable = get_post_meta( $post->ID, 'csf_form_turnstile_enable', true );
        $template = get_post_meta( $post->ID, 'csf_form_template', true );
        $login_redirect = get_post_meta( $post->ID, 'csf_form_login_redirect', true );
        $register_redirect = get_post_meta( $post->ID, 'csf_form_register_redirect', true );
        $thankyou_heading = get_post_meta( $post->ID, 'csf_form_thankyou_heading', true );
        $thankyou_message = get_post_meta( $post->ID, 'csf_form_thankyou_message', true );
        $thankyou_referrer = get_post_meta( $post->ID, 'csf_form_thankyou_referrer', true );
        $post_type_setting = get_post_meta( $post->ID, 'csf_form_post_type', true );
        $post_status = get_post_meta( $post->ID, 'csf_form_post_status', true );
        $guest_submit = get_post_meta( $post->ID, 'csf_form_guest_submit', true );
        $default_category = get_post_meta( $post->ID, 'csf_form_default_category', true );

        wp_nonce_field( 'csf_form_settings_save', 'csf_form_settings_nonce' );

        echo '<p>' . __( 'Form wrapper CSS class.', 'cotlas-simple-forms' ) . '</p>';
        echo '<input type="text" name="csf_form_class" value="' . esc_attr( $form_class ) . '" class="widefat">';

        echo '<hr>';

        echo '<p><strong>' . __( 'Form Type', 'cotlas-simple-forms' ) . '</strong></p>';
        echo '<p>';
        echo '<label for="csf_form_type">' . __( 'Form behaviour', 'cotlas-simple-forms' ) . '</label>';
        echo '<select name="csf_form_type" id="csf_form_type" class="widefat">';
        echo '<option value="normal"' . selected( $form_type, 'normal', false ) . '>' . esc_html__( 'Normal (contact/enquiry)', 'cotlas-simple-forms' ) . '</option>';
        echo '<option value="login"' . selected( $form_type, 'login', false ) . '>' . esc_html__( 'Login form', 'cotlas-simple-forms' ) . '</option>';
        echo '<option value="register"' . selected( $form_type, 'register', false ) . '>' . esc_html__( 'Registration form', 'cotlas-simple-forms' ) . '</option>';
        echo '<option value="payment"' . selected( $form_type, 'payment', false ) . '>' . esc_html__( 'Payment form', 'cotlas-simple-forms' ) . '</option>';
        echo '<option value="donation"' . selected( $form_type, 'donation', false ) . '>' . esc_html__( 'Donation form', 'cotlas-simple-forms' ) . '</option>';
        echo '<option value="frontend_post"' . selected( $form_type, 'frontend_post', false ) . '>' . esc_html__( 'Frontend Add Post Form', 'cotlas-simple-forms' ) . '</option>';
        echo '</select>';
        echo '</p>';

        echo '<hr>';

        echo '<p><strong>' . __( 'Labels and Help Text', 'cotlas-simple-forms' ) . '</strong></p>';
        echo '<p>';
        echo '<label for="csf_form_label_mode">' . __( 'Label behaviour', 'cotlas-simple-forms' ) . '</label>';
        echo '<select name="csf_form_label_mode" id="csf_form_label_mode" class="widefat">';
        echo '<option value="">' . esc_html__( 'Default (per field)', 'cotlas-simple-forms' ) . '</option>';
        echo '<option value="hide"' . selected( $label_mode, 'hide', false ) . '>' . esc_html__( 'Hide all labels', 'cotlas-simple-forms' ) . '</option>';
        echo '<option value="placeholder"' . selected( $label_mode, 'placeholder', false ) . '>' . esc_html__( 'Use labels as placeholders', 'cotlas-simple-forms' ) . '</option>';
        echo '</select>';
        echo '</p>';

        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="csf_form_hide_help_when_label" value="1"' . checked( $hide_help_when_label, '1', false ) . '>';
        echo ' ' . esc_html__( 'Hide help text when labels are visible', 'cotlas-simple-forms' );
        echo '</label>';
        echo '</p>';

        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="csf_form_hide_help_all" value="1"' . checked( $hide_help_all, '1', false ) . '>';
        echo ' ' . esc_html__( 'Hide all help text for this form', 'cotlas-simple-forms' );
        echo '</label>';
        echo '</p>';

        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="csf_form_keep_session_data" value="1"' . checked( $keep_session_data, '1', false ) . '>';
        echo ' ' . esc_html__( 'Keep filled data in this browser session', 'cotlas-simple-forms' );
        echo '</label>';
        echo '</p>';

        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="csf_form_show_page_heading" value="1"' . checked( $show_page_heading, '1', false ) . '>';
        echo ' ' . esc_html__( 'Show page heading in multi-step header', 'cotlas-simple-forms' );
        echo '</label>';
        echo '</p>';

        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="csf_form_show_pagination" value="1"' . checked( $show_pagination, '1', false ) . '>';
        echo ' ' . esc_html__( 'Show pagination (step counter and dots)', 'cotlas-simple-forms' );
        echo '</label>';
        echo '</p>';

        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="csf_form_conversational" value="1"' . checked( $conversational, '1', false ) . '>';
        echo ' ' . esc_html__( 'Enable conversational layout (one step at a time)', 'cotlas-simple-forms' );
        echo '</label>';
        echo '</p>';

        echo '<hr>';

        echo '<div id="csf_frontend_post_settings">';
        echo '<p><strong>' . __( 'Frontend Post Settings', 'cotlas-simple-forms' ) . '</strong></p>';
        echo '<p>';
        echo '<label for="csf_form_post_type">' . __( 'Post Type', 'cotlas-simple-forms' ) . '</label>';
        echo '<select name="csf_form_post_type" id="csf_form_post_type" class="widefat">';
        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        foreach ( $post_types as $pt ) {
            if ( $pt->name === 'attachment' ) continue;
            echo '<option value="' . esc_attr( $pt->name ) . '"' . selected( $post_type_setting, $pt->name, false ) . '>' . esc_html( $pt->label ) . '</option>';
        }
        echo '</select>';
        echo '</p>';

        echo '<p>';
        echo '<label for="csf_form_post_status">' . __( 'Post Status', 'cotlas-simple-forms' ) . '</label>';
        echo '<select name="csf_form_post_status" id="csf_form_post_status" class="widefat">';
        echo '<option value="pending"' . selected( $post_status, 'pending', false ) . '>' . esc_html__( 'Pending', 'cotlas-simple-forms' ) . '</option>';
        echo '<option value="draft"' . selected( $post_status, 'draft', false ) . '>' . esc_html__( 'Draft', 'cotlas-simple-forms' ) . '</option>';
        echo '<option value="publish"' . selected( $post_status, 'publish', false ) . '>' . esc_html__( 'Publish', 'cotlas-simple-forms' ) . '</option>';
        echo '</select>';
        echo '</p>';

        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="csf_form_guest_submit" value="1"' . checked( $guest_submit, '1', false ) . '>';
        echo ' ' . esc_html__( 'Allow guest submission', 'cotlas-simple-forms' );
        echo '</label>';
        echo '</p>';

        echo '<p>';
        echo '<label for="csf_form_default_category">' . __( 'Default Category', 'cotlas-simple-forms' ) . '</label>';
        wp_dropdown_categories( array(
            'name'             => 'csf_form_default_category',
            'id'               => 'csf_form_default_category',
            'show_option_none' => __( '— No default —', 'cotlas-simple-forms' ),
            'option_none_value' => '0',
            'hide_empty'       => 0,
            'selected'         => $default_category,
            'class'            => 'widefat',
        ) );
        echo '</p>';
        echo '</div>';

        echo '<script>
            jQuery(document).ready(function($) {
                function toggleFrontendPostSettings() {
                    if ($("#csf_form_type").val() === "frontend_post") {
                        $("#csf_frontend_post_settings").show();
                    } else {
                        $("#csf_frontend_post_settings").hide();
                    }
                }
                $("#csf_form_type").on("change", toggleFrontendPostSettings);
                toggleFrontendPostSettings();
            });
        </script>';

        echo '<hr>';

        echo '<p><strong>' . __( 'Email Settings', 'cotlas-simple-forms' ) . '</strong></p>';
        echo '<p>';
        echo '<label for="csf_form_email_to">' . __( 'Send to email', 'cotlas-simple-forms' ) . '</label>';
        echo '<input type="email" name="csf_form_email_to" id="csf_form_email_to" value="' . esc_attr( $email_to ) . '" class="widefat">';
        echo '</p>';

        echo '<p>';
        echo '<label for="csf_form_email_from_name">' . __( 'From name', 'cotlas-simple-forms' ) . '</label>';
        echo '<input type="text" name="csf_form_email_from_name" id="csf_form_email_from_name" value="' . esc_attr( $email_from_name ) . '" class="widefat">';
        echo '</p>';

        echo '<p>';
        echo '<label for="csf_form_email_from_email">' . __( 'From email', 'cotlas-simple-forms' ) . '</label>';
        echo '<input type="email" name="csf_form_email_from_email" id="csf_form_email_from_email" value="' . esc_attr( $email_from_email ) . '" class="widefat">';
        echo '</p>';

        echo '<p>';
        echo '<label for="csf_form_email_subject">' . __( 'Subject', 'cotlas-simple-forms' ) . '</label>';
        echo '<input type="text" name="csf_form_email_subject" id="csf_form_email_subject" value="' . esc_attr( $email_subject ) . '" class="widefat">';
        echo '</p>';

        echo '<hr>';

        echo '<p><strong>' . __( 'Payment and Donation Redirect', 'cotlas-simple-forms' ) . '</strong></p>';
        echo '<p>';
        echo '<label for="csf_form_redirect_url">' . __( 'Redirect URL after submission', 'cotlas-simple-forms' ) . '</label>';
        echo '<input type="url" name="csf_form_redirect_url" id="csf_form_redirect_url" value="' . esc_attr( get_post_meta( $post->ID, 'csf_form_redirect_url', true ) ) . '" class="widefat" placeholder="https://">';
        echo '<span class="description">' . esc_html__( 'Use this for payment gateways, donation links, or any thank-you page. It also works for normal forms.', 'cotlas-simple-forms' ) . '</span>';
        echo '</p>';

        echo '<hr>';

        echo '<p><strong>' . __( 'Login and Registration Redirect', 'cotlas-simple-forms' ) . '</strong></p>';
        echo '<p>';
        echo '<label for="csf_form_login_redirect">' . __( 'Redirect after successful login', 'cotlas-simple-forms' ) . '</label>';
        wp_dropdown_pages( array(
            'name'              => 'csf_form_login_redirect',
            'id'                => 'csf_form_login_redirect',
            'show_option_none'  => __( '— No redirect —', 'cotlas-simple-forms' ),
            'option_none_value' => '',
            'selected'          => $login_redirect,
        ) );
        echo '</p>';

        echo '<p>';
        echo '<label for="csf_form_register_redirect">' . __( 'Redirect after successful registration', 'cotlas-simple-forms' ) . '</label>';
        wp_dropdown_pages( array(
            'name'              => 'csf_form_register_redirect',
            'id'                => 'csf_form_register_redirect',
            'show_option_none'  => __( '— No redirect —', 'cotlas-simple-forms' ),
            'option_none_value' => '',
            'selected'          => $register_redirect,
        ) );
        echo '</p>';

        echo '<hr>';

        echo '<p><strong>' . __( 'Mail 2 (User Email)', 'cotlas-simple-forms' ) . '</strong></p>';
        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="csf_form_mail2_enable" value="1"' . checked( $mail2_enable, '1', false ) . '>';
        echo ' ' . esc_html__( 'Send a copy to the user', 'cotlas-simple-forms' );
        echo '</label>';
        echo '</p>';

        echo '<p>';
        echo '<label for="csf_form_mail2_email_field">' . __( 'User email field name', 'cotlas-simple-forms' ) . '</label>';
        echo '<input type="text" name="csf_form_mail2_email_field" id="csf_form_mail2_email_field" value="' . esc_attr( $mail2_email_field ) . '" class="widefat" placeholder="' . esc_attr__( 'email', 'cotlas-simple-forms' ) . '">';
        echo '</p>';

        echo '<p>';
        echo '<label for="csf_form_mail2_subject">' . __( 'Mail 2 subject', 'cotlas-simple-forms' ) . '</label>';
        echo '<input type="text" name="csf_form_mail2_subject" id="csf_form_mail2_subject" value="' . esc_attr( $mail2_subject ) . '" class="widefat">';
        echo '</p>';

        echo '<hr>';

        echo '<p><strong>' . __( 'Conversational Thank You Screen', 'cotlas-simple-forms' ) . '</strong></p>';
        echo '<p>';
        echo '<label for="csf_form_thankyou_heading">' . __( 'Thank you heading', 'cotlas-simple-forms' ) . '</label>';
        echo '<input type="text" name="csf_form_thankyou_heading" id="csf_form_thankyou_heading" value="' . esc_attr( $thankyou_heading ) . '" class="widefat" placeholder="' . esc_attr__( 'Thank you', 'cotlas-simple-forms' ) . '">';
        echo '</p>';

        echo '<p>';
        echo '<label for="csf_form_thankyou_message">' . __( 'Thank you message', 'cotlas-simple-forms' ) . '</label>';
        echo '<textarea name="csf_form_thankyou_message" id="csf_form_thankyou_message" class="widefat" rows="3">' . esc_textarea( $thankyou_message ) . '</textarea>';
        echo '</p>';

        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="csf_form_thankyou_referrer" value="1"' . checked( $thankyou_referrer, '1', false ) . '>';
        echo ' ' . esc_html__( 'Show button to return to previous page', 'cotlas-simple-forms' );
        echo '</label>';
        echo '</p>';

        echo '<hr>';

        echo '<p><strong>' . __( 'Spam Protection', 'cotlas-simple-forms' ) . '</strong></p>';
        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="csf_form_turnstile_enable" value="1"' . checked( $turnstile_enable, '1', false ) . '>';
        echo ' ' . esc_html__( 'Enable Turnstile for this form', 'cotlas-simple-forms' );
        echo '</label>';
        echo '</p>';

        echo '<hr>';

        echo '<p><strong>' . __( 'Display Template', 'cotlas-simple-forms' ) . '</strong></p>';
        echo '<p>';
        echo '<label for="csf_form_template">' . __( 'Template', 'cotlas-simple-forms' ) . '</label>';
        echo '<select name="csf_form_template" id="csf_form_template" class="widefat">';
        echo '<option value="">' . esc_html__( 'Default', 'cotlas-simple-forms' ) . '</option>';
        echo '<option value="boxed"' . selected( $template, 'boxed', false ) . '>' . esc_html__( 'Boxed card', 'cotlas-simple-forms' ) . '</option>';
        echo '<option value="underline"' . selected( $template, 'underline', false ) . '>' . esc_html__( 'Underline fields', 'cotlas-simple-forms' ) . '</option>';
        echo '</select>';
        echo '</p>';
    }

    public static function save_form_meta( $post_id, $post ) {
        if ( $post->post_type !== 'csf_form' ) {
            return;
        }
        if ( ! isset( $_POST['csf_form_settings_nonce'] ) || ! wp_verify_nonce( $_POST['csf_form_settings_nonce'], 'csf_form_settings_save' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( isset( $_POST['csf_form_class'] ) ) {
            $class = sanitize_text_field( $_POST['csf_form_class'] );
            update_post_meta( $post_id, 'csf_form_class', $class );
        }

        $form_type = isset( $_POST['csf_form_type'] ) ? sanitize_text_field( $_POST['csf_form_type'] ) : 'normal';
        update_post_meta( $post_id, 'csf_form_type', $form_type );

        $label_mode = isset( $_POST['csf_form_label_mode'] ) ? sanitize_text_field( $_POST['csf_form_label_mode'] ) : '';
        update_post_meta( $post_id, 'csf_form_label_mode', $label_mode );

        $hide_help_when_label = isset( $_POST['csf_form_hide_help_when_label'] ) ? '1' : '';
        update_post_meta( $post_id, 'csf_form_hide_help_when_label', $hide_help_when_label );

        $hide_help_all = isset( $_POST['csf_form_hide_help_all'] ) ? '1' : '';
        update_post_meta( $post_id, 'csf_form_hide_help_all', $hide_help_all );

        $keep_session_data = isset( $_POST['csf_form_keep_session_data'] ) ? '1' : '';
        update_post_meta( $post_id, 'csf_form_keep_session_data', $keep_session_data );

        $show_page_heading = isset( $_POST['csf_form_show_page_heading'] ) ? '1' : '';
        update_post_meta( $post_id, 'csf_form_show_page_heading', $show_page_heading );

        $show_pagination = isset( $_POST['csf_form_show_pagination'] ) ? '1' : '';
        update_post_meta( $post_id, 'csf_form_show_pagination', $show_pagination );

        $conversational = isset( $_POST['csf_form_conversational'] ) ? '1' : '';
        update_post_meta( $post_id, 'csf_form_conversational', $conversational );

        $email_to = isset( $_POST['csf_form_email_to'] ) ? sanitize_email( $_POST['csf_form_email_to'] ) : '';
        update_post_meta( $post_id, 'csf_form_email_to', $email_to );

        $email_from_name = isset( $_POST['csf_form_email_from_name'] ) ? sanitize_text_field( $_POST['csf_form_email_from_name'] ) : '';
        update_post_meta( $post_id, 'csf_form_email_from_name', $email_from_name );

        $email_from_email = isset( $_POST['csf_form_email_from_email'] ) ? sanitize_email( $_POST['csf_form_email_from_email'] ) : '';
        update_post_meta( $post_id, 'csf_form_email_from_email', $email_from_email );

        $email_subject = isset( $_POST['csf_form_email_subject'] ) ? sanitize_text_field( $_POST['csf_form_email_subject'] ) : '';
        update_post_meta( $post_id, 'csf_form_email_subject', $email_subject );

        $login_redirect = isset( $_POST['csf_form_login_redirect'] ) ? absint( $_POST['csf_form_login_redirect'] ) : 0;
        update_post_meta( $post_id, 'csf_form_login_redirect', $login_redirect );

        $register_redirect = isset( $_POST['csf_form_register_redirect'] ) ? absint( $_POST['csf_form_register_redirect'] ) : 0;
        update_post_meta( $post_id, 'csf_form_register_redirect', $register_redirect );

        $redirect_url = isset( $_POST['csf_form_redirect_url'] ) ? esc_url_raw( $_POST['csf_form_redirect_url'] ) : '';
        update_post_meta( $post_id, 'csf_form_redirect_url', $redirect_url );

        $mail2_enable = isset( $_POST['csf_form_mail2_enable'] ) ? '1' : '';
        update_post_meta( $post_id, 'csf_form_mail2_enable', $mail2_enable );

        $mail2_email_field = isset( $_POST['csf_form_mail2_email_field'] ) ? sanitize_text_field( $_POST['csf_form_mail2_email_field'] ) : '';
        update_post_meta( $post_id, 'csf_form_mail2_email_field', $mail2_email_field );

        $mail2_subject = isset( $_POST['csf_form_mail2_subject'] ) ? sanitize_text_field( $_POST['csf_form_mail2_subject'] ) : '';
        update_post_meta( $post_id, 'csf_form_mail2_subject', $mail2_subject );

        $turnstile_enable = isset( $_POST['csf_form_turnstile_enable'] ) ? '1' : '';
        update_post_meta( $post_id, 'csf_form_turnstile_enable', $turnstile_enable );

        $template = isset( $_POST['csf_form_template'] ) ? sanitize_text_field( $_POST['csf_form_template'] ) : '';
        update_post_meta( $post_id, 'csf_form_template', $template );

        $post_type_setting = isset( $_POST['csf_form_post_type'] ) ? sanitize_text_field( $_POST['csf_form_post_type'] ) : 'post';
        update_post_meta( $post_id, 'csf_form_post_type', $post_type_setting );

        $post_status = isset( $_POST['csf_form_post_status'] ) ? sanitize_text_field( $_POST['csf_form_post_status'] ) : 'pending';
        update_post_meta( $post_id, 'csf_form_post_status', $post_status );

        $guest_submit = isset( $_POST['csf_form_guest_submit'] ) ? '1' : '';
        update_post_meta( $post_id, 'csf_form_guest_submit', $guest_submit );

        $default_category = isset( $_POST['csf_form_default_category'] ) ? absint( $_POST['csf_form_default_category'] ) : 0;
        update_post_meta( $post_id, 'csf_form_default_category', $default_category );

        $thankyou_heading = isset( $_POST['csf_form_thankyou_heading'] ) ? sanitize_text_field( $_POST['csf_form_thankyou_heading'] ) : '';
        update_post_meta( $post_id, 'csf_form_thankyou_heading', $thankyou_heading );

        $thankyou_message = isset( $_POST['csf_form_thankyou_message'] ) ? sanitize_textarea_field( $_POST['csf_form_thankyou_message'] ) : '';
        update_post_meta( $post_id, 'csf_form_thankyou_message', $thankyou_message );

        $thankyou_referrer = isset( $_POST['csf_form_thankyou_referrer'] ) ? '1' : '';
        update_post_meta( $post_id, 'csf_form_thankyou_referrer', $thankyou_referrer );
    }
}
