<?php

namespace MediaWiki\Extension\DiscordRCFeed;

use ChangesList;
use ExtensionRegistry;
use Flow\Container;
use Linker;
use LogFormatter;
use MediaWiki\MediaWikiServices;
use Message;
use RCFeedFormatter;
use RecentChange;
use Sanitizer;
use Title;
use User;

class DiscordRCFeedFormatter implements RCFeedFormatter {

	/** @var DiscordLinker */
	private $linker;

	/** @var HtmlToDiscordConverter */
	private $converter;

	/** @var array */
	private $feed;

	/** @var User */
	private $performer;

	/** @var Title */
	private $title;

	/**
	 * @param array|null $feed
	 * @param User|null $performer
	 * @param Title|null $title
	 */
	public function __construct( $feed = null, $performer = null, $title = null ) {
		$this->feed = $feed;
		$this->performer = $performer;
		$this->title = $title;
	}

	/**
	 * @inheritDoc
	 */
	public function getLine( array $feed, RecentChange $rc, $actionComment ) {
		$this->feed = $feed;
		$attribs = $rc->getAttributes();
		$rcType = $attribs['rc_type'];
		$this->title = Util::getTitleFromRC( $rc );
		$this->performer = Util::getPerformerFromRC( $rc );
		$this->linker = new DiscordLinker( $feed['user_tools'], $feed['page_tools'] );
		$this->converter = new HtmlToDiscordConverter( $this->linker );

		if ( in_array( $rcType, [ RC_EDIT, RC_NEW ] ) ) {
			$color = Constants::COLOR_MAP_ACTION[$rcType] ?? Constants::COLOR_DEFAULT;
			$store = MediaWikiServices::getInstance()->getCommentStore();
			$comment = $store->getComment( 'rc_comment', $attribs )->text;
		} elseif ( $rcType == RC_LOG ) {
			$color = Constants::COLOR_MAP_LOG[$attribs['rc_log_type']] ?? Constants::COLOR_MAP_ACTION[RC_LOG];
			$comment = $attribs['rc_comment'];
		} elseif ( self::isFlowLoaded() && $rcType == RC_FLOW ) {
			$color = Constants::COLOR_ACTION_FLOW;
			$comment = '';
		} else {
			return null;
		}

		$desc = $this->getDescription( $rc, $feed['style'] != 'structure' );
		if ( $comment ) {
			$comment = Linker::formatComment( $comment, $this->title );
			$comment = $this->converter->convert( $comment, true );
		}

		return $this->makePostData( $attribs, $color, $desc, $comment );
	}

	/**
	 * @param RecentChange $rc
	 * @param bool $includeTools
	 * @return string
	 */
	private function getDescription(
		RecentChange $rc,
		bool $includeTools = true
	): string {
		$feed = $this->feed;
		$attribs = $rc->getAttributes();
		$rcType = $attribs['rc_type'];
		$performer = $this->performer;
		$title = $this->title;
		if ( in_array( $rcType, [ RC_EDIT, RC_NEW ] ) ) {
			$flag = '';
			if ( $attribs['rc_minor'] ) {
				$flag .= '-minor';
			}
			if ( $attribs['rc_bot'] ) {
				$flag .= '-bot';
			}

			if ( $rcType == RC_NEW ) {
				$emoji = Util::msgText( 'discordrcfeed-emoji-log-create-create' );
				$desc = 'logentry-create-create';
			} else {
				// i18n messages:
				//  discordrcfeed-emoji-edit
				//  discordrcfeed-emoji-edit-minor
				//  discordrcfeed-emoji-edit-bot
				//  discordrcfeed-emoji-edit-minor-bot
				$emoji = Util::msgText( 'discordrcfeed-emoji-edit' . $flag );
				// i18n messages:
				//  discordrcfeed-emoji-edit
				//  discordrcfeed-emoji-edit-minor
				//  discordrcfeed-emoji-edit-bot
				//  discordrcfeed-emoji-edit-minor-bot
				$desc = 'discordrcfeed-line-edit' . $flag;
			}
			$desc = new Message( $desc );

			if ( $includeTools ) {
				$params = [
					// $1: username
					$this->linker->makeUserTextWithTools( $performer ),
					// $2: username for GENDER
					$performer->getName(),
					// $3
					$this->linker->makePageTextWithTools( $title ),
				];
			} else {
				$params = [
					// $1: username
					$performer->getName(),
					// $2: username for GENDER
					$performer->getName(),
					// $3
					$title->getFullText(),
				];
			}
			$desc = $desc->params( ...$params )->inContentLanguage()->text();
		} elseif ( $rcType == RC_LOG ) {
			$logType = $attribs['rc_log_type'];
			$logAction = $attribs['rc_log_action'];

			$emoji = self::getEmojiForKeys( 'discordrcfeed-emoji-log', $logType, $logAction );

			$formatter = LogFormatter::newFromRow( $attribs );
			$formatter->setContext( Util::getContentLanguageContext() );
			if ( $includeTools ) {
				$desc = $formatter->getActionText();
				$desc = $this->converter->convert( $desc );
			} else {
				$desc = $formatter->getPlainActionText();
				$desc = preg_replace( '/\[\[|\]\]/', '"', $desc );
			}
			$comment = $attribs['rc_comment'];
		} elseif ( self::isFlowLoaded() && $rcType == RC_FLOW ) {
			$action = unserialize( $attribs['rc_params'] )['flow-workflow-change']['action'];
			$emoji = self::getEmojiForKeys( 'discordrcfeed-emoji-flow', $action, '' );

			$formatter = FlowDiscordFormatter::getInstance();
			$formatter->plaintext = !$includeTools;
			$query = Container::get( 'query.changeslist' );
			$changesList = new ChangesList( Util::getContentLanguageContext() );
			$row = $query->getResult( $changesList, $rc );
			$desc = $formatter->format( $row, $changesList );
			if ( $includeTools ) {
				$desc = $this->converter->convert( $desc );
			} else {
				$desc = Sanitizer::stripAllTags( $desc );
			}

			$color = Constants::COLOR_ACTION_FLOW;
		} else {
			return '';
		}

		return "$emoji $desc";
	}

	/**
	 * @param mixed $attribs
	 * @param bool $diffOnly
	 * @return string|array
	 */
	private static function getSizeDiff( $attribs, $diffOnly ) {
		if (
			isset( $attribs['rc_old_len'] ) && isset( $attribs['rc_new_len'] )
			&& $attribs['rc_old_len'] !== null && $attribs['rc_new_len'] !== null
		) {
			$szdiff = $attribs['rc_new_len'] - $attribs['rc_old_len'];
			if ( $diffOnly ) {
				$msg = ( new Message( 'nbytes' ) )->numParams( $szdiff );
				return ( $szdiff > 0 ? '+' : '' ) . $msg->inContentLanguage()->text();
			} else {
				$msg = ( new Message( 'nbytes' ) )->numParams( $attribs['rc_new_len'] );
				$newLen = $msg->inContentLanguage()->text();
				$szdiff = ( $szdiff > 0 ? '+' : '' ) . strval( $szdiff );
				return [ $newLen, $szdiff ];
			}
		}
		return $diffOnly ? '' : [ '', '' ];
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
			$msg = new Message( $key );
			if ( $msg->exists() ) {
				return $msg->inContentLanguage()->text();
			}
		}
		return '';
	}

	/**
	 * https://discord.com/developers/docs/resources/webhook#execute-webhook
	 * @param array $attribs
	 * @param int $color
	 * @param string $desc
	 * @param string $comment
	 * @return string
	 */
	private function makePostData(
		array $attribs,
		int $color = Constants::COLOR_DEFAULT,
		string $desc = '',
		string $comment = ''
	): string {
		global $wgSitename;
		$feed = $this->feed;
		$performer = $this->performer;
		$title = $this->title;
		$style = $feed['style'];

		if ( $style == 'structure' ) {
			list( $size, $szdiff ) = self::getSizeDiff( $attribs, false );
		} else {
			$comment = $comment ? Util::msgText( 'parentheses', $comment ) : '';
			$szdiff = self::getSizeDiff( $attribs, true );
			$szdiff = $szdiff ? Util::msgText( 'parentheses', $szdiff ) : '';
		}

		$fullString = implode( ' ', array_filter( [
			$desc,
			$comment,
			$szdiff,
		] ) );

		$post = [
			'username' => $wgSitename,
		];
		switch ( $feed['style'] ) {
			case 'embed':
				$post['embeds'] = [
					[
						'color' => $color,
						'description' => $fullString,
					],
				];
				break;
			case 'inline':
				$post['content'] = $fullString;
				break;
			case 'structure':
				$post['embeds'] = [
					[
						'color' => $color,
						'description' => $desc,
						'fields' => [],
					],
				];
				if ( $performer ) {
					$post['embeds'][0]['fields'][] = [
						'name' => $performer->getName(),
						'value' => $this->linker->makeUserTools( $performer, PHP_EOL, true ),
						'inline' => true,
					];
				}
				if ( $title ) {
					$post['embeds'][0]['fields'][] = [
						'name' => $title->getFullText(),
						'value' => $this->linker->makePageTools( $title, PHP_EOL, true ),
						'inline' => true,
					];
				}
				if ( isset( $size ) && $size ) {
					$post['embeds'][0]['fields'][] = [
						'name' => Util::msgText( 'listfiles_size' ),
						'value' => "$size" . PHP_EOL . Util::msgText( 'parentheses', $szdiff ),
						'inline' => true,
					];
				}
				if ( $comment ) {
					$post['embeds'][0]['fields'][] = [
						'name' => Util::msgText( 'summary' ),
						'value' => $comment,
						'inline' => true,
					];
				}
				break;
		}
		if ( isset( $feed['request_replace'] ) ) {
			$post = array_replace_recursive( $post, $feed['request_replace'] );
		}
		return json_encode( $post );
	}

	/**
	 * @return bool
	 */
	private static function isFlowLoaded(): bool {
		return ExtensionRegistry::getInstance()->isLoaded( 'Flow' );
	}
}
