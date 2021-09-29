<?php

namespace MediaWiki\Extension\DiscordRCFeed;

use ChangesList;
use Flow\Container;
use IContextSource;
use MediaWiki\MediaWikiServices;
use RecentChange;
use Sanitizer;

class FlowDiscordFormatter extends \Flow\Formatter\ChangesListFormatter {

	/** @var bool */
	private $plaintext = false;

	/** @var IContextSource */
	private $context;

	/** @var array */
	private $data;

	/** @var HtmlToDiscordConverter */
	private $converter;

	/** @var string[] */
	private $i18nProperties;

	/**
	 * @inheritDoc
	 */
	protected function getHistoryType() {
		return '';
	}

	/**
	 * @param RecentChange $rc
	 * @param HtmlToDiscordConverter $converter
	 * @param bool $plaintext
	 */
	public function __construct( RecentChange $rc, HtmlToDiscordConverter $converter, $plaintext = true ) {
		$permissions = MediaWikiServices::getInstance()->getService( 'FlowPermissions' );
		$revisionFormatter = Container::get( 'formatter.revision.factory' )->create();
		parent::__construct( $permissions, $revisionFormatter );

		$this->plaintext = $plaintext;
		$this->converter = $converter;

		// Additional $rc specific initializing
		$query = Container::get( 'query.changeslist' );
		$this->context = Util::getContentLanguageContext();
		$changesList = new ChangesList( $this->context );
		$row = $query->getResult( $changesList, $rc );

		// Get data for formatting
		$this->serializer->setIncludeHistoryProperties( true );
		$this->serializer->setIncludeContent( true );
		$data = $this->serializer->formatApi( $row, $this->context, 'recentchanges' );
		if ( $data && is_array( $data ) ) {
			$this->storeI18Properties( $data['properties'] );
			$this->data = $this->modifyData( $data );
		}
	}

	/**
	 * @param array $properties
	 */
	private function storeI18Properties( $properties ) {
		$keyMap = [
			'summary' => [
				'moderated-reason',
			],
			'post-of-summary' => [
				'topic-of-post-text-from-html',
			],
		];
		foreach ( $keyMap as $target => $sources ) {
			if ( isset( $properties[$target] ) ) {
				$this->i18nProperties[$target] = $properties[$target]['plaintext'] ?? $properties[$target];
				unset( $properties[$target] );
			}
			foreach ( $sources as $src ) {
				if ( isset( $properties[$src] ) ) {
					$this->i18nProperties[$target] = $properties[$src]['plaintext'] ?? $properties[$src];
					unset( $properties[$src] );
				}
			}
		}

		foreach ( $properties as $k => $v ) {
			$this->i18nProperties[$k] = $v['plaintext'] ?? $v;
		}
	}

	/**
	 * @param array $data
	 * @return array
	 */
	private function modifyData( $data ): array {
		// The summary should not be included in the main line, because it should be accessed by
		// self::getI18nProperty( 'summary' ).
		foreach ( [
			'summary',
			'moderated-reason',
		] as $key ) {
			if ( isset( $data['properties'][$key] ) && isset( $data['properties'][$key]['plaintext'] ) ) {
				$data['properties'][$key]['plaintext'] = '';
			}
		}

		// Replace user links(user text + links) with user text. We add our own user links.
		if ( $this->plaintext ) {
			$data['properties']['user-links'] = $data['properties']['user-text'];
		}

		return $data;
	}

	/**
	 * @param string $key
	 * @return string
	 */
	public function getI18nProperty( string $key ): string {
		return $this->i18nProperties[$key] ?? '';
	}

	/**
	 * @return string
	 */
	public function getDiscordDescription(): string {
		$data = $this->data;
		if ( !$data ) {
			return '';
		}

		$changeType = $data['changeType'];
		$actions = $this->permissions->getActions();

		$key = $actions->getValue( $changeType, 'history', 'i18n-message' );
		$msg = $this->context->msg( $key );

		// Fetch message
		$desc = $msg->params( $this->getDescriptionParams( $data, $actions, $changeType ) )->parse();

		// Remove tags
		if ( $this->plaintext ) {
			$desc = Sanitizer::stripAllTags( $desc );
		} else {
			$desc = $this->converter->convert( $desc );
		}

		// Remove empty parentheses which wrapped the removed summary.
		$desc = str_replace( '()', '', $desc );

		return $desc;
	}
}
