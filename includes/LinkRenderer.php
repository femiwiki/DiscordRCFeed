<?php
namespace MediaWiki\Extension\DiscordNotifications;

use MediaWiki\User\UserIdentity;
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
	public static function parseUrl( string $url ) : string {
		foreach ( [
			" " => "%20",
			"(" => "%28",
			")" => "%29"
		] as $ch => $rep ) {
			$url = str_replace( $ch, $rep, $url );
		}
		return $url;
	}

	/**
	 * @param string $target
	 * @param string $text
	 * @return string
	 */
	public static function makeLink( string $target, $text ) : string {
		$target = self::parseUrl( $target );
		return "<$target|$text>";
	}

	/**
	 * @param string|array $tools
	 * @return string
	 */
	public static function makeNiceTools( $tools ) {
		if ( is_string( $tools ) ) {
			$tools = [ $tools ];
		}
		return '(' . implode( ' | ', $tools ) . ')';
	}

	/**
	 * Gets nice HTML text for user containing the link to user page
	 * and also links to user site, groups editing, talk and contribs pages.
	 * @param User|UserIdentity $user
	 * @return string
	 */
	public static function getDiscordUserText( $user ) {
		global $wgDiscordNotificationWikiUrl, $wgDiscordNotificationWikiUrlEnding,
			$wgDiscordNotificationWikiUrlEndingBlockUser, $wgDiscordNotificationWikiUrlEndingUserRights,
			$wgDiscordNotificationWikiUrlEndingUserContributions,
			$wgDiscordIncludeUserUrls;

		$name = $user->getName();
		$userUrl = str_replace( "&", "%26", $name );
		$prefix = $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding;
		if ( $wgDiscordIncludeUserUrls && $user instanceof User ) {
			$tools = self::MakeNiceTools( [
				self::makeLink( $prefix . $wgDiscordNotificationWikiUrlEndingBlockUser . $userUrl,
					Core::msg( 'discordnotifications-block' ) ),
				self::makeLink( $prefix . $wgDiscordNotificationWikiUrlEndingUserRights . $userUrl,
					Core::msg( 'discordnotifications-groups' ) ),
				self::makeLink( $user->getTalkPage()->getFullURL(), Core::msg( 'discordnotifications-talk' ) ),
				self::makeLink( $prefix . $wgDiscordNotificationWikiUrlEndingUserContributions . $userUrl,
					Core::msg( 'discordnotifications-contribs' ) )
			] );
			return self::makeLink( $user->getUserPage()->getFullURL(), $name ) . " $tools";
		} else {
			return self::makeLink( $user->getUserPage()->getFullURL(), $name );
		}
	}

	/**
	 * Gets nice HTML text for article containing the link to article page
	 * and also into edit, delete and article history pages.
	 * @param WikiPage|Title $title
	 * @param int|bool $newId
	 * @return string
	 */
	public static function getDiscordArticleText( $title, $newId = false ) {
		global $wgDiscordIncludePageUrls;

		if ( $title instanceof WikiPage ) {
			$title = $title->getTitle();
		}
		$fullText = $title->getFullText();
		$titleUrl = str_replace( "&", "%26", $fullText );
		if ( $wgDiscordIncludePageUrls ) {
			$tools = [
				self::makeLink( $title->getFullURL( 'action=edit' ),
					Core::msg( 'discordnotifications-edit' ) ),
				self::makeLink( $title->getFullURL( 'action=delete' ),
					Core::msg( 'discordnotifications-delete' ) ),
				self::makeLink( $title->getFullURL( 'action=history' ),
					Core::msg( 'discordnotifications-history' ) )
			];
			if ( $newId ) {
				$tools[] = self::makeLink( $title->getFullURL( "diff=prev&oldid=$newId" ),
					Core::msg( 'discordnotifications-diff' ) );
			}
			$tools = self::MakeNiceTools( $tools );
			return self::makeLink( $title->getFullURL(), $fullText ) . " $tools";
		} else {
			return self::makeLink( $title->getFullURL(), $fullText );
		}
	}
}
