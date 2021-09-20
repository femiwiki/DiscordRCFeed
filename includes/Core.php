<?php

namespace MediaWiki\Extension\DiscordRCFeed;

use Flow\Model\UUID;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MessageSpecifier;
use Psr\Log\LoggerInterface;
use Title;
use User;

class Core {
	/**
	 * @var string used for phpunit
	 */
	public static $lastMessage;

	/**
	 * @var LoggerInterface
	 */
	private static $logger = null;

	/**
	 * Returns whether the given title should be excluded
	 * @param Title $title
	 * @return bool
	 * @todo Check case-sensitively when $wgCapitalLinks is false. Case-sensitive only now.
	 */
	public static function titleIsExcluded( Title $title ) {
		global $wgDiscordRCFeedExclude;
		$exclude = $wgDiscordRCFeedExclude['page'];
		if ( isset( $exclude['list'] ) ) {
			$list = $exclude['list'];
			if ( !is_array( $list ) ) {
				$list = [ $list ];
			}
			if ( in_array( $title->getText(), $list ) ) {
				return true;
			}
		}

		if ( isset( $exclude['patterns'] ) ) {
			$patterns = $exclude['patterns'];
			if ( !is_array( $patterns ) ) {
				$patterns = [ $patterns ];
			}
			foreach ( $patterns as $pattern ) {
				if ( preg_match( $pattern, $title ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Sends the message into Discord room.
	 * @param string $message to be sent.
	 * @param User|null $user
	 * @param string $action
	 * @return void|bool returns false if there is no output.
	 * @see https://discordapp.com/developers/docs/resources/webhook#execute-webhook
	 */
	public function pushDiscordNotify( string $message, $user, string $action ) {
		global $wgDiscordRCFeedIncomingWebhookUrl, $wgDiscordRCFeedSendMethod;

		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			self::$lastMessage = $message;
		}

		if ( !in_array( $wgDiscordRCFeedSendMethod, [ 'MWHttpRequest', 'file_get_contents', 'curl' ] ) ) {
			self::getLogger()->warning( "Unknown send method: $wgDiscordRCFeedSendMethod" );
			return false;
		}

		$hooks = $wgDiscordRCFeedIncomingWebhookUrl;
		if ( !$hooks ) {
			self::getLogger()->warning( '$wgDiscordRCFeedIncomingWebhookUrl is not set' );
			return false;
		} elseif ( is_string( $hooks ) ) {
			$hooks = [ $hooks ];
		}

		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return;
		}

		$post = $this->makePost( $message, $action );
		foreach ( $hooks as $hook ) {
			switch ( $wgDiscordRCFeedSendMethod ) {
				case 'MWHttpRequest':
					return self::sendMWHttpRequest( $hook, $post );
				case 'file_get_contents':
					self::getLogger()->warning(
						'\'file_get_contents\' for \$wgDiscordRCFeedSendMethod is deprecated' );
					// Use file_get_contents to send the data. Note that you will need to have allow_url_fopen
					// enabled in php.ini for this to work.
					self::sendHttpRequest( $hook, $post );
					break;
				case 'curl':
					self::getLogger()->warning( '\'curl\' for \$wgDiscordRCFeedSendMethod is deprecated' );
					// Call the Discord API through cURL (default way). Note that you will need to have cURL
					// enabled for this to work.
					self::sendCurlRequest( $hook, $post );
					break;
			}
		}
	}

	private const ACTION_COLOR_MAP = [
		'article_saved'       => 2993970,
		'import_complete'     => 2993970,
		'user_groups_changed' => 2993970,
		'article_inserted'    => 3580392,
		'article_deleted'     => 15217973,
		'article_moved'       => 14038504,
		'article_protected'   => 3493864,
		'new_user_account'    => 3580392,
		'file_uploaded'       => 3580392,
		'user_blocked'        => 15217973,
		'flow'                => 2993970,
	];

	/**
	 * @param string $message to be sent.
	 * @param string $action
	 * @return string
	 */
	private function makePost( $message, $action ) {
		global $wgDiscordRCFeedRequestOverride, $wgSitename;

		$colour = 11777212;
		if ( isset( self::ACTION_COLOR_MAP[$action] ) ) {
			$colour = self::ACTION_COLOR_MAP[$action];
		}

		$post = [
			'embeds' => [
				[
					'color' => "$colour",
					'description' => $message,
				]
			],
			'username' => $wgSitename
		];
		$post = array_replace_recursive( $post, $wgDiscordRCFeedRequestOverride );
		return json_encode( $post );
	}

	/**
	 * @param string $url
	 * @param string $postData
	 */
	private static function sendCurlRequest( $url, $postData ) {
		$h = curl_init();
		foreach ( [
			CURLOPT_URL => $url,
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $postData,
			CURLOPT_RETURNTRANSFER => true,
			// Set 10 second timeout to connection
			CURLOPT_CONNECTTIMEOUT => 10,
			// Set global 10 second timeout to handle all data
			CURLOPT_TIMEOUT => 10,
			// Set Content-Type to application/json
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Content-Length: ' . strlen( $postData )
			],
			// Commented out lines below. Using default curl settings for host and peer verification.
			// CURLOPT_SSL_VERIFYHOST => 0,
			// CURLOPT_SSL_VERIFYPEER => 0,
		] as $option => $value ) {
			curl_setopt( $h, $option, $value );
		}
		// ... And execute the curl script!
		$_ = curl_exec( $h );
		curl_close( $h );
	}

	/**
	 * @param string $url
	 * @param string $postData
	 * @return void|bool
	 */
	private static function sendMWHttpRequest( $url, $postData ) {
		$httpRequestFactory = MediaWikiServices::getInstance()->getHttpRequestFactory();
		$req = $httpRequestFactory->create(
			$url,
			[
				'method' => 'POST',
				'postData' => $postData
			],
			__METHOD__
		);
		$req->setHeader( 'Content-Type', 'application/json' );

		$status = $req->execute();
		if ( !$status->isOK() ) {
			self::getLogger()->warning( $status->getMessage() );
			return false;
		}
	}

	/**
	 * @param string $url
	 * @param string $postData
	 */
	private static function sendHttpRequest( $url, $postData ) {
		$extra = [
			'http' => [
				'header'  => 'Content-type: application/json',
				'method'  => 'POST',
				'content' => $postData,
			],
		];
		$context = stream_context_create( $extra );
		file_get_contents( $url, false, $context );
	}

	/**
	 * @param string|string[]|MessageSpecifier $key Message key, or array of keys, or a MessageSpecifier
	 * @param mixed ...$params Normal message parameters
	 * @return string
	 */
	public static function msg( $key, ...$params ) {
		if ( $params ) {
			return wfMessage( $key, ...$params )->inContentLanguage()->text();
		} else {
			return wfMessage( $key )->inContentLanguage()->text();
		}
	}

	/**
	 * @param string|UUID $uuid
	 * @return string Text of the title for given UUID. If not found, empty string will be returned.
	 */
	public static function flowUUIDToTitleText( $uuid ) {
		if ( is_string( $uuid ) ) {
			$uuid = strtolower( $uuid );
			$uuid = UUID::create( $uuid );
		}
		if ( !( $uuid instanceof UUID ) ) {
			return '';
		}
		$collection = \Flow\Collection\PostCollection::newFromId( $uuid );
		$revision = $collection->getLastRevision();
		return $revision->getContent( 'topic-title-plaintext' );
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
