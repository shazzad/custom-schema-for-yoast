<?php
/**
 * Yoast SEO integration.
 *
 * @package Shazzad\CustomSchemaForYoast
 */

namespace Shazzad\CustomSchemaForYoast\Yoast;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Yoast filters for one request.
 */
final class Hooks {

	/**
	 * Yoast is active and exposes the schema-piece base class we extend.
	 *
	 * @return bool
	 */
	public static function available(): bool {
		return defined( 'WPSEO_VERSION' ) && class_exists( '\Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece' );
	}
}
