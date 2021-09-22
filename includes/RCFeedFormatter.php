<?php

namespace MediaWiki\Extension\DiscordRCFeed;

use ExtensionRegistry;
use IRCColourfulRCFeedFormatter;
use MediaWiki\MediaWikiServices;
use RCFeedFormatter as MediaWikiRCFeedFormatter;
use RecentChange;
use User;

class RCFeedFormatter implements MediaWikiRCFeedFormatter {

	/**
	 * @inheritDoc
	 */
	public function getLine( array $feed, RecentChange $rc, $comment ) {
		$attribs = $rc->getAttributes();
		$rcType = $attribs['rc_type'];
		if ( in_array( $rcType, $feed['omit_types'] ) ) {
			return null;
		}

		$linkRenderer = new LinkRenderer( $feed['user_tools'], $feed['page_tools'] );
		$user = $rc->getPerformer();

		if ( $rcType == RC_LOG ) {
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

			$comment = $linkRenderer->makeLinksClickable( $comment );
			$comment = self::cleanupForDiscord( $comment );
			if ( isset( Constants::COLOR_MAP_LOG[$logType] ) ) {
				$color = Constants::COLOR_MAP_LOG[$logType];
			} else {
				$color = Constants::COLOR_MAP_ACTION[RC_LOG];
			}

			$emoji = self::getEmojiForLog( $logType, $logAction );
			$user = $linkRenderer->getDiscordUserText( $user );

			$fullString = implode( ' ', [ $emoji, $user, $comment ] );
			return self::makePostData( $feed, $fullString, $color );
		} elseif ( in_array( $rcType, [ RC_EDIT, RC_NEW ] ) ) {
			$titleObj = $rc->getTitle();
			if ( in_array( $titleObj->getNamespace(), $feed['omit_namespaces'] ) ) {
				return null;
			}
			$store = MediaWikiServices::getInstance()->getCommentStore();
			$comment = $store->getComment( 'rc_comment', $attribs )->text;
			if ( $comment ) {
				$comment = wfMessage( 'parentheses', $comment )->inContentLanguage()->text();
				$comment = self::cleanupForDiscord( $comment );
			} else {
				$comment = '';
			}

			$flags = [];
			if ( $rcType == RC_NEW ) {
				$action = 'new';
				$flags[] = 'new';
			} elseif ( $rcType == RC_EDIT ) {
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

			$szdiff = $this->getSizeDiff( $attribs );

			$messageKey = $rcType == RC_LOG ? 'discordrcfeed-line-log'
				: 'discordrcfeed-line-' . implode( '-', $flags );
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

			$fullString = implode( ' ', [ $message, $szdiff, $comment ] );
			if ( isset( Constants::COLOR_MAP_ACTION[$rcType] ) ) {
				$color = Constants::COLOR_MAP_ACTION[$rcType];
			} else {
				$color = Constants::COLOR_DEFAULT;
			}
			return self::makePostData( $feed, $fullString, $color );
		} elseif ( ExtensionRegistry::getInstance()->isLoaded( 'Flow' ) && $rcType == RC_FLOW ) {
			$emoji = wfMessage( 'discordrcfeed-emoji-flow' )->inContentLanguage()->text();

			$flowFormatter = new FlowRCFeedFormatter( $linkRenderer );
			$comment = $flowFormatter->getDiscordLine( $rc ) ?: $comment;

			$titleObj = $rc->getTitle();
			$title = LinkRenderer::makeLink( $titleObj->getFullURL(), $titleObj->getFullText() );
			$title = wfMessage( 'parentheses', $title )->inContentLanguage()->text();

			$szdiff = $this->getSizeDiff( $attribs );

			$fullString = implode( ' ', [ $emoji, $comment, $title, $szdiff ] );
			return self::makePostData( $feed, $fullString, Constants::COLOR_ACTION_FLOW );
			// return self::makePostData( $feed, print_r( $rc, true ) );
		}
	}

	/**
	 * @param mixed $attribs
	 * @return string
	 */
	private function getSizeDiff( $attribs ) {
		if ( $attribs['rc_old_len'] !== null && $attribs['rc_new_len'] !== null ) {
			$szdiff = $attribs['rc_new_len'] - $attribs['rc_old_len'];
			return wfMessage( 'historysize' )->numParams( $szdiff )->inContentLanguage()->text();
		}
		return '';
	}

	/**
	 * @param string $LogType
	 * @param string $LogAction
	 * @return string
	 */
	private static function getEmojiForLog( $LogType, $LogAction ) {
		$keys = [
			"discordrcfeed-emoji-log-$LogType-$LogAction",
			"discordrcfeed-emoji-log-$LogType",
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
	 * @param int|null $color
	 * @return string
	 */
	private static function makePostData( $feed, $description, $color = Constants::COLOR_DEFAULT ) {
		global $wgSitename;

		$embed = [
			'color' => $color,
			'description' => $description,
		];
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
