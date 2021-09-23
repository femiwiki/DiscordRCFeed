<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Integration;

use MediaWiki\Extension\DiscordRCFeed\RCFeedFormatter;
use MediaWikiIntegrationTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @group DiscordRCFeed
 * @group Database
 *
 * @covers \MediaWiki\Extension\DiscordRCFeed\RCFeedFormatter
 */
class RCFeedFormatterTest extends MediaWikiIntegrationTestCase {

	/** @var RCFeedFormatter */
	private $formatter;

	/** @var TestingAccessWrapper */
	private $wrapper;

	protected function setUp(): void {
		parent::setUp();
		$this->formatter = new RCFeedFormatter();
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
	 * @covers \MediaWiki\Extension\DiscordRCFeed\RCFeedFormatter::makePostData
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
