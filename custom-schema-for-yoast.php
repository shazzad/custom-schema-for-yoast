<?php
/**
 * Plugin Name: Custom Schema for Yoast
 * Plugin URI: https://github.com/shazzad/custom-schema-for-yoast
 * Description: Paste JSON-LD on any post or page and merge it into Yoast SEO's schema graph as the page's main entity, or replace Yoast's schema on that page.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Shazzad Hossain Khan
 * Author URI: https://w4dev.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-schema-for-yoast
 *
 * @package Shazzad\CustomSchemaForYoast
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'CSFY_VERSION' ) ) {
	return;
}

define( 'CSFY_VERSION', '1.0.0' );
define( 'CSFY_PLUGIN_FILE', __FILE__ );
define( 'CSFY_DIR', plugin_dir_path( __FILE__ ) );
define( 'CSFY_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( CSFY_DIR . 'vendor/autoload.php' ) ) {
	require_once CSFY_DIR . 'vendor/autoload.php';
} else {
	// Bare checkout (no composer install): load our own classes; the updater is simply absent.
	spl_autoload_register(
		static function ( $class_name ) {
			$prefix = 'Shazzad\\CustomSchemaForYoast\\';
			if ( 0 !== strpos( $class_name, $prefix ) ) {
				return;
			}
			$file = CSFY_DIR . 'includes/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	);
}

add_action( 'plugins_loaded', [ Shazzad\CustomSchemaForYoast\Plugin::class, 'boot' ] );

// Self-update from GitHub releases when the updater library is installed.
if ( class_exists( '\Shazzad\GithubPlugin\Updater' ) ) {
	new Shazzad\GithubPlugin\Updater(
		[
			'file'         => __FILE__,
			'owner'        => 'shazzad',
			'repo'         => 'custom-schema-for-yoast',
			'private_repo' => false,
			'owner_name'   => 'Shazzad',
		]
	);
}
