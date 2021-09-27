<?php

namespace MediaWiki\Extension\DiscordRCFeed;

use ChangesList;
use ExtensionRegistry;
use Flow\Container;
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

	/**
	 * @inheritDoc
	 */
	public function getLine( array $feed, RecentChange $rc, $actionComment ) {
		$attribs = $rc->getAttributes();
		$rcType = $attribs['rc_type'];
		if (
			in_array( $rcType, $feed['omit_types'] )
			|| in_array( $rc->getTitle()->getNamespace(), $feed['omit_namespaces'] )
		) {
			return null;
		}

		if ( in_array( $rcType, [ RC_EDIT, RC_NEW ] ) ) {
			$color = Constants::COLOR_MAP_ACTION[$rcType] ?? Constants::COLOR_DEFAULT;

			$store = MediaWikiServices::getInstance()->getCommentStore();
			$comment = $store->getComment( 'rc_comment', $attribs )->text;
		} elseif ( $rcType == RC_LOG ) {
			$logType = $attribs['rc_log_type'];
			$logAction = $attribs['rc_log_action'];
			if ( in_array( $logType, $feed['omit_log_types'] )
				|| in_array( "$logType/$logAction", $feed['omit_log_actions'] )
			) {
				return null;
			}

			$color = Constants::COLOR_MAP_LOG[$logType] ?? Constants::COLOR_MAP_ACTION[RC_LOG];
			$comment = $attribs['rc_comment'];
		} elseif ( self::isFlowLoaded() && $rcType == RC_FLOW ) {
			$color = Constants::COLOR_ACTION_FLOW;
			$comment = '';
		} else {
			return null;
		}

		$this->linker = new DiscordLinker( $feed['user_tools'], $feed['page_tools'] );
		$this->converter = new HtmlToDiscordConverter( $this->linker );
		$desc = $this->getDescription( $feed, $rc, $feed['style'] != 'structure' );
		if ( !$desc ) {
			return null;
		}
		if ( defined( 'MW_VERSION' ) && version_compare( MW_VERSION, '1.37', '>=' ) ) {
			// @phan-suppress-next-line PhanUndeclaredMethod, PhanUndeclaredStaticMethod
			$title = Title::castFromPageReference( $rc->getPage() );
		} else {
			$title = $rc->getTitle();
		}
		return $this->makePostData( $attribs, $feed, $color, $desc, $comment,
			User::newFromIdentity( $rc->getPerformerIdentity() ), $title );
	}

	/**
	 * @param array $feed
	 * @param RecentChange $rc
	 * @param bool $includeTools
	 * @return string
	 */
	private function getDescription( array $feed, RecentChange $rc, bool $includeTools = true ): string {
		$attribs = $rc->getAttributes();
		$rcType = $attribs['rc_type'];
		$user = User::newFromIdentity( $rc->getPerformerIdentity() );
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

			$titleObj = $rc->getTitle();
			if ( $includeTools ) {
				$params = [
					// $1: username
					$this->linker->makeUserTextWithTools( $user ),
					// $2: username for GENDER
					$user->getName(),
					// $3
					$this->linker->makePageTextWithTools( $titleObj, $attribs['rc_this_oldid'],
					$attribs['rc_last_oldid'] ?? null ),
				];
			} else {
				$params = [
					// $1: username
					$user->getName(),
					// $2: username for GENDER
					$user->getName(),
					// $3
					$titleObj->getFullText(),
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
	 * @param array $feed
	 * @param int $color
	 * @param string $desc
	 * @param string $comment
	 * @param User|null $performer
	 * @param Title|null $title
	 * @return string
	 */
	private function makePostData(
		array $attribs,
		array $feed,
		int $color = Constants::COLOR_DEFAULT,
		string $desc = '',
		string $comment = '',
		User $performer = null,
		Title $title = null
	): string {
		global $wgSitename;
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
						'value' => $this->linker->makeUserTools( $performer, PHP_EOL ),
						'inline' => true,
					];
				}
				if ( $title ) {
					$post['embeds'][0]['fields'][] = [
						'name' => $title->getFullText(),
						'value' => $this->linker->makePageTools( $title, $attribs['rc_this_oldid'],
							$attribs['rc_last_oldid'] ?? null, PHP_EOL ),
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
