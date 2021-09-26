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
				'[Foo](https://foo.bar/index.php/User:Foo)',
				'should be able to disable user tools'
			],
			[
				[ 'wgServer' => 'https://foo.bar' ],
				[],
				'Foo&bar',
				'[Foo&bar](https://foo.bar/index.php/User:Foo%26bar)',
				'should urlencode special characters'
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
				'[Foo](https://foo.bar/index.php/User:Foo) '
				. '([IP Block](https://foo.bar/index.php/Special:Block/Foo))',
				'should render user tools'
			],
			[
				[ 'wgServer' => 'https://foo.bar' ],
				[
					[
						'target' => 'talk',
						'text' => 'Talk'
					]
				],
				'Foo',
				'[Foo](https://foo.bar/index.php/User:Foo) '
				. '([Talk](https://foo.bar/index.php/User_talk:Foo))',
				'target should be able to set to "talk"'
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
				'[Foo](https://foo.bar/index.php/Usuario:Foo) '
				. '([IP Block](https://foo.bar/index.php/Especial:Bloquear/Foo))',
				'User tools should be in the content language'
			]
		];
	}

	/**
	 * @dataProvider providerDiscordUserText
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordLinker::makeUserTextWithTools
	 */
	public function testMakeUserTextWithTools( array $globals, array $userTools, string $name,
		string $regex, string $message = '' ) {
		$this->setMwGlobals( $globals );
		$linkRenderer = new DiscordLinker( $userTools );
		$user = new User();
		$user->setName( $name );
		$user->addToDatabase();
		$this->assertSame(
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
				'[Foo](https://foo.bar/index.php/Foo)',
				'should be able to disable page tools'
			],
			[
				[ 'wgServer' => 'https://foo.bar' ],
				[],
				'Foo&bar',
				[],
				'[Foo&bar](https://foo.bar/index.php/Foo%26bar)',
				'should urlencode special characters'
			],
			[
				[ 'wgServer' => 'https://foo.bar' ],
				[ $editPageTool ],
				'Foo',
				[],
				'[Foo](https://foo.bar/index.php/Foo) ([Edit](https://foo.bar/index.php?title=Foo&action=edit))',
				'should render user tools'
			],
			[
				[ 'wgServer' => 'https://foo.bar' ],
				[ $editPageTool ],
				'Foo',
				[ 2, 1 ],
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'[Foo](https://foo.bar/index.php/Foo) ([Edit](https://foo.bar/index.php?title=Foo&action=edit) | [diff](https://foo.bar/index.php?title=Foo&diff=2&oldid=1))',
				'should render "Diff" if revision ids are given'
			],
		];
	}

	/**
	 * @dataProvider providerDiscordPageText
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordLinker::makePageTextWithTools
	 */
	public function testMakePageTextWithTools( array $globals, array $pageTools,
		string $titleText, array $params, string $expected, $message = '' ) {
		$this->setMwGlobals( $globals );
		$linkRenderer = new DiscordLinker( null, $pageTools );
		$page = $this->getExistingTestPage( $titleText );
		$title = $page->getTitle();

		$this->assertSame(
			$expected,
			$linkRenderer->makePageTextWithTools( $title, ...$params ),
			$message
		);
	}

	public static function providerTools(): array {
		return [
			[ [ 'edit' ], '(edit)' ],
			[ [ 'edit', 'block' ], '(edit | block)' ],
		];
	}
}
