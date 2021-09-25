<?php

namespace MediaWiki\Extension\DiscordRCFeed;

use ExtensionRegistry;
use IRCColourfulRCFeedFormatter;
use LogFormatter;
use MediaWiki\MediaWikiServices;
use RCFeedFormatter;
use RecentChange;

class DiscordRCFeedFormatter implements RCFeedFormatter {

	/**
	 * @inheritDoc
	 */
	public function getLine( array $feed, RecentChange $rc, $comment ) {
		$attribs = $rc->getAttributes();
		$rcType = $attribs['rc_type'];
		$user = $rc->getPerformer();
		$titleObj = $rc->getTitle();
		if (
			in_array( $rcType, $feed['omit_types'] )
			|| in_array( $titleObj->getNamespace(), $feed['omit_namespaces'] )
		) {
			return null;
		}

		$linkRenderer = new LinkRenderer( $feed['user_tools'], $feed['page_tools'] );
		if ( $rcType == RC_LOG ) {
			$logType = $attribs['rc_log_type'];
			$logAction = $attribs['rc_log_action'];
			if ( in_array( $logType, $feed['omit_log_types'] )
				|| in_array( "$logType/$logAction", $feed['omit_log_actions'] )
			) {
				return null;
			}

			if ( isset( Constants::COLOR_MAP_LOG[$logType] ) ) {
				$color = Constants::COLOR_MAP_LOG[$logType];
			} else {
				$color = Constants::COLOR_MAP_ACTION[RC_LOG];
			}

			$emoji = self::getEmojiForLog( $logType, $logAction );

			$formatter = LogFormatter::newFromRow( $attribs );
			$actionText = $formatter->getPlainActionText();
			$actionText = $linkRenderer->makeLinksClickable( $actionText, $user );
			$actionText = self::cleanupForDiscord( $actionText );

			$comment = $attribs['rc_comment'];
			if ( $comment ) {
				$comment = Util::msg( 'parentheses', $comment );
			}

			$fullString = implode( ' ', array_filter( [
				$emoji,
				$actionText,
				$comment,
			] ) );
		} elseif ( in_array( $rcType, [ RC_EDIT, RC_NEW ] ) ) {
			$store = MediaWikiServices::getInstance()->getCommentStore();
			$comment = $store->getComment( 'rc_comment', $attribs )->text;
			if ( $comment ) {
				$comment = Util::msg( 'parentheses', $comment );
				$comment = self::cleanupForDiscord( $comment );
			}

			$flags = [];
			if ( $rcType == RC_NEW ) {
				$flags[] = 'new';
			} elseif ( $rcType == RC_EDIT ) {
				$flags[] = 'edit';
			}
			if ( $attribs['rc_minor'] ) {
				$flags[] = 'minor';
			}
			$title = $linkRenderer->getDiscordPageTextWithTools( $titleObj, $attribs['rc_this_oldid'],
				$attribs['rc_last_oldid'] ?? null );

			$szdiff = self::getSizeDiff( $attribs );

			$messageKey = $rcType == RC_LOG ? 'discordrcfeed-line-log'
				: 'discordrcfeed-line-' . implode( '-', $flags );
			$message = wfMessage( $messageKey );
			$params = [
				// $1: username
				$linkRenderer->getDiscordUserTextWithTools( $user ),
				// $2: username for GENDER
				$user->getName(),
				// $3
				$linkRenderer->getDiscordPageTextWithTools( $titleObj ),
			];
			$message = $message->params( ...$params )->inContentLanguage()->text();

			$fullString = implode( ' ', array_filter( [ $message, $szdiff, $comment ] ) );
			if ( isset( Constants::COLOR_MAP_ACTION[$rcType] ) ) {
				$color = Constants::COLOR_MAP_ACTION[$rcType];
			} else {
				$color = Constants::COLOR_DEFAULT;
			}
		} elseif ( ExtensionRegistry::getInstance()->isLoaded( 'Flow' ) && $rcType == RC_FLOW ) {
			$emoji = Util::msg( 'discordrcfeed-emoji-flow' );

			$flowFormatter = new DiscordFlowRCFeedFormatter( $linkRenderer );
			$comment = $flowFormatter->getDiscordLine( $rc ) ?: $comment;

			$title = LinkRenderer::makeLink( $titleObj->getFullURL(), $titleObj->getFullText() );
			$title = Util::msg( 'parentheses', $title );

			$szdiff = self::getSizeDiff( $attribs );

			$fullString = implode( ' ', [ $emoji, $comment, $title, $szdiff ] );
			$color = Constants::COLOR_ACTION_FLOW;
		} else {
			return null;
		}
		return self::makePostData( $feed, $fullString, $color );
	}

	/**
	 * @param mixed $attribs
	 * @return string
	 */
	private static function getSizeDiff( $attribs ): string {
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
	private static function getEmojiForLog( string $LogType, string $LogAction ): string {
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
	 * @param int $color
	 * @return string
	 */
	private static function makePostData( array $feed, string $description,
		int $color = Constants::COLOR_DEFAULT ): string {
		global $wgSitename;

		$post = [
			'username' => $wgSitename,
		];
		if ( $feed['line_style'] == 'embed' ) {
			$post['embeds'] = [
				[
					'color' => $color,
					'description' => $description,
				],
			];
		} else {
			$post['content'] = $description;
		}
		if ( isset( $feed['request_replace'] ) ) {
			$post = array_replace_recursive( $post, $feed['request_replace'] );
		}
		return json_encode( $post );
	}

	/**
	 * Remove newlines, carriage returns and decode html entities
	 * @param string $text
	 * @return string
	 */
	public static function cleanupForDiscord( string $text ): string {
		$text = IRCColourfulRCFeedFormatter::cleanupForIRC( $text );
		return $text;
	}
}
