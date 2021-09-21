<?php
namespace MediaWiki\Extension\DiscordRCFeed;

use SpecialPage;
use Title;
use User;
use WikiPage;

class LinkRenderer {
	/**
	 * Replaces some special characters on urls. This has to be done as Discord webhook api does not
	 * accept urlencoded text.
	 * @param string $url
	 * @return string
	 */
	public static function parseUrl( string $url ): string {
		foreach ( [
			' ' => '%20',
			'(' => '%28',
			')' => '%29',
		] as $search => $replace ) {
			$url = str_replace( $search, $replace, $url );
		}
		return $url;
	}

	/**
	 * @param string $target
	 * @param string $text
	 * @return string
	 */
	public static function makeLink( string $target, $text ): string {
		$target = self::parseUrl( $target );
		return "[$text]($target)";
	}

	/**
	 * @param string|array $tools
	 * @return string
	 */
	public static function makeNiceTools( $tools ) {
		if ( is_string( $tools ) ) {
			$tools = [ $tools ];
		}
		$sep = wfMessage( 'pipe-separator' )->inContentLanguage()->text();
		$tools = implode( $sep, $tools );
		return wfMessage( 'parentheses', $tools )->inContentLanguage()->text();
	}

	/** @var array */
	private $userTools;

	/** @var array */
	private $pageTools;

	/**
	 * @inheritDoc
	 */
	public function __construct( $userTools = [], $pageTools = [] ) {
		$this->userTools = $userTools;
		$this->pageTools = $pageTools;
	}

	/**
	 * Gets nice HTML text for user containing the link to user page
	 * and also links to user site, groups editing, talk and contribs pages.
	 * @param User $user
	 * @return string
	 */
	public function getDiscordUserText( $user ) {
		$name = $user->getName();

		$rt = self::makeLink( $user->getUserPage()->getFullURL(), $name );
		if ( $this->userTools && $user instanceof User ) {
			$tools = [];
			foreach ( $this->userTools as $tool ) {
				if ( $tool['target'] == 'talk' ) {
					$link = $user->getTalkPage()->getFullURL();
				} else {
					$link = SpecialPage::getTitleFor( $tool['special'], $name )->getFullURL();
				}
				$text = isset( $tool['msg'] ) ? Core::msg( $tool['msg'] ) : $text = $tool['text'];
				$tools[] = self::makeLink( $link, $text );
			}
			$tools = self::MakeNiceTools( $tools );
			$rt .= " $tools";
		}
		return $rt;
	}

	/**
	 * Gets nice HTML text for article containing the link to article page
	 * and also into edit, delete and article history pages.
	 * @param WikiPage|Title $title
	 * @param int|bool $thisOldId
	 * @param int|bool $lastOldId
	 * @return string
	 */
	public function getDiscordArticleText( $title, $thisOldId = false, $lastOldId = false ) {
		if ( $title instanceof WikiPage ) {
			$title = $title->getTitle();
		}
		$link = self::makeLink( $title->getFullURL(), $title->getFullText() );
		if ( $this->pageTools ) {
			$tools = [];
			foreach ( $this->pageTools as $tool ) {
				$tools[] = self::makeLink( $title->getFullURL( $tool['query'] ),
					Core::msg( $tool['msg'] ) );
			}
			if ( $thisOldId && $lastOldId ) {
				$tools[] = self::makeLink( $title->getFullURL( "diff=$thisOldId&oldid=$lastOldId" ),
					Core::msg( 'diff' ) );
			}
			$tools = self::makeNiceTools( $tools );
			$link .= " $tools";
		}
		return $link;
	}

	/**
	 * @param string $wt wikitext to parse.
	 * @param bool $includingTools
	 * @return string text with Discord syntax.
	 */
	public function makeLinksClickable( $wt, $includingTools = true ) {
		if ( !preg_match_all( '/\[\[([^]]+)\]\]/', $wt, $matches ) ) {
			return $wt;
		}
		foreach ( $matches[0] as $i => $match ) {
			$titleText = $matches[1][$i];
			$titleObj = Title::newFromText( $titleText );
			if ( !$titleObj ) {
				continue;
			}
			if ( $includingTools ) {
				if ( $titleObj->getNamespace() == NS_USER ) {
					$replacement = $this->getDiscordUserText( User::newFromName( $titleObj ) );
				} else {
					$replacement = $this->getDiscordArticleText( $titleObj );
				}
			} else {
				$replacement = self::makeLink( $titleObj->getFullURL(), $titleText );
			}
			$wt = str_replace( $match, $replacement, $wt );
		}

		return $wt;
	}
}
