<?php

namespace MediaWiki\Extension\DiscordNotifications\Tests\Unit;

use DiscordNotificationsCore;
use MediaWikiUnitTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @group DiscordNotifications
 *
 * @covers \DiscordNotificationsCore
 */
class DiscordNotificationsCoreTest extends MediaWikiUnitTestCase {

	/** @var TestingAccessWrapper */
	private $wrapper;

	protected function setUp() : void {
		parent::setUp();
		$this->wrapper = TestingAccessWrapper::newFromClass( DiscordNotificationsCore::class );
	}

	/**
	 * @covers \DiscordNotificationsCore::parseurl
	 */
	public function testParseurl() {
		$this->assertSame(
			'https://example.com/wiki/title=Foo%20%28bar%29',
			$this->wrapper->parseurl( 'https://example.com/wiki/title=Foo (bar)' )
		);
	}

	/**
	 * @covers \DiscordNotificationsCore::getDiscordUserText
	 */
	public function testGetDiscordUserText() {
		$w = TestingAccessWrapper::newFromClass( DiscordNotificationsCore::class );
		$this->assertSame(
			'<Foo|Foo>',
			$w->getDiscordUserText( 'Foo' )
		);
	}
}
