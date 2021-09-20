# DiscordRCFeed [![Github checks status]][github checks link] [![codecov.io status]][codecov.io link]

**⚠️ Work-in-progress**

- [ ] Remove all TODOs
- [ ] Fancy output for new user event
- [ ] Fix ci test

This is a fork of [kulttuuri/DiscordNotifications] which is an extension for [MediaWiki](https://www.mediawiki.org/wiki/MediaWiki) that sends notifications of actions in your wiki like editing, adding or removing a page into [Discord](https://discordapp.com/) channel.

## Requirements

- Setting a feed requires the [sockets PHP extension]. If the extension is not enabled, actions like edits, moves, etc may work, but the action may not get logged in recent changes at all. See [[Manual:$wgRCFeeds]] for details.
- MediaWiki 1.36+
- Apache should have NE (NoEscape) flag on to prevent issues in URLs. By default you should have this enabled.

## How to install

1. Create a new Discord Webhook for your channel. You can create and manage webhooks for your channel by clicking the settings icon next to channel name in the Discord app. Read more from here: https://support.discord.com/hc/articles/228383668

2. After setting up the Webhook you will get a Webhook URL. Copy that URL as you will need it in step 4.

3. [Download latest release of this extension](https://github.com/kulttuuri/discord_mediawiki/archive/master.zip), uncompress the archive and move folder `DiscordRCFeed` into your `mediawiki_installation/extensions` folder. (And instead of manually downloading the latest version, you could also just git clone this repository to that same extensions folder).

4. Add settings listed below in your `localSettings.php`. Note that it is mandatory to set these settings for this extension to work:

```php
wfLoadExtension( 'DiscordRCFeed' );
$wgRCFeeds['discord'] = [
	// Required. Your Discord webhook URL. Read more from here: https://support.discord.com/hc/articles/228383668
	'url' => 'https://discord.com/api/webhooks/xx/xxxx';
];
```

5. Enjoy the notifications in your Discord room!

## Additional options

You can set the following keys of the associative array:

- `'omit_bots'` - `true` or `false` whether to skip bot edits. Same as described on [Manual:$wgRCFeeds].
- `'omit_anon'` - `true` or `false` whether to skip anon edits. Same as described on [Manual:$wgRCFeeds].
- `'omit_user'` - `true` or `false` whether to skip registered users. Same as described on [Manual:$wgRCFeeds].
- `'omit_minor'` - `true` or `false` whether to skip minor edits. Same as described on [Manual:$wgRCFeeds].
- `'omit_patrolled'` - `true` or `false` whether to skip patrolled edits. Same as described on [Manual:$wgRCFeeds].
- `'omit_namespaces'`, `'omit_types'`, `'omit_log_types'` and `'omit_log_actions'` - Lists for filtering notifications. See [Filtering Notifications](#filtering-notifications) below for details.
- `'user_tools'` and `'page_tools'` - Associative arrays for Controlling the display of tools shown with notification. See [Controlling Page Tools And User Tools](#controlling-page-tools-and-user-tools) below for details.
- `'request_override'` - An array used to override the post data of the webhook request. See [Webhook Request Overriding](#webhook-request-overriding) below for details. Defaults to `[]`.

### Webhook Request Overriding

`$$wgRCFeeds['discord']['request_override']` is an associative array used to override the post data of the webhook request. You can set username or avatar using this instead of setting in Discord.
See https://discord.com/developers/docs/resources/webhook#execute-webhook-jsonform-params for all available parameters.

```php
$wgRCFeeds['discord']['request_override'] = [
  'username' => 'Captain Hook',
  'avatar_url' => '',
];
```

### Filtering Notifications

`'omit_namespace'` is an list that contains namespaces should be omit.

```php
$wgRCFeeds['discord']['omit_namespace'] = [ NS_TALK ];

$wgRCFeeds['discord']['omit_namespace'] = [
  NS_PROJECT,
  NS_PROJECT_TALK,
  NS_TALK,
];
```

Defaults to:

```php
$wgDiscordRCFeedActions = [
  'new-user' => true,
  'block-user' => true,
  'add-page' => true,
  'remove-page' => true,
  'move-page' => true,
  'edit-page' => true,
  'minor-edit-page' => true,
  'upload-file' => true,
  'protect-page' => true,
  'change-user-groups' => true,
  'flow' => true,
  'import-page' => true,
];
```

### Omitting Specific Notifications

`wgDiscordRCFeedExclude` is an associative array to disable notifications related to certain pages or users. This config has below keys:

- `'page'` - Actions (add, edit, modify) won't be notified to Discord room from articles matching with these names.

Defaults to:

```php
$wgDiscordRCFeedExclude = [
  'pages' => [
    'list' => [],
    'pattern' => [],
  ],
];
```

### Controlling Page Tools And User Tools

Page tools And user tools are tools shown after page or user link.

| Option         | Default value | Description                                                                                                             |
| -------------- | ------------- | ----------------------------------------------------------------------------------------------------------------------- |
| `'user_tools'` | array         | If this is false, users will not get additional links in the notification message (block \| groups \| talk \| contribs) |
| `'page_tools'` | array         | If this is false, pages will not get additional links in the notification message (edit \| delete \| history).          |

```php
// Remove page tools
$wgRCFeeds['discord']['page_tools'] = false;

// Override user tools
$wgRCFeeds['discord']['user_tools'] = [
	[
		'target' => 'special',
		'special' => 'Block',
		'text' => 'IP Block'
	],
	[
		'target' => 'talk',
		'text' => 'Discussion'
	],
	[
		'target' => 'special',
		'special' => 'Contributions',
		// message would be shown if 'msg' is given.
		'msg' => 'contribslink'
	],
];
```

## Registering Multiple Webhooks

You can register multiple webhooks with separate settings. The key should start with `'discord'`.

```php
wfLoadExtension( 'DiscordRCFeed' );
$wgRCFeeds[] = [
	'discord' => [
		'url' => 'https://discord.com/api/webhooks/aa/xxxx',
		'omit_user' = true,
	],
	'discord_anon' => [
		'url' => 'https://discord.com/api/webhooks/bb/xxxx',
		'omit_anon' = true,
	],
];
```

## License

[MIT License](http://en.wikipedia.org/wiki/MIT_License)

[github checks status]: https://badgen.net/github/checks/femiwiki/DiscordRCFeed
[github checks link]: https://github.com/femiwiki/DiscordRCFeed/actions
[codecov.io status]: https://badgen.net/codecov/c/github/femiwiki/DiscordRCFeed
[codecov.io link]: https://codecov.io/gh/femiwiki/DiscordRCFeed
[kulttuuri/discordnotifications]: https://github.com/kulttuuri/DiscordRCFeed
[sockets php extension]: https://www.php.net/sockets
[manual:$wgrcfeeds]: https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:$wgRCFeeds
