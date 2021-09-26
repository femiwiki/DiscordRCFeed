<?php

namespace MediaWiki\Extension\DiscordRCFeed;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MessageSpecifier;
use Psr\Log\LoggerInterface;
use RequestContext;

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
		return wfMessage( $key, ...$params )->inContentLanguage()->text();
	}

	/**
	 * @param string $url
	 * @return bool
	 */
	public static function urlIsLocal( string $url ): bool {
		global $wgServer;
		$server = wfParseUrl( $wgServer );
		$url = wfParseUrl( $url );
		$bitNames = [
			'scheme',
			'host',
			'port',
		];
		foreach ( $bitNames as $name ) {
			if ( isset( $url[$name] ) && $server[$name] !== $url[$name] ) {
				return false;
			}
		}
		return true;
	}

	/**
	 *
	 * get a context which has the content language to prevent the message shown in an arbitrary language the editor
	 * uses.
	 * https://github.com/femiwiki/DiscordRCFeed/issues/6
	 * @return RequestContext
	 */
	public static function getContentLanguageContext(): RequestContext {
		$context = RequestContext::getMain();
		$context->setLanguage( MediaWikiServices::getInstance()->getContentLanguage() );
		return $context;
	}
}
