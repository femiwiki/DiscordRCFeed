<?php

namespace MediaWiki\Extension\DiscordRCFeed;

use IRCColourfulRCFeedFormatter;
use MediaWiki\MediaWikiServices;
use RCFeedFormatter as MediaWikiRCFeedFormatter;
use RecentChange;
use Title;

class RCFeedFormatter implements MediaWikiRCFeedFormatter {

	private const ACTION_COLOR_MAP = [
		'new'                 => '2993970',
		'edit'                => '2993970',
		'edit'                => '2993970',
		'delete'              => '15217973',
		'move'                => '14038504',
		'protect'             => '3493864',
		'upload'              => '3580392',

		'import_complete'     => '2993970',
		'user_groups_changed' => '2993970',
		'article_inserted'    => '3580392',
		'new_user_account'    => '3580392',
		'user_blocked'        => '15217973',
		'flow'                => '2993970',
	];

	/**
	 * @inheritDoc
	 */
	public function getLine( array $feed, RecentChange $rc, $actionComment ) {
		global $wgUseRCPatrol, $wgUseNPPatrol, $wgCanonicalServer, $wgScript;
		$attribs = $rc->getAttributes();
		if ( $attribs['rc_type'] == RC_CATEGORIZE ) {
			// Same as IRCColourfulRCFeedFormatter
			return null;
		}

		$linkRenderer = new LinkRenderer( $feed['user_tools'], $feed['page_tools'] );

		if ( $attribs['rc_type'] == RC_LOG ) {
			// We don't have reason to do not use SpecialPage::getTitleFor, but below is just a
			// copy of IRCColourfulRCFeedFormatter.
			$titleObj = Title::newFromText( 'Log/' . $attribs['rc_log_type'], NS_SPECIAL );
		} else {
			$titleObj =& $rc->getTitle();
		}

		if ( $attribs['rc_type'] == RC_LOG ) {
			$url = '';
		} else {
			$url = $wgCanonicalServer . $wgScript;
			if ( $attribs['rc_type'] == RC_NEW ) {
				$query = '?oldid=' . $attribs['rc_this_oldid'];
			} else {
				$query = '?diff=' . $attribs['rc_this_oldid'] . '&oldid=' . $attribs['rc_last_oldid'];
			}
			if ( $wgUseRCPatrol || ( $attribs['rc_type'] == RC_NEW && $wgUseNPPatrol ) ) {
				$query .= '&rcid=' . $attribs['rc_id'];
			}

			$url .= $query;
		}

		if ( $attribs['rc_old_len'] !== null && $attribs['rc_new_len'] !== null ) {
			$szdiff = $attribs['rc_new_len'] - $attribs['rc_old_len'];
			$szdiff = wfMessage( 'historysize' )->numParams( $szdiff )->inContentLanguage()->text();
		} else {
			$szdiff = '';
		}

		$user = $rc->getPerformer();

		if ( $attribs['rc_type'] == RC_LOG ) {
			$target = $rc->getTitle();
			$targetText = $target->getPrefixedText();
			$comment = self::cleanupForDiscord( $actionComment );
			$comment = str_replace(
				"[[$targetText]]",
				LinkRenderer::makeLink( $target->getFullURL(), $targetText ),
				$comment
			);
			$flags = [ $attribs['rc_log_type'], $attribs['rc_log_action'] ];
			$action = $attribs['rc_log_type'];
		} else {
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
		}

		$title = $linkRenderer->getDiscordArticleText( $attribs['rc_type'] == RC_LOG ? $target : $titleObj );
		$messageKey = 'discordrcfeed-line-' . implode( '-', $flags );
		$message = wfMessage( $messageKey );
		if ( $message->exists() ) {
			$params = [
				// $1: username
				$linkRenderer->getDiscordUserText( $user ),
				// $2: username for GENDER
				$user->getName(),
			];
			if ( in_array( $attribs['rc_log_type'], [ 'block', 'rights' ] ) ) {
				// username
				$params[] = $linkRenderer->getDiscordUserText( $user );
				// username for GENDER
				$params[] = $user->getName();
			} else {
				$params[] = $title;
			}
			$target2 = self::getSecondTarget(
				$actionComment,
				$attribs['rc_type'] == RC_NEW ? 'new' : ( $attribs['rc_type'] == RC_EDIT ? 'edit' : 'log' ),
				$attribs['rc_log_type'] ?? null,
				$attribs['rc_log_action'] ?? null
			);
			if ( $target2 ) {
				$params[] = $linkRenderer->getDiscordArticleText( $target2 );
			}
			$message->params( ...$params );
			$fullString = $message->inContentLanguage()->text();
		} else {
			$user = $linkRenderer->getDiscordUserText( $user );
			$fullString = " $user * $title * " .
				empty( $flags ) ? '' : '(' . implode( ', ', $flags ) . ')';
		}
		// TODO: Remove this before commit.
		$fullString .= " $messageKey";
		$fullString .= ' / $flags: ' . implode( '-', $flags );
		$fullString .= ' / rc_log_type: ' . $attribs['rc_log_type'];

		return $this->makePostData( $feed, $fullString, $action, $comment );
	}

	/**
	 * @param string $actionComment
	 * @param string $type
	 * @param string|null $logType
	 * @param string|null $logAction
	 * @return Title|null
	 */
	private static function getSecondTarget( $actionComment, $type, $logType = null, $logAction = null ) {
		if ( $logType == 'protect' && $logAction == 'move_prot'
			&& preg_match_all( '/\[\[([^]]+)\]\]/', $actionComment, $matches ) ) {
			return Title::newFromText( $matches[1][0] );
		}
		if ( $logType == 'move' && preg_match_all( '/\[\[([^]]+)\]\]/', $actionComment, $matches ) ) {
			return Title::newFromText( $matches[1][1] );
		}
		return null;
	}

	/**
	 * @param array $feed
	 * @param string $description message to be sent.
	 * @param string $action
	 * @param string|null $summary An edit summary.
	 * @return string
	 */
	private function makePostData( $feed, $description, $action, $summary = null ) {
		global $wgSitename;

		$color = '11777212';
		if ( isset( self::ACTION_COLOR_MAP[$action] ) ) {
			$color = self::ACTION_COLOR_MAP[$action];
		}

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
