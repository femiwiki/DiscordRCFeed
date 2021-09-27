<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Integration;

use MediaWiki\Extension\DiscordRCFeed\DiscordRCFeedFormatter;
use MediaWiki\Extension\DiscordRCFeed\FeedSanitizer;
use MediaWikiIntegrationTestCase;
use MessageCache;
use RecentChange;
use Title;
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

	private static function makeRecentChange( array $attribs ) {
		$rc = new RecentChange;
		$rc->setAttribs( $attribs );
		return $rc;
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
		FeedSanitizer::initializeParameters( $feed, $defaultParams );
		$user = $this->getTestSysop()->getUser();
		$user->setName( 'Dummy' );
		$this->assertJsonStringEqualsJsonString(
			$expected,
			$this->wrapper->makePostData(
				[],
				$feed,
				0x0000ff,
				'message',
				'comment',
				$user,
				Title::newFromText( 'Dummy' )
			)
		);
	}

	public static function providerGetDescription() {
		return [
			[
				[
					'discordrcfeed-emoji-log-create-create',
					'logentry-create-create',
				],
				[],
				[
					'rc_type' => RC_NEW,
				],
				'should describe new page'
			],
			[
				[
					'discordrcfeed-emoji-edit',
					'discordrcfeed-line-edit',
				],
				[],
				[
					'rc_type' => RC_EDIT,
				],
				'should describe edit'
			],
		];
	}

	/**
	 * @dataProvider providerGetDescription
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordRCFeedFormatter::getDescription
	 */
	public function testGetDescription( array $expected, array $feed, array $attribs, string $message = '' ) {
		// Provide mandatory parameters if not given
		$testUser = $this->getTestSysop()->getUser();
		$testUser->setName( 'GetDescriptionTestUser' );
		$title = Title::newFromText( 'Test page' );
		$attribs = array_replace_recursive( [
			'rc_minor' => false,
			'rc_bot' => false,
			'rc_user' => $testUser->getId(),
			'rc_namespace' => NS_MAIN,
			'rc_title' => 'Test page',
		], $attribs );
		FeedSanitizer::initializeParameters( $feed );

		$rc = self::makeRecentChange( $attribs );
		$this->setContentLang( 'qqx' );
		$rt = $this->wrapper->getDescription( $feed, $rc, false, $testUser, $title );
		foreach ( $expected as $key ) {
			$this->assertStringContainsString( $key, $rt, $message );
		}
		$this->assertStringContainsString( 'GetDescriptionTestUser', $rt, $message );
		$this->assertStringContainsString( 'Test page', $rt, $message );
	}

	public static function provideEmojiKeys(): array {
		return [
			[
				'🔓',
				'block',
				'unblock',
				'',
				'should show if exact match exists'
			],
			[
				'❌',
				'block',
				'reblock',
				'',
				'should show if exact match does not exist'
			],
			[
				'🚀',
				'random',
				'random',
				'test-emoji-rocket',
				'should use the given fallback if no match is found'
			],
			[
				'❓',
				'random',
				'random',
				'',
				'should only use prefix if no match is found and fallback is not given'
			],
		];
	}

	/**
	 * @dataProvider provideEmojiKeys
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordRCFeedFormatter::getEmojiForKeys
	 */
	public function testGetEmojiForKeys( $expected, $mainKey, $subKey, $fallback, $message ) {
		$msgMap = [
			'test-emoji-block-unblock' => '🔓',
			'test-emoji-block' => '❌',
			'test-emoji-rocket' => '🚀',
			'test-emoji' => '❓',
		];
		$mock = $this->createMock( MessageCache::class );
		$mock->method( 'get' )
			->will( $this->returnCallback(
				static function ( $key, $useDB, $lang ) use ( $msgMap ) {
					return $msgMap[$key] ?? false;
				}
			)
		);
		$mock->method( 'transform' )
			->will( $this->returnArgument( 0 ) );
		$this->setService( 'MessageCache', $mock );

		$emoji = $this->wrapper->getEmojiForKeys( 'test-emoji', $mainKey, $subKey, $fallback );
		$this->assertSame( $expected, $emoji, $message );
	}
}
