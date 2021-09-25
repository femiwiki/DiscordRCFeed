<?php

namespace MediaWiki\Extension\DiscordRCFeed\LogFormatter;

use DatabaseLogEntry;
use LegacyLogFormatter;
use LogEntry;
use LogFormatter as ParentLogFormatter;

class LogFormatter extends ParentLogFormatter {

	/**
	 * @inheritDoc
	 */
	public static function newFromEntry( LogEntry $entry ) {
		global $wgLogActionsHandlers;
		$fulltype = $entry->getFullType();
		$wildcard = $entry->getType() . '/*';
		$handler = $wgLogActionsHandlers[$fulltype] ?? $wgLogActionsHandlers[$wildcard] ?? '';

		if ( $handler === '' || !is_string( $handler ) || !class_exists( $handler ) ) {
			$handler = LegacyLogFormatter::class;
		}

		// The craziest hack for arbitrary parent extending.
		// https://stackoverflow.com/a/37895055/10916512
		class_alias( $handler, __NAMESPACE__ . '\ArbitraryParentLogFormatter' );
		// @phan-suppress-next-line PhanParamTooMany, PhanTypeMismatchReturnProbablyReal
		return new ContentLanguageLogFormatter( $entry );
	}

	/**
	 * @inheritDoc
	 */
	public static function newFromRow( $row ) {
		return self::newFromEntry( DatabaseLogEntry::newFromRow( $row ) );
	}

}
