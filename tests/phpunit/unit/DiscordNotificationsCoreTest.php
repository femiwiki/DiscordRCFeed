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
	 * @covers \DiscordNotificationsCore::parseUrl
	 */
	public function testParseUrl() {
		$this->assertSame(
			'https://example.com/wiki/title=Foo%20%28bar%29',
			$this->wrapper->parseUrl( 'https://example.com/wiki/title=Foo (bar)' )
		);
	}
}
