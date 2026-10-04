<?php
/**
 * Parses and shape-checks pasted JSON-LD.
 *
 * @package Shazzad\CustomSchemaForYoast
 */

namespace Shazzad\CustomSchemaForYoast;

defined( 'ABSPATH' ) || exit;

/**
 * Turns the raw textarea string into a ValidationResult. Pure PHP.
 */
final class Validator {

	/**
	 * Parse the textarea contents.
	 *
	 * Accepted: a single node object, a list of node objects, or an object with "@graph".
	 *
	 * @param string $raw Raw textarea contents.
	 * @return ValidationResult
	 */
	public static function parse( string $raw ): ValidationResult {
		$raw = preg_replace( '/^\xEF\xBB\xBF/', '', $raw );
		$raw = trim( (string) $raw );

		if ( '' === $raw ) {
			return new ValidationResult( true, null, [], false, null );
		}

		$decoded = json_decode( $raw, true, 512 );
		if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
			return ValidationResult::failure( json_last_error_msg() );
		}

		if ( ! is_array( $decoded ) ) {
			return ValidationResult::failure( 'Expected an object or an array of objects.' );
		}

		// A list of nodes.
		if ( self::is_list( $decoded ) ) {
			$error = self::check_nodes( $decoded );
			$nodes = self::strip_context( $decoded );
			return $error ? ValidationResult::failure( $error ) : new ValidationResult( true, null, $nodes, false, $decoded );
		}

		// A {"@graph": [...]} document.
		if ( array_key_exists( '@graph', $decoded ) ) {
			if ( ! is_array( $decoded['@graph'] ) || ! self::is_list( $decoded['@graph'] ) ) {
				return ValidationResult::failure( '"@graph" must be an array of objects.' );
			}
			$error = self::check_nodes( $decoded['@graph'] );
			return $error ? ValidationResult::failure( $error ) : new ValidationResult( true, null, self::strip_context( $decoded['@graph'] ), true, $decoded );
		}

		// A single node.
		$error = self::check_nodes( [ $decoded ] );
		return $error ? ValidationResult::failure( $error ) : new ValidationResult( true, null, self::strip_context( [ $decoded ] ), false, $decoded );
	}

	/**
	 * Drop a node-level "@context"; the document-level one is what matters.
	 *
	 * @param array<int, array<string, mixed>> $nodes Valid nodes.
	 * @return array<int, array<string, mixed>>
	 */
	private static function strip_context( array $nodes ): array {
		$out = [];
		foreach ( $nodes as $node ) {
			if ( is_array( $node ) ) {
				unset( $node['@context'] );
			}
			$out[] = $node;
		}
		return $out;
	}

	/**
	 * Every entry must be an object with a string "@type".
	 *
	 * @param array<int, mixed> $nodes Candidate nodes.
	 * @return string|null Error message or null when fine.
	 */
	private static function check_nodes( array $nodes ): ?string {
		$i = 0;
		foreach ( $nodes as $node ) {
			++$i;
			if ( ! is_array( $node ) || self::is_list( $node ) ) {
				return sprintf( 'Node %d is not an object.', $i );
			}
			if ( empty( $node['@type'] ) || ( ! is_string( $node['@type'] ) && ! is_array( $node['@type'] ) ) ) {
				return sprintf( 'Node %d has no "@type".', $i );
			}
		}
		return null;
	}

	/**
	 * PHP 7.4-safe array_is_list(). An empty array counts as a list.
	 *
	 * @param array<mixed> $value Array.
	 * @return bool
	 */
	private static function is_list( array $value ): bool {
		return [] === $value || array_keys( $value ) === range( 0, count( $value ) - 1 );
	}
}
