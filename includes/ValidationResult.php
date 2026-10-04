<?php
/**
 * Outcome of parsing the pasted JSON-LD.
 *
 * @package Shazzad\CustomSchemaForYoast
 */

namespace Shazzad\CustomSchemaForYoast;

defined( 'ABSPATH' ) || exit;

/**
 * Value object returned by Validator::parse().
 */
final class ValidationResult {

	/**
	 * Whether parsing succeeded. Empty input counts as success with no nodes.
	 *
	 * @var bool
	 */
	public $ok;

	/**
	 * Parser or shape error when not ok.
	 *
	 * @var string|null
	 */
	public $error;

	/**
	 * List of node arrays.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public $nodes;

	/**
	 * True when the input was a {"@context", "@graph"} document.
	 *
	 * @var bool
	 */
	public $had_graph_wrapper;

	/**
	 * The whole decoded input (assoc), or null on failure/empty.
	 *
	 * @var array<mixed>|null
	 */
	public $document;

	/**
	 * Constructor.
	 *
	 * @param bool        $ok                Success flag.
	 * @param string|null $error             Error message.
	 * @param array       $nodes             Nodes.
	 * @param bool        $had_graph_wrapper Wrapper flag.
	 * @param array|null  $document          Decoded document.
	 */
	public function __construct( bool $ok, ?string $error, array $nodes, bool $had_graph_wrapper, ?array $document ) {
		$this->ok                = $ok;
		$this->error             = $error;
		$this->nodes             = $nodes;
		$this->had_graph_wrapper = $had_graph_wrapper;
		$this->document          = $document;
	}

	/**
	 * Successful parse of nothing.
	 *
	 * @return bool
	 */
	public function is_empty(): bool {
		return $this->ok && [] === $this->nodes;
	}

	/**
	 * Failure factory.
	 *
	 * @param string $error Message.
	 * @return self
	 */
	public static function failure( string $error ): self {
		return new self( false, $error, [], false, null );
	}
}
