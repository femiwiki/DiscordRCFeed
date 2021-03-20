<?php

namespace MediaWiki\Extension\DiscordNotifications;

use Flow\Model\UUID;
use MediaWiki\User\UserIdentity;
use MessageSpecifier;
use Title;
use User;

class Core {
	/**
	 * @var string used for phpunit
	 */
	public static $lastMessage;

	/**
	 * Returns whether the given title should be excluded
	 * @param Title $title
	 * @return bool
	 * @todo Check case-sensitively when $wgCapitalLinks is false. Case-sensitive only now.
	 */
	public static function titleIsExcluded( Title $title ) {
		global $wgDiscordExcludeNotificationsFrom;
		return is_array( $wgDiscordExcludeNotificationsFrom ) &&
			in_array( $title->getText(), $wgDiscordExcludeNotificationsFrom );
	}

	/**
	 * Sends the message into Discord room.
	 * @param string $message to be sent.
	 * @param User|UserIdentity|null $user
	 * @param string $action
	 * @see https://discordapp.com/developers/docs/resources/webhook#execute-webhook
	 */
	public static function pushDiscordNotify( string $message, $user, string $action ) {
		global $wgDiscordNotificationsIncomingWebhookUrl, $wgDiscordNotificationsSendMethod, $wgDiscordExcludedPermission;

		// Users with the permission suppress notifications
		if ( isset( $wgDiscordExcludedPermission ) && $wgDiscordExcludedPermission != "" ) {
			if ( $user && $user instanceof User && $user->isAllowed( $wgDiscordExcludedPermission ) ) {
				return;
			}
		}

		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			self::$lastMessage = $message;
		}

		$post = self::makePost( $message, $action );

		$hooks = $wgDiscordNotificationsIncomingWebhookUrl;
		if ( is_string( $hooks ) ) {
			$hooks = [ $hooks ];
		}
		// Use file_get_contents to send the data. Note that you will need to have allow_url_fopen
		// enabled in php.ini for this to work.
		if ( $wgDiscordNotificationsSendMethod == "file_get_contents" ) {
			foreach ( $hooks as $hook ) {
				self::sendHttpRequest( $hook, $post );
			}
		} else {
			// Call the Discord API through cURL (default way). Note that you will need to have cURL
			// enabled for this to work.
			foreach ( $hooks as $hook ) {
				self::sendCurlRequest( $hook, $post );
			}
		}
	}

	/**
	 * @param string $message to be sent.
	 * @param string $action
	 * @return string
	 */
	private static function makePost( $message, $action ) {
		global $wgDiscordNotificationsFromName, $wgDiscordNotificationsAvatarUrl, $wgSitename;

		// Convert " to ' in the message to be sent as otherwise JSON formatting would break.
		$message = str_replace( '"', "'", $message );

		$discordFromName = $wgDiscordNotificationsFromName;
		if ( $discordFromName == "" ) {
			$discordFromName = $wgSitename;
		}

		$message = preg_replace( "~(<)(http)([^|]*)(\|)([^\>]*)(>)~", "[$5]($2$3)", $message );
		$message = str_replace( [ "\r", "\n" ], '', $message );

		$colour = 11777212;
		switch ( $action ) {
			case 'article_saved':
				$colour = 2993970;
				break;
			case 'import_complete':
				$colour = 2993970;
				break;
			case 'user_groups_changed':
				$colour = 2993970;
				break;
			case 'article_inserted':
				$colour = 3580392;
				break;
			case 'article_deleted':
				$colour = 15217973;
				break;
			case 'article_moved':
				$colour = 14038504;
				break;
			case 'article_protected':
				$colour = 3493864;
				break;
			case 'new_user_account':
				$colour = 3580392;
				break;
			case 'file_uploaded':
				$colour = 3580392;
				break;
			case 'user_blocked':
				$colour = 15217973;
				break;
			case 'flow':
				$colour = 2993970;
				break;
			default:
				$colour = 11777212;
			break;
		}

		$post = sprintf( '{"embeds": [{ "color" : "' . $colour . '" ,"description" : "%s"}], "username": "%s"',
		$message,
		$discordFromName );
		if ( isset( $wgDiscordNotificationsAvatarUrl ) && !empty( $wgDiscordNotificationsAvatarUrl ) ) {
			$post .= ', "avatar_url": "' . $wgDiscordNotificationsAvatarUrl . '"';
		}
		$post .= '}';
		return $post;
	}

	/**
	 * @param string $url
	 * @param string $postData
	 */
	private static function sendCurlRequest( $url, $postData ) {
		$h = curl_init();
		curl_setopt( $h, CURLOPT_URL, $url );
		curl_setopt( $h, CURLOPT_POST, 1 );
		curl_setopt( $h, CURLOPT_POSTFIELDS, $postData );
		curl_setopt( $h, CURLOPT_RETURNTRANSFER, true );
		// Set 10 second timeout to connection
		curl_setopt( $h, CURLOPT_CONNECTTIMEOUT, 10 );
		// Set global 10 second timeout to handle all data
		curl_setopt( $h, CURLOPT_TIMEOUT, 10 );
		// Set Content-Type to application/json
		curl_setopt( $h, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Content-Length: ' . strlen( $postData )
			]
		);
		// Commented out lines below. Using default curl settings for host and peer verification.
		//curl_setopt ($h, CURLOPT_SSL_VERIFYHOST, 0);
		//curl_setopt ($h, CURLOPT_SSL_VERIFYPEER, 0);
		// ... And execute the curl script!
		$curl_output = curl_exec( $h );
		curl_close( $h );
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
		$result = file_get_contents( $url, false, $context );
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
	 * @param UUID $uuid
	 * @return string
	 */
	public static function flowUUIDToTitleText( UUID $uuid ) {
		$uuid = UUID::create( $uuid );
		$collection = \Flow\Collection\PostCollection::newFromId( $uuid );
		$revision = $collection->getLastRevision();
		return $revision->getContent( 'topic-title-plaintext' );
	}
}
