<?php

namespace MediaWiki\Extension\DiscordRCFeed;

use MediaWiki\Logger\LoggerFactory;
use MessageSpecifier;
use Psr\Log\LoggerInterface;

final class Util {
	/** @var LoggerInterface */
	private static $logger = null;

	/**
	 * @return LoggerInterface
	 */
	public static function getLogger(): LoggerInterface {
		if ( !self::$logger ) {
			self::$logger = LoggerFactory::getInstance( 'DiscordRCFeed' );
		}
		return self::$logger;
	}

	/**
	 * @param string|string[]|MessageSpecifier $key Message key, or array of keys, or a MessageSpecifier
	 * @param mixed ...$params Normal message parameters
	 * @return string
	 */
	public static function msg( $key, ...$params ): string {
		if ( $params ) {
			return wfMessage( $key, ...$params )->inContentLanguage()->text();
		} else {
			return wfMessage( $key )->inContentLanguage()->text();
		}
	}
}
