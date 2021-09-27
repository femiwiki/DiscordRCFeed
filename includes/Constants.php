<?php
namespace MediaWiki\Extension\DiscordRCFeed;

final class Constants {
	public const DEFAULT_RC_FEED_PARAMS = [
		'formatter' => DiscordRCFeedFormatter::class,
		'class' => DiscordRCFeedEngine::class,
		'style' => 'embed',
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
		'omit_log_types' => [
			'patrol',
		]
	];

	private const COLOR_GRAY = 0xb3b4bc;
	private const COLOR_GREEN = 0x2daf32;
	private const COLOR_BLUE = 0x36a1e8;
	private const COLOR_RED = 0xe83535;
	private const COLOR_MAGENTA = 0xd635e8;
	private const COLOR_CYAN = 0x00ffff;
	private const COLOR_DARK_BLUE = 0x354fe8;

	public const COLOR_DEFAULT = self::COLOR_GRAY;

	public const COLOR_MAP_ACTION = [
		RC_EDIT => self::COLOR_GREEN,
		RC_NEW  => self::COLOR_BLUE,
		RC_LOG  => self::COLOR_GREEN,
	];

	public const COLOR_MAP_LOG = [
		'delete'   => self::COLOR_RED,
		'move'     => self::COLOR_MAGENTA,
		'protect'  => self::COLOR_DARK_BLUE,
		'upload'   => self::COLOR_BLUE,
		'newusers' => self::COLOR_BLUE,
		'rights'   => self::COLOR_GREEN,
		'block'    => self::COLOR_RED,
		'import'   => self::COLOR_GREEN,
	];

	public const COLOR_ACTION_FLOW = self::COLOR_CYAN;
}
