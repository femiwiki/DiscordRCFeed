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

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\RCFeedFormatter::makePostData
	 */
	public function testMakePostData() {
		$this->assertJsonStringEqualsJsonString(
			'{"embeds": [ { "color" : "000" ,"description" : "message"} ], "username": "TestWiki"}',
			$this->wrapper->makePostData(
				[],
				'message',
				000
			)
		);

		$this->setMwGlobals( 'wgSitename', 'FooWiki' );
		$this->assertJsonStringEqualsJsonString(
			'{"embeds": [ { "color" : "000" ,"description" : "message"} ], "username": "FooWiki"}',
			$this->wrapper->makePostData(
				'message',
				000
			)
		);

		$this->setMwGlobals( 'wgDiscordRCFeedRequestOverride', [ 'username' => 'DummyBot' ] );
		$this->assertJsonStringEqualsJsonString(
			'{"embeds": [ { "color" : "000" ,"description" : "message"} ], "username": "DummyBot"}',
			$this->wrapper->makePostData(
				'message',
				000
			)
		);
	}
}
