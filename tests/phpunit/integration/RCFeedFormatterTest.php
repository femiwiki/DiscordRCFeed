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

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\RCFeedFormatter::pushDiscordNotify
	 */
	public function testPushDiscordNotify() {
		$formatter = $this->formatter;
		$this->assertFalse( $formatter->pushDiscordNotify( '', null, 'article_saved' ) );

		$this->setMwGlobals( 'wgDiscordRCFeedIncomingWebhookUrl', 'http://127.0.0.1/webhook' );
		$this->assertNull( $formatter->pushDiscordNotify( '', null, 'article_saved' ) );

		$this->setMwGlobals( 'wgDiscordRCFeedSendMethod', 'random' );
		$this->assertFalse( $formatter->pushDiscordNotify( '', null, 'article_saved' ) );
	}

	public function testDiscordRCFeed() {
		$this->setMwGlobals( [
			'wgDiscordRCFeedIncomingWebhookUrl' => 'https:// webhook',
			'wgServer' => 'https://foo.bar'
		] );
		$ct = 1;
		$user = new User();
		$user->setName( 'EditTest' );
		$user->addToDatabase();
		$this->editPage( 'Edit Test', str_repeat( 'lorem', $ct++ ), '', NS_MAIN, $user );

		// phpcs:ignore Generic.Files.LineLength.TooLong
		$regex = '~📄 \[EditTest\]\(https://foo\.bar/index\.php/User:EditTest\) \(\[block\]\(https://foo\.bar/index\.php(\?title=|/)Special:Block/EditTest\) \| \[groups\]\(https://foo\.bar/index\.php(\?title=|/)Special(%3A|:)UserRights(&user=|/)EditTest\) \| \[talk\]\(https://foo\.bar/index\.php(\?title=|/)User_talk:EditTest\) \| \[contribs\]\(https://foo\.bar/index\.php(\?title=|/)Special:Contributions/EditTest\)\) has created article \[Edit Test\]\(https://foo\.bar/index\.php(\?title=|/)Edit(%20|_)Test\) \(\[edit\]\(https://foo\.bar/index\.php\?title=Edit(%20|_)Test&action=edit\) \| \[delete\]\(https://foo\.bar/index\.php\?title=Edit(%20|_)Test&action=delete\) \| \[history\]\(https://foo\.bar/index\.php\?title=Edit(%20|_)Test&action=history\)\)  \(5 bytes\)~';
		$this->assertRegExp( $regex, RCFeedFormatter::$lastMessage );
	}
}
