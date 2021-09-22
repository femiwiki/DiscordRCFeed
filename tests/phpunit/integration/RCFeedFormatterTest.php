<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Integration;

use MediaWiki\Extension\DiscordRCFeed\RCFeedFormatter;
use MediaWikiIntegrationTestCase;
use User;
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
	 * @covers \MediaWiki\Extension\DiscordRCFeed\RCFeedFormatter::makePost
	 */
	public function testMakePost() {
		$this->assertJsonStringEqualsJsonString(
			'{"embeds": [ { "color" : "2993970" ,"description" : "message"} ], "username": "TestWiki"}',
			$this->wrapper->makePost(
				'message',
				'article_saved'
			)
		);

		$this->setMwGlobals( 'wgSitename', 'FooWiki' );
		$this->assertJsonStringEqualsJsonString(
			'{"embeds": [ { "color" : "2993970" ,"description" : "message"} ], "username": "FooWiki"}',
			$this->wrapper->makePost(
				'message',
				'article_saved'
			)
		);

		$this->setMwGlobals( 'wgDiscordRCFeedRequestOverride', [ 'username' => 'DummyBot' ] );
		$this->assertJsonStringEqualsJsonString(
			'{"embeds": [ { "color" : "2993970" ,"description" : "message"} ], "username": "DummyBot"}',
			$this->wrapper->makePost(
				'message',
				'article_saved'
			)
		);
	}
}
