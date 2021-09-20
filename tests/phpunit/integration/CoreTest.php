<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Integration;

use MediaWiki\Extension\DiscordRCFeed\Core;
use MediaWikiIntegrationTestCase;
use User;
use Wikimedia\TestingAccessWrapper;

/**
 * @group DiscordRCFeed
 * @group Database
 *
 * @covers \MediaWiki\Extension\DiscordRCFeed\Core
 */
class CoreTest extends MediaWikiIntegrationTestCase {

	/** @var Core */
	private $core;

	/** @var TestingAccessWrapper */
	private $wrapper;

	protected function setUp(): void {
		parent::setUp();
		$this->core = new Core();
		$this->wrapper = TestingAccessWrapper::newFromObject( $this->core );
	}

	public static function providerTitleIsExcluded() {
		return [
			[ [], 'test', false ],
			[ [ 'list' => [ 'Test' ] ], 'Test', true ],
			[ [ 'list' => [ 'Text' ] ], 'Test', false ],
			[ [ 'list' => [ 'Foo', 'Bar' ] ], 'Test', false ],
			[ [ 'list' => [ 'Foo', 'Bar' ] ], 'Foo', true ],
			[ [ 'list' => [ 'Foo', 'Bar' ] ], 'Bar', true ],
			[ [ 'patterns' => [ '/Foo/' ] ], 'Foo', true ],
			[ [ 'patterns' => [ '/^Foo$/' ] ], 'Foo', true ],
			[ [ 'patterns' => [ '/^Fo+$/' ] ], 'Foo', true ],
			[ [ 'patterns' => [ '/^b..$/' ] ], 'Foo', false ],
		];
	}

	/**
	 * @dataProvider providerTitleIsExcluded
	 * @covers \MediaWiki\Extension\DiscordRCFeed\Core::titleIsExcluded
	 */
	public function testTitleIsExcluded( $excluded, string $titleText, bool $expected ) {
		global $wgDiscordRCFeedExclude;
		$excluded = array_merge( $wgDiscordRCFeedExclude, [ 'page' => $excluded ] );
		$this->setMwGlobals( 'wgDiscordRCFeedExclude', $excluded );
		$title = $this->getExistingTestPage( $titleText )->getTitle();
		$this->assertSame( $expected, Core::titleIsExcluded( $title ) );
	}

	public static function providerPermissions() {
		return [
			[ 'non-exist-permission', false ],
			[ 'read', true ],
			[ [ 'non-exist-permission' ], false ],
			[ [ 'read' ], true ],
		];
	}

	/**
	 * @dataProvider providerPermissions
	 * @covers \MediaWiki\Extension\DiscordRCFeed\Core::userIsExcluded
	 */
	public function testUserIsExcluded( $permission, $excluded ) {
		global $wgDiscordRCFeedExclude;
		$permission = array_merge( $wgDiscordRCFeedExclude, [ 'permissions' => $permission ] );
		$this->setMwGlobals( 'wgDiscordRCFeedExclude', $permission );

		$user = $this->getTestUser()->getUser();
		$this->assertSame( $excluded, Core::userIsExcluded( $user ) );
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\Core::makePost
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
	 * @covers \MediaWiki\Extension\DiscordRCFeed\Core::pushDiscordNotify
	 */
	public function testPushDiscordNotify() {
		$core = $this->core;
		$this->assertFalse( $core->pushDiscordNotify( '', null, 'article_saved' ) );

		$this->setMwGlobals( 'wgDiscordRCFeedIncomingWebhookUrl', 'http://127.0.0.1/webhook' );
		$this->assertNull( $core->pushDiscordNotify( '', null, 'article_saved' ) );

		$this->setMwGlobals( 'wgDiscordRCFeedSendMethod', 'random' );
		$this->assertFalse( $core->pushDiscordNotify( '', null, 'article_saved' ) );
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
		$this->assertRegExp( $regex, Core::$lastMessage );
	}
}
