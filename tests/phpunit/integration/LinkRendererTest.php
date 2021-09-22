<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Integration;

use MediaWiki\Extension\DiscordRCFeed\LinkRenderer;
use MediaWikiIntegrationTestCase;
use User;

/**
 * @group DiscordRCFeed
 * @group Database
 *
 * @covers \MediaWiki\Extension\DiscordRCFeed\LinkRenderer
 */
class LinkRendererTest extends MediaWikiIntegrationTestCase {

	public static function providerDiscordUserText() {
		return [
			[
				[
					'wgServer' => 'https://foo.bar'
				],
				false,
				'Foo',
				'~\[Foo\]\(https://foo\.bar/index\.php/User:Foo\)~'
			],
			[
				[
					'wgServer' => 'https://foo.bar'
				],
				false,
				'Foo&bar',
				'~\[Foo&bar\]\(https://foo\.bar/index\.php/User:Foo%26bar\)~'
			],
			[
				[
					'wgServer' => 'https://foo.bar'
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
				'~\[Foo\]\(https://foo\.bar/index\.php(\?title=|/)User:Foo\) \(\[IP Block\]\(https://foo\.bar/index\.php(\?title=|/)Special:Block/Foo\)\)~'
			],
			[
				[
					'wgLanguageCode' => 'es',
					'wgServer' => 'https://foo.bar'
				],
				'Foo',
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'~\[Foo\]\(https://foo\.bar/index\.php(\?title=|/)Usuario:Foo\) \(\[bloquear\]\(https://foo\.bar/index\.php(\?title=|/)Especial:Bloquear/Foo\) \| \[grupos\]\(https://foo\.bar/index\.php(\?title=|/)Especial(%3A|:)PermisosUsuarios(&user=|/)Foo\) \| \[discusión\]\(https://foo\.bar/index\.php(\?title=|/)Usuario_discusi%C3%B3n:Foo\) \| \[contribuciones\]\(https://foo\.bar/index\.php(\?title=|/)Especial:Contribuciones/Foo\)\)~'
			]
		];
	}

	/**
	 * @dataProvider providerDiscordUserText
	 * @covers \MediaWiki\Extension\DiscordRCFeed\LinkRenderer::getDiscordUserText
	 */
	public function testGetDiscordUserText( array $globals, array $userTools, string $name, string $regex, string $message = '' ) {
		$this->setMwGlobals( $globals );
		$linkRenderer = new LinkRenderer( $userTools );
		$user = new User();
		$user->setName( $name );
		$user->addToDatabase();
		$this->assertRegExp(
			$regex,
			$linkRenderer->getDiscordUserText( $user ),
			$message
		);
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\LinkRenderer::getDiscordArticleText
	 */
	public function testGetDiscordArticleText() {
		$this->setMwGlobals( 'wgServer', 'https://foo.bar' );
		$pageTools =
		[
			[
				'query' => 'action=edit',
				'msg' => 'edit'
			],
		];
		$linkRenderer = new LinkRenderer( null, $pageTools );
		$page = $this->getExistingTestPage( 'foo' );
		$title = $page->getTitle();

		$this->assertSame(
			// phpcs:ignore Generic.Files.LineLength.TooLong
			'[Foo](https://foo.bar/index.php/Foo) ([edit](https://foo.bar/index.php?title=Foo&action=edit))',
			$linkRenderer->getDiscordArticleText( $page, 2 )
		);
		// phpcs:ignore Generic.Files.LineLength.TooLong
		$expected = '[Foo](https://foo.bar/index.php/Foo) ([edit](https://foo.bar/index.php?title=Foo&action=edit))';
		$this->assertSame( $expected, $linkRenderer->getDiscordArticleText( $page ) );

		$this->setMwGlobals( 'wgDiscordRCFeedDisplay',
			array_merge( $wgDiscordRCFeedDisplay, [ 'page-tools' => false ] ) );
		$expected = '[Foo](https://foo.bar/index.php/Foo)';
		$this->assertSame( $expected, $linkRenderer->getDiscordArticleText( $page ) );
		$expected = '[Foo&bar](https://foo.bar/index.php/Foo%26bar)';
		$page = $this->getExistingTestPage( 'foo&bar' );
		$title = $page->getTitle();
		$this->assertSame( $expected, $linkRenderer->getDiscordArticleText( $page ) );
	}

	public static function providerTools() {
		return [
			[ 'edit', '(edit)' ],
			[ [ 'edit', 'block' ], '(edit | block)' ],
		];
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\LinkRenderer::makeNiceTools
	 * @param string|array $tools
	 * @param string $expected
	 * @dataProvider providerTools
	 */
	public function testMakeNiceTools( $tools, $expected ) {
		$this->assertSame( $expected, LinkRenderer::makeNiceTools( $tools ) );
	}

	public static function providerWikitextWithLinks() {
		return [
			[ 'A edited [[B]]', 'A edited [B](https://foo.bar/index.php/B)' ],
			[ 'A moved [[B]] to [[C]]', 'A moved [B](https://foo.bar/index.php/B) to [C](https://foo.bar/index.php/C)' ],
		];
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\LinkRenderer::makeLinksClickable
	 * @param string $wt
	 * @param string $expected
	 * @dataProvider providerWikitextWithLinks
	 */
	public function testMakeLinksClickable( $wt, $expected ) {
		$this->setMwGlobals( 'wgServer', 'https://foo.bar' );
		$renderer = new LinkRenderer();
		$actual = $renderer->makeLinksClickable( $wt );
		$this->assertSame( $expected, $actual );
	}
}
