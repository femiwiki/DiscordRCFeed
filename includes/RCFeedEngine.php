<?php

namespace MediaWiki\Extension\DiscordRCFeed;

use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use RCFeedEngine as MediaWikiRCFeedEngine;

class RCFeedEngine extends MediaWikiRCFeedEngine {

	/** @var HttpRequestFactory */
	private $httpRequestFactory;

	/** @var LoggerInterface */
	private static $logger = null;

	/**
	 * @inheritDoc
	 */
	public function __construct( array $params ) {
		parent::__construct( $params );
		$this->httpRequestFactory = MediaWikiServices::getInstance()->getHttpRequestFactory();
	}

	/**
	 * @inheritDoc
	 */
	public function send( array $feed, $line ) {
		$req = $this->httpRequestFactory->create(
			$feed['url'] ?? $feed['uri'],
			[
				'method' => 'POST',
				'postData' => $line
			],
			__METHOD__
		);
		$req->setHeader( 'Content-Type', 'application/json' );

		$status = $req->execute();
		if ( !$status->isOK() ) {
			self::getLogger()->warning( $status->getMessage()->text() );
			return false;
		}
		return true;
	}

	/**
	 * @return LoggerInterface
	 */
	private static function getLogger(): LoggerInterface {
		if ( !self::$logger ) {
			self::$logger = LoggerFactory::getInstance( 'DiscordRCFeed' );
		}
		return self::$logger;
	}
}
