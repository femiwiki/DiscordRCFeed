<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Integration;

use MediaWiki\Extension\DiscordRCFeed\DiscordLinker;
use MediaWikiIntegrationTestCase;
use User;
use Wikimedia\TestingAccessWrapper;

/**
 * @group DiscordRCFeed
 * @group Database
 *
 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordLinker
 */
class DiscordLinkerTest extends MediaWikiIntegrationTestCase {

	/** @var TestingAccessWrapper */
	private $wrapper;

	protected function setUp(): void {
		parent::setUp();
		$renderer = new DiscordLinker();
		$this->wrapper = TestingAccessWrapper::newFromObject( $renderer );
	}

	public static function providerDiscordUserText() {
		return [
			[
				[ 'wgServer' => 'https://foo.bar' ],
				[],
				'Foo',
				'~\[Foo\]\(https://foo\.bar/index\.php/User:Foo\)~'
			],
			[
				[ 'wgServer' => 'https://foo.bar' ],
				[],
				'Foo&bar',
				'~\[Foo&bar\]\(https://foo\.bar/index\.php/User:Foo%26bar\)~'
			],
			[
				[ 'wgServer' => 'https://foo.bar' ],
				[
					[
						'target' => 'special',
						'special' => 'Block',
						'text' => 'IP Block'
					]
				],
				'Foo',
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'~\[Foo\]\(https://foo\.bar/index\.php(\?title=|/)User:Foo\) \(\[IP Block\]\(https://foo\.bar/index\.php(\?title=|/)Special:Block/Foo\)\)~'
			],
			[
				[
					'wgLanguageCode' => 'es',
					'wgServer' => 'https://foo.bar',
				],
				[
					[
						'target' => 'special',
						'special' => 'Block',
						'text' => 'IP Block'
					]
				],
				'Foo',
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'~\[Foo\]\(https://foo\.bar/index\.php(\?title=|/)Usuario:Foo\) \(\[IP Block\]\(https://foo\.bar/index\.php(\?title=|/)Especial:Bloquear/Foo\)\)~'
			]
		];
	}

	/**
	 * @dataProvider providerDiscordUserText
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordLinker::makeUserTextWithTools
	 */
	public function testMakeUserTextWithTools( array $globals, array $userTools, string $name, string $regex,
		string $message = '' ) {
		$this->setMwGlobals( $globals );
		$linkRenderer = new DiscordLinker( $userTools );
		$user = new User();
		$user->setName( $name );
		$user->addToDatabase();
		$this->assertRegExp(
			$regex,
			$linkRenderer->makeUserTextWithTools( $user ),
			$message
		);
	}

	public static function providerDiscordPageText(): array {
		$editPageTool = [
			'query' => 'action=edit',
			'msg' => 'edit'
		];
		return [
			[
				[ 'wgServer' => 'https://foo.bar' ],
				[],
				'Foo',
				[],
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'[Foo](https://foo.bar/index.php/Foo)'
			],
			[
				[ 'wgServer' => 'https://foo.bar' ],
				[],
				'Foo&bar',
				[],
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'[Foo&bar](https://foo.bar/index.php/Foo%26bar)'
			],
			[
				[ 'wgServer' => 'https://foo.bar' ],
				[ $editPageTool ],
				'Foo',
				[],
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'[Foo](https://foo.bar/index.php/Foo) ([Edit](https://foo.bar/index.php?title=Foo&action=edit))'
			],
			[
				[ 'wgServer' => 'https://foo.bar' ],
				[ $editPageTool ],
				'Foo',
				[ 2, 1 ],
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'[Foo](https://foo.bar/index.php/Foo) ([Edit](https://foo.bar/index.php?title=Foo&action=edit) | [diff](https://foo.bar/index.php?title=Foo&diff=2&oldid=1))'
			],
		];
	}

	/**
	 * @dataProvider providerDiscordPageText
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordLinker::makePageTextWithTools
	 */
	public function testMakePageTextWithTools( array $globals, array $pageTools, string $titleText,
		array $params, string $expected ) {
		$this->setMwGlobals( $globals );
		$linkRenderer = new DiscordLinker( null, $pageTools );
		$page = $this->getExistingTestPage( $titleText );
		$title = $page->getTitle();

		$this->assertSame(
			$expected,
			$linkRenderer->makePageTextWithTools( $title, ...$params )
		);
	}

	public static function providerTools(): array {
		return [
			[ [ 'edit' ], '(edit)' ],
			[ [ 'edit', 'block' ], '(edit | block)' ],
		];
	}
}
