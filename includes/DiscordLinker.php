<?php
namespace MediaWiki\Extension\DiscordRCFeed;

use SpecialPage;
use Title;
use User;

class DiscordLinker {
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
	 * @param User $user
	 * @param string|null $sep separator
	 * @return string
	 */
	public function makeUserTools( User $user, $sep = null ): string {
		$tools = [];
		foreach ( $this->userTools as $tool ) {
			if ( $tool['target'] == 'talk' ) {
				$link = $user->getTalkPage()->getFullURL();
			} else {
				$link = SpecialPage::getTitleFor( $tool['special'], $user->getName() )->getFullURL();
			}
			$text = isset( $tool['msg'] ) ? Util::msgText( $tool['msg'] ) : $tool['text'];
			$tools[] = self::makeLink( $link, $text );
		}
		return implode( $sep ?: Util::msgText( 'pipe-separator' ), $tools );
	}

	/**
	 * Gets nice HTML text for user containing the link to user page and also links to user site,
	 * groups editing, talk and contribs pages if configured.
	 * @param User $user
	 * @return string
	 */
	public function makeUserTextWithTools( User $user ): string {
		$rt = self::makeLink( $user->getUserPage()->getFullURL(), $user->getName() );
		if ( $this->userTools ) {
			$tools = $this->makeUserTools( $user );
			$tools = Util::msgText( 'parentheses', $tools );
			$rt .= ' ' . $tools;
		}
		return $rt;
	}

	/**
	 * @param Title $title
	 * @param int|null $thisOldId
	 * @param int|null $lastOldId
	 * @param string|null $sep separator
	 * @return string
	 */
	public function makePageTools( Title $title, $thisOldId = null, $lastOldId = null, $sep = null ): string {
		$tools = [];
		foreach ( $this->pageTools as $tool ) {
			$tools[] = self::makeLink( $title->getFullURL( $tool['query'] ),
				Util::msgText( $tool['msg'] ) );
		}
		if ( $thisOldId && $lastOldId ) {
			$tools[] = self::makeLink( $title->getFullURL( "diff=$thisOldId&oldid=$lastOldId" ),
				Util::msgText( 'diff' ) );
		}
		return implode( $sep ?: Util::msgText( 'pipe-separator' ), $tools );
	}

	/**
	 * Gets nice HTML text for article containing the link to article page
	 * and also into edit, delete and article history pages.
	 * @param Title $title
	 * @param int|null $thisOldId
	 * @param int|null $lastOldId
	 * @return string
	 */
	public function makePageTextWithTools( Title $title, $thisOldId = null, $lastOldId = null ): string {
		$rt = self::makeLink( $title->getFullURL(), $title->getFullText() );
		if ( $this->pageTools ) {
			$tools = $this->makePageTools( $title, $thisOldId, $lastOldId );
			$tools = Util::msgText( 'parentheses', $tools );
			$rt .= ' ' . $tools;
		}
		return $rt;
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
