<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Integration;

use MediaWiki\Extension\DiscordRCFeed\MediaWikiServices;
use MediaWikiUnitTestCase;

/**
 * @group DiscordRCFeed
 *
 * @covers \MediaWiki\Extension\DiscordRCFeed\MediaWikiServices
 */
class MediaWikiServicesTest extends MediaWikiUnitTestCase {

	public static function providerUserName(): array {
		return [
			[
				[
					'omit_namespaces' => [],
					'omit_types' => [],
					'omit_log_types' => [],
					'omit_log_actions' => [],
				],
				[],
				[],
				'should provide must-be-array parameters'
			],
			[
				[
					'omit_namespaces' => [],
					'omit_types' => [ 5 ],
					'omit_log_types' => [],
					'omit_log_actions' => [],
				],
				[
					null,
					[
						'omit_types' => [ 5 ],
					],
				],
				[],
				'should merge when the input is empty'
			],
			[
				[
					'omit_namespaces' => [],
					'omit_types' => [ 0, 5 ],
					'omit_log_types' => [],
					'omit_log_actions' => [],
				],
				[
					null,
					[
						'omit_types' => [ 5 ],
					],
				],
				[
					'omit_types' => [ 0 ],
				],
				'should merge when the input is not empty'
			],
			[
				[
					'omit_namespaces' => [],
					'omit_types' => [],
					'omit_log_types' => [ 'patrol' ],
					'omit_log_actions' => [],
				],
				[
					[
						'omit_log_types' => [ 'patrol' ],
					],
				],
				[],
				'should provide default parameters'
			],
			[
				[
					'omit_namespaces' => [],
					'omit_types' => [],
					'omit_log_types' => [ 'protect' ],
					'omit_log_actions' => [],
				],
				[
					[
						'omit_log_types' => [ 'patrol' ],
					],
				],
				[
					'omit_log_types' => [ 'protect' ],
				],
				'default is ignored if the parameter is given'
			],
			[
				[
					'omit_namespaces' => [],
					'omit_types' => [ 3, 5 ],
					'omit_log_types' => [ 'protect' ],
					'omit_log_actions' => [],
				],
				[
					[
						'omit_log_types' => [ 'patrol' ],
					],
					[
						'omit_types' => [ 5 ],
					],
				],
				[
					'omit_types' => [ 3 ],
					'omit_log_types' => [ 'protect' ],
				],
				'replacement and merging should be done in the same time'
			],
		];
	}

	/**
	 * @dataProvider providerUserName
	 * @covers \MediaWiki\Extension\DiscordRCFeed\MediaWikiServices::onMediaWikiServices
	 */
	public function testConvertUserName( $expected, $extraCallParams, $inputFeed, $message = '' ) {
		MediaWikiServices::initializeParameters( $inputFeed, ...$extraCallParams );
		ksort( $expected );
		ksort( $inputFeed );
		$this->assertSame(
			$expected,
			$inputFeed,
			$message
		);
	}
}
