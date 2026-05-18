<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CSF_Admin_List {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_submissions_page' ) );
    }

    public function add_submissions_page() {
        add_submenu_page(
            'edit.php?post_type=csf_form',
            __( 'Submissions', 'cotlas-simple-forms' ),
            __( 'Submissions', 'cotlas-simple-forms' ),
            'manage_options',
            'csf-submissions',
            array( $this, 'render_list_page' )
        );
        // Hidden View Page
        add_submenu_page(
            null,
            __( 'View Submission', 'cotlas-simple-forms' ),
            __( 'View Submission', 'cotlas-simple-forms' ),
            'manage_options',
            'csf-view-submission',
            array( $this, 'render_view_page' )
        );
    }

    public function render_list_page() {
        // Handle Export CSV/Excel
        if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'export_csv', 'export_excel' ) ) ) {
            $this->handle_export( $_GET['action'] );
            exit;
        }

        // Handle Delete Action
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['post'] ) ) {
            check_admin_referer( 'csf_delete_submission_' . $_GET['post'] );
            wp_delete_post( intval( $_GET['post'] ), true );
            echo '<div class="notice notice-success"><p>' . __( 'Submission deleted.', 'cotlas-simple-forms' ) . '</p></div>';
        }

        // Handle Resend Email Action
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'resend_email' && isset( $_GET['post'] ) ) {
            check_admin_referer( 'csf_resend_email_' . $_GET['post'] );
            $this->resend_email( intval( $_GET['post'] ) );
            echo '<div class="notice notice-success"><p>' . __( 'Email resent successfully.', 'cotlas-simple-forms' ) . '</p></div>';
        }
        
        // Handle Bulk Delete
        if ( isset( $_POST['action'] ) && $_POST['action'] === 'bulk_delete' && ! empty( $_POST['submission_ids'] ) ) {
             check_admin_referer( 'csf_bulk_delete' );
             foreach ( $_POST['submission_ids'] as $id ) {
                 wp_delete_post( intval( $id ), true );
             }
             echo '<div class="notice notice-success"><p>' . __( 'Submissions deleted.', 'cotlas-simple-forms' ) . '</p></div>';
        }

        $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $args = array(
            'post_type'      => 'csf_submission',
            'posts_per_page' => 20,
            'paged'          => $paged,
            'post_status'    => 'publish',
        );

        if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
            $args['s'] = sanitize_text_field( $_GET['s'] );
        }

        if ( isset( $_GET['filter_form_id'] ) && ! empty( $_GET['filter_form_id'] ) ) {
            $args['meta_query'] = array(
                array(
                    'key'   => 'csf_form_id',
                    'value' => intval( $_GET['filter_form_id'] ),
                )
            );
        }

        $query = new WP_Query( $args );

        // Get all forms for filter dropdown
        $forms = get_posts( array(
            'post_type' => 'csf_form',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ) );

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Form Submissions', 'cotlas-simple-forms' ); ?></h1>
            <hr class="wp-header-end">
            
            <form method="get">
                <input type="hidden" name="post_type" value="csf_form">
                <input type="hidden" name="page" value="csf-submissions">
                <p class="search-box">
                    <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Submissions:', 'cotlas-simple-forms' ); ?></label>
                    <input type="search" id="post-search-input" name="s" value="<?php echo isset( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : ''; ?>">
                    <input type="submit" id="search-submit" class="button" value="<?php _e( 'Search', 'cotlas-simple-forms' ); ?>">
                </p>
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <select name="filter_form_id">
                            <option value=""><?php _e( 'All Forms', 'cotlas-simple-forms' ); ?></option>
                            <?php foreach ( $forms as $form ) : ?>
                                <option value="<?php echo esc_attr( $form->ID ); ?>" <?php selected( isset( $_GET['filter_form_id'] ) ? $_GET['filter_form_id'] : '', $form->ID ); ?>><?php echo esc_html( $form->post_title ); ?> (ID: <?php echo esc_html( $form->ID ); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <input type="submit" class="button" value="<?php _e( 'Filter', 'cotlas-simple-forms' ); ?>">
                        
                        <a href="<?php echo esc_url( add_query_arg( 'action', 'export_csv' ) ); ?>" class="button"><?php _e( 'Export CSV', 'cotlas-simple-forms' ); ?></a>
                        <a href="<?php echo esc_url( add_query_arg( 'action', 'export_excel' ) ); ?>" class="button"><?php _e( 'Export Excel', 'cotlas-simple-forms' ); ?></a>
                    </div>
                </div>
            </form>
            
            <form method="post">
                <?php wp_nonce_field( 'csf_bulk_delete' ); ?>
                <div class="tablenav top" style="clear:none; margin-top:0;">
                    <div class="alignleft actions bulkactions">
                        <select name="action">
                            <option value="-1"><?php _e( 'Bulk Actions', 'cotlas-simple-forms' ); ?></option>
                            <option value="bulk_delete"><?php _e( 'Delete Permanently', 'cotlas-simple-forms' ); ?></option>
                        </select>
                        <input type="submit" class="button action" value="<?php _e( 'Apply', 'cotlas-simple-forms' ); ?>">
                    </div>
                </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column"><input type="checkbox"></td>
                        <th scope="col" class="manage-column column-date"><?php _e( 'Date', 'cotlas-simple-forms' ); ?></th>
                        <th scope="col" class="manage-column column-form"><?php _e( 'Form ID', 'cotlas-simple-forms' ); ?></th>
                        <th scope="col" class="manage-column column-page"><?php _e( 'Page', 'cotlas-simple-forms' ); ?></th>
                        <th scope="col" class="manage-column column-status"><?php _e( 'Status', 'cotlas-simple-forms' ); ?></th>
                        <th scope="col" class="manage-column column-data"><?php _e( 'Data', 'cotlas-simple-forms' ); ?></th>
                        <th scope="col" class="manage-column column-actions"><?php _e( 'Actions', 'cotlas-simple-forms' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $query->have_posts() ) : ?>
                        <?php while ( $query->have_posts() ) : $query->the_post(); 
                            $post_id = get_the_ID();
                            $form_id = get_post_meta( $post_id, 'csf_form_id', true );
                            $page_url = get_post_meta( $post_id, 'csf_page_url', true );
                            $delete_url = wp_nonce_url( admin_url( 'edit.php?post_type=csf_form&page=csf-submissions&action=delete&post=' . $post_id ), 'csf_delete_submission_' . $post_id );
                            $resend_url = wp_nonce_url( admin_url( 'edit.php?post_type=csf_form&page=csf-submissions&action=resend_email&post=' . $post_id ), 'csf_resend_email_' . $post_id );
                            $view_url = admin_url( 'edit.php?post_type=csf_form&page=csf-view-submission&id=' . $post_id );
                        ?>
                            <tr>
                                <th scope="row" class="check-column"><input type="checkbox" name="submission_ids[]" value="<?php echo $post_id; ?>"></th>
                                <td><?php echo get_the_date( 'Y-m-d H:i' ); ?></td>
                                <td>
                                    <?php if ( $form_id ) : ?>
                                        <a href="<?php echo get_edit_post_link( $form_id ); ?>">ID: <?php echo esc_html( $form_id ); ?></a>
                                    <?php else : ?>
                                        <?php _e( '(Deleted Form)', 'cotlas-simple-forms' ); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( $page_url ) : ?>
                                        <a href="<?php echo esc_url( $page_url ); ?>" target="_blank"><?php _e( 'View Page', 'cotlas-simple-forms' ); ?></a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="csf-status-label" style="background:#e5f5fa; color:#005a9e; padding:3px 8px; border-radius:3px; font-size:12px; font-weight:600;"><?php _e('Received', 'cotlas-simple-forms'); ?></span>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url( $view_url ); ?>" class="button button-secondary"><?php _e( 'View Data', 'cotlas-simple-forms' ); ?></a>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url( $resend_url ); ?>" class="button button-small"><?php _e( 'Resend Email', 'cotlas-simple-forms' ); ?></a>
                                    <a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small delete" onclick="return confirm('<?php _e( 'Are you sure you want to delete this submission?', 'cotlas-simple-forms' ); ?>')"><?php _e( 'Delete', 'cotlas-simple-forms' ); ?></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr><td colspan="7"><?php _e( 'No submissions found.', 'cotlas-simple-forms' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </form>
            
            <?php
            // Pagination
            $big = 999999999;
            echo '<div class="tablenav bottom"><div class="tablenav-pages">';
            echo paginate_links( array(
                'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                'format'  => '&paged=%#%',
                'current' => $paged,
                'total'   => $query->max_num_pages,
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;'
            ) );
            echo '</div></div>';
            ?>
        </div>
        <?php
        wp_reset_postdata();
    }

    private function handle_export( $type ) {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $args = array(
            'post_type'      => 'csf_submission',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        );

        if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
            $args['s'] = sanitize_text_field( $_GET['s'] );
        }

        if ( isset( $_GET['filter_form_id'] ) && ! empty( $_GET['filter_form_id'] ) ) {
            $args['meta_query'] = array(
                array(
                    'key'   => 'csf_form_id',
                    'value' => intval( $_GET['filter_form_id'] ),
                )
            );
        }

        $query = new WP_Query( $args );

        if ( $type === 'export_excel' ) {
            header( 'Content-Type: application/vnd.ms-excel; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=submissions-' . date('Y-m-d') . '.csv' ); // Using CSV format with excel mime for simple compatibility
            // To be robust Excel, use UTF-16LE or UTF-8 BOM. We will use UTF-8 BOM.
            echo "\xEF\xBB\xBF";
        } else {
            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=submissions-' . date('Y-m-d') . '.csv' );
        }

        $output = fopen( 'php://output', 'w' );
        
        // Find all possible keys for headers
        $all_keys = array( 'ID', 'Date', 'Form ID', 'Page URL' );
        $all_data = array();
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();
                $form_id = get_post_meta( $post_id, 'csf_form_id', true );
                $page_url = get_post_meta( $post_id, 'csf_page_url', true );
                $data = get_post_meta( $post_id, 'csf_data', true );
                
                $row = array(
                    'ID' => $post_id,
                    'Date' => get_the_date( 'Y-m-d H:i:s' ),
                    'Form ID' => $form_id,
                    'Page URL' => $page_url,
                );
                
                if ( is_array( $data ) ) {
                    foreach ( $data as $k => $v ) {
                        if ( in_array( $k, array( 'form_id', 'action', 'page_url', 'page_title', 'nonce' ) ) ) continue;
                        $label = str_replace( 'csf_', '', $k );
                        $label = ucwords( str_replace( '_', ' ', $label ) );
                        if ( ! in_array( $label, $all_keys ) ) {
                            $all_keys[] = $label;
                        }
                        $row[ $label ] = is_array( $v ) ? implode( ', ', $v ) : $v;
                    }
                }
                $all_data[] = $row;
            }
        }
        
        fputcsv( $output, $all_keys );
        
        foreach ( $all_data as $row_data ) {
            $row = array();
            foreach ( $all_keys as $key ) {
                $row[] = isset( $row_data[ $key ] ) ? $row_data[ $key ] : '';
            }
            fputcsv( $output, $row );
        }
        
        fclose( $output );
        exit;
    }

    private function resend_email( $post_id ) {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $data = get_post_meta( $post_id, 'csf_data', true );
        $form_id = get_post_meta( $post_id, 'csf_form_id', true );
        $files = get_post_meta( $post_id, 'csf_files', true );
        
        if ( ! $files ) $files = array();
        if ( ! $data ) $data = array();
        
        // Ensure necessary classes are available or instantiate submission handler
        $submission = new CSF_Submission();
        // Since send_email is private in CSF_Submission, we need to make it public or use reflection.
        // As we just refactored CSF_Submission, let's use reflection to call it, or we could just make it public.
        // It's better to modify CSF_Submission's send_email to be public.
        if ( method_exists( $submission, 'send_email' ) ) {
            $reflection = new ReflectionMethod( 'CSF_Submission', 'send_email' );
            $reflection->setAccessible( true );
            $reflection->invoke( $submission, $form_id, $data, $files );
        }
    }
    
    public function render_view_page() {
        if ( ! isset( $_GET['id'] ) ) return;
        $post_id = intval( $_GET['id'] );
        $data = get_post_meta( $post_id, 'csf_data', true );
        $form_id = get_post_meta( $post_id, 'csf_form_id', true );
        
        ?>
        <div class="wrap">
            <h1><?php _e( 'Submission Details', 'cotlas-simple-forms' ); ?></h1>
            <div class="card" style="max-width: 600px; padding: 20px;">
                <h2>Submission #<?php echo $post_id; ?></h2>
                <p><strong>Date:</strong> <?php echo get_the_date( 'Y-m-d H:i', $post_id ); ?></p>
                <?php
                $page_url = get_post_meta( $post_id, 'csf_page_url', true );
                if ( $page_url ) {
                     // Try to find the page title from URL if possible, or just show URL
                     // We can store page title in meta during submission too, but currently only url is saved.
                     // Wait, in csf_data we might have it? No.
                     // Let's just show URL for now or if we saved title.
                     // Ah, the user asked to save page title too.
                     $page_title = get_post_meta( $post_id, 'csf_page_title', true );
                     if ( ! $page_title ) $page_title = 'View Page';
                     
                     echo '<p><strong>Submitted on Page:</strong> <a href="' . esc_url( $page_url ) . '" target="_blank">' . esc_html( $page_title ) . '</a></p>';
                }
                ?>
                <hr>
                <?php 
                if ( ! empty( $data ) && is_array( $data ) ) {
                    echo '<div style="background: #fff; border: 1px solid #e5e5e5; border-radius: 4px; overflow: hidden;">';
                    echo '<table class="widefat striped" style="border:none; box-shadow:none;">';
                    foreach ( $data as $k => $v ) {
                        // Skip internal fields
                        if ( in_array( $k, array( 'form_id', 'action', 'page_url', 'page_title', 'nonce' ) ) ) continue;
                        // Strip csf_ prefix for display
                        $label = str_replace( 'csf_', '', $k );
                        $label = ucwords( str_replace( '_', ' ', $label ) );
                        echo '<tr>
                                <td style="width: 30%; padding: 15px; font-weight: 600; color: #555; border-bottom: 1px solid #eee;">' . esc_html( $label ) . '</td>
                                <td style="padding: 15px; color: #333; font-size: 14px; line-height: 1.5; border-bottom: 1px solid #eee;">' . nl2br( esc_html( $v ) ) . '</td>
                              </tr>';
                    }
                    echo '</table>';
                    echo '</div>';
                }
                ?>
                <p style="margin-top: 20px;"><a href="<?php echo admin_url( 'edit.php?post_type=csf_form&page=csf-submissions' ); ?>" class="button">&laquo; Back to List</a></p>
            </div>
        </div>
        <?php
    }
}
