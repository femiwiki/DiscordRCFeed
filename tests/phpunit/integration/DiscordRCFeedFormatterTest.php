<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Integration;

use MediaWiki\Extension\DiscordRCFeed\DiscordRCFeedFormatter;
use MediaWiki\Extension\DiscordRCFeed\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @group DiscordRCFeed
 * @group Database
 *
 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordRCFeedFormatter
 */
class DiscordRCFeedFormatterTest extends MediaWikiIntegrationTestCase {

	/** @var DiscordRCFeedFormatter */
	private $formatter;

	/** @var TestingAccessWrapper */
	private $wrapper;

	protected function setUp(): void {
		parent::setUp();
		$this->formatter = new DiscordRCFeedFormatter();
		$this->wrapper = TestingAccessWrapper::newFromObject( $this->formatter );
	}

	public static function providerEmbed(): array {
		return [
			[
				'{"embeds": [ { "color" : 255 ,"description" : "message"} ], "username": "TestWiki"}',
				[],
				[],
			],
			[
				'{"embeds": [ { "color" : 255 ,"description" : "message"} ], "username": "FooWiki"}',
				[ 'wgSitename' => 'FooWiki' ],
				[],
			],
			[
				'{"embeds": [ { "color" : 255 ,"description" : "message"} ], "username": "DummyBot"}',
				[],
				[ 'request_override' => [ 'username' => 'DummyBot' ] ],
			],
		];
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordRCFeedFormatter::makePostData
	 * @dataProvider providerEmbed
	 */
	public function testMakePostData( string $expected, array $globals, array $feed ) {
		$this->setMwGlobals( $globals );
		MediaWikiServices::addDefaultValues( $feed );
		$this->assertJsonStringEqualsJsonString(
			$expected,
			$this->wrapper->makePostData(
				$feed,
				'message',
				0x0000ff
			)
		);
	}
}
