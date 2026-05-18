<?php
/**
 * Lightweight class autoloader for the upgraded Cotlas Simple Forms modules.
 *
 * @package CotlasSimpleForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'Cotlas\\SimpleForms\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative_class = substr( $class_name, strlen( $prefix ) );
		$relative_path  = str_replace( '\\', '/', $relative_class ) . '.php';

		$paths = array(
			CSF_PLUGIN_DIR . 'classes/' . $relative_path,
			CSF_PLUGIN_DIR . 'admin/' . $relative_path,
			CSF_PLUGIN_DIR . 'modules/' . $relative_path,
			CSF_PLUGIN_DIR . 'forms/' . $relative_path,
		);

		if ( 0 === strpos( $relative_path, 'Admin/' ) ) {
			$paths[] = CSF_PLUGIN_DIR . 'admin/' . substr( $relative_path, strlen( 'Admin/' ) );
		}

		if ( 0 === strpos( $relative_path, 'Modules/' ) ) {
			$paths[] = CSF_PLUGIN_DIR . 'modules/' . substr( $relative_path, strlen( 'Modules/' ) );
		}

		if ( 0 === strpos( $relative_path, 'Forms/' ) ) {
			$paths[] = CSF_PLUGIN_DIR . 'forms/' . substr( $relative_path, strlen( 'Forms/' ) );
		}

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
);
