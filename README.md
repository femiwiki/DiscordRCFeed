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
require_once("$IP/extensions/DiscordNotifications/DiscordNotifications.php");
// Required. Your Discord webhook URL. Read more from here: https://support.discordapp.com/hc/en-us/articles/228383668
$wgDiscordNotificationsIncomingWebhookUrl = "";
```

5. Enjoy the notifications in your Discord room!

## Additional options

These options can be set after including your plugin in your `localSettings.php` file.
Each configuration option is shown without the $wgDiscord prefix for brevity; replace the '…' when using.

| Option                             | Default value | Documentation                                                                                                                                                                                                                                         |
| ---------------------------------- | ------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `…NotificationsIncomingWebhookUrl` | `''`          | You can add multiple webhook urls that you want to send notifications to by adding them in this array: `["https://yourUrlOne.com", "https://yourUrlTwo..."]`                                                                                          |
| `…NotificationsSendMethod`         | `"curl"`      | If you use VisualEditor and get unknown errors, do not have curl enabled on your server or notice other problems, the recommended solution is to change method to `"file_get_contents"`. This can be: "curl" or "file_get_contents". Default: "curl". |
| `…NotificationsFromName`           | `''`          |                                                                                                                                                                                                                                                       |
| `…NotificationsAvatarUrl`          | `''`          | Avatar to use for messages. If blank, uses the webhook's default avatar.                                                                                                                                                                              |
| `…IncludePageUrls`                 | `true`        | If this is true, pages will get additional links in the notification message (edit \| delete \| history).                                                                                                                                             |
| `…IncludeUserUrls`                 | `true`        | If this is true, users will get additional links in the notification message (block \| groups \| talk \| contribs).                                                                                                                                   |
| `…IncludeDiffSize`                 | `true`        | By default we show size of the edit. You can hide this information with the setting below.                                                                                                                                                            |
| `…ShowNewUserFullName`             | `false`       | If this is true, newly created user full name is added to notification.                                                                                                                                                                               |
| `…IgnoreMinorEdits`                | `false`       | If this is true, all minor edits made to articles will not be submitted to Discord.                                                                                                                                                                   |
| `…ExcludeNotificationsFrom`        | `[]`          | Actions (add, edit, modify) won't be notified to Discord room from articles starting with these names                                                                                                                                                 |
| `…ExcludedPermission`              | `''`          | If this is set, actions by users with this permission won't cause alerts                                                                                                                                                                              |
| `…NotificationsNewUser`            | `true`        | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationsBlockedUser`        | `true`        | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationsAddedArticle`       | `true`        | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationsRemovedArticle`     | `true`        | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationsMovedArticle`       | `true`        | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationsEditedArticle`      | `true`        | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationsFileUpload`         | `true`        | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationsProtectedArticle`   | `true`        | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationsShowSuppressed`     | `true`        | By default we do not show non-public article deletion notifications. You can change this using the parameter below.                                                                                                                                   |
| `…NotificationsUserGroupsChanged`  | `true`        | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationsFlow`               | `true`        | Set to false to disable notifications of those actions. (experimental)                                                                                                                                                                                |
| `…NotificationsAfterImportPage`    | `true`        | Set to false to disable notifications of those actions.                                                                                                                                                                                               |

## License

[MIT License](http://en.wikipedia.org/wiki/MIT_License)

```

```
