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

	public static function providerDiscordUserText() {
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
	 * @dataProvider providerDiscordUserText
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordLinker::makeUserTextWithTools
	 */
	public function testMakeUserTextWithTools(
		string $expected,
		array $globals,
		array $userTools,
		string $name
	) {
		$globals += [ 'wgServer' => 'https://foo.bar' ];
		$this->setMwGlobals( $globals );
		$linkRenderer = new DiscordLinker( $userTools );
		$user = new User();
		$user->setName( $name );
		$user->addToDatabase();
		$actual = $linkRenderer->makeUserTextWithTools( $user );
		$this->assertSame( $expected, $actual );
	}

	public static function providerDiscordPageText(): array {
		$editPageTool = [
			'query' => 'action=edit',
			'msg' => 'edit'
		];
		return [
			'should be able to disable page tools' => [
				[ 'wgServer' => 'https://foo.bar' ],
				[],
				'Foo',
				'[Foo](https://foo.bar/index.php/Foo)',
			],
			'should urlencode special characters' => [
				[ 'wgServer' => 'https://foo.bar' ],
				[],
				'Foo&bar',
				'[Foo&bar](https://foo.bar/index.php/Foo%26bar)',
			],
			'should render user tools' => [
				[ 'wgServer' => 'https://foo.bar' ],
				[ $editPageTool ],
				'Foo',
				'[Foo](https://foo.bar/index.php/Foo) ([Edit](https://foo.bar/index.php?title=Foo&action=edit))',
			],
			'"view" should be omitted in the block style' => [
				[ 'wgServer' => 'https://foo.bar' ],
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
	public function testMakePageTextWithTools( array $globals, array $pageTools,
		string $titleText, string $expected ) {
		$this->setMwGlobals( $globals );
		$linkRenderer = new DiscordLinker( null, $pageTools );
		$page = $this->getExistingTestPage( $titleText );
		$title = $page->getTitle();

		$this->assertSame(
			$expected,
			$linkRenderer->makePageTextWithTools( $title )
		);
	}

	public static function diffProvider() {
		return [
			'should render diff if the title is not root' => [
				'[Dummy page](http://127.0.0.1:9412/index.php/Dummy_page) '
				. '([diff](http://127.0.0.1:9412/index.php?title=Dummy_page&oldid=11&diff=prev))',
				false,
			],
			'should not render diff if the title is root' => [
				'[Dummy page](http://127.0.0.1:9412/index.php/Dummy_page)',
				true,
			],
		];
	}

	/**
	 * @dataProvider diffProvider
	 * @covers \MediaWiki\Extension\DiscordRCFeed\DiscordLinker::makePageTextWithTools
	 */
	public function testDiffPageTool( $expected, $titleIsRoot ) {
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
