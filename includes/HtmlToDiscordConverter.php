<?php
namespace MediaWiki\Extension\DiscordRCFeed;

use ExtensionRegistry;
use Sanitizer;
use Title;
use User;

class HtmlToDiscordConverter {
	/** @var string */
	private const REGEX_USER = '#<a[^>]+class=[\'"][^\'"]*mw-userlink[^\'"]*[\'"][^>]*><bdi>([^<+]+)</bdi></a>#';

	/** @var string */
	private const REGEX_USER_LINKS = '#<span[^>]+class=[\'"][^\'"]*mw-usertoollinks[^\'"]*[\'"][^>]*>.+</span>#';

	/** @var string */
	private const REGEX_TITLE = '#<a[^>]+href=[\'"][^\'"=]*[/=]([^\'"]+)[\'"][^>]*title=[^>]*>([^<+]+)</a>#';

	/** @var string */
	private const REGEX_LINK = '#<a[^>]+href=[\'"]([^\'"]+)[\'"][^>]*>([^<+]+)</a>#';

	/** @var DiscordLinker */
	private $linker;

	/**
	 * @param DiscordLinker|null $linker
	 */
	public function __construct( $linker = null ) {
		$this->linker = $linker ?? new DiscordLinker();
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function convert( string $text ): string {
		$text = self::removeUserTools( $text );
		$text = $this->replaceUserName( $text );
		$text = $this->replaceTitleLinks( $text );
		$text = self::replaceLinks( $text );

		$text = Sanitizer::stripAllTags( $text );
		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function replaceUserName( string $text ): string {
		if ( preg_match_all( self::REGEX_USER, $text, $matches ) ) {
			foreach ( $matches[0] as $i => $group ) {
				$username = $matches[1][$i];
				$user = User::newFromName( $username );
				if ( !$user ) {
					continue;
				}
				$replace = $this->linker->getDiscordUserTextWithTools( $user );
				$text = str_replace( $group, $replace, $text );
			}
		}
		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private static function removeUserTools( string $text ): string {
		return preg_replace( self::REGEX_USER_LINKS, '', $text );
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function replaceTitleLinks( string $text ): string {
		if ( preg_match_all( self::REGEX_TITLE, $text, $matches ) ) {
			foreach ( $matches[1] as $i => $group ) {
				$capture = $matches[0][$i];
				$url = $matches[1][$i];
				$label = $matches[2][$i];

				$title = Title::newFromUrl( $url );
				if ( !$title ) {
					continue;
				}
				if ( self::shouldIncludeTitleLinks( $title ) ) {
					$replace = $this->linker->getDiscordPageTextWithTools( $title );
				} else {
					$replace = DiscordLinker::makeLink( $title->getFullURL(), $label );
				}
				$text = str_replace( $capture, $replace, $text );
			}
		}
		return $text;
	}

	/**
	 * @param Title|null $title
	 * @return bool
	 */
	private static function shouldIncludeTitleLinks( $title ): bool {
		if ( !$title ) {
			return true;
		}
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Flow' ) ) {
			if ( $title->getContentModel() == CONTENT_MODEL_FLOW_BOARD ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private static function replaceLinks( string $text ): string {
		if ( preg_match_all( self::REGEX_LINK, $text, $matches ) ) {
			foreach ( $matches[1] as $i => $group ) {
				$capture = $matches[0][$i];
				// Links can omit some parts (example: "/index.php/Title"). Prepend it.
				$url = parse_url( $matches[1][$i] );
				$url = Util::urlUnparse( $url );
				$label = $matches[2][$i];

				$link = DiscordLinker::makeLink( $url, $label );
				$text = str_replace( $capture, $link, $text );
			}
		}
		return $text;
	}
}
