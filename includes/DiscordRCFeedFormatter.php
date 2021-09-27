<?php

namespace MediaWiki\Extension\DiscordRCFeed;

use ChangesList;
use ExtensionRegistry;
use Flow\Container;
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

		$linkRenderer = new DiscordLinker( $feed['user_tools'], $feed['page_tools'] );
		$converter = new HtmlToDiscordConverter( $linkRenderer );
		if ( in_array( $rcType, [ RC_EDIT, RC_NEW ] ) ) {
			$flag = '';
			if ( $attribs['rc_minor'] ) {
				$flag .= '-minor';
			}
			if ( $attribs['rc_bot'] ) {
				$flag .= '-bot';
			}

			if ( $rcType == RC_NEW ) {
				$emoji = Util::msg( 'discordrcfeed-emoji-log-create-create' );
				$desc = 'logentry-create-create';
			} else {
				// i18n messages:
				//  discordrcfeed-emoji-edit
				//  discordrcfeed-emoji-edit-minor
				//  discordrcfeed-emoji-edit-bot
				//  discordrcfeed-emoji-edit-minor-bot
				$emoji = Util::msg( 'discordrcfeed-emoji-edit' . $flag );
			}

			$szdiff = self::getSizeDiff( $attribs );

			// i18n messages:
			//  discordrcfeed-emoji-edit
			//  discordrcfeed-emoji-edit-minor
			//  discordrcfeed-emoji-edit-bot
			//  discordrcfeed-emoji-edit-minor-bot
			$desc = wfMessage( 'discordrcfeed-line-edit' . $flag );
			$params = [
				// $1: username
				$linkRenderer->getDiscordUserTextWithTools( $user ),
				// $2: username for GENDER
				$user->getName(),
				// $3
				$linkRenderer->getDiscordPageTextWithTools( $titleObj, $attribs['rc_this_oldid'],
					$attribs['rc_last_oldid'] ?? null ),
			];
			$desc = $desc->params( ...$params )->inContentLanguage()->text();

			$store = MediaWikiServices::getInstance()->getCommentStore();
			$comment = $store->getComment( 'rc_comment', $attribs )->text;
			if ( $comment ) {
				$comment = Util::msg( 'parentheses', $comment );
			}

			$fullString = implode( ' ', array_filter( [
				$emoji,
				$desc,
				$szdiff,
				$comment
			] ) );
			if ( isset( Constants::COLOR_MAP_ACTION[$rcType] ) ) {
				$color = Constants::COLOR_MAP_ACTION[$rcType];
			} else {
				$color = Constants::COLOR_DEFAULT;
			}
		} elseif ( $rcType == RC_LOG ) {
			$logType = $attribs['rc_log_type'];
			$logAction = $attribs['rc_log_action'];
			if ( in_array( $logType, $feed['omit_log_types'] )
				|| in_array( "$logType/$logAction", $feed['omit_log_actions'] )
			) {
				return null;
			}

			$color = Constants::COLOR_MAP_LOG[$logType] ?? Constants::COLOR_MAP_ACTION[RC_LOG];

			$emoji = self::getEmojiForKeys( 'discordrcfeed-emoji-log', $logType, $logAction );

			$formatter = LogFormatter::newFromRow( $attribs );
			$formatter->setContext( Util::getContentLanguageContext() );
			$desc = $formatter->getActionText();
			$desc = $converter->convert( $desc );

			$comment = $attribs['rc_comment'];
			if ( $comment ) {
				$comment = Util::msg( 'parentheses', $comment );
			}

			$fullString = implode( ' ', array_filter( [
				$emoji,
				$desc,
				$comment,
			] ) );
		} elseif ( ExtensionRegistry::getInstance()->isLoaded( 'Flow' ) && $rcType == RC_FLOW ) {
			$action = unserialize( $attribs['rc_params'] )['flow-workflow-change']['action'];
			$emoji = self::getEmojiForKeys( 'discordrcfeed-emoji-flow', $action, '' );

			$flowFormatter = FlowDiscordFormatter::getInstance();
			$query = Container::get( 'query.changeslist' );
			$changesList = new ChangesList( Util::getContentLanguageContext() );
			$row = $query->getResult( $changesList, $rc, );
			$desc = $flowFormatter->format( $row, $changesList );
			$desc = $converter->convert( $desc );

			$szdiff = self::getSizeDiff( $attribs );

			$fullString = implode( ' ', [
				$emoji,
				$desc,
				$szdiff,
			] );
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
	 * @param string $prefix
	 * @param string $mainKey
	 * @param string $subKey
	 * @param string $fallback
	 * @return string
	 */
	private static function getEmojiForKeys( string $prefix, string $mainKey,
		string $subKey = '', string $fallback = ''
	): string {
		$keys = array_filter( [
			$subKey ? "$prefix-$mainKey-$subKey" : '',
			$mainKey ? "$prefix-$mainKey" : '',
			$fallback ?: "$prefix",
		] );
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
		if ( $feed['style'] == 'embed' ) {
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
}
