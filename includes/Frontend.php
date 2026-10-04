<?php
/**
 * Decides, per request, how the pasted JSON reaches the page.
 *
 * @package Shazzad\CustomSchemaForYoast
 */

namespace Shazzad\CustomSchemaForYoast;

use Shazzad\CustomSchemaForYoast\Yoast\Hooks as YoastHooks;

defined( 'ABSPATH' ) || exit;

/**
 * Singular views only. Append → Yoast graph piece; Replace or no Yoast → standalone script.
 */
final class Frontend {

	/**
	 * Hook up. `wp` fires after the main query, before wp_head, so is_singular() is reliable and
	 * Yoast's generator (which runs at wp_head) sees our filters.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp', [ $this, 'setup' ] );
	}

	/**
	 * Inspect the queried post and register the right output path.
	 *
	 * @return void
	 */
	public function setup(): void {
		if ( is_admin() || ! is_singular() ) {
			return;
		}

		$post_id = (int) get_queried_object_id();
		if ( $post_id <= 0 ) {
			return;
		}

		$result = Validator::parse( Meta::json( $post_id ) );
		if ( ! $result->ok || $result->is_empty() ) {
			return;
		}

		$permalink = (string) get_permalink( $post_id );
		$mode      = Meta::mode( $post_id );
		$yoast     = YoastHooks::available();

		if ( $yoast && Meta::MODE_APPEND === $mode ) {
			$nodes = Document::assign_ids( $result->nodes, $permalink );
			( new YoastHooks( $nodes, Meta::main_entity( $post_id ) ) )->register_append();
			return;
		}

		if ( $yoast ) {
			( new YoastHooks( [], false ) )->disable_yoast_output();
		}

		$document = Document::standalone( $result, $permalink );
		add_action(
			'wp_head',
			static function () use ( $document ): void {
				echo Output::render( $document ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON re-encoded by wp_json_encode inside a script tag.
			},
			1
		);
	}
}
