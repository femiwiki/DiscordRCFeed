<?php

namespace MediaWiki\Extension\DiscordRCFeed;

use Flow\Container;
use Flow\FlowActions;
use Flow\Formatter\IRCLineUrlFormatter;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use RecentChange;
use User;

class FlowRCFeedFormatter extends IRCLineUrlFormatter {

	/** @var LinkRenderer */
	private $linkRenderer;

	public function __construct() {
		$permissions = MediaWikiServices::getInstance()->getService( 'FlowPermissions' );
		$serializer = Container::get( 'formatter.revision.factory' )->create();
		parent::__construct( $permissions, $serializer );
	}

	/**
	 * @param RecentChange $rc
	 * @return string
	 */
	public function getDiscordLine( RecentChange $rc ) {
		$ctx = \RequestContext::getMain();

		$serialized = $this->serializeRcRevision( $rc, $ctx );
		if ( !$serialized ) {
			LoggerFactory::getInstance( 'DiscordRCFeed' )->debug(
				__METHOD__ . ': Failed to obtain serialized RC revision.'
			);
			return null;
		}

		$msg = $this->getDescription( $serialized, $ctx );
		return $msg->inContentLanguage()->text();
	}

	/**
	 * @param LinkRenderer $linkRenderer
	 */
	public function setLinkRenderer( $linkRenderer ) {
		$this->linkRenderer = $linkRenderer;
	}

	/**
	 * @inheritDoc
	 */
	protected function getDescriptionParams( array $data, FlowActions $actions, $changeType ) {
		$source = $actions->getValue( $changeType, 'history', 'i18n-params' );
		$params = [];
		foreach ( $source as $param ) {
			if ( $param == 'user-text' && $data['properties'][$param] ) {
				$user = User::newFromName( $data['properties'][$param] );
				$params[] = $this->linkRenderer->getDiscordUserText( $user );
			} elseif (
				in_array( $param, [ 'post-of-summary', 'topic-of-post-text-from-html' ] )
				&& $data['properties'][$param]
				) {
				$url = $data['properties']['post-url'] ?? $data['properties']['workflow-url'] ?? '';
				$text = $data['properties'][$param]['plaintext'] ?? $data['properties'][$param];
				$params[] = LinkRenderer::makeLink( $url, $text );
			} elseif ( isset( $data['properties'][$param] ) ) {
				$params[] = $data['properties'][$param];
			} else {
				wfDebugLog( 'Flow', __METHOD__ .
					": Missing expected parameter $param for change type $changeType" );
				$params[] = '';
			}
		}

		return $params;
	}
}
