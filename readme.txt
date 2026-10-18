=== LYTE Playlist Grid ===
Tags: youtube, playlist, video grid, wp youtube lyte, video gallery
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display public YouTube playlists as responsive video grids using the existing WP YouTube Lyte player.

== Description ==

LYTE Playlist Grid is an independent extension for WP YouTube Lyte. It displays videos from public YouTube playlists in a responsive grid, loading the individual players through WP YouTube Lyte.

* Reuses the YouTube API key already configured in WP YouTube Lyte.
* Automatically refreshes the playlist after a 12-hour cache expires and the page is revisited.
* Shows the video titles and skips duplicate video IDs.
* Supports up to 1,000 playlist entries and up to four desktop columns.
* Keeps a last-known-good playlist for up to 29 days during temporary API failures.
* Uses WordPress translations (German included).

Requires WP YouTube Lyte and an API key for YouTube Data API v3 `playlistItems.list` calls. No affiliation with YouTube, Google, or WP YouTube Lyte's developers.

== Installation ==

1. Install WP YouTube Lyte and configure its YouTube Data API v3 key.
2. Upload and activate LYTE Playlist Grid.
3. Insert `[lyte_playlist_grid id="YOUR_PLAYLIST_ID"]` in a Shortcode block.
4. Optionally set `[lyte_playlist_grid id="YOUR_PLAYLIST_ID" columns="4"]`.

== Frequently Asked Questions ==

= Can I keep an existing technickr_playlist shortcode? =

Yes. `[technickr_playlist id="YOUR_PLAYLIST_ID"]` remains an alias. Deactivate the previous Technickr plugin before activating this one; the renamed plugin installs to a separate directory.

= How often does the grid update? =

After the successful 12-hour cache expires, at the next page load. Page caches or CDNs may delay visible changes.

= Are the video players lazy loaded? =

The player rendering is handled by WP YouTube Lyte. This plugin does not add a second player implementation.

= How do I find the playlist ID? =

It is the `list=` parameter of your YouTube playlist URL.

= How many videos are supported? =

Up to 1,000 source entries, across at most 20 API pages. Private/deleted placeholders and duplicate IDs are ignored.

== Changelog ==

= 1.2.0 =
* Rename plugin to LYTE Playlist Grid, add neutral shortcode, keep the legacy alias, separate cache keys, and ship renamed translations.

= 1.1.0 =
* Deduplicate video IDs, including cached results from version 1.0.0.
* Load the grid stylesheet only when needed.
* Add translations, uninstall cleanup, public documentation and smoke tests.

= 1.0.0 =
* Initial release.
