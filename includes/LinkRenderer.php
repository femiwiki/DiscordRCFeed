<?php
namespace MediaWiki\Extension\DiscordRCFeed;

use SpecialPage;
use Title;
use User;

class LinkRenderer {
	/** @var array */
	private $userTools;

	/** @var array */
	private $pageTools;

	/**
	 * @param array $userTools
	 * @param array $pageTools
	 */
	public function __construct( $userTools = [], $pageTools = [] ) {
		$this->userTools = $userTools;
		$this->pageTools = $pageTools;
	}

	/**
	 * Gets nice HTML text for user containing the link to user page and also links to user site,
	 * groups editing, talk and contribs pages if configured.
	 * @param User $user
	 * @return string
	 */
	public function getDiscordUserTextWithTools( User $user ): string {
		$name = self::makeLink( $user->getUserPage()->getFullURL(), $user->getName() );
		if ( $this->userTools ) {
			$tools = [];
			foreach ( $this->userTools as $tool ) {
				if ( $tool['target'] == 'talk' ) {
					$link = $user->getTalkPage()->getFullURL();
				} else {
					$link = SpecialPage::getTitleFor( $tool['special'], $name )->getFullURL();
				}
				$text = isset( $tool['msg'] ) ? Util::msg( $tool['msg'] ) : $tool['text'];
				$tools[] = self::makeLink( $link, $text );
			}
			$tools = self::MakeNiceTools( $tools );
		} else {
			$tools = '';
		}
		return "$name $tools";
	}

	/**
	 * Gets nice HTML text for article containing the link to article page
	 * and also into edit, delete and article history pages.
	 * @param Title $title
	 * @param int|null $thisOldId
	 * @param int|null $lastOldId
	 * @return string
	 */
	public function getDiscordPageTextWithTools( Title $title, $thisOldId = null, $lastOldId = null ): string {
		$page = self::makeLink( $title->getFullURL(), $title->getFullText() );
		if ( $this->pageTools ) {
			$tools = [];
			foreach ( $this->pageTools as $tool ) {
				$tools[] = self::makeLink( $title->getFullURL( $tool['query'] ),
					Util::msg( $tool['msg'] ) );
			}
			if ( $thisOldId && $lastOldId ) {
				$tools[] = self::makeLink( $title->getFullURL( "diff=$thisOldId&oldid=$lastOldId" ),
					Util::msg( 'diff' ) );
			}
			$tools = self::makeNiceTools( $tools );
		} else {
			$tools = '';
		}
		return "$page $tools";
	}

	/**
	 * @param string $wt wikitext to parse.
	 * @param bool $includingTools
	 * @return string text with Discord syntax.
	 */
	public function makeLinksClickable( string $wt, bool $includingTools = true ): string {
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
					$replacement = $this->getDiscordUserTextWithTools( User::newFromName( $titleObj->getText() ) );
				} else {
					$replacement = $this->getDiscordPageTextWithTools( $titleObj );
				}
			} else {
				$replacement = self::makeLink( $titleObj->getFullURL(), $titleText );
			}
			$wt = str_replace( $match, $replacement, $wt );
		}

		return $wt;
	}

	/**
	 * @param string $target
	 * @param string $text
	 * @return string
	 */
	public static function makeLink( string $target, string $text ): string {
		if ( !$target ) {
			return $text;
		}
		$target = self::parseUrl( $target );
		return "[$text]($target)";
	}

	/**
	 * @param array $tools
	 * @return string
	 */
	private static function makeNiceTools( array $tools ): string {
		$tools = implode( Util::msg( 'pipe-separator' ), $tools );
		return Util::msg( 'parentheses', $tools );
	}

	/**
	 * Replaces some special characters on urls. This has to be done as Discord webhook api does not
	 * accept urlencoded text.
	 * @param string $url
	 * @return string
	 */
	private static function parseUrl( string $url ): string {
		foreach ( [
			' ' => '%20',
			'(' => '%28',
			')' => '%29',
		] as $search => $replace ) {
			$url = str_replace( $search, $replace, $url );
		}
		return $url;
	}
}
