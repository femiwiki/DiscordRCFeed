# DiscordRCFeed [![Github checks status]][github checks link] [![codecov.io status]][codecov.io link]

**⚠️ Work-in-progress**

- [ ] Remove all TODOs

This is a fork of [kulttuuri/DiscordRCFeed] which is an extension for [MediaWiki](https://www.mediawiki.org/wiki/MediaWiki) that sends notifications of actions in your Wiki like editing, adding or removing a page into [Discord](https://discordapp.com/) channel.

## Supported MediaWiki operations to send notifications

- Article is added, removed, moved or edited.
- Article protection settings are changed.
- Article is imported.
- New user is added.
- User is blocked.
- User groups are changed.
- File is uploaded.
- ... and each notification can be individually enabled or disabled :)

## Requirements

- [cURL](http://curl.haxx.se/) or ability to use PHP function `file_get_contents()` for sending the data. Defaults to cURL. See the configuration parameter `$wgDiscordRCFeedSendMethod` below to switch between cURL and file_get_contents.
- MediaWiki 1.35+
- Apache should have NE (NoEscape) flag on to prevent issues in URLs. By default you should have this enabled.

## How to install

1. Create a new Discord Webhook for your channel. You can create and manage webhooks for your channel by clicking the settings icon next to channel name in the Discord app. Read more from here: https://support.discordapp.com/hc/en-us/articles/228383668

2. After setting up the Webhook you will get a Webhook URL. Copy that URL as you will need it in step 4.

3. [Download latest release of this extension](https://github.com/kulttuuri/discord_mediawiki/archive/master.zip), uncompress the archive and move folder `DiscordRCFeed` into your `mediawiki_installation/extensions` folder. (And instead of manually downloading the latest version, you could also just git clone this repository to that same extensions folder).

4. Add settings listed below in your `localSettings.php`. Note that it is mandatory to set these settings for this extension to work:

```php
wfLoadExtension( 'DiscordRCFeed' );
// Required. Your Discord webhook URL. Read more from here: https://support.discord.com/hc/articles/228383668
$wgDiscordRCFeedIncomingWebhookUrl = 'https://discord.com/api/webhooks/xx/xxxx';
$wgDiscordRCFeedSendMethod = 'MWHttpRequest';
```

5. Enjoy the notifications in your Discord room!

## Additional options

These options can be set after including your plugin in your `localSettings.php` file.

- `$wgDiscordRCFeedIncomingWebhookUrl` - (Required) Your Discord webhook URL. You can add multiple webhook urls that you want to send notifications to by adding them in this array: `[ 'https://yourUrlOne.com', 'https://yourUrlTwo...' ]`. Defaults to `false`.
- `$wgDiscordRCFeedSendMethod` - Can be `'MWHttpRequest'`, <s>`'file_get_contents'`</s>(deprecated) or <s>`'curl'`</s>(deprecated). Defaults to `'curl'`.
- `$wgDiscordRCFeedShowSuppressed` - By default we do not show non-public article deletion notifications. You can change this using the parameter below. Defaults to `true`.
- `$wgDiscordRCFeedActions` - An associative array for actions to notify. See [Disabling Each Notification Individually](#disabling-each-notification-individually) below for details.
- `$wgDiscordRCFeedDisplay` - An associative array for tweaks the display of notification. See [Change Display Options for Notification](#change-display-options-for-notification) below for details.
- `$wgDiscordRCFeedExclude` - An associative array to disable notifications related to certain pages or users. See [Denylisting Notifications](#denylisting-notifications) below for details.
- `$wgDiscordRCFeedRequestOverride` - An array used to override the post data of the webhook request. See [Webhook Request Overriding](#webhook-request-overriding) below for details. Defaults to `[]`.

### Webhook Request Overriding

`$wgDiscordRCFeedRequestOverride` is an associative array used to override the post data of the webhook request. You can set username or avatar using this instead of setting in Discord.
See https://discord.com/developers/docs/resources/webhook#execute-webhook-jsonform-params for all available parameters.

```php
$wgDiscordRCFeedRequestOverride = [
  'username' => 'Captain Hook',
  'avatar_url' => '',
];
```

### Disabling Each Notification Individually

`$wgDiscordRCFeedActions` is an associative array to disable notification. You can disable notification indivisually.

```php
// Disable an action
$wgDiscordRCFeedActions = [ 'new-user' => false ];

// Disable multiple actions at once
$wgDiscordRCFeedActions = [
  'add-page' => false,
  'remove-page' => false,
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

### Denylisting Notifications

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

### Change Display Options for Notification

| Option         | Default value | Description                                                                                                             |
| -------------- | ------------- | ----------------------------------------------------------------------------------------------------------------------- |
| `'user-tools'` | array         | If this is false, users will not get additional links in the notification message (block \| groups \| talk \| contribs) |
| `'page-tools'` | array         | If this is false, pages will not get additional links in the notification message (edit \| delete \| history).          |
| `'diff'`       | `true`        | show size of the edit                                                                                                   |
| `'full-name'`  | `false`       | If this is true, newly created user full name is added to notification.                                                 |

```php
// Remove page tools
$wgDiscordRCFeedDisplay = [
  'page-tools' => false
];

// Override user tools
$wgDiscordRCFeedDisplay = [
  'user-tools' => [
    [
      'target' => 'special',
      'special' => 'Block',
      'text' => 'IP Block'
    ],
    [
      'target' => 'talk',
      'text' => 'Discussion'
    ],
  ]
];
```

## License

[MIT License](http://en.wikipedia.org/wiki/MIT_License)

[github checks status]: https://badgen.net/github/checks/femiwiki/DiscordRCFeed
[github checks link]: https://github.com/femiwiki/DiscordRCFeed/actions
[codecov.io status]: https://badgen.net/codecov/c/github/femiwiki/DiscordRCFeed
[codecov.io link]: https://codecov.io/gh/femiwiki/DiscordRCFeed
[kulttuuri/discordnotifications]: https://github.com/kulttuuri/DiscordRCFeed
[visualeditor]: https://www.mediawiki.org/wiki/Special:MyLanguage/Extension:VisualEditor
