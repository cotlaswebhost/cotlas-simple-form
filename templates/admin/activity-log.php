<?php
/**
 * Activity Log Admin Template.
 *
 * @package CotlasSimpleForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap csf-admin-activity-log">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Activity Log', 'cotlas-simple-forms' ); ?></h1>
    <hr class="wp-header-end">

    <p><?php esc_html_e( 'Recent events within the Cotlas Simple Forms plugin.', 'cotlas-simple-forms' ); ?></p>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-date"><?php esc_html_e( 'Date', 'cotlas-simple-forms' ); ?></th>
                <th scope="col" class="manage-column column-type"><?php esc_html_e( 'Event Type', 'cotlas-simple-forms' ); ?></th>
                <th scope="col" class="manage-column column-message"><?php esc_html_e( 'Message', 'cotlas-simple-forms' ); ?></th>
                <th scope="col" class="manage-column column-ip"><?php esc_html_e( 'IP Address', 'cotlas-simple-forms' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $logs ) ) : ?>
                <?php foreach ( $logs as $log ) : ?>
                    <tr>
                        <td><?php echo esc_html( get_date_from_gmt( $log->created_at, get_option('date_format') . ' ' . get_option('time_format') ) ); ?></td>
                        <td>
                            <?php 
                            $type_label = ucwords( str_replace( '_', ' ', $log->event_type ) );
                            echo '<strong>' . esc_html( $type_label ) . '</strong>';
                            ?>
                        </td>
                        <td>
                            <?php echo esc_html( $log->message ); ?>
                            <?php
                            $context = json_decode( $log->context, true );
                            if ( ! empty( $context ) ) {
                                $details = array();
                                foreach ( $context as $k => $v ) {
                                    if ( ! empty( $v ) ) {
                                        $details[] = esc_html( ucfirst( $k ) . ': ' . $v );
                                    }
                                }
                                if ( ! empty( $details ) ) {
                                    echo '<br><span style="color: #666; font-size: 12px;">' . implode( ' | ', $details ) . '</span>';
                                }
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html( $log->ip_address ? $log->ip_address : '-' ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4"><?php esc_html_e( 'No activity found.', 'cotlas-simple-forms' ); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
