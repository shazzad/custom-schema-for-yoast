<?php
/**
 * Encodes and prints JSON-LD.
 *
 * @package Shazzad\CustomSchemaForYoast
 */

namespace Shazzad\CustomSchemaForYoast;

defined( 'ABSPATH' ) || exit;

/**
 * The only place JSON leaves the plugin. Always re-encoded, never echoed raw.
 */
final class Output {

	/**
	 * Encode with slashes escaped (so "</script>" cannot close the tag) and unicode kept readable.
	 *
	 * @param array<mixed> $document Document.
	 * @return string
	 */
	public static function encode( array $document ): string {
		$json = wp_json_encode( $document, JSON_UNESCAPED_UNICODE );
		return false === $json ? '{}' : $json;
	}

	/**
	 * The script tag.
	 *
	 * @param array<mixed> $document Document.
	 * @return string
	 */
	public static function render( array $document ): string {
		return '<script type="application/ld+json" class="csfy-schema">' . self::encode( $document ) . "</script>\n";
	}
}
