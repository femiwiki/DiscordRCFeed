<?php

namespace MediaWiki\Extension\DiscordRCFeed;

class MediaWikiServices implements \MediaWiki\Hook\MediaWikiServicesHook {

	/**
	 * Modifies RC Feeds with keys start with 'discord'.
	 * @inheritDoc
	 */
	public function onMediaWikiServices( $services ) {
		global $wgRCFeeds;
		if ( !$wgRCFeeds ) {
			return;
		}

		foreach ( $wgRCFeeds as $feedKey => $feed ) {
			if ( strpos( $feedKey, 'discord' ) !== 0 ) {
				continue;
			}
			if ( !isset( $wgRCFeeds[$feedKey]['url'] ) ) {
				// Reads 'uri' which is a key for other RCFeedFormatters, like
				// JSONRCFeedFormatter and IRCColourfulRCFeedFormatter as the fallback of 'url'.
				// DiscordRCFeedEngine should read only 'url', but this makes it less confusing for the end user.
				if ( isset( $wgRCFeeds[$feedKey]['uri'] ) ) {
					$wgRCFeeds[$feedKey]['url'] = $wgRCFeeds[$feedKey]['uri'];
				} else {
					continue;
				}
			}

			// Don't send RC_CATEGORIZE events (same as T127360)
			$replacingArray = [
				'omit_types' => [ RC_CATEGORIZE ],
			];

			self::initializeParameters( $wgRCFeeds[$feedKey], Constants::DEFAULT_RC_FEED_PARAMS, $replacingArray );
		}
	}

	/**
	 * @param array &$feed
	 * @param array|null $defaultParameters
	 * @param array|null $mergeParameters
	 */
	public static function initializeParameters( &$feed, $defaultParameters = null, $mergeParameters = null ) {
		// Makes sure always being array.
		$mustBeArray = [
			'omit_namespaces',
			'omit_types',
			'omit_log_types',
			'omit_log_actions',
		];
		foreach ( $mustBeArray as $param ) {
			if ( isset( $feed[$param] ) ) {
				if ( !is_array( $feed[$param] ) ) {
					$feed[$param] = [ $feed[$param] ];
				}
			} else {
				$feed[$param] = [];
			}
		}

		if ( $defaultParameters ) {
			$feed = array_replace_recursive( $defaultParameters, $feed );
		}
		if ( $mergeParameters ) {
			$feed = array_merge_recursive( $feed, $mergeParameters );
		}
	}
}
