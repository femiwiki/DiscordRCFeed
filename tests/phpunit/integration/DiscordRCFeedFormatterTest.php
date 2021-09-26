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
				'{"embeds": [ { "color" : 255 ,"description" : "message (comment)"} ], "username": "TestWiki"}',
				'',
				[],
			],
			[
				'{"embeds": [ { "color" : 255 ,"description" : "message (comment)"} ], "username": "FooWiki"}',
				'FooWiki',
				[],
			],
			[
				'{"embeds": [ { "color" : 255 ,"description" : "message (comment)"} ], "username": "DummyBot"}',
				'',
				[ 'request_replace' => [ 'username' => 'DummyBot' ] ],
			],
		];
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordRCFeedFormatter::makePostData
	 * @dataProvider providerEmbed
	 */
	public function testMakePostData( string $expected, string $sitename, array $feed ) {
		if ( $sitename ) {
			$this->setMwGlobals( 'wgSitename', $sitename );
		}
		$defaultParams = [
			'style' => 'embed',
		];
		MediaWikiServices::initializeParameters( $feed, $defaultParams );
		$this->assertJsonStringEqualsJsonString(
			$expected,
			$this->wrapper->makePostData(
				[],
				$feed,
				0x0000ff,
				'message',
				'comment'
			)
		);
	}
}
