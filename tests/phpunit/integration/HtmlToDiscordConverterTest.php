<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Integration;

use MediaWiki\Extension\DiscordRCFeed\HtmlToDiscordConverter;
use MediaWikiIntegrationTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @group DiscordRCFeed
 *
 * @covers \MediaWiki\Extension\DiscordRCFeed\HtmlToDiscordConverter
 */
class HtmlToDiscordConverterTest extends MediaWikiIntegrationTestCase {

	/** @var TestingAccessWrapper */
	private $wrapper;

	protected function setUp(): void {
		parent::setUp();
		$converter = new HtmlToDiscordConverter();
		$this->wrapper = TestingAccessWrapper::newFromObject( $converter );
	}

	public static function providerUserName(): array {
		return [
			[
				'[Admin](https://foo.bar/index.php/User:Admin)',
				'<a href="/index.php?title=User:Admin&amp;action=view&amp;redlink=1" '
				. 'class="new mw-userlink fw-link" title="User:Admin (page does not exist)"><bdi>Admin</bdi></a>',
				'replaceUserName()',
			],
		];
	}

	/**
	 * @dataProvider providerUserName
	 * @covers \MediaWiki\Extension\DiscordRCFeed\HtmlToDiscordConverter::replaceUserName
	 */
	public function testReplaceUserName( $expected, $params, $message = null ) {
		$this->setMwGlobals( 'wgServer', 'https://foo.bar' );
		$this->assertSame(
			$expected,
			$this->wrapper->replaceUserName( $params ),
			$message
		);
	}

	public static function providerTitle(): array {
		return [
			[
				'[Main Page](https://foo.bar/index.php/Main_Page)',
				'<a href="/w/Main_Page" title="Main Page">Main Page</a>',
				'should replace a title link',
			],
			[
				'[Main Page](https://foo.bar/index.php/Main_Page) and [Main Page](https://foo.bar/index.php/Main_Page)',
				'<a href="/w/Main_Page" title="Main Page">Main Page</a>'
				. ' and <a href="/w/Main_Page" title="Main Page">Main Page</a>',
				'should replace two title links',
			],
		];
	}

	/**
	 * @dataProvider providerTitle
	 * @covers \MediaWiki\Extension\DiscordRCFeed\HtmlToDiscordConverter::replaceTitleLinks
	 */
	public function testReplaceTitleLinks( $expected, $params, $message = null ) {
		$this->setMwGlobals( 'wgServer', 'https://foo.bar' );
		$this->assertSame(
			$expected,
			$this->wrapper->replaceTitleLinks( $params ),
			$message
		);
	}

	public static function providerLinks(): array {
		return [
			[
				'[commented](https://foo.bar/Talk:Wh8294kyt52ohquw)',
				'<a rel="nofollow noreferrer noopener" class="text" '
				. 'href="https://foo.bar/Talk:Wh8294kyt52ohquw">'
				. 'commented</a>',
				'should replace link to wiki page'
			],
			[
				'[Label](https://foo.bar/index.php?query=1&query2=1#fragment)',
				'<a target="_blank" rel="nofollow noreferrer noopener" class="external text" '
				. 'href="https://foo.bar/index.php?query=1&query2=1#fragment">Label</a>',
				'should replace link contains query and fragment'
			]
		];
	}

	/**
	 * @dataProvider providerLinks
	 * @covers \MediaWiki\Extension\DiscordRCFeed\HtmlToDiscordConverter::replaceLinks
	 */
	public function testReplaceLinks( $expected, $params, $message = null ) {
		$this->setMwGlobals( 'wgServer', 'https://foo.bar' );
		$this->assertSame(
			$expected,
			$this->wrapper->replaceLinks( $params ),
			$message
		);
	}
}
