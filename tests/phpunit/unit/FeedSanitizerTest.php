<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Unit;

use MediaWiki\Extension\DiscordRCFeed\FeedSanitizer;
use MediaWikiUnitTestCase;

/**
 * @group DiscordRCFeed
 *
 * @covers \MediaWiki\Extension\DiscordRCFeed\FeedSanitizer
 */
class FeedSanitizerTest extends MediaWikiUnitTestCase {

	public static function providerUserName(): array {
		return [
			'should provide must-be-array parameters' => [
				[
					'omit_namespaces' => [],
					'omit_types' => [],
				],
				[
					[],
					null,
					null,
					[
						'omit_namespaces',
						'omit_types',
					],
				],
			],
			'should merge when the input is empty' => [
				[
					'omit_namespaces' => [],
					'omit_types' => [ 5 ],
				],
				[
					[],
					null,
					[
						'omit_types' => [ 5 ],
					],
					[
						'omit_namespaces',
						'omit_types',
					],
				],
			],
			'should merge when the input is not empty' => [
				[
					'omit_namespaces' => [],
					'omit_types' => [ 0, 5 ],
				],
				[
					[
						'omit_types' => [ 0 ],
					],
					null,
					[
						'omit_types' => [ 5 ],
					],
					[
						'omit_namespaces',
						'omit_types',
					],
				],
			],
			'should provide default parameters' => [
				[
					'omit_log_types' => [ 'patrol' ],
					'omit_log_actions' => [],
				],
				[
					[],
					[
						'omit_log_types' => [ 'patrol' ],
					],
					null,
					[
						'omit_log_types',
						'omit_log_actions',
					],
				],
			],
			'default is ignored if the parameter is given' => [
				[
					'omit_log_types' => [ 'protect' ],
					'omit_log_actions' => [],
				],
				[
					[
						'omit_log_types' => [ 'protect' ],
					],
					[
						'omit_log_types' => [ 'patrol' ],
					],
					null,
					[
						'omit_log_types',
						'omit_log_actions',
					]
				],
			],
			'replacement and merging should be done in the same time' => [
				[
					'omit_namespaces' => [],
					'omit_types' => [ 3, 5 ],
					'omit_log_types' => [ 'protect' ],
					'omit_log_actions' => [],
				],
				[
					[
						'omit_types' => [ 3 ],
						'omit_log_types' => [ 'protect' ],
					],
					[
						'omit_log_types' => [ 'patrol' ],
					],
					[
						'omit_types' => [ 5 ],
					],
					[
						'omit_namespaces',
						'omit_types',
						'omit_log_types',
						'omit_log_actions',
					],
				],
			],
		];
	}

	/**
	 * @dataProvider providerUserName
	 * @covers \MediaWiki\Extension\DiscordRCFeed\FeedSanitizer::onMediaWikiServices
	 */
	public function testConvertUserName( $expected, $params ) {
		FeedSanitizer::initializeParameters( ...$params );
		ksort( $expected );
		ksort( $params[0] );
		$this->assertSame(
			$expected,
			$params[0]
		);
	}
}
