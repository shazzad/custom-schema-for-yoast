<?php
/**
 * Plugin bootstrap.
 *
 * @package Shazzad\CustomSchemaForYoast
 */

namespace Shazzad\CustomSchemaForYoast;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin's components together.
 */
final class Plugin {

	/**
	 * Runs on plugins_loaded.
	 *
	 * @return void
	 */
	public static function boot(): void {
		Meta::register();

		if ( is_admin() ) {
			( new Admin\MetaBox() )->register();
		}

		( new Frontend() )->register();
	}
}
