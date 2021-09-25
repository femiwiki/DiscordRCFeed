<?php

namespace MediaWiki\Extension\DiscordRCFeed;

use Flow\Container;
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
		// $text = Utils::htmlToPlaintext( $text );
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
}
