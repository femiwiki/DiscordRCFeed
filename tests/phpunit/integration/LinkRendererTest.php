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
		global $wgDiscordRCFeedDisplay;
		$d = $wgDiscordRCFeedDisplay;
		return [
			[
				[
					'wgDiscordRCFeedDisplay' => array_merge( $d, [ 'user-tools' => false ] ),
					'wgServer' => 'https://foo.bar'
				],
				'Foo',
				'~\[Foo\]\(https://foo\.bar/index\.php/User:Foo\)~'
			],
			[
				[
					'wgDiscordRCFeedDisplay' => array_merge( $d, [ 'user-tools' => false ] ),
					'wgServer' => 'https://foo.bar'
				],
				'Foo&bar',
				'~\[Foo&bar\]\(https://foo\.bar/index\.php/User:Foo%26bar\)~'
			],
			[
				[
					'wgDiscordRCFeedDisplay' => array_merge( $d, [ 'user-tools' => [
						[
							'target' => 'special',
							'special' => 'Block',
							'text' => 'IP Block'
						]
					] ] ),
					'wgServer' => 'https://foo.bar'
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
	public function testGetDiscordUserText( array $globals, string $name, string $regex, string $message = '' ) {
		$this->setMwGlobals( $globals );
		$user = new User();
		$user->setName( $name );
		$user->addToDatabase();
		$this->assertRegExp(
			$regex,
			LinkRenderer::getDiscordUserText( $user ),
			$message
		);
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\LinkRenderer::getDiscordArticleText
	 */
	public function testGetDiscordArticleText() {
		global $wgDiscordRCFeedDisplay;
		$this->setMwGlobals( 'wgServer', 'https://foo.bar' );
		$page = $this->getExistingTestPage( 'foo' );
		$title = $page->getTitle();

		$this->assertSame(
			// phpcs:ignore Generic.Files.LineLength.TooLong
			'[Foo](https://foo.bar/index.php/Foo) ([edit](https://foo.bar/index.php?title=Foo&action=edit) | [delete](https://foo.bar/index.php?title=Foo&action=delete) | [history](https://foo.bar/index.php?title=Foo&action=history) | [diff](https://foo.bar/index.php?title=Foo&diff=prev&oldid=2))',
			LinkRenderer::getDiscordArticleText( $page, 2 )
		);
		// phpcs:ignore Generic.Files.LineLength.TooLong
		$expected = '[Foo](https://foo.bar/index.php/Foo) ([edit](https://foo.bar/index.php?title=Foo&action=edit) | [delete](https://foo.bar/index.php?title=Foo&action=delete) | [history](https://foo.bar/index.php?title=Foo&action=history))';
		$this->assertSame( $expected, LinkRenderer::getDiscordArticleText( $page ) );

		$this->setMwGlobals( 'wgDiscordRCFeedDisplay',
			array_merge( $wgDiscordRCFeedDisplay, [ 'page-tools' => false ] ) );
		$expected = '[Foo](https://foo.bar/index.php/Foo)';
		$this->assertSame( $expected, LinkRenderer::getDiscordArticleText( $page ) );
		$expected = '[Foo&bar](https://foo.bar/index.php/Foo%26bar)';
		$page = $this->getExistingTestPage( 'foo&bar' );
		$title = $page->getTitle();
		$this->assertSame( $expected, LinkRenderer::getDiscordArticleText( $page ) );
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
}
