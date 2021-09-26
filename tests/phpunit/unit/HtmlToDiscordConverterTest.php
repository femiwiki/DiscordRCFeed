<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Unit;

use MediaWiki\Extension\DiscordRCFeed\HtmlToDiscordConverter;
use MediaWikiUnitTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @group DiscordRCFeed
 *
 * @covers \MediaWiki\Extension\DiscordRCFeed\HtmlToDiscordConverter
 */
class HtmlToDiscordConverterTest extends MediaWikiUnitTestCase {

	/** @var TestingAccessWrapper */
	private $wrapper;

	protected function setUp(): void {
		parent::setUp();
		$converter = new HtmlToDiscordConverter();
		$this->wrapper = TestingAccessWrapper::newFromObject( $converter );
	}

	public static function providerUserTools(): array {
		return [
			[
				'START-END',
				'START-<span class="mw-usertoollinks">( '
				. '<a href="/index.php?title=User_talk:Admin&amp;action=view&amp;redlink=1" '
				. 'class="new mw-usertoollinks-talk fw-link" title="User talk:Admin (page does not exist)">talk</a> | '
				. '<a href="/w/Special:Contributions/Admin" class="mw-usertoollinks-contribs fw-link" '
				. 'title="Special:Contributions/Admin">contribs</a> | <a href="/w/Special:Block/Admin" '
				. 'class="mw-usertoollinks-block fw-link" title="Special:Block/Admin">block</a>)</span>END',
				'removeUserTools'
			],
		];
	}

	/**
	 * @dataProvider providerUserTools
	 * @covers \MediaWiki\Extension\DiscordRCFeed\HtmlToDiscordConverter::removeUserTools
	 */
	public function testRemoveUserTools( $expected, $params, $message = '' ) {
		$this->assertSame(
			$expected,
			$this->wrapper->removeUserTools( $params ),
			$message
		);
	}
}
