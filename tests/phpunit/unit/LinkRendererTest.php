<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Unit;

use MediaWiki\Extension\DiscordRCFeed\LinkRenderer;
use MediaWikiUnitTestCase;

/**
 * @group DiscordRCFeed
 *
 * @covers \MediaWiki\Extension\DiscordRCFeed\LinkRenderer
 */
class LinkRendererTest extends MediaWikiUnitTestCase {

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\LinkRenderer::parseUrl
	 */
	public function testParseUrl() {
		$this->assertSame(
			'https://example.com/wiki/title=Foo%20%28bar%29',
			LinkRenderer::parseUrl( 'https://example.com/wiki/title=Foo (bar)' )
		);
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\LinkRenderer::makeLink
	 */
	public function testMakeLink() {
		$this->assertSame(
			'[Foo](Foo)',
			LinkRenderer::makeLink( 'Foo', 'Foo' )
		);
		$this->assertSame(
			'[Foo](Foo%20Bar)',
			LinkRenderer::makeLink( 'Foo Bar', 'Foo' )
		);
	}

	public static function providerTools() {
		return [
			[ 'edit', '(edit)' ],
			[ [ 'edit', 'block' ], '(edit | block)' ],
		];
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\LinkRenderer::makeNiceTools
	 * @param string|array $tools
	 * @param string $expected
	 * @dataProvider providerTools
	 */
	public function testMakeNiceTools( $tools, $expected ) {
		$this->assertSame( $expected, LinkRenderer::makeNiceTools( $tools ) );
	}

	public static function providerWikitextWithLinks() {
		return [
			[ 'A edited [[B]]', 'A edited [B]()' ],
			[ 'A moved [[B]] to [[C]]', 'A moved [B]() to [C]()' ],
		];
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\LinkRenderer::makeLinksClickable
	 * @param string $wt
	 * @param string $expected
	 * @dataProvider providerWikitextWithLinks
	 */
	public function testMakeLinksClickable( $wt, $expected ) {
		$renderer = new LinkRenderer();
		$actual = $renderer->makeLinksClickable( $wt );
		$this->assertSame( $expected, $actual );
	}
}
