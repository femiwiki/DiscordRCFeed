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
			if ( !isset( $wgRCFeeds[$feedKey]['url'] ) && !isset( $wgRCFeeds[$feedKey]['uri'] ) ) {
				continue;
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

			// Set default values for parameters
			foreach ( Constants::DEFAULT_RC_FEED_PARAMS as $paramKey => $param ) {
				if ( !isset( $wgRCFeeds[$feedKey][$paramKey] ) ) {
					$wgRCFeeds[$feedKey][$paramKey] = $param;
				}
			}
		}
	}
}
