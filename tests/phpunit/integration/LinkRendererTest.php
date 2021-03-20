<?php

namespace MediaWiki\Extension\DiscordNotifications\Tests\Integration;

use MediaWiki\Extension\DiscordNotifications\LinkRenderer;
use MediaWikiIntegrationTestCase;
use User;

/**
 * @group DiscordNotifications
 * @group Database
 *
 * @covers \MediaWiki\Extension\DiscordNotifications\LinkRenderer
 */
class LinkRendererTest extends MediaWikiIntegrationTestCase {

	public static function providerDiscordUserText() {
		return [
			[
				[
					'DiscordIncludeUserUrls' => false,
					'Server' => 'https://foo.bar'
				],
				'Foo',
				'~<https://foo\.bar/index\.php/User:Foo\|Foo>~'
			],
			[
				[
					'DiscordIncludeUserUrls' => false,
					'Server' => 'https://foo.bar'
				],
				'Foo&bar',
				'~<https://foo\.bar/index\.php/User:Foo%26bar\|Foo&bar>~'
			],
			[
				[
					'LanguageCode' => 'es',
					'Server' => 'https://foo.bar'
				],
				'Foo',
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'~<https://foo\.bar/index\.php(\?title=|/)Usuario:Foo\|Foo> \(<https://foo\.bar/index\.php(\?title=|/)Especial:Bloquear/Foo\|bloquear> \| <https://foo\.bar/index\.php(\?title=|/)Especial(%3A|:)PermisosUsuarios(&user=|/)Foo\|grupos> \| <https://foo\.bar/index\.php(\?title=|/)Usuario_discusi%C3%B3n:Foo\|discusión> \| <https://foo\.bar/index\.php(\?title=|/)Especial:Contribuciones/Foo\|contribuciones>\)~'
			]
		];
	}

	/**
	 * @dataProvider providerDiscordUserText
	 * @covers \MediaWiki\Extension\DiscordNotifications\LinkRenderer::getDiscordUserText
	 */
	public function testGetDiscordUserText( array $globals, string $name, string $regex, string $message = '' ) {
		foreach ( $globals as $key => $val ) {
			$this->setMwGlobals( "wg$key", $val );
		}
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
	 * @covers \MediaWiki\Extension\DiscordNotifications\LinkRenderer::getDiscordArticleText
	 */
	public function testGetDiscordArticleText() {
		$this->setMwGlobals( 'wgServer', 'https://foo.bar' );
		$page = $this->getExistingTestPage( 'foo' );
		$title = $page->getTitle();

		$this->assertSame(
			// phpcs:ignore Generic.Files.LineLength.TooLong
			'<https://foo.bar/index.php/Foo|Foo> (<https://foo.bar/index.php?title=Foo&action=edit|edit> | <https://foo.bar/index.php?title=Foo&action=delete|delete> | <https://foo.bar/index.php?title=Foo&action=history|history> | <https://foo.bar/index.php?title=Foo&diff=prev&oldid=2|diff>)',
			LinkRenderer::getDiscordArticleText( $page, 2 )
		);
		// phpcs:ignore Generic.Files.LineLength.TooLong
		$expected = '<https://foo.bar/index.php/Foo|Foo> (<https://foo.bar/index.php?title=Foo&action=edit|edit> | <https://foo.bar/index.php?title=Foo&action=delete|delete> | <https://foo.bar/index.php?title=Foo&action=history|history>)';
		$this->assertSame( $expected, LinkRenderer::getDiscordArticleText( $page ) );

		$this->setMwGlobals( 'wgDiscordIncludePageUrls', false );
		$expected = '<https://foo.bar/index.php/Foo|Foo>';
		$this->assertSame( $expected, LinkRenderer::getDiscordArticleText( $page ) );
		$expected = '<https://foo.bar/index.php/Foo%26bar|Foo&bar>';
		$page = $this->getExistingTestPage( 'foo&bar' );
		$title = $page->getTitle();
		$this->assertSame( $expected, LinkRenderer::getDiscordArticleText( $page ) );
	}
}
