<?php
namespace MediaWiki\Extension\DiscordRCFeed;

final class Constants {
	public const DEFAULT_PARAMS = [
		'formatter' => RCFeedFormatter::class,
		'class' => RCFeedEngine::class,
		'user_tools' => [
			[
				'target' => 'talk',
				'msg' => 'talkpagelinktext'
			],
			[
				'target' => 'special',
				'special' => 'Block',
				'msg' => 'blocklink'
			],
			[
				'target' => 'special',
				'special' => 'Contributions',
				'msg' => 'contribslink'
			],
		],
		'page_tools' => [
			[
				'query' => 'action=edit',
				'msg' => 'edit'
			],
			[
				'query' => 'action=history',
				'msg' => 'hist'
			],
		],
	];

	private const ACTION_COLOR_MAP = [
		'new'      => '3580392',
		'edit'     => '2993970',

		// Logs
		'delete'   => '15217973',
		'move'     => '14038504',
		'protect'  => '3493864',
		'upload'   => '3580392',
		'newusers' => '3580392',
		'rights'   => '2993970',
		'block'    => '15217973',
		'import'   => '2993970',

		// Etc
		'flow'     => '2993970',
	];
}
