<?php

use PHPUnit\Framework\TestCase;
use Shazzad\CustomSchemaForYoast\Validator;

final class ValidatorTest extends TestCase {

	public function test_empty_input_is_ok_with_no_nodes(): void {
		foreach ( [ '', "   \n\t ", "\r\n" ] as $raw ) {
			$r = Validator::parse( $raw );
			$this->assertTrue( $r->ok );
			$this->assertTrue( $r->is_empty() );
			$this->assertNull( $r->error );
			$this->assertSame( [], $r->nodes );
		}
	}

	public function test_single_node(): void {
		$r = Validator::parse( '{"@type":"SoftwareApplication","name":"X"}' );
		$this->assertTrue( $r->ok );
		$this->assertCount( 1, $r->nodes );
		$this->assertSame( 'SoftwareApplication', $r->nodes[0]['@type'] );
		$this->assertFalse( $r->had_graph_wrapper );
		$this->assertSame( 'X', $r->document['name'] );
	}

	public function test_array_of_nodes(): void {
		$r = Validator::parse( '[{"@type":"Thing","name":"a"},{"@type":"Thing","name":"b"}]' );
		$this->assertTrue( $r->ok );
		$this->assertCount( 2, $r->nodes );
		$this->assertSame( 'b', $r->nodes[1]['name'] );
		$this->assertFalse( $r->had_graph_wrapper );
	}

	public function test_graph_wrapper(): void {
		$raw = '{"@context":"https://schema.org","@graph":[{"@type":"Thing","name":"a"}]}';
		$r   = Validator::parse( $raw );
		$this->assertTrue( $r->ok );
		$this->assertTrue( $r->had_graph_wrapper );
		$this->assertCount( 1, $r->nodes );
		$this->assertSame( 'https://schema.org', $r->document['@context'] );
	}

	public function test_invalid_json_reports_parser_message(): void {
		$r = Validator::parse( '{"@type":"Thing",}' );
		$this->assertFalse( $r->ok );
		$this->assertSame( 'Syntax error', $r->error );
		$this->assertSame( [], $r->nodes );
		$this->assertNull( $r->document );
	}

	public function test_rejects_scalars(): void {
		foreach ( [ '"hello"', '42', 'null', 'true' ] as $raw ) {
			$r = Validator::parse( $raw );
			$this->assertFalse( $r->ok, $raw );
			$this->assertSame( 'Expected an object or an array of objects.', $r->error, $raw );
		}
	}

	public function test_rejects_node_without_type(): void {
		$r = Validator::parse( '[{"@type":"Thing"},{"name":"no type"}]' );
		$this->assertFalse( $r->ok );
		$this->assertSame( 'Node 2 has no "@type".', $r->error );
	}

	public function test_rejects_non_object_graph_entry(): void {
		$r = Validator::parse( '{"@graph":[{"@type":"Thing"},"oops"]}' );
		$this->assertFalse( $r->ok );
		$this->assertSame( 'Node 2 is not an object.', $r->error );
	}

	public function test_rejects_graph_wrapper_whose_graph_is_not_a_list(): void {
		$r = Validator::parse( '{"@graph":{"@type":"Thing"}}' );
		$this->assertFalse( $r->ok );
		$this->assertSame( '"@graph" must be an array of objects.', $r->error );
	}

	public function test_strips_bom_and_whitespace(): void {
		$raw = "\xEF\xBB\xBF\r\n  {\"@type\":\"Thing\"}  \r\n";
		$r   = Validator::parse( $raw );
		$this->assertTrue( $r->ok );
		$this->assertCount( 1, $r->nodes );
	}

	public function test_object_without_type_and_without_graph_is_rejected(): void {
		$r = Validator::parse( '{"name":"x"}' );
		$this->assertFalse( $r->ok );
		$this->assertSame( 'Node 1 has no "@type".', $r->error );
	}

	public function test_empty_array_object_and_graph_are_ok_and_empty(): void {
		foreach ( [ '[]', '{}', '{"@graph":[]}' ] as $raw ) {
			$r = Validator::parse( $raw );
			$this->assertTrue( $r->ok, $raw );
			$this->assertTrue( $r->is_empty(), $raw );
			$this->assertSame( [], $r->nodes, $raw );
		}
	}

	public function test_array_type_is_accepted(): void {
		$r = Validator::parse( '{"@type":["SoftwareApplication","Product"],"name":"x"}' );
		$this->assertTrue( $r->ok );
		$this->assertSame( [ 'SoftwareApplication', 'Product' ], $r->nodes[0]['@type'] );
	}

	public function test_node_level_context_is_stripped_in_all_shapes(): void {
		$single = Validator::parse( '{"@context":"https://schema.org","@type":"Thing","name":"a"}' );
		$this->assertTrue( $single->ok );
		$this->assertArrayNotHasKey( '@context', $single->nodes[0] );
		$this->assertFalse( $single->had_graph_wrapper );

		$list = Validator::parse( '[{"@context":"https://schema.org","@type":"Thing"},{"@type":"Thing"}]' );
		$this->assertArrayNotHasKey( '@context', $list->nodes[0] );
		$this->assertCount( 2, $list->nodes );

		$graph = Validator::parse( '{"@context":"https://schema.org","@graph":[{"@context":"https://schema.org","@type":"Thing"}]}' );
		$this->assertArrayNotHasKey( '@context', $graph->nodes[0] );
		$this->assertSame( 'https://schema.org', $graph->document['@context'] );
	}
}
