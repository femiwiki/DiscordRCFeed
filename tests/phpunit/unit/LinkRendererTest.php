<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Unit;

use MediaWiki\Extension\DiscordRCFeed\LinkRenderer;
use MediaWikiUnitTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @group DiscordRCFeed
 *
 * @covers \MediaWiki\Extension\DiscordRCFeed\LinkRenderer
 */
class LinkRendererTest extends MediaWikiUnitTestCase {

	/** @var TestingAccessWrapper */
	private $wrapper;

	protected function setUp(): void {
		parent::setUp();
		$renderer = new LinkRenderer();
		$this->wrapper = TestingAccessWrapper::newFromObject( $renderer );
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\LinkRenderer::parseUrl
	 */
	public function testParseUrl() {
		$this->assertSame(
			'https://example.com/wiki/title=Foo%20%28bar%29',
			$this->wrapper->parseUrl( 'https://example.com/wiki/title=Foo (bar)' )
		);
	}

	public static function providerLink(): array {
		return [
			[
				'[Foo](Foo)',
				[ 'Foo', 'Foo' ]
			],
			[
				'[Foo](Foo%20Bar)',
				[ 'Foo Bar', 'Foo' ]
			],
		];
	}

	/**
	 * @dataProvider providerLink
	 * @covers \MediaWiki\Extension\DiscordRCFeed\LinkRenderer::makeLink
	 */
	public function testMakeLink( $expected, $params ) {
		$this->assertSame(
			$expected,
			LinkRenderer::makeLink( ...$params )
		);
	}
}
