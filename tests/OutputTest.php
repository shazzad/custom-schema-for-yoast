<?php

use PHPUnit\Framework\TestCase;
use Shazzad\CustomSchemaForYoast\Output;

final class OutputTest extends TestCase {

	public function test_render_wraps_in_script_tag(): void {
		$html = Output::render( [ '@context' => 'https://schema.org', '@graph' => [] ] );
		$this->assertStringStartsWith( '<script type="application/ld+json" class="csfy-schema">', $html );
		$this->assertStringEndsWith( "</script>\n", $html );
	}

	public function test_escapes_closing_script_tag(): void {
		$html = Output::render( [ '@type' => 'Thing', 'description' => 'x</script><script>alert(1)</script>' ] );
		$this->assertStringNotContainsString( '</script><script>', $html );
		$this->assertStringContainsString( '<\/script><script>alert(1)<\/script>', $html );
		$this->assertSame( 1, substr_count( $html, '</script>' ) );
	}

	public function test_keeps_unicode(): void {
		$json = Output::encode( [ '@type' => 'Thing', 'name' => 'শাজ্জাদ – café' ] );
		$this->assertStringContainsString( 'শাজ্জাদ – café', $json );
		$this->assertStringNotContainsString( '\\u2013', $json );
	}

	public function test_url_slashes_are_escaped(): void {
		$json = Output::encode( [ 'url' => 'https://w4dev.com/x/' ] );
		$this->assertSame( '{"url":"https:\/\/w4dev.com\/x\/"}', $json );
	}
}
