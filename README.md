# DiscordNotifications

This is a fork of kulttuuri/DiscordNotifications and an extension for [MediaWiki](https://www.mediawiki.org/wiki/MediaWiki) that sends notifications of actions in your Wiki like editing, adding or removing a page into [Discord](https://discordapp.com/) channel.

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

- [cURL](http://curl.haxx.se/) or ability to use PHP function `file_get_contents` for sending the data. Defaults to cURL. See the configuration parameter `$wgDiscordNotificationsSendMethod` below to switch between cURL and file_get_contents.
- MediaWiki 1.35+
- Apache should have NE (NoEscape) flag on to prevent issues in URLs. By default you should have this enabled.

## How to install

1. Create a new Discord Webhook for your channel. You can create and manage webhooks for your channel by clicking the settings icon next to channel name in the Discord app. Read more from here: https://support.discordapp.com/hc/en-us/articles/228383668

2. After setting up the Webhook you will get a Webhook URL. Copy that URL as you will need it in step 4.

3. [Download latest release of this extension](https://github.com/kulttuuri/discord_mediawiki/archive/master.zip), uncompress the archive and move folder `DiscordNotifications` into your `mediawiki_installation/extensions` folder. (And instead of manually downloading the latest version, you could also just git clone this repository to that same extensions folder).

4. Add settings listed below in your `localSettings.php`. Note that it is mandatory to set these settings for this extension to work:

```php
wfLoadExtension( 'DiscordNotifications' )
// Required. Your Discord webhook URL. Read more from here: https://support.discord.com/hc/articles/228383668
$wgDiscordNotificationsIncomingWebhookUrl = "https://discord.com/api/webhooks/xx/xxxx";
```

5. Enjoy the notifications in your Discord room!

## Additional options

These options can be set after including your plugin in your `localSettings.php` file.

- `$wgDiscordNotificationsIncomingWebhookUrl` - (Required) Your Discord webhook URL. You can add multiple webhook urls that you want to send notifications to by adding them in this array: `[ 'https://yourUrlOne.com', 'https://yourUrlTwo...' ]`. Defaults to `false`.
- `$wgDiscordNotificationsSendMethod` - Can be `'file_get_contents'` or `'curl'`. If you use VisualEditor and get unknown errors, do not have curl enabled on your server or notice other problems, the recommended solution is to change method to `"file_get_contents"`. Defaults to `'curl'`.
- `$wgDiscordNotificationsRequestOverride` - An array used for overriding the post data of the webhook request. See [Webhook Request Overriding](#webhook-request-overriding) below for details. Defaults to `[]`.
- `$wgDiscordIncludePageUrls` - If this is true, pages will get additional links in the notification message (edit \| delete \| history). Defaults to `true`.
- `$wgDiscordIncludeUserUrls` - If this is true, users will get additional links in the notification message (block \| groups \| talk \| contribs). Defaults to `true`.
- `$wgDiscordIncludeDiffSize` - By default we show size of the edit. You can hide this information with the setting below. Defaults to `true`.
- `$wgDiscordShowNewUserFullName` - If this is true, newly created user full name is added to notification. Defaults to `false.
- `$wgDiscordIgnoreMinorEdits` - If this is true, all minor edits made to articles will not be submitted to Discord. Defaults to `false`.
- `$wgDiscordExcludeNotificationsFrom` - Actions (add, edit, modify) won't be notified to Discord room from articles starting with these names Defaults to `[]`.
- `$wgDiscordExcludedPermission` - If this is set, actions by users with this permission won't cause alerts Defaults to `''`.
- `$wgDiscordNotificationsShowSuppressed` - By default we do not show non-public article deletion notifications. You can change this using the parameter below. Defaults to `true`.
- `$wgDiscordNotificationsActions` - An associative array for actions to notify. See [Disabling Each Notification Individually](#disabling-each-notification-individually) below for details.

### Webhook Request Overriding

`$wgDiscordNotificationsRequestOverride` is an array used for overriding the post data of the webhook request. You can set username or avatar using this.
See https://discord.com/developers/docs/resources/webhook#execute-webhook-jsonform-params for all available parameters.

```php
$wgDiscordNotificationsRequestOverride = [
  'username' => 'Captain Hook',
  'avatar_url' => '',
];
```

This array is used as the second argument for [array_replace_recursive()].

### Disabling Each Notification Individually

`$wgDiscordNotificationsActions` is an associative array to disable notification. You can disable notification indivisually.

```php
// Disable an action
$wgDiscordNotificationsActions = [ 'new-user' => false ];

// Disable multiple actions at once
$wgDiscordNotificationsActions = [
  'add-page' => false,
  'remove-page' => false,
];
```

## Migrating from the base of this fork

```php
# Configurations for kulttuuri/DiscordNotifications
$wgDiscordFromName = 'FROM_NAME';
$wgDiscordAvatarUrl = 'https://AVARTAR_URL';

$wgDiscordNotificationsNewUser = false;
$wgDiscordNotificationsBlockedUser = false;
$wgDiscordNotificationsAddedArticle = false;
$wgDiscordNotificationsRemovedArticle = false;
$wgDiscordNotificationsMovedArticle = false;
$wgDiscordNotificationsEditedArticle = false;
$wgDiscordNotificationsFileUpload = false;
$wgDiscordNotificationsProtectedArticle = false;
$wgDiscordNotificationsUserGroupsChanged = false;
$wgDiscordNotificationsFlow = false;
$wgDiscordNotificationsAfterImportPage = false;

$wgDiscordIgnoreMinorEdits = true;
```

below config for this fork has the same effect above:

```php
# Configurations for femiwiki/DiscordNotifications
$wgDiscordNotificationsRequestOverride = [
  'username' => 'FROM_NAME';,
  'avatar_url' => 'https://AVARTAR_URL';,
];

$wgDiscordNotificationsActions = [
  'new-user' => false,
  'block-user' => false,
  'add-page' => false,
  'remove-page' => false,
  'move-page' => false,
  'edit-page' => false,
  'minor-edit-page' => false,
  'upload-file' => false,
  'protect-page' => false,
  'change-user-groups' => false,
  'flow' => false,
  'import-page' => false,
];
```

## License

[MIT License](http://en.wikipedia.org/wiki/MIT_License)

[array_replace_recursive()]: https://www.php.net/manual/en/function.array-replace-recursive.php
