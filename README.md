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

- [cURL](http://curl.haxx.se/) or ability to use PHP function `file_get_contents` for sending the data. Defaults to cURL. See the configuration parameter `$wgDiscordSendMethod` below to switch between cURL and file_get_contents.
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
$wgDiscordIncomingWebhookUrl = "";
// Required. Name the message will appear to be sent from. Change this to whatever you wish it to be.
$wgDiscordFromName = $wgSitename;
$wgDiscordNotificationWikiUrl = "http://your_wiki_url/";
```

5. Enjoy the notifications in your Discord room!

## Additional options

These options can be set after including your plugin in your `localSettings.php` file.
Each configuration option is shown without the $wgDiscord prefix for brevity; replace the '…' when using.

| Option                                        | Default value                  | Documentation                                                                                                                                                                                                                                         |
| --------------------------------------------- | ------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `…IncomingWebhookUrl`                         | `""`                           |                                                                                                                                                                                                                                                       |
| `…AdditionalIncomingWebhookUrls`              | `[]`                           | You can add more webhook urls that you want to send notifications to by adding them in this array: `["https://yourUrlOne.com", "https://yourUrlTwo..."]`                                                                                              |
| `…FromName`                                   | `""`                           |                                                                                                                                                                                                                                                       |
| `…SendMethod`                                 | `"curl"`                       | If you use VisualEditor and get unknown errors, do not have curl enabled on your server or notice other problems, the recommended solution is to change method to `"file_get_contents"`. This can be: "curl" or "file_get_contents". Default: "curl". |
| `…IncludePageUrls`                            | `true`                         | If this is true, pages will get additional links in the notification message (edit \| delete \| history).                                                                                                                                             |
| `…IncludeUserUrls`                            | `true`                         | If this is true, users will get additional links in the notification message (block \| groups \| talk \| contribs).                                                                                                                                   |
| `…IgnoreMinorEdits`                           | `false`                        | If this is true, all minor edits made to articles will not be submitted to Discord.                                                                                                                                                                   |
| `…ExcludeNotificationsFrom`                   | `[]`                           | Actions (add, edit, modify) won't be notified to Discord room from articles starting with these names                                                                                                                                                 |
| `…ExcludedPermission`                         | `""`                           | If this is set, actions by users with this permission won't cause alerts                                                                                                                                                                              |
| `…NotificationWikiUrl`                        | `""`                           | URL into your MediaWiki installation with the trailing /.                                                                                                                                                                                             |
| `…NotificationWikiUrlEnding`                  | `"index.php?title="`           | Wiki script name. Leave this to default one if you do not have URL rewriting enabled.                                                                                                                                                                 |
| `…NotificationWikiUrlEndingUserRights`        | `"Special%3AUserRights&user="` |                                                                                                                                                                                                                                                       |
| `…NotificationWikiUrlEndingBlockUser`         | `"Special:Block/"`             |                                                                                                                                                                                                                                                       |
| `…NotificationWikiUrlEndingUserPage`          | `"User:"`                      |                                                                                                                                                                                                                                                       |
| `…NotificationWikiUrlEndingUserTalkPage`      | `"User_talk:"`                 |                                                                                                                                                                                                                                                       |
| `…NotificationWikiUrlEndingUserContributions` | `"Special:Contributions/"`     |                                                                                                                                                                                                                                                       |
| `…NotificationWikiUrlEndingEditArticle`       | `"action=edit"`                |                                                                                                                                                                                                                                                       |
| `…NotificationWikiUrlEndingDeleteArticle`     | `"action=delete"`              |                                                                                                                                                                                                                                                       |
| `…NotificationWikiUrlEndingHistory`           | `"action=history"`             |                                                                                                                                                                                                                                                       |
| `…NotificationWikiUrlEndingDiff`              | `"diff=prev&oldid="`           |                                                                                                                                                                                                                                                       |
| `…NotificationNewUser`                        | `true`                         | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationBlockedUser`                    | `true`                         | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationAddedArticle`                   | `true`                         | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationRemovedArticle`                 | `true`                         | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationMovedArticle`                   | `true`                         | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationEditedArticle`                  | `true`                         | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationFileUpload`                     | `true`                         | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationProtectedArticle`               | `true`                         | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationShowSuppressed`                 | `true`                         | By default we do not show non-public article deletion notifications. You can change this using the parameter below.                                                                                                                                   |
| `…NotificationUserGroupsChanged`              | `true`                         | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…NotificationFlow`                           | `true`                         | Set to false to disable notifications of those actions. (experimental)                                                                                                                                                                                |
| `…NotificationAfterImportPage`                | `true`                         | Set to false to disable notifications of those actions.                                                                                                                                                                                               |
| `…IncludeDiffSize`                            | `true`                         | By default we show size of the edit. You can hide this information with the setting below.                                                                                                                                                            |
| `…ShowNewUserFullName`                        | `true`                        | If this is true, newly created user full name is added to notification.                                                                                                                                                                               |
| `…AvatarUrl`                                  | `""`                           | Avatar to use for messages. If blank, uses the webhook's default avatar.                                                                                                                                                                              |

## License

[MIT License](http://en.wikipedia.org/wiki/MIT_License)

```

```
