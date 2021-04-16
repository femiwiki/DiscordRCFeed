<?php

namespace MediaWiki\Extension\DiscordNotifications;

use APIBase;
use Config;
use Exception;
use ExtensionRegistry;
use MediaWiki\MediaWikiServices;
use SpecialPage;
use Title;

class Hooks implements
	\MediaWiki\Auth\Hook\LocalUserCreatedHook,
	\MediaWiki\Hook\AfterImportPageHook,
	\MediaWiki\Hook\BlockIpCompleteHook,
	\MediaWiki\Hook\PageMoveCompleteHook,
	\MediaWiki\Hook\UploadCompleteHook,
	\MediaWiki\Page\Hook\ArticleDeleteCompleteHook,
	\MediaWiki\Page\Hook\ArticleProtectCompleteHook,
	\MediaWiki\Storage\Hook\PageSaveCompleteHook,
	\MediaWiki\User\Hook\UserGroupsChangedHook
{

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @var Core
	 */
	private $core;

	/**
	 * @param Config $config
	 */
	public function __construct(
		Config $config
	) {
		$this->config = $config;
		$this->core = new Core();
	}

	/**
	 * @param Core $core
	 */
	public function setCore( Core $core ) {
		$this->core = $core;
	}

	/**
	 * @inheritDoc
	 */
	public function onPageSaveComplete( $wikiPage, $user, $summary, $flags, $revisionRecord, $editResult ) {
		global $wgDiscordNotificationsActions, $wgDiscordNotificationsDisplay;
		$isNew = (bool)( $flags & EDIT_NEW );
		$isMinor = (bool)( $flags & EDIT_MINOR );

		if ( Core::titleIsExcluded( $wikiPage->getTitle() ) ) {
			return true;
		}

		if ( $summary != '' ) {
			$summary = wfMessage( 'discordnotifications-summary', $summary )->inContentLanguage()->plain();
		}
		if ( $wgDiscordNotificationsActions['add-page'] && $isNew ) {
			$message = wfMessage( 'discordnotifications-article-created' )
				->plaintextParams(
					LinkRenderer::getDiscordUserText( $user ),
					LinkRenderer::getDiscordArticleText( $wikiPage ),
					$summary
				)->inContentLanguage()->text();
			if ( $wgDiscordNotificationsDisplay['diff'] ?? true ) {
				$size = Core::msg( 'discordnotifications-bytes', $revisionRecord->getSize() );
				$message .= " ($size)";
			}
			$this->core->pushDiscordNotify( $message, $user, 'article_inserted' );
		} elseif ( $wgDiscordNotificationsActions['edit-page'] && !$isNew && !$isMinor ) {
			$message = wfMessage( 'discordnotifications-article-saved' )
				->plaintextParams(
					LinkRenderer::getDiscordUserText( $user ),
					Core::msg( 'discordnotifications-article-saved-edit' ),
					LinkRenderer::getDiscordArticleText( $wikiPage, $revisionRecord->getId() ),
					$summary
				)->inContentLanguage()->text();
			if ( $wgDiscordNotificationsDisplay['diff'] ) {
				$old = MediaWikiServices::getInstance()->getRevisionLookup()->getPreviousRevision( $revisionRecord );
				if ( $old ) {
					$message .= ' (' . Core::msg( 'discordnotifications-bytes',
						$revisionRecord->getSize() - $old->getSize() ) . ')';
				}
			}
			$this->core->pushDiscordNotify( $message, $user, 'article_saved' );
		} elseif ( $wgDiscordNotificationsActions['minor-edit-page'] && $isMinor ) {
			$message = wfMessage( 'discordnotifications-article-saved' )
				->plaintextParams(
					LinkRenderer::getDiscordUserText( $user ),
					Core::msg( 'discordnotifications-article-saved-minor-edits' ),
					LinkRenderer::getDiscordArticleText( $wikiPage, $revisionRecord->getId() ),
					$summary
				)->inContentLanguage()->text();
			if ( $wgDiscordNotificationsDisplay['diff'] ) {
				$old = MediaWikiServices::getInstance()->getRevisionLookup()->getPreviousRevision( $revisionRecord );
				if ( $old ) {
					$message .= ' (' . Core::msg( 'discordnotifications-bytes',
						$revisionRecord->getSize() - $old->getSize() ) . ')';
				}
			}
			$this->core->pushDiscordNotify( $message, $user, 'article_saved' );
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function onArticleDeleteComplete( $wikiPage, $user, $reason, $id,
		$content, $logEntry, $archivedRevisionCount
	) {
		global $wgDiscordNotificationsActions;
		if ( !$wgDiscordNotificationsActions['remove-page'] ) {
			return;
		}

		global $wgDiscordNotificationsShowSuppressed;
		if ( !$wgDiscordNotificationsShowSuppressed && $logEntry->getType() != 'delete' ) {
			return;
		}

		// Discard notifications from excluded pages
		if ( Core::titleIsExcluded( $wikiPage->getTitle() ) ) {
			return;
		}

		$message = wfMessage( 'discordnotifications-article-deleted' )->plaintextParams(
			LinkRenderer::getDiscordUserText( $user ),
			LinkRenderer::getDiscordArticleText( $wikiPage ),
			$reason
		)->inContentLanguage()->text();
		$this->core->pushDiscordNotify( $message, $user, 'article_deleted' );
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function onPageMoveComplete( $old, $new, $user, $pageid, $redirid,
			$reason, $revision
		) {
		if ( !( $old instanceof Title ) || !( $new instanceof Title ) ) {
			return;
		}
		global $wgDiscordNotificationsActions;
		if ( !$wgDiscordNotificationsActions['move-page'] ) {
			return;
		}

		$message = Core::msg( 'discordnotifications-article-moved',
			LinkRenderer::getDiscordUserText( $user ),
			LinkRenderer::getDiscordArticleText( $old ),
			LinkRenderer::getDiscordArticleText( $new ),
			$reason );
		$this->core->pushDiscordNotify( $message, $user, 'article_moved' );
	}

	/**
	 * @inheritDoc
	 */
	public function onLocalUserCreated( $user, $autocreated ) {
		global $wgDiscordNotificationsActions, $wgDiscordNotificationsDisplay;

		if ( !$wgDiscordNotificationsActions['new-user'] ) {
			return;
		}

		$email = '';
		$realName = '';
		try {
			$email = $user->getEmail();
		} catch ( Exception $e ) {
		}
		try {
			$realName = $user->getRealName();
		} catch ( Exception $e ) {
		}

		$messageExtra = '';
		if ( $wgDiscordNotificationsDisplay['full-name'] ?? false ) {
			$messageExtra = '(';
			$messageExtra .= $realName . ', ';
			// Remove trailing ,
			$messageExtra = substr( $messageExtra, 0, -2 );
			$messageExtra .= ')';
		}

		$message = Core::msg( 'discordnotifications-new-user',
			LinkRenderer::getDiscordUserText( $user ),
			$messageExtra );
		$this->core->pushDiscordNotify( $message, $user, 'new_user_account' );
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function onBlockIpComplete( $block, $user, $priorBlock ) {
		global $wgDiscordNotificationsActions;
		if ( !$wgDiscordNotificationsActions['block-user'] ) {
			return;
		}

		$mReason = $block->getReasonComment()->text;

		$message = Core::msg( 'discordnotifications-block-user',
			LinkRenderer::getDiscordUserText( $user ),
			LinkRenderer::getDiscordUserText( $block->getTarget() ),
			$mReason == '' ? '' : Core::msg( 'discordnotifications-block-user-reason' ) . " '" . $mReason . "'.",
			$block->mExpiry,
			LinkRenderer::makeLink( SpecialPage::getTitleFor( 'Block' )->getFullURL(),
				Core::msg( 'discordnotifications-block-user-list' ) ) );
		$this->core->pushDiscordNotify( $message, $user, 'user_blocked' );
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function onUploadComplete( $uploadBase ) {
		global $wgDiscordNotificationsActions;
		if ( !$wgDiscordNotificationsActions['upload-file'] ) {
			return;
		}

		global $wgUser;
		$localFile = $uploadBase->getLocalFile();

		# Use bytes, KiB, and MiB, rounded to two decimal places.
		$fSize = $localFile->size;
		$fUnits = '';
		if ( $localFile->size < 2048 ) {
			$fUnits = 'bytes';
		} elseif ( $localFile->size < 2048 * 1024 ) {
			$fSize /= 1024;
			$fSize = round( $fSize, 2 );
			$fUnits = 'KiB';
		} else {
			$fSize /= 1024 * 1024;
			$fSize = round( $fSize, 2 );
			$fUnits = 'MiB';
		}

		$message = Core::msg( 'discordnotifications-file-uploaded',
			LinkRenderer::getDiscordUserText( $wgUser ),
			LinkRenderer::parseUrl( $uploadBase->getLocalFile()->getTitle()->getFullURL() ),
			$localFile->getTitle(),
			$localFile->getMimeType(),
			$fSize, $fUnits,
			$localFile->getDescription() );

		$this->core->pushDiscordNotify( $message, $wgUser, 'file_uploaded' );
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function onArticleProtectComplete( $wikiPage, $user, $protect, $reason ) {
		global $wgDiscordNotificationsActions;
		if ( !$wgDiscordNotificationsActions['protect-page'] ) {
			return;
		}

		$message = Core::msg( 'discordnotifications-article-protected',
			LinkRenderer::getDiscordUserText( $user ),
			Core::msg( $protect ? 'discordnotifications-article-protected-change' :
				'discordnotifications-article-protected-remove' ),
			LinkRenderer::getDiscordArticleText( $wikiPage ),
			$reason );
		$this->core->pushDiscordNotify( $message, $user, 'article_protected' );
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function onUserGroupsChanged(
		$user,
		$added,
		$removed,
		$performer,
		$reason,
		$oldUGMs,
		$newUGMs
	) {
		global $wgDiscordNotificationsActions;
		if ( !$wgDiscordNotificationsActions['change-user-groups'] ) {
			return;
		}

		$message = Core::msg( 'discordnotifications-change-user-groups-with-old',
			LinkRenderer::getDiscordUserText( $performer ),
			LinkRenderer::getDiscordUserText( $user ),
			implode( ', ', array_keys( $oldUGMs ) ),
			implode( ', ', $user->getGroups() ),
			LinkRenderer::makeLink( SpecialPage::getTitleFor( 'Userrights', $performer->getName() )->getFullURL(),
				Core::msg( 'discordnotifications-view-user-rights' ) ) );
		$this->core->pushDiscordNotify( $message, $user, 'user_groups_changed' );
		return true;
	}

	/**
	 * Occurs after the execute() method of an Flow API module
	 * @param APIBase $module
	 * @return void
	 */
	public static function onAPIFlowAfterExecute( APIBase $module ) {
		global $wgDiscordNotificationsActions;

		if ( !$wgDiscordNotificationsActions['flow'] || !ExtensionRegistry::getInstance()->isLoaded( 'Flow' ) ) {
			return;
		}

		global $wgRequest;
		$action = $module->getModuleName();
		$request = $wgRequest->getValues();
		$result = $module->getResult()->getResultData()['flow'][$action];
		if ( $result['status'] != 'ok' ) {
			return;
		}

		$title = Title::newFromText( $request['page'] );
		if ( Core::titleIsExcluded( $title ) ) {
			return;
		}

		global $wgUser;
		switch ( $action ) {
			case 'edit-header':
				$message = Core::msg( 'discordnotifications-flow-edit-header',
					LinkRenderer::getDiscordUserText( $wgUser ),
					LinkRenderer::makeLink( $title->getFullUrl(), $request['page'] ) );
				break;
			case 'edit-post':
				$message = Core::msg( 'discordnotifications-flow-edit-post',
					LinkRenderer::getDiscordUserText( $wgUser ),
					LinkRenderer::makeLink( Title::newFromText( $result['workflow'], NS_TOPIC )->getFullUrl(),
						Core::flowUUIDToTitleText( $result['workflow'] ) ) );
				break;
			case 'edit-title':
				$message = Core::msg( 'discordnotifications-flow-edit-title',
					LinkRenderer::getDiscordUserText( $wgUser ),
					$request['etcontent'],
					LinkRenderer::makeLink( Title::newFromText( $result['workflow'], NS_TOPIC )->getFullUrl(),
						Core::flowUUIDToTitleText( $result['workflow'] ) ) );
				break;
			case 'edit-topic-summary':
				$message = Core::msg( 'discordnotifications-flow-edit-topic-summary',
					LinkRenderer::getDiscordUserText( $wgUser ),
					LinkRenderer::makeLink( Title::newFromText( $result['workflow'], NS_TOPIC )->getFullUrl(),
						Core::flowUUIDToTitleText( $result['workflow'] ) ) );
				break;
			case 'lock-topic':
				$message = Core::msg( 'discordnotifications-flow-lock-topic',
					LinkRenderer::getDiscordUserText( $wgUser ),
					// Messages that can be used here:
					// * discordnotifications-flow-lock-topic-lock
					// * discordnotifications-flow-lock-topic-unlock
					Core::msg( 'discordnotifications-flow-lock-topic-' . $request['cotmoderationState'] ),
					LinkRenderer::makeLink( $title->getFullUrl(),
						Core::flowUUIDToTitleText( $result['workflow'] ) ) );
				break;
			case 'moderate-post':
				$message = Core::msg( 'discordnotifications-flow-moderate-post',
					LinkRenderer::getDiscordUserText( $wgUser ),
					// Messages that can be used here:
					// * discordnotifications-flow-moderate-hide
					// * discordnotifications-flow-moderate-unhide
					// * discordnotifications-flow-moderate-suppress
					// * discordnotifications-flow-moderate-unsuppress
					// * discordnotifications-flow-moderate-delete
					// * discordnotifications-flow-moderate-undelete
					Core::msg( 'discordnotifications-flow-moderate-' . $request['mpmoderationState'] ),
					LinkRenderer::makeLink( $title->getFullUrl(),
						Core::flowUUIDToTitleText( $result['workflow'] ) ) );
				break;
			case 'moderate-topic':
				$message = Core::msg( 'discordnotifications-flow-moderate-topic',
					LinkRenderer::getDiscordUserText( $wgUser ),
					// Messages that can be used here:
					// * discordnotifications-flow-moderate-hide
					// * discordnotifications-flow-moderate-unhide
					// * discordnotifications-flow-moderate-suppress
					// * discordnotifications-flow-moderate-unsuppress
					// * discordnotifications-flow-moderate-delete
					// * discordnotifications-flow-moderate-undelete
					Core::msg( 'discordnotifications-flow-moderate-' . $request['mtmoderationState'] ),
					LinkRenderer::makeLink( $title->getFullUrl(),
						Core::flowUUIDToTitleText( $result['workflow'] ) ) );
				break;
			case 'new-topic':
				$message = Core::msg( 'discordnotifications-flow-new-topic',
					LinkRenderer::getDiscordUserText( $wgUser ),
					LinkRenderer::makeLink(
						Title::newFromText( $result['committed']['topiclist']['topic-id'], NS_TOPIC )->getFullUrl(),
						$request['nttopic'] ),
					LinkRenderer::makeLink( $title->getFullUrl(), $request['page'] ) );
				break;
			case 'reply':
				$message = Core::msg( 'discordnotifications-flow-reply',
					LinkRenderer::getDiscordUserText( $wgUser ),
					LinkRenderer::makeLink( Title::newFromText( $result['workflow'], NS_TOPIC )->getFullUrl(),
						Core::flowUUIDToTitleText( $result['workflow'] ) ) );
				break;
			default:
				return;
		}
		$core = new Core();
		$core->pushDiscordNotify( $message, $wgUser, 'flow' );
	}

	/**
	 * @inheritDoc
	 */
	public function onAfterImportPage( $title, $foreignTitle, $revCount,
		$sRevCount, $pageInfo
	) {
		global $wgDiscordNotificationsActions;
		if ( !$wgDiscordNotificationsActions['import-page'] ) {
			return;
		}

		$message = Core::msg( 'discordnotifications - import - complete',
			LinkRenderer::getDiscordArticleText( $title ) );
		$this->core->pushDiscordNotify( $message, null, 'import_complete' );
		return true;
	}
}
