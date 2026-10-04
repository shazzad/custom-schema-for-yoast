<?php
/**
 * Our nodes as a Yoast graph piece.
 *
 * @package Shazzad\CustomSchemaForYoast
 */

namespace Shazzad\CustomSchemaForYoast\Yoast;

use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

defined( 'ABSPATH' ) || exit;

/**
 * Yoast calls is_needed() then generate(); a list of nodes (numeric keys, no "@type" at the top
 * level) is iterated and each node is added to @graph.
 */
final class GraphPiece extends Abstract_Schema_Piece {

	/**
	 * Used by Yoast for the wpseo_schema_needs_<identifier> / wpseo_schema_<identifier> filters.
	 *
	 * @var string
	 */
	public $identifier = 'csfy_custom';

	/**
	 * Nodes with @id already assigned.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $nodes;

	/**
	 * Constructor.
	 *
	 * @param array<int, array<string, mixed>> $nodes Nodes.
	 */
	public function __construct( array $nodes ) {
		$this->nodes = array_values( $nodes );
	}

	/**
	 * Only constructed when there is something to output.
	 *
	 * @return bool
	 */
	public function is_needed() {
		return [] !== $this->nodes;
	}

	/**
	 * The nodes.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function generate() {
		return $this->nodes;
	}
}
