<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Integration;

use MediaWiki\Extension\DiscordRCFeed\DiscordLinker;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWikiIntegrationTestCase;
use Title;
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

	public static function providerUserText() {
		return [
			'should disable user tools' => [
				'[Foo](https://foo.bar/index.php/User:Foo)',
				[],
				[],
				'Foo',
			],
			'should urlencode special characters' => [
				'[Foo&bar](https://foo.bar/index.php/User:Foo%26bar)',
				[],
				[],
				'Foo&bar',
			],
			'should render user tools' => [
				'[Foo](https://foo.bar/index.php/User:Foo) '
				. '([IP Block](https://foo.bar/index.php/Special:Block/Foo))',
				[],
				[
					[
						'target' => 'special',
						'special' => 'Block',
						'text' => 'IP Block'
					]
				],
				'Foo',
			],
			'"talk" target should be shown' => [
				'[Foo](https://foo.bar/index.php/User:Foo) '
				. '([Talk](https://foo.bar/index.php/User_talk:Foo))',
				[],
				[
					[
						'target' => 'talk',
						'text' => 'Talk'
					]
				],
				'Foo',
			],
			'"user_page" should be omitted in the block style' => [
				'[Foo](https://foo.bar/index.php/User:Foo)',
				[],
				[
					[
						'target' => 'user_page',
						'text' => 'User page'
					]
				],
				'Foo',
			],
			'User tools should be in the content language' => [
				'[Foo](https://foo.bar/index.php/Usuario:Foo) '
				. '([IP Block](https://foo.bar/index.php/Especial:Bloquear/Foo))',
				[ 'wgLanguageCode' => 'es' ],
				[
					[
						'target' => 'special',
						'special' => 'Block',
						'text' => 'IP Block'
					]
				],
				'Foo',
			]
		];
	}

	/**
	 * @dataProvider providerUserText
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordLinker::makeUserTextWithTools
	 */
	public function testMakeUserTextWithTools(
		string $expected,
		array $globals,
		array $userTools,
		string $name
	) {
		$globals += [
			'wgServer' => 'https://foo.bar',
			'wgArticlePath' => '/index.php/$1',
		];
		$this->setMwGlobals( $globals );
		$linkRenderer = new DiscordLinker( $userTools );
		$user = new User();
		$user->setName( $name );
		$user->addToDatabase();
		$actual = $linkRenderer->makeUserTextWithTools( $user );
		$this->assertSame( $expected, $actual );
	}

	public static function providerUserTools() {
		return [
			'should render link to user page' => [
				'[User page](https://foo.bar/index.php/User:Foo)',
				[
					[
						'target' => 'user_page',
						'text' => 'User page'
					]
				],
				'Foo',
			],
		];
	}

	/**
	 * @dataProvider providerUserTools
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordLinker::makeUserTools
	 */
	public function testMakeUserTools(
		string $expected,
		array $userTools,
		string $name
	) {
		$this->setMwGlobals( [
			'wgServer' => 'https://foo.bar',
			'wgArticlePath' => '/index.php/$1',
		] );
		$linkRenderer = new DiscordLinker( $userTools );
		$user = new User();
		$user->setName( $name );
		$user->addToDatabase();
		$actual = $linkRenderer->makeUserTools( $user, ' ', true );
		$this->assertSame( $expected, $actual );
	}

	public static function providerDiscordPageText(): array {
		$editPageTool = [
			'query' => 'action=edit',
			'msg' => 'edit'
		];
		$deletePageTool = [
			'query' => 'action=delete',
			'msg' => 'delete'
		];
		return [
			'should be able to disable page tools' => [
				[],
				'Foo',
				'[Foo](https://foo.bar/index.php/Foo)',
			],
			'should urlencode special characters' => [
				[],
				'Foo&bar',
				'[Foo&bar](https://foo.bar/index.php/Foo%26bar)',
			],
			'should render user tools' => [
				[ $editPageTool ],
				'Foo',
				'[Foo](https://foo.bar/index.php/Foo) ([Edit](https://foo.bar/index.php?title=Foo&action=edit))',
			],
			'"view" should be omitted in the block style' => [
				[
					[
						'target' => 'view',
						'text' => 'view'
					]
				],
				'Foo',
				'[Foo](https://foo.bar/index.php/Foo)',
			],
		];
	}

	/**
	 * @dataProvider providerDiscordPageText
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordLinker::makePageTextWithTools
	 */
	public function testMakePageTextWithTools( array $pageTools,
		string $titleText, string $expected ) {
		$this->setMwGlobals( [
			'wgServer' => 'https://foo.bar',
			'wgArticlePath' => '/index.php/$1',
			'wgScript' => '/index.php'
		] );
		$linkRenderer = new DiscordLinker( null, $pageTools );
		$page = $this->getExistingTestPage( $titleText );
		$title = $page->getTitle();

		$this->assertSame(
			$expected,
			$linkRenderer->makePageTextWithTools( $title )
		);
	}

	public static function providerPageTools(): array {
		$tools = [
			[
				'query' => 'action=edit',
				'text' => 'edit'
			],
			[
				'query' => 'action=delete',
				'text' => 'delete'
			],
			[
				'query' => 'action=history',
				'text' => 'hist'
			],
		];
		return [
			'all tools should be shown in the structured style' => [
				'[edit](https://foo.bar/index.php?title=Foo&action=edit)' . PHP_EOL
				. '[delete](https://foo.bar/index.php?title=Foo&action=delete)' . PHP_EOL
				. '[hist](https://foo.bar/index.php?title=Foo&action=history)',
				$tools,
				'Foo',
				[ PHP_EOL, true ],
			],
			'all tools should be shown in the embed style' => [
				'[edit](https://foo.bar/index.php?title=Foo&action=edit) | '
				. '[delete](https://foo.bar/index.php?title=Foo&action=delete) | '
				. '[hist](https://foo.bar/index.php?title=Foo&action=history)',
				$tools,
				'Foo',
				[],
			],
		];
	}

	/**
	 * @dataProvider providerPageTools
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordLinker::makePageTools
	 */
	public function testMakePageTools(
		string $expected,
		array $pageTools,
		string $titleText,
		array $params
	) {
		$this->setMwGlobals( [
			'wgServer' => 'https://foo.bar',
			'wgArticlePath' => '/index.php/$1',
			'wgScript' => '/index.php'
		] );
		$linkRenderer = new DiscordLinker( null, $pageTools );
		$page = $this->getExistingTestPage( $titleText );
		$title = $page->getTitle();

		$this->assertSame(
			$expected,
			$linkRenderer->makePageTools( $title, ...$params )
		);
	}

	public static function diffProvider() {
		return [
			'should render diff if the title is not root' => [
				'[Dummy page](https://foo.bar/index.php/Dummy_page) '
				. '([diff](https://foo.bar/index.php?title=Dummy_page&oldid=11&diff=prev))',
				false,
			],
			'should not render diff if the title is root' => [
				'[Dummy page](https://foo.bar/index.php/Dummy_page)',
				true,
			],
		];
	}

	/**
	 * @dataProvider diffProvider
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordLinker::makePageTextWithTools
	 */
	public function testDiffPageTool( $expected, $titleIsRoot ) {
		$this->setMwGlobals( [
			'wgServer' => 'https://foo.bar',
			'wgArticlePath' => '/index.php/$1',
			'wgScript' => '/index.php'
		] );
		$diffTool = [
			'target' => 'diff',
			'text' => 'diff',
		];
		$linkRenderer = new DiscordLinker( null, [ $diffTool ] );

		$title = $this->mockTitle( $titleIsRoot );
		$actual = $linkRenderer->makePageTextWithTools( $title );
		$this->assertSame( $expected, $actual );
	}

	public function mockTitle( $isRoot ): Title {
		$title = $this->getExistingTestPage( 'Dummy page' )->getTitle();

		$revision = $this->createMock( RevisionRecord::class );
		$revision->expects( $this->any() )
			->method( 'getParentId' )
			->will( $this->returnValue( $isRoot ? null : 10 ) );
		$revision->expects( $this->any() )
			->method( 'getId' )
			->will( $this->returnValue( 11 ) );

		$store = $this->createMock( RevisionStore::class );
		$store->expects( $this->any() )
			->method( 'getRevisionByTitle' )
			->will( $this->returnValue( $revision ) );
		$this->setService( 'RevisionStore', $store );

		return $title;
	}
}
