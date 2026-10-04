<?php
/**
 * Post meta keys, registration and typed getters.
 *
 * @package Shazzad\CustomSchemaForYoast
 */

namespace Shazzad\CustomSchemaForYoast;

defined( 'ABSPATH' ) || exit;

/**
 * Single place that knows the meta keys and their defaults.
 */
final class Meta {

	const KEY_JSON = '_csfy_json';
	const KEY_MODE = '_csfy_mode';
	const KEY_MAIN = '_csfy_main_entity';

	const MODE_APPEND  = 'append';
	const MODE_REPLACE = 'replace';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', [ self::class, 'register_meta' ], 20 );
	}

	/**
	 * Public post types.
	 *
	 * @return string[]
	 */
	public static function post_types(): array {
		return array_values( get_post_types( [ 'public' => true ], 'names' ) );
	}

	/**
	 * Register post meta for each public post type. Not exposed in REST.
	 *
	 * @return void
	 */
	public static function register_meta(): void {
		$auth = static function (): bool {
			return current_user_can( 'manage_options' );
		};

		foreach ( self::post_types() as $post_type ) {
			register_post_meta(
				$post_type,
				self::KEY_JSON,
				[
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => false,
					'sanitize_callback' => static function ( $value ): string {
						return is_string( $value ) ? $value : '';
					},
					'auth_callback'     => $auth,
				]
			);
			register_post_meta(
				$post_type,
				self::KEY_MODE,
				[
					'type'              => 'string',
					'single'            => true,
					'default'           => self::MODE_APPEND,
					'show_in_rest'      => false,
					'sanitize_callback' => [ self::class, 'sanitize_mode' ],
					'auth_callback'     => $auth,
				]
			);
			register_post_meta(
				$post_type,
				self::KEY_MAIN,
				[
					'type'              => 'string',
					'single'            => true,
					'default'           => '1',
					'show_in_rest'      => false,
					'sanitize_callback' => static function ( $value ): string {
						return '1' === (string) $value ? '1' : '0';
					},
					'auth_callback'     => $auth,
				]
			);
		}
	}

	/**
	 * Whitelist the mode.
	 *
	 * @param mixed $value Raw.
	 * @return string
	 */
	public static function sanitize_mode( $value ): string {
		return self::MODE_REPLACE === $value ? self::MODE_REPLACE : self::MODE_APPEND;
	}

	/**
	 * Raw pasted JSON.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function json( int $post_id ): string {
		$value = get_post_meta( $post_id, self::KEY_JSON, true );
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Output mode.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function mode( int $post_id ): string {
		return self::sanitize_mode( get_post_meta( $post_id, self::KEY_MODE, true ) );
	}

	/**
	 * Whether to set the WebPage mainEntity (append mode only).
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function main_entity( int $post_id ): bool {
		$value = get_post_meta( $post_id, self::KEY_MAIN, true );
		return '' === $value || '1' === $value; // Default on.
	}
}
