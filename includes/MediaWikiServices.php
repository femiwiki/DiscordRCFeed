<?php

namespace MediaWiki\Extension\DiscordRCFeed;

class MediaWikiServices implements \MediaWiki\Hook\MediaWikiServicesHook {
	private const DEFAULT_PARAMS = [
		'formatter' => RCFeedFormatter::class,
		'class' => RCFeedEngine::class,
		'user_tools' => [
			[
				'target' => 'talk',
				'msg' => 'talkpagelinktext'
			],
			[
				'target' => 'special',
				'special' => 'Block',
				'msg' => 'blocklink'
			],
			[
				'target' => 'special',
				'special' => 'Contributions',
				'msg' => 'contribslink'
			],
		],
		'page_tools' => [
			[
				'query' => 'action=edit',
				'msg' => 'edit'
			],
			[
				'query' => 'action=history',
				'msg' => 'hist'
			],
		],
	];

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
			// Makes sure always being array.
			$mustBeArray = [
				'omit_namespaces',
				// not yet implemented
				// 'omit_types',
				// 'omit_log_types',
				// 'omit_log_actions',
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

			foreach ( self::DEFAULT_PARAMS as $paramKey => $param ) {
				if ( !isset( $wgRCFeeds[$feedKey][$paramKey] ) ) {
					$wgRCFeeds[$feedKey][$paramKey] = $param;
				}
			}
		}
	}
}
