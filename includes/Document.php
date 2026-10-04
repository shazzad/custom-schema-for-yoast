<?php
/**
 * Builds the JSON-LD document that gets printed or merged.
 *
 * @package Shazzad\CustomSchemaForYoast
 */

namespace Shazzad\CustomSchemaForYoast;

defined( 'ABSPATH' ) || exit;

/**
 * Pure array transforms over validated nodes.
 */
final class Document {

	const CONTEXT = 'https://schema.org';

	/**
	 * Give every node a string "@id", deriving one from the page URL where missing or malformed.
	 *
	 * @param array<int, array<string, mixed>> $nodes    Nodes.
	 * @param string                           $base_url Page permalink.
	 * @return array<int, array<string, mixed>>
	 */
	public static function assign_ids( array $nodes, string $base_url ): array {
		$out = [];
		$i   = 0;
		foreach ( array_values( $nodes ) as $node ) {
			++$i;
			if ( ! isset( $node['@id'] ) || ! is_string( $node['@id'] ) || '' === $node['@id'] ) {
				$node['@id'] = $base_url . '#csfy-' . $i;
			}
			$out[] = $node;
		}
		return $out;
	}

	/**
	 * The full document for Replace mode / no-Yoast output.
	 *
	 * @param ValidationResult $result   Parsed input (must be ok and non-empty).
	 * @param string           $base_url Page permalink.
	 * @return array<string, mixed>
	 */
	public static function standalone( ValidationResult $result, string $base_url ): array {
		$nodes = self::assign_ids( $result->nodes, $base_url );

		if ( $result->had_graph_wrapper && is_array( $result->document ) ) {
			$document = $result->document;
			if ( ! array_key_exists( '@context', $document ) ) {
				$document = [ '@context' => self::CONTEXT ] + $document;
			}
			$document['@graph'] = $nodes;
			return $document;
		}

		return [
			'@context' => self::CONTEXT,
			'@graph'   => $nodes,
		];
	}
}
