<?php

namespace MediaWiki\Extension\DiscordRCFeed\LogFormatter;

/**
 * This class must be constructed after calling
 * `class_alias( Parent::class, __NAMESPACE__ . '\ArbitraryParentLogFormatter' );`
 * The craziest hack for arbitrary parent extending.
 * https://stackoverflow.com/a/37895055/10916512
 */
// @phan-suppress-next-line PhanUndeclaredExtendedClass
class ContentLanguageLogFormatter extends ArbitraryParentLogFormatter {

	/**
	 * Show messages always in the content language of the wiki.
	 * @inheritDoc
	 */
	protected function msg( $key, ...$params ) {
		// @phan-suppress-next-line PhanUndeclaredClass
		return parent::msg( $key, ...$params )->inContentLanguage();
	}

}
