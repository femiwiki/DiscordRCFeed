<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Integration;

use MediaWiki\Extension\DiscordRCFeed\Util;
use MediaWikiIntegrationTestCase;
use RequestContext;

/**
 * @group DiscordRCFeed
 *
 * @covers \MediaWiki\Extension\DiscordRCFeed\Util
 */
class UtilTest extends MediaWikiIntegrationTestCase {

	public static function providerUrls(): array {
		return [
			[
				true,
				'/index.php/Title',
			],
			[
				true,
				'/index.php/Title',
				'https://example.com',
			],
			[
				true,
				'https://example.com/index.php/Title',
				'https://example.com',
			],
			[
				false,
				'https://example.com/index.php',
				'http://example.com',
			],
			[
				false,
				'https://example.com/index.php',
				'https://foo.com',
			],
		];
	}

	/**
	 * @dataProvider providerUrls
	 * @covers \MediaWiki\Extension\DiscordRCFeed\Util::urlIsLocal
	 */
	public function testUrlIsLocal( $expected, $url, $server = '', $message = '' ) {
		if ( $server ) {
			$this->setMwGlobals( 'wgServer', $server );
		}
		$this->assertEquals(
			$expected,
			Util::urlIsLocal( $url ),
			$message
		);
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\Util::getContentLanguageContext
	 */
	public function testGetContentLanguageContext() {
		$ctx = Util::getContentLanguageContext();
		$this->assertInstanceOf( RequestContext::class, $ctx );
	}
}
