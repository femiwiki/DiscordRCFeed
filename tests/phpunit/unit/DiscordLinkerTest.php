<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Unit;

use MediaWiki\Extension\DiscordRCFeed\DiscordLinker;
use MediaWikiUnitTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @group DiscordRCFeed
 *
 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordLinker
 */
class DiscordLinkerTest extends MediaWikiUnitTestCase {

	/** @var TestingAccessWrapper */
	private $wrapper;

	protected function setUp(): void {
		parent::setUp();
		$renderer = new DiscordLinker();
		$this->wrapper = TestingAccessWrapper::newFromObject( $renderer );
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordLinker::parseUrl
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
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordLinker::makeLink
	 */
	public function testMakeLink( $expected, $params ) {
		$this->assertSame(
			$expected,
			DiscordLinker::makeLink( ...$params )
		);
	}
}
