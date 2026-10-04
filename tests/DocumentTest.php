<?php

use PHPUnit\Framework\TestCase;
use Shazzad\CustomSchemaForYoast\Document;
use Shazzad\CustomSchemaForYoast\Validator;

final class DocumentTest extends TestCase {

	private const BASE = 'https://w4dev.com/plugins/w4-post-list/';

	public function test_assigns_ids_only_where_missing(): void {
		$nodes = [
			[ '@type' => 'Thing', '@id' => 'https://x.test/#keep' ],
			[ '@type' => 'Thing' ],
			[ '@type' => 'Thing' ],
		];
		$out = Document::assign_ids( $nodes, self::BASE );
		$this->assertSame( 'https://x.test/#keep', $out[0]['@id'] );
		$this->assertSame( self::BASE . '#csfy-2', $out[1]['@id'] );
		$this->assertSame( self::BASE . '#csfy-3', $out[2]['@id'] );
	}

	public function test_non_string_id_is_replaced(): void {
		$nodes = [
			[ '@type' => 'Thing', '@id' => [ 'nested' => true ] ],
			[ '@type' => 'Thing', '@id' => 42 ],
			[ '@type' => 'Thing', '@id' => '' ],
		];
		$out = Document::assign_ids( $nodes, self::BASE );
		$this->assertSame( self::BASE . '#csfy-1', $out[0]['@id'] );
		$this->assertSame( self::BASE . '#csfy-2', $out[1]['@id'] );
		$this->assertSame( self::BASE . '#csfy-3', $out[2]['@id'] );
	}

	public function test_standalone_wraps_bare_nodes(): void {
		$r   = Validator::parse( '[{"@type":"Thing","name":"a"}]' );
		$doc = Document::standalone( $r, self::BASE );
		$this->assertSame( 'https://schema.org', $doc['@context'] );
		$this->assertCount( 1, $doc['@graph'] );
		$this->assertSame( self::BASE . '#csfy-1', $doc['@graph'][0]['@id'] );
	}

	public function test_standalone_wraps_single_node(): void {
		$r   = Validator::parse( '{"@type":"Thing","name":"a"}' );
		$doc = Document::standalone( $r, self::BASE );
		$this->assertSame( [ '@context', '@graph' ], array_keys( $doc ) );
		$this->assertSame( 'a', $doc['@graph'][0]['name'] );
	}

	public function test_standalone_keeps_user_document_when_graph_wrapper_present(): void {
		$raw = '{"@context":"https://schema.org/","custom":"kept","@graph":[{"@type":"Thing"}]}';
		$r   = Validator::parse( $raw );
		$doc = Document::standalone( $r, self::BASE );
		$this->assertSame( 'https://schema.org/', $doc['@context'] );
		$this->assertSame( 'kept', $doc['custom'] );
		$this->assertSame( self::BASE . '#csfy-1', $doc['@graph'][0]['@id'] );
	}
}
