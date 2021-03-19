<?php

namespace MediaWiki\Extension\DiscordNotifications\Tests\Integration;

use DiscordNotificationsCore;
use MediaWikiIntegrationTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @group DiscordNotifications
 * @group Database
 *
 * @covers \DiscordNotificationsCore
 */
class DiscordNotificationsCoreTest extends MediaWikiIntegrationTestCase {

	/** @var TestingAccessWrapper */
	private $wrapper;

	protected function setUp() : void {
		parent::setUp();
		$this->wrapper = TestingAccessWrapper::newFromClass( DiscordNotificationsCore::class );
	}

	public static function providerDiscordUserText() {
		return [
			[
				[ 'DiscordIncludeUserUrls' => false ],
				'Foo',
				'<index.php?title=User:Foo|Foo>'
			],
			[
				[ 'DiscordIncludeUserUrls' => false ],
				'Foo&bar',
				'<index.php?title=User:Foo%26bar|Foo&bar>'
			],
			[
				[ 'DiscordNotificationWikiUrl' => 'https://foo.bar/' ],
				'Foo',
				'<https://foo.bar/index.php?title=User:Foo|Foo> ' .
				'(<https://foo.bar/index.php?title=Special:Block/Foo|block> | ' .
				'<https://foo.bar/index.php?title=Special%3AUserRights&user=Foo|groups> | ' .
				'<https://foo.bar/index.php?title=User_talk:Foo|talk> | ' .
				'<https://foo.bar/index.php?title=Special:Contributions/Foo|contribs>)'
			],
			[
				[
					'DiscordNotificationWikiUrl' => 'https://foo.bar/',
					'DiscordNotificationWikiUrlEndingBlockUser' => 'Special:Foo/'
				],
				'Foo',
				'<https://foo.bar/index.php?title=User:Foo|Foo> ' .
				'(<https://foo.bar/index.php?title=Special:Foo/Foo|block> | ' .
				'<https://foo.bar/index.php?title=Special%3AUserRights&user=Foo|groups> | ' .
				'<https://foo.bar/index.php?title=User_talk:Foo|talk> | ' .
				'<https://foo.bar/index.php?title=Special:Contributions/Foo|contribs>)'
			],
			[
				[
					'DiscordNotificationWikiUrlEndingUserPage' => 'Usuario:',
					'DiscordNotificationWikiUrlEndingBlockUser' => 'Especial:Bloquear/',
					'DiscordNotificationWikiUrlEndingUserRights' => 'Especial%3APermisosUsuarios&user=',
					'DiscordNotificationWikiUrlEndingUserTalkPage' => 'Usuario_discusión:',
					'DiscordNotificationWikiUrlEndingUserContributions' => 'Especial:Contribuciones/'
				],
				'Foo',
				'<index.php?title=Usuario:Foo|Foo> ' .
				'(<index.php?title=Especial:Bloquear/Foo|block> | ' .
				'<index.php?title=Especial%3APermisosUsuarios&user=Foo|groups> | ' .
				'<index.php?title=Usuario_discusión:Foo|talk> | ' .
				'<index.php?title=Especial:Contribuciones/Foo|contribs>)'
			]
		];
	}

	/**
	 * @dataProvider providerDiscordUserText
	 * @covers \DiscordNotificationsCore::getDiscordUserText
	 */
	public function testGetDiscordUserText( array $globals, string $name, string $expected, string $message = '' ) {
		foreach ( $globals as $key => $val ) {
			$this->setMwGlobals( "wg$key", $val );
		}
		$this->assertSame(
			$expected,
			$this->wrapper->getDiscordUserText( $name ),
			$message
		);
	}

	/**
	 * @covers \DiscordNotificationsCore::getDiscordArticleText
	 * @covers \DiscordNotificationsCore::getDiscordTitleText
	 */
	public function testGetDiscordArticleText() {
		$w = $this->wrapper;
		$page = $this->getExistingTestPage( 'foo' );
		$title = $page->getTitle();

		$this->assertSame(
			'<index.php?title=Foo|Foo> (<index.php?title=Foo&action=edit|edit> | ' .
			'<index.php?title=Foo&action=delete|delete> | <index.php?title=Foo&action=history|history> | ' .
			'<index.php?title=Foo&diff=prev&oldid=2|diff>)' . "\n",
			$w->getDiscordArticleText( $page, true )
		);
		$expected = '<index.php?title=Foo|Foo> (<index.php?title=Foo&action=edit|edit> | ' .
			'<index.php?title=Foo&action=delete|delete> | <index.php?title=Foo&action=history|history>)';
		$this->assertSame( $expected . "\n", $w->getDiscordArticleText( $page, false ) );
		$this->assertSame( $expected, $w->getDiscordTitleText( $title ) );

		$this->setMwGlobals( 'wgDiscordIncludePageUrls', false );
		$expected = '<index.php?title=Foo|Foo>';
		$this->assertSame( $expected, $w->getDiscordArticleText( $page, false ) );
		$this->assertSame( $expected, $w->getDiscordTitleText( $title ) );
		$expected = '<index.php?title=Foo%26bar|Foo&bar>';
		$page = $this->getExistingTestPage( 'foo&bar' );
		$title = $page->getTitle();
		$this->assertSame( $expected, $w->getDiscordArticleText( $page, false ) );
		$this->assertSame( $expected, $w->getDiscordTitleText( $title ) );
	}

	public static function providerTitleIsExcluded() {
		return [
			[ '', 'test', false ],
			[ [ 'test' ], 'test', true ],
			[ [ 'text' ], 'test', false ],
			[ [ 'foo', 'bar' ], 'test', false ],
			[ [ 'foo', 'bar' ], 'foo', true ],
			[ [ 'foo', 'bar' ], 'bar', true ],
		];
	}

	/**
	 * @dataProvider providerTitleIsExcluded
	 * @covers \DiscordNotificationsCore::titleIsExcluded
	 */
	public function testTitleIsExcluded( $excluded, string $titleText, bool $expected ) {
		$this->setMwGlobals( 'wgDiscordExcludeNotificationsFrom', $excluded );
		$this->assertSame( $expected, $this->wrapper->titleIsExcluded( $titleText ) );
	}

	public static function providerPermissions() {
		return [
			[ 'not-exist', true ],
			[ 'read', false ]
		];
	}

	/**
	 * @dataProvider providerPermissions
	 */
	public function testExcludedPermission( $excluded, $expected ) {
		$this->setMwGlobals( 'wgDiscordExcludedPermission', $excluded );
		$user = $this->getTestUser()->getUser();
		$arbitrary = 'test' . time() . rand();
		$this->wrapper->pushDiscordNotify( $arbitrary, $user, 'article_saved' );
		$this->assertSame( $expected, DiscordNotificationsCore::$lastMessage === $arbitrary );
	}

	/**
	 * @covers \DiscordNotificationsCore::makePost
	 */
	public function testMakePost() {
		$this->assertSame(
			'{"embeds": [{ "color" : "2993970" ,"description" : "message"}], "username": "TestWiki"}',
			$this->wrapper->makePost(
				'message',
				$this->getTestUser(),
				'article_saved'
			)
		);

		$this->setMwGlobals( 'wgSitename', 'FooWiki' );
		$this->assertSame(
			'{"embeds": [{ "color" : "2993970" ,"description" : "message"}], "username": "FooWiki"}',
			$this->wrapper->makePost(
				'message',
				$this->getTestUser(),
				'article_saved'
			)
		);

		$this->setMwGlobals( 'wgDiscordFromName', 'DummyBot' );
		$this->assertSame(
			'{"embeds": [{ "color" : "2993970" ,"description" : "message"}], "username": "DummyBot"}',
			$this->wrapper->makePost(
				'message',
				$this->getTestUser(),
				'article_saved'
			)
		);
	}

	public function testDiscordNotifications() {
		$ct = 1;
		$this->editPage( 'Edit Test', str_repeat( 'lorem', $ct++ ), '', NS_MAIN );
		$expected = '📄 <index.php?title=User:127.0.0.1|127.0.0.1> ' .
		'(<index.php?title=Special:Block/127.0.0.1|block> | ' .
		'<index.php?title=Special%3AUserRights&user=127.0.0.1|groups> | ' .
		'<index.php?title=User_talk:127.0.0.1|talk> | ' .
		'<index.php?title=Special:Contributions/127.0.0.1|contribs>) ' .
		'has created article ' .
		'<index.php?title=Edit%20Test|Edit Test> ' .
		'(<index.php?title=Edit%20Test&action=edit|edit> | <index.php?title=Edit%20Test&action=delete|delete> | ' .
		'<index.php?title=Edit%20Test&action=history|history>)' .
		"\n" .
		'  (5 bytes)';
		$this->assertSame( $expected, DiscordNotificationsCore::$lastMessage );
	}
}
