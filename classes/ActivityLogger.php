<?php
/**
 * Activity log storage.
 *
 * @package CotlasSimpleForms
 */

namespace Cotlas\SimpleForms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores lightweight plugin activity entries.
 */
class ActivityLogger {

	const TABLE_SLUG = 'csf_activity_log';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'wp_mail_succeeded', array( $this, 'log_mail_success' ) );
		add_action( 'save_post_csf_form', array( $this, 'log_form_saved' ), 20, 3 );
		add_action( 'save_post_csf_submission', array( $this, 'log_submission_saved' ), 20, 3 );
	}

	/**
	 * Create the activity table.
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_type varchar(80) NOT NULL DEFAULT '',
			object_type varchar(80) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			message text NOT NULL,
			context longtext NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			ip_address varchar(100) NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY event_type (event_type),
			KEY object_type (object_type),
			KEY object_id (object_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Get full activity table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_SLUG;
	}

	/**
	 * Add an activity entry.
	 *
	 * @param string $event_type Event type.
	 * @param string $message    Human readable message.
	 * @param array  $args       Optional object/context args.
	 * @return int|false
	 */
	public function log( $event_type, $message, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'object_type' => '',
			'object_id'   => 0,
			'context'     => array(),
			'user_id'     => get_current_user_id(),
			'ip_address'  => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		);
		$args     = wp_parse_args( $args, $defaults );

		$inserted = $wpdb->insert(
			self::table_name(),
			array(
				'event_type'  => sanitize_key( $event_type ),
				'object_type' => sanitize_key( $args['object_type'] ),
				'object_id'   => absint( $args['object_id'] ),
				'message'     => sanitize_text_field( $message ),
				'context'     => wp_json_encode( $args['context'] ),
				'user_id'     => absint( $args['user_id'] ),
				'ip_address'  => sanitize_text_field( $args['ip_address'] ),
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Log successful mail and maintain an aggregate count.
	 *
	 * @param array $mail_data Mail data from WordPress.
	 * @return void
	 */
	public function log_mail_success( $mail_data ) {
		$count = (int) get_option( 'csf_email_sent_count', 0 );
		update_option( 'csf_email_sent_count', $count + 1, false );

		$to = isset( $mail_data['to'] ) ? $mail_data['to'] : '';
		if ( is_array( $to ) ) {
			$to = implode( ', ', array_map( 'sanitize_email', $to ) );
		}

		$this->log(
			'email_sent',
			__( 'Email sent', 'cotlas-simple-forms' ),
			array(
				'object_type' => 'email',
				'context'     => array(
					'to'      => sanitize_text_field( $to ),
					'subject' => isset( $mail_data['subject'] ) ? sanitize_text_field( $mail_data['subject'] ) : '',
				),
			)
		);
	}

	/**
	 * Log form creation and edits.
	 *
	 * @param int      $post_id Form ID.
	 * @param \WP_Post $post    Form post object.
	 * @param bool     $update  Whether this is an existing post update.
	 * @return void
	 */
	public function log_form_saved( $post_id, $post, $update ) {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$this->log(
			$update ? 'form_edited' : 'form_created',
			$update ? __( 'Form edited', 'cotlas-simple-forms' ) : __( 'Form created', 'cotlas-simple-forms' ),
			array(
				'object_type' => 'form',
				'object_id'   => $post_id,
				'context'     => array(
					'title' => $post instanceof \WP_Post ? $post->post_title : '',
				),
			)
		);
	}

	/**
	 * Log stored submissions.
	 *
	 * @param int      $post_id Submission ID.
	 * @param \WP_Post $post    Submission post object.
	 * @param bool     $update  Whether this is an existing post update.
	 * @return void
	 */
	public function log_submission_saved( $post_id, $post, $update ) {
		if ( $update || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$this->log(
			'submission_received',
			__( 'Submission received', 'cotlas-simple-forms' ),
			array(
				'object_type' => 'submission',
				'object_id'   => $post_id,
				'context'     => array(
					'title' => $post instanceof \WP_Post ? $post->post_title : '',
				),
			)
		);
	}

	/**
	 * Get latest activity entries.
	 *
	 * @param int $limit Number of rows.
	 * @return array
	 */
	public function latest( $limit = 8 ) {
		global $wpdb;

		$limit = max( 1, min( 50, absint( $limit ) ) );

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table_name() . ' ORDER BY created_at DESC, id DESC LIMIT %d',
				$limit
			)
		);
	}
}
