<?php

namespace MediaWiki\Extension\DiscordRCFeed;

use IRCColourfulRCFeedFormatter;
use MediaWiki\MediaWikiServices;
use RCFeedFormatter as MediaWikiRCFeedFormatter;
use RecentChange;
use User;

class RCFeedFormatter implements MediaWikiRCFeedFormatter {

	/**
	 * @inheritDoc
	 */
	public function getLine( array $feed, RecentChange $rc, $actionComment ) {
		$attribs = $rc->getAttributes();
		$feed['omit_types'][] = RC_CATEGORIZE;
		if ( in_array( $attribs['rc_type'], $feed['omit_types'] ) ) {
			return null;
		}

		$linkRenderer = new LinkRenderer( $feed['user_tools'], $feed['page_tools'] );
		$user = $rc->getPerformer();

		if ( $attribs['rc_type'] == RC_LOG ) {
			$logType = $attribs['rc_log_type'];
			$logAction = $attribs['rc_log_action'];
			if ( in_array( $logType, $feed['omit_log_types'] )
				|| in_array( "$logType/$logAction", $feed['omit_log_actions'] )
			) {
				return null;
			}
			$titleObj = $rc->getTitle();
			if ( in_array( $titleObj->getNamespace(), $feed['omit_namespaces'] ) ) {
				return null;
			}

			$comment = self::cleanupForDiscord( $actionComment );
			$comment = $linkRenderer->makeLinksClickable( $comment );
			if ( isset( Constants::COLOR_MAP_LOG[$logType] ) ) {
				$color = Constants::COLOR_MAP_LOG[$logType];
			} else {
				$color = Constants::COLOR_MAP_ACTION[RC_LOG];
			}

			$emoji = self::getEmojiForLog( $logType, $logAction );
			$user = $linkRenderer->getDiscordUserText( $user );

			$fullString = implode( ' ', [ $emoji, $user, $comment ] );
			return $this->makePostData( $feed, $fullString, $color );
		} else {
			$titleObj =& $rc->getTitle();
			if ( in_array( $titleObj->getNamespace(), $feed['omit_namespaces'] ) ) {
				return null;
			}
			$store = MediaWikiServices::getInstance()->getCommentStore();
			$comment = self::cleanupForDiscord(
				$store->getComment( 'rc_comment', $attribs )->text
			);
			$flags = [];
			if ( $attribs['rc_type'] == RC_NEW ) {
				$action = 'new';
				$flags[] = 'new';
			} elseif ( $attribs['rc_type'] == RC_EDIT ) {
				$action = 'edit';
				$flags[] = 'edit';
			}
			if ( $attribs['rc_minor'] ) {
				$flags[] = 'minor';
			}
			if ( $attribs['rc_bot'] ) {
				$flags[] = 'bot';
			}
			$title = $linkRenderer->getDiscordArticleText( $titleObj, $attribs['rc_this_oldid'],
				$attribs['rc_last_oldid'] ?? false );

			if ( $attribs['rc_old_len'] !== null && $attribs['rc_new_len'] !== null ) {
				$szdiff = $attribs['rc_new_len'] - $attribs['rc_old_len'];
				$szdiff = wfMessage( 'historysize' )->numParams( $szdiff )->inContentLanguage()->text();
			} else {
				$szdiff = '';
			}

			$messageKey = $attribs['rc_type'] == RC_LOG ? 'discordrcfeed-line-log'
				: 'discordrcfeed-line-' . implode( '-', $flags ?? [] );
			$message = wfMessage( $messageKey );
			$params = [
				// $1: username
				$linkRenderer->getDiscordUserText( $user ),
				// $2: username for GENDER
				$user->getName(),
			];
			if ( $titleObj->getNamespace() == NS_USER ) {
				$targetUser = User::newFromName( $titleObj->getText() );
				// username
				$params[] = $linkRenderer->getDiscordUserText( $targetUser );
				// username for GENDER
				$params[] = $targetUser->getName();
			} else {
				$params[] = $title;
			}
			$message = $message->params( ...$params )->inContentLanguage()->text();

			$fullString = implode( ' ', [ $message, $szdiff ] );
			if ( isset( Constants::COLOR_MAP_ACTION[$attribs['rc_type']] ) ) {
				$color = Constants::COLOR_MAP_ACTION[$attribs['rc_type']];
			} else {
				$color = Constants::COLOR_DEFAULT;
			}
			return $this->makePostData( $feed, $fullString, $color, $comment );
		}
	}

	/**
	 * @param string $LogType
	 * @param string $LogAction
	 * @return string
	 */
	private static function getEmojiForLog( $LogType, $LogAction ) {
		$keys = [
			"discordrcfeed-emoji-$LogType-$LogAction",
			"discordrcfeed-emoji-$LogType",
		];
		foreach ( $keys as $key ) {
			$msg = wfMessage( $key );
			if ( $msg->exists() ) {
				return $msg->inContentLanguage()->text();
			}
		}
		return '';
	}

	/**
	 * @param array $feed
	 * @param string $description message to be sent.
	 * @param string $color
	 * @param string|null $summary An edit summary.
	 * @return string
	 */
	private function makePostData( $feed, $description, $color, $summary = null ) {
		global $wgSitename;

		$embed = [
			'color' => $color,
			'description' => $description,
		];
		if ( $summary ) {
			$embed['fields'] = [
				[
					'name' => wfMessage( 'discordrcfeed-summary' )->inContentLanguage()->text(),
					'value' => $summary,
				],
			];
		}

		$post = [
			'embeds' => [ $embed ],
			'username' => $wgSitename
		];
		if ( isset( $feed['request_override'] ) ) {
			$post = array_replace_recursive( $post, $feed['request_override'] );
		}
		return json_encode( $post );
	}

	/**
	 * Remove newlines, carriage returns and decode html entities
	 * @param string $text
	 * @return string
	 */
	public static function cleanupForDiscord( $text ) {
		$text = IRCColourfulRCFeedFormatter::cleanupForIRC( $text );
		return $text;
	}
}
