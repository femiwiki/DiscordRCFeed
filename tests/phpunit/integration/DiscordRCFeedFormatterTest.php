<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Integration;

use MediaWiki\Extension\DiscordRCFeed\DiscordRCFeedFormatter;
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
				'{"embeds": [ { "color" : "fff" ,"description" : "message"} ], "username": "TestWiki"}',
				[],
				[],
			],
			[
				'{"embeds": [ { "color" : "fff" ,"description" : "message"} ], "username": "FooWiki"}',
				[ 'wgSitename' => 'FooWiki' ],
				[],
			],
			[
				'{"embeds": [ { "color" : "fff" ,"description" : "message"} ], "username": "DummyBot"}',
				[],
				[ 'request_override' => [ 'username' => 'DummyBot' ] ],
			],
		];
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordRCFeedFormatter::makePostData
	 * @dataProvider providerEmbed
	 */
	public function testMakePostData( string $expected, array $globals, array $requestOverride ) {
		$this->setMwGlobals( $globals );
		$this->assertJsonStringEqualsJsonString(
			$expected,
			$this->wrapper->makePostData(
				$requestOverride,
				'message',
				'fff'
			)
		);
	}
}