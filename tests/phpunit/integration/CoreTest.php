<?php

namespace MediaWiki\Extension\DiscordNotifications\Tests\Integration;

use MediaWiki\Extension\DiscordNotifications\Core;
use MediaWikiIntegrationTestCase;
use User;
use Wikimedia\TestingAccessWrapper;

/**
 * @group DiscordNotifications
 * @group Database
 *
 * @covers \MediaWiki\Extension\DiscordNotifications\Core
 */
class CoreTest extends MediaWikiIntegrationTestCase {

	/** @var Core */
	private $core;

	/** @var TestingAccessWrapper */
	private $wrapper;

	protected function setUp() : void {
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
	 * @covers \MediaWiki\Extension\DiscordNotifications\Core::titleIsExcluded
	 */
	public function testTitleIsExcluded( $excluded, string $titleText, bool $expected ) {
		global $wgDiscordNotificationsExclude;
		$excluded = array_merge( $wgDiscordNotificationsExclude, [ 'page' => $excluded ] );
		$this->setMwGlobals( 'wgDiscordNotificationsExclude', $excluded );
		$title = $this->getExistingTestPage( $titleText )->getTitle();
		$this->assertSame( $expected, Core::titleIsExcluded( $title ) );
	}

	public static function providerPermissions() {
		return [
			[ 'not-exist', true ],
			[ 'read', false ],
			[ [ 'not-exist' ], true ],
			[ [ 'read' ], false ],
		];
	}

	/**
	 * @dataProvider providerPermissions
	 */
	public function testExcludedPermission( $excluded, $expected ) {
		global $wgDiscordNotificationsExclude;
		$excluded = array_merge( $wgDiscordNotificationsExclude, [ 'permissions' => $excluded ] );
		$this->setMwGlobals( 'wgDiscordNotificationsExclude', $excluded );
		$user = $this->getTestUser()->getUser();
		$arbitrary = 'test' . time() . rand();
		$this->core->pushDiscordNotify( $arbitrary, $user, 'article_saved' );
		$this->assertSame( $expected, Core::$lastMessage === $arbitrary );
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordNotifications\Core::makePost
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

		$this->setMwGlobals( 'wgDiscordNotificationsRequestOverride', [ 'username' => 'DummyBot' ] );
		$this->assertJsonStringEqualsJsonString(
			'{"embeds": [ { "color" : "2993970" ,"description" : "message"} ], "username": "DummyBot"}',
			$this->wrapper->makePost(
				'message',
				'article_saved'
			)
		);
	}

	public function testDiscordNotifications() {
		$this->setMwGlobals( [
			'wgDiscordNotificationsIncomingWebhookUrl' => 'https:// webhook',
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
