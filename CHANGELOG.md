# Changelog

Versions and bullets are arranged chronologically from latest to oldest.

## v1.0.3

- Fixed not working links if `$wgServer` is a protocol-relative URL. (https://github.com/femiwiki/DiscordRCFeed/issues/94)
- Fixed expanded templates in edit summaries. (https://github.com/femiwiki/DiscordRCFeed/issues/66)

## v1.0.2

- Modified the title capturing regex to fix the bug on wikis using `wiki/` as the article path. (https://github.com/femiwiki/DiscordRCFeed/issues/63)

## v1.0.1

- Fixed unknown actors. (https://github.com/femiwiki/DiscordRCFeed/issues/46)

## v1.0.0

Intentionally has no significant changes.

## v0.2.2

The default parameters of $wgRCFeeds changes:

- The 'diff' tool now shown as the first tool of the page tools.

Enhancements:

- The View tool for a page change now links to the permalink. (https://github.com/femiwiki/DiscordRCFeed/issues/45)

Bug fixes:

- Fixed that changes on special pages ignored. (https://github.com/femiwiki/DiscordRCFeed/issues/48)
- Fixed fatal error happened if the wiki didn't install StructuredDisscussions
- Fixed the empty tool on StructuredDiscussions changes.

## v0.2.1

- Fixed the broken link to the user page.
- The separator for tool links of the structured style is changed from newline to bar(|).

## v0.2.0

Breaking changes:

- the `'line_style'` parameter for $wgRCFeeds is renamed to `'style'`.

The default parameters of $wgRCFeeds changes:

- Patrol events are now omitted by default.
- The default values of the page/user tools are changed as same as Special:RecentChanges.
- The default value of 'style' is changed from `'embed'` to `'structure'`.

Others:

- Fixed bug that the array type default parameters are ignored.
- `'structure'` which is a new parameter for `'style'` is added.
- The next parameters are added:
  - `omit_talk` and `only_talk`
  - `only_namespaces`
  - `only_types`
  - `only_log_types`
  - `only_log_actions`
  - `omit_usernames` and `only_usernames`
  - `omit_pages` and `only_pages`
  - `omit_content_models` and `only_content_models`

## v0.1.2

- The Logging messages are now always shown in the content language of the wiki. (https://github.com/femiwiki/DiscordRCFeed/issues/6)

## v0.1.1

Breaking changes:

- `'request_override'` parameter is renamed to `'request_replace'`.

Others:

- Log messages are now translated to the language of the user as LogFormatter is used for logs instead of IRCActionComment.
