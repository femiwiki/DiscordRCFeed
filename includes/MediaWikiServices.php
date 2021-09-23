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
			if ( !isset( $wgRCFeeds[$feedKey]['omit_types'] ) ) {
				$wgRCFeeds[$feedKey]['omit_types'] = [ RC_CATEGORIZE ];
			} else {
				$wgRCFeeds[$feedKey]['omit_types'][] = RC_CATEGORIZE;
			}

			// Makes sure always being array.
			$mustBeArray = [
				'omit_namespaces',
				'omit_types',
				'omit_log_types',
				'omit_log_actions',
			];
			foreach ( $mustBeArray as $param ) {
				if ( isset( $wgRCFeeds[$feedKey][$param] ) ) {
					if ( !is_array( $wgRCFeeds[$feedKey][$param] ) ) {
						$wgRCFeeds[$feedKey][$param] = [ $wgRCFeeds[$feedKey][$param] ];
					}
				} else {
					$wgRCFeeds[$feedKey][$param] = [];
				}
			}

			self::addDefaultValues( $wgRCFeeds[$feedKey] );
		}
	}

	/**
	 * Sets default values for feed
	 * @param array &$feed
	 */
	public static function addDefaultValues( &$feed ) {
		foreach ( Constants::DEFAULT_RC_FEED_PARAMS as $paramKey => $param ) {
			if ( !isset( $feed[$paramKey] ) ) {
				$feed[$paramKey] = $param;
			}
		}
	}
}
