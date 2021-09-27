<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Integration;

use MediaWiki\Extension\DiscordRCFeed\Util;
use MediaWikiIntegrationTestCase;
use MessageCache;
use RequestContext;

/**
 * @group DiscordRCFeed
 *
 * @covers \MediaWiki\Extension\DiscordRCFeed\Util
 */
class UtilTest extends MediaWikiIntegrationTestCase {

	public static function providerUrls(): array {
		return [
			[
				true,
				'/index.php/Title',
			],
			[
				true,
				'/index.php/Title',
				'https://example.com',
			],
			[
				true,
				'https://example.com/index.php/Title',
				'https://example.com',
			],
			[
				false,
				'https://example.com/index.php',
				'http://example.com',
			],
			[
				false,
				'https://example.com/index.php',
				'https://foo.com',
			],
		];
	}

	/**
	 * @dataProvider providerUrls
	 * @covers \MediaWiki\Extension\DiscordRCFeed\Util::urlIsLocal
	 */
	public function testUrlIsLocal( $expected, $url, $server = '', $message = '' ) {
		if ( $server ) {
			$this->setMwGlobals( 'wgServer', $server );
		}
		$this->assertEquals(
			$expected,
			Util::urlIsLocal( $url ),
			$message
		);
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\Util::getContentLanguageContext
	 */
	public function testGetContentLanguageContext() {
		$ctx = Util::getContentLanguageContext();
		$this->assertInstanceOf( RequestContext::class, $ctx );
	}

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\Util::msgText
	 */
	public function testConvertUserName() {
		$msgMap = [
			'en' => [
				'test-prefix-block-block' => 'A blocked B.',
			],
			'ko' => [
				'test-prefix-block-block' => 'A가 B를 차단했습니다.',
			],
		];
		$mock = $this->createMock( MessageCache::class );
		$mock->method( 'get' )
			->will( $this->returnCallback(
				static function ( $key, $_, $lang ) use ( $msgMap ) {
					return $msgMap[$lang->getCode()][$key] ?? '';
				}
			)
		);
		$mock->method( 'transform' )
			->will( $this->returnArgument( 0 ) );

		$this->setService( 'MessageCache', $mock );
		// $cache = \MediaWiki\MediaWikiServices::getInstance()->getMessageCache();
		// fwrite( STDERR, $cache->get( 'test-prefix-block-block', null, null ) );
		// fwrite( STDERR, get_class( $cache ) );
		// $this->assertSame( '$1 blocked $2.', $cache->get( 'test-prefix-block-block', null, null ) );
		$msg = Util::msgText( 'test-prefix-block-block' );
		$this->assertNotEmpty( $msg, 'should return not empty value when valid key is given' );
		$this->assertIsString( $msg, 'should return string value' );
		$this->setMwGlobals( 'wgLanguageCode', 'ko' );
		$msg = Util::msgText( 'test-prefix-block-block' );
		$this->assertSame( 'A가 B를 차단했습니다.', $msg, 'should return message in the content language' );
	}
}
