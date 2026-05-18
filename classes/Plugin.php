<?php
/**
 * Main upgraded plugin bootstrap.
 *
 * @package CotlasSimpleForms
 */

namespace Cotlas\SimpleForms;

use Cotlas\SimpleForms\Admin\DashboardPage;
use Cotlas\SimpleForms\Admin\EmailTemplatesPage;
use Cotlas\SimpleForms\Admin\ActivityLogPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads additive modules while legacy CSF_* classes remain active.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot Phase 1 services.
	 *
	 * @return void
	 */
	public function boot() {
		if ( get_option( 'csf_plugin_version' ) !== CSF_PLUGIN_VERSION ) {
			self::activate();
		}

		$activity_logger = new ActivityLogger();
		$activity_logger->hooks();

		$assets = new Assets();
		$assets->hooks();

		$dashboard = new DashboardPage(
			new FormRepository(),
			new SubmissionRepository(),
			$activity_logger
		);
		$dashboard->hooks();

		$email_templates = new EmailTemplatesPage();
		$email_templates->hooks();

		$activity_log_page = new ActivityLogPage( $activity_logger );
		$activity_log_page->hooks();

		/**
		 * Fires after the upgraded Cotlas Simple Forms bootstrap is loaded.
		 *
		 * This gives future modules a stable extension point without changing
		 * legacy classes.
		 */
		do_action( 'csf_plugin_booted', $this );
	}

	/**
	 * Install additive database structures.
	 *
	 * @return void
	 */
	public static function activate() {
		ActivityLogger::install();
		update_option( 'csf_plugin_version', CSF_PLUGIN_VERSION, false );
	}

	/**
	 * Prevent direct construction.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @return void
	 */
	public function __wakeup() {}
}
