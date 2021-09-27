<?php
namespace MediaWiki\Extension\DiscordRCFeed;

use MediaWiki\MediaWikiServices;
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
	 * @param array $tools
	 * @param callable $makeLink
	 * @param string|null $sep separator
	 * @return string
	 */
	private static function makeTools( array $tools, callable $makeLink, $sep = null ) {
		$links = [];
		foreach ( $tools as $tool ) {
			$link = $makeLink( $tool );
			if ( !$link ) {
				continue;
			}
			$label = isset( $tool['msg'] ) ? Util::msgText( $tool['msg'] ) : $tool['text'];
			$links[] = self::makeLink( $link, $label );
		}
		return implode( $sep ?: Util::msgText( 'pipe-separator' ), $links );
	}

	/**
	 * @param User $user
	 * @param string|null $sep separator
	 * @param bool $includeSelf
	 * @return string
	 */
	public function makeUserTools( User $user, $sep = null, $includeSelf = false ): string {
		return self::makeTools(
			$this->userTools,
			static function ( $tool ) use ( $user, $includeSelf ) {
				if ( $tool['target'] == 'user_page' && !$includeSelf ) {
					return null;
				}
				if ( $tool['target'] == 'talk' ) {
					return $user->getTalkPage()->getFullURL();
				}
				return SpecialPage::getTitleFor( $tool['special'], $user->getName() )->getFullURL();
			},
			$sep
		);
	}

	/**
	 * @param Title $title
	 * @param string|null $sep separator
	 * @param bool $includeSelf
	 * @return string
	 */
	public function makePageTools( Title $title, $sep = null, $includeSelf = false ): string {
		return self::makeTools(
			$this->pageTools,
			static function ( $tool ) use ( $title, $includeSelf ) {
				if ( $tool['target'] == 'view' && !$includeSelf ) {
					return null;
				} elseif ( $tool['target'] == 'diff' ) {
					$store = MediaWikiServices::getInstance()->getRevisionStore();
					$revision = $store->getRevisionByTitle( $title );
					if ( !$revision ) {
						return null;
					}
					$parentId = $revision->getParentId();
					if ( !$parentId ) {
						// New page, skips diff
						return null;
					}
					$revisionId = $revision->getId();
					return $title->getFullURL( "oldid=$revisionId&diff=prev" );
				}
				return $title->getFullURL( $tool['query'] );
			},
			$sep
		);
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
	 * Gets nice HTML text for article containing the link to article page
	 * and also into edit, delete and article history pages.
	 * @param Title $title
	 * @return string
	 */
	public function makePageTextWithTools( Title $title ): string {
		$rt = self::makeLink( $title->getFullURL(), $title->getFullText() );
		if ( $this->pageTools ) {
			$tools = $this->makePageTools( $title );
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
