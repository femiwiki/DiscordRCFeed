# Changelog

Versions and bullets are arranged chronologically from latest to oldest.

## Unreleased

Breaking changes:

- `'line_style'` parameter is renamed to `'style'`.
- Patrol events are omitted by default now.

Others:

- Fixed bug that the array type default parameters are ignored.
- A new parameter for `'style'`, `'structure'` is added.

## v0.1.2

- The Logging messages are now always shown in the content language of the wiki. (https://github.com/femiwiki/DiscordRCFeed/issues/6)

## v0.1.1

Breaking changes:

- `'request_override'` parameter is renamed to `'request_replace'`.

Others:

- Log messages are now translated to the language of the user as LogFormatter is used for logs instead of IRCActionComment.
