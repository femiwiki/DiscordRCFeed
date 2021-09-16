<?php

namespace MediaWiki\Extension\DiscordNotifications\Tests\Unit;

use HashConfig;
use MediaWiki\Extension\DiscordNotifications\Core;
use MediaWiki\Extension\DiscordNotifications\Hooks;
use MediaWiki\Revision\RevisionRecord;
use MediaWikiIntegrationTestCase;
use Title;
use User;
use WikiPage;

/**
 * @group DiscordNotifications
 *
 * @covers \MediaWiki\Extension\DiscordNotifications\Hooks
 */
class HooksTest extends MediaWikiIntegrationTestCase {
	/**
	 * @param string $eMessage
	 * @param User $eUser
	 * @param string $eAction
	 * @return Hooks $hook
	 */
	private function expectPushDiscordNotification( $eMessage, $eUser, $eAction ) {
		$coreMock = $this->createMock( Core::class );
		$coreMock
			->expects( $this->any() )
			->method( 'pushDiscordNotify' )
			->with( $eMessage, $eUser, $eAction );
		$hooks = new Hooks( new HashConfig(), $this->getServiceContainer()->getUserFactory() );
		$hooks->setCore( $coreMock );
		return $hooks;
	}

	/**
	 * @param int $size
	 * @return RevisionRecode
	 */
	private function createRevisionRecodeMock( $size ) {
		$mock = $this->createMock( RevisionRecord::class );
		$mock->expects( $this->any() )
			->method( 'getSize' )
			->will( $this->returnValue( $size ) );
		return $mock;
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordNotifications\Hooks::onPageSaveComplete
	 */
	public function testOnPageSaveComplete() {
		$this->setMwGlobals( [
			'wgServer' => '',
			'wgDiscordNotificationsDisplay' => [ 'user-tools' => [] ]
		] );

		$hooks = $this->expectPushDiscordNotification(
			'📄 [TestUser]() has created article [Test Title]() Summary: test summary (10 bytes)',
			User::newFromName( 'TestUser' ),
			'article_inserted'
		);
		$this->assertTrue( $hooks->onPageSaveComplete(
			WikiPage::factory( Title::newFromText( 'Test Title' ) ),
			User::newFromName( 'TestUser' ),
			'test summary',
			EDIT_NEW,
			$this->createRevisionRecodeMock( 10 ),
			null
		) );

		// Summary should not be parsed
		$hooks = $this->expectPushDiscordNotification(
			"📄 [TestUser]() has created article [Test Title]() Summary: ''[[link]]'', {{transclusion}} (10 bytes)",
			User::newFromName( 'TestUser' ),
			'article_inserted'
		);
		$this->assertTrue( $hooks->onPageSaveComplete(
			WikiPage::factory( Title::newFromText( 'Test Title' ) ),
			User::newFromName( 'TestUser' ),
			"''[[link]]'', {{transclusion}}",
			EDIT_NEW,
			$this->createRevisionRecodeMock( 10 ),
			null
		) );
	}
}
