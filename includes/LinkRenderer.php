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
		$tools = implode( ' | ', $tools );
		return "($tools)";
	}

	/**
	 * @param User|WikiPage|Title $target
	 * @return string
	 */
	public static function getDiscordText( $target ) {
		if ( $target instanceof User ) {
			return self::getDiscordUserText( $target );
		}
		return self::getDiscordArticleText( $target );
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
	 * @param int|bool $newId
	 * @return string
	 */
	public function getDiscordArticleText( $title, $newId = false ) {
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
			if ( $newId ) {
				$tools[] = self::makeLink( $title->getFullURL( "diff=prev&oldid=$newId" ),
					Core::msg( 'discordrcfeed-diff' ) );
			}
			$tools = self::makeNiceTools( $tools );
			$link .= " $tools";
		}
		return $link;
	}
}
