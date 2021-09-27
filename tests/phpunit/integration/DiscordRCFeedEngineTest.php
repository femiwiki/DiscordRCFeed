<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Integration;

use MediaWiki\Extension\DiscordRCFeed\DiscordRCFeedEngine;
use MediaWiki\Extension\DiscordRCFeed\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use RecentChange;

/**
 * @group DiscordRCFeed
 *
 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordRCFeedEngine
 */
class DiscordRCFeedEngineTest extends MediaWikiIntegrationTestCase {

	private static function makeRecentChange( array $attribs ) {
		$rc = new RecentChange;
		$rc->setAttribs( $attribs );
		return $rc;
	}

	public static function providerNotifyOmittance() {
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
	 * @dataProvider providerNotifyOmittance
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordRCFeedEngine::notify
	 */
	public function testOmitChanges( array $feed, array $attribs, string $message = '' ) {
		// Provide mandatory parameters if not given
		$attribs = array_replace_recursive( [
			'rc_namespace' => NS_MAIN,
			'rc_title' => 'Test page',
			'rc_type' => RC_EDIT,
		], $attribs );
		MediaWikiServices::initializeParameters( $feed, [
			'url' => 'https://example.webhook/',
			'style' => 'embed',
			'user_tools' => [],
			'page_tools' => [],
		], [ 'omit_types' => [ RC_CATEGORIZE ] ] );

		$rc = self::makeRecentChange( $attribs );
		$engine = new DiscordRCFeedEngine( $feed );
		$sent = $engine->notify( $rc, '' );
		$this->assertFalse( $sent, $message );
	}
}
