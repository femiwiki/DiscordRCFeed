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
			$this->wrapper->parseUrl( 'https://example.com/wiki/title=Foo (bar)' )
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
}
