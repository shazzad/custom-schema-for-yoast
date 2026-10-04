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
	 * Nodes with @id assigned.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $nodes;

	/**
	 * Whether WebPage.mainEntity should point at nodes[0].
	 *
	 * @var bool
	 */
	private $set_main_entity;

	/**
	 * Constructor.
	 *
	 * @param array<int, array<string, mixed>> $nodes           Nodes.
	 * @param bool                             $set_main_entity Main-entity flag.
	 */
	public function __construct( array $nodes, bool $set_main_entity ) {
		$this->nodes           = array_values( $nodes );
		$this->set_main_entity = $set_main_entity;
	}

	/**
	 * Yoast is active and exposes the schema-piece base class we extend.
	 *
	 * @return bool
	 */
	public static function available(): bool {
		return defined( 'WPSEO_VERSION' ) && class_exists( '\Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece' );
	}

	/**
	 * Yoast is available and its own schema output is switched on (Settings > Site features).
	 * When it is off, Yoast suppresses the whole graph, so Append has nothing to join.
	 *
	 * @return bool
	 */
	public static function prints_schema(): bool {
		return self::available() && ( ! class_exists( '\WPSEO_Options' ) || false !== \WPSEO_Options::get( 'enable_schema', true ) );
	}

	/**
	 * Append mode.
	 *
	 * @return void
	 */
	public function register_append(): void {
		add_filter( 'wpseo_schema_graph_pieces', [ $this, 'add_piece' ], 11, 2 );
		if ( $this->set_main_entity && isset( $this->nodes[0]['@id'] ) ) {
			add_filter( 'wpseo_schema_webpage', [ $this, 'set_main_entity' ], 11, 1 );
		}
	}

	/**
	 * Replace mode: switch Yoast's JSON-LD off for this request.
	 *
	 * @return void
	 */
	public function disable_yoast_output(): void {
		add_filter( 'wpseo_json_ld_output', '__return_false' );
	}

	/**
	 * wpseo_schema_graph_pieces callback.
	 *
	 * @param array $pieces  Yoast pieces.
	 * @param mixed $context Meta_Tags_Context (unused).
	 * @return array
	 */
	public function add_piece( $pieces, $context ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Yoast's signature.
		if ( ! is_array( $pieces ) ) {
			return $pieces;
		}
		$pieces[] = new GraphPiece( $this->nodes );
		return $pieces;
	}

	/**
	 * wpseo_schema_webpage callback. Overwrites any existing mainEntity — that is the checkbox's intent.
	 *
	 * @param array $webpage The WebPage piece.
	 * @return array
	 */
	public function set_main_entity( $webpage ) {
		if ( is_array( $webpage ) ) {
			$webpage['mainEntity'] = [ '@id' => $this->nodes[0]['@id'] ];
		}
		return $webpage;
	}
}
