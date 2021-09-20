<?php

namespace MediaWiki\Extension\DiscordRCFeed;

class MediaWikiServices implements \MediaWiki\Hook\MediaWikiServicesHook {
	private const DEFAULT_PARAMS = [
		'formatter' => RCFeedFormatter::class,
		'class' => RCFeedEngine::class,
		'user_tools' => [
			[
				'target' => 'special',
				'special' => 'Block',
				'msg' => 'discordrcfeed-block'
			],
			[
				'target' => 'special',
				'special' => 'Userrights',
				'msg' => 'discordrcfeed-groups'
			],
			[
				'target' => 'talk',
				'msg' => 'discordrcfeed-talk'
			],
			[
				'target' => 'special',
				'special' => 'Contributions',
				'msg' => 'discordrcfeed-contribs'
			],
		],
		'page_tools' => [
			[
				'query' => 'action=edit',
				'msg' => 'discordrcfeed-edit'
			],
			[
				'query' => 'action=delete',
				'msg' => 'discordrcfeed-delete'
			],
			[
				'query' => 'action=history',
				'msg' => 'discordrcfeed-history'
			],
		],
	];

	/**
	 * Make it possible to omit some options for the RC Feeds with keys start with 'discord'.
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

			foreach ( self::DEFAULT_PARAMS as $paramKey => $param ) {
				if ( !isset( $wgRCFeeds[$feedKey][$paramKey] ) ) {
					$wgRCFeeds[$feedKey][$paramKey] = $param;
				}
			}
		}
	}
}
