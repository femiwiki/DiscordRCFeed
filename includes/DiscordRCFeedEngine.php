<?php

namespace MediaWiki\Extension\DiscordRCFeed;

use Exception;
use FormattedRCFeed;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\MediaWikiServices;

class DiscordRCFeedEngine extends FormattedRCFeed {

	/** @var HttpRequestFactory */
	private $httpRequestFactory;

	/**
	 * @inheritDoc
	 */
	public function __construct( array $params ) {
		if ( !isset( $params['url'] ) ) {
			throw new Exception( "RCFeed for Discord must have a 'url' set." );
		}
		$this->httpRequestFactory = MediaWikiServices::getInstance()->getHttpRequestFactory();
		parent::__construct( $params );
	}

	/**
	 * @inheritDoc
	 */
	public function send( array $feed, $line ) {
		$req = $this->httpRequestFactory->create(
			$feed['url'],
			[
				'method' => 'POST',
				'postData' => $line
			],
			__METHOD__
		);
		$req->setHeader( 'Content-Type', 'application/json' );

		$status = $req->execute();
		if ( !$status->isOK() ) {
			Util::getLogger()->warning( $status->getMessage()->text() );
			return false;
		}
		return true;
	}
}
