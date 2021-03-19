<?php

namespace MediaWiki\Extension\DiscordNotifications\Tests\Unit;

use MediaWiki\Extension\DiscordNotifications\LinkRenderer;
use MediaWikiUnitTestCase;

/**
 * @group DiscordNotifications
 *
 * @covers \MediaWiki\Extension\DiscordNotifications\LinkRenderer
 */
class LinkRendererTest extends MediaWikiUnitTestCase {

	/**
	 * @covers \MediaWiki\Extension\DiscordNotifications\LinkRenderer::parseUrl
	 */
	public function testParseUrl() {
		$this->assertSame(
			'https://example.com/wiki/title=Foo%20%28bar%29',
			LinkRenderer::parseUrl( 'https://example.com/wiki/title=Foo (bar)' )
		);
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordNotifications\LinkRenderer::makeLink
	 */
	public function testMakeLink() {
		$this->assertSame(
			'<Foo|Foo>',
			LinkRenderer::makeLink( 'Foo', 'Foo' )
		);
		$this->assertSame(
			'<Foo%20Bar|Foo>',
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
	 * @param string|array $tools
	 * @param string $expected
	 */
	public function testMakeNiceTools( $tools, $expected ) {
		$this->assertSame( $expected, LinkRenderer::MakeNiceTools( $tools ) );
	}
}
