<?php

namespace MediaWiki\Extension\DiscordRCFeed\Tests\Unit;

use MediaWiki\Extension\DiscordRCFeed\FlowDiscordFormatter;
use MediaWikiIntegrationTestCase;

/**
 * @group DiscordRCFeed
 *
 * @covers \MediaWiki\Extension\DiscordRCFeed\FlowDiscordFormatter
 */
class FlowDiscordFormatterTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers \MediaWiki\Extension\DiscordRCFeed\FlowDiscordFormatter::getInstance
	 */
	public function testGetInstance() {
		$this->assertInstanceOf( FlowDiscordFormatter::class, FlowDiscordFormatter::getInstance() );
	}
}
