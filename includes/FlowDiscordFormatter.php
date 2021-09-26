<?php

namespace MediaWiki\Extension\DiscordRCFeed;

use Flow\Container;
use Flow\FlowActions;
use Flow\Formatter\ChangesListFormatter as FlowChangesListFormatter;
use Flow\Formatter\RecentChangesRow;
use IContextSource;
use MediaWiki\MediaWikiServices;

class FlowDiscordFormatter extends FlowChangesListFormatter {
	/**
	 * @inheritDoc
	 */
	protected function getHistoryType() {
		return '';
	}

	/** @var bool */
	public $plaintext = false;

	/**
	 * @inheritDoc
	 */
	public function format( RecentChangesRow $row, IContextSource $ctx, $linkOnly = false ) {
		$this->serializer->setIncludeHistoryProperties( true );
		$this->serializer->setIncludeContent( true );

		$data = $this->serializer->formatApi( $row, $ctx, 'recentchanges' );
		if ( !$data ) {
			return false;
		}
		$text = $this->getDescription( $data, $ctx )->parse();
		$titleLink = $this->getTitleLink( $data, $row, $ctx );
		if ( $titleLink ) {
			$titleLink = $ctx->msg( 'parentheses' )->rawParams( $titleLink )->text();
			$text .= " $titleLink";
		}
		return $text;
	}

	/**
	 * @return self
	 */
	public static function getInstance() {
		$permissions = MediaWikiServices::getInstance()->getService( 'FlowPermissions' );
		$revisionFormatter = Container::get( 'formatter.revision.factory' )->create();
		return new self( $permissions, $revisionFormatter );
	}

	/**
	 * @inheritDoc
	 */
	protected function getDescriptionParams( array $data, FlowActions $actions, $changeType ) {
		if ( !$this->plaintext ) {
			return parent::getDescriptionParams( $data,  $actions, $changeType );
		}
		$source = $actions->getValue( $changeType, 'history', 'i18n-params' );
		$params = [];
		foreach ( $source as $param ) {
			if ( isset( $data['properties'][$param] ) ) {
				$params[] = $param == 'user-links' ?
					$data['properties']['user-text'] : $data['properties'][$param];
			} else {
				wfDebugLog( 'Flow', __METHOD__ .
					": Missing expected parameter $param for change type $changeType" );
				$params[] = '';
			}
		}

		return $params;
	}
}
