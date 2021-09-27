<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Integration;

use MediaWiki\Extension\DiscordRCFeed\DiscordRCFeedFormatter;
use MediaWiki\Extension\DiscordRCFeed\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use RecentChange;
use User;
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

	public static function providerGetLineOmittance() {
		return [
			[
				[
					'omit_namespaces' => [ NS_TALK ],
				],
				[
					'rc_namespace' => NS_TALK,
				],
				'should omit the given namespaces'
			],
			[
				[],
				[
					'rc_type' => RC_CATEGORIZE,
				],
				'should omit RC_CATEGORIZE change always'
			],
			[
				[
					'omit_log_types' => 'patrol',
				],
				[
					'rc_type' => RC_LOG,
					'rc_log_type' => 'patrol',
					'rc_log_action' => 'patrol',
				],
				'should omit the given log types'
			],
			[
				[
					'omit_log_actions' => 'patrol/patrol-auto',
				],
				[
					'rc_type' => RC_LOG,
					'rc_log_type' => 'patrol',
					'rc_log_action' => 'patrol-auto',
				],
				'should omit the given log action'
			],
		];
	}

	/**
	 * @dataProvider providerGetLineOmittance
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordRCFeedFormatter::getLine
	 */
	public function testOmitChanges( array $feed, array $attribs, string $message = '' ) {
		// Provide mandatory parameters if not given
		$attribs = array_replace_recursive( [
			'rc_namespace' => NS_MAIN,
			'rc_title' => 'Test page',
			'rc_type' => RC_EDIT,
		], $attribs );
		MediaWikiServices::initializeParameters( $feed, [
			'style' => 'embed',
			'user_tools' => [],
			'page_tools' => [],
		], [ 'omit_types' => [ RC_CATEGORIZE ] ] );

		$rc = self::makeRecentChange( $attribs );
		$rt = $this->formatter->getLine( $feed, $rc, '' );
		$this->assertNull( $rt, $message );
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
		$testUser = new User();
		$testUser->setName( 'GetDescriptionTestUser' );
		$testUser->addToDatabase();
		$attribs = array_replace_recursive( [
			'rc_minor' => false,
			'rc_bot' => false,
			'rc_user' => $testUser->getId(),
			'rc_namespace' => NS_MAIN,
			'rc_title' => 'Test page',
		], $attribs );
		MediaWikiServices::initializeParameters( $feed, [], [] );

		$rc = self::makeRecentChange( $attribs );
		$this->setContentLang( 'qqx' );
		$rt = $this->wrapper->getDescription( $feed, $rc, '' );
		foreach ( $expected as $key ) {
			$this->assertStringContainsString( $key, $rt, $message );
		}
		$this->assertStringContainsString( 'GetDescriptionTestUser', $rt, $message );
		$this->assertStringContainsString( 'Test page', $rt, $message );
	}
}
