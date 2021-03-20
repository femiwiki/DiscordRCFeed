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
		global $wgDiscordNotificationsDisplay;
		$d = $wgDiscordNotificationsDisplay;
		return [
			[
				[
					'wgDiscordNotificationsDisplay' => array_merge( $d, [ 'user-tools' => false ] ),
					'wgServer' => 'https://foo.bar'
				],
				'Foo',
				'~\[Foo\]\(https://foo\.bar/index\.php/User:Foo\)~'
			],
			[
				[
					'wgDiscordNotificationsDisplay' => array_merge( $d, [ 'user-tools' => false ] ),
					'wgServer' => 'https://foo.bar'
				],
				'Foo&bar',
				'~\[Foo&bar\]\(https://foo\.bar/index\.php/User:Foo%26bar\)~'
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
	 * @covers \MediaWiki\Extension\DiscordNotifications\LinkRenderer::getDiscordUserText
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
	 * @covers \MediaWiki\Extension\DiscordNotifications\LinkRenderer::getDiscordArticleText
	 */
	public function testGetDiscordArticleText() {
		global $wgDiscordNotificationsDisplay;
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

		$this->setMwGlobals( 'wgDiscordNotificationsDisplay',
			array_merge( $wgDiscordNotificationsDisplay, [ 'page-tools' => false ] ) );
		$expected = '[Foo](https://foo.bar/index.php/Foo)';
		$this->assertSame( $expected, LinkRenderer::getDiscordArticleText( $page ) );
		$expected = '[Foo&bar](https://foo.bar/index.php/Foo%26bar)';
		$page = $this->getExistingTestPage( 'foo&bar' );
		$title = $page->getTitle();
		$this->assertSame( $expected, LinkRenderer::getDiscordArticleText( $page ) );
	}
}
