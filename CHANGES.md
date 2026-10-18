# Changelog

## 1.2.0 — 2026-10-17

- Rename the plugin and repository to **LYTE Playlist Grid**; keep **Technickr** as author.
- Introduce `[lyte_playlist_grid]` as the recommended shortcode, preserving `[technickr_playlist]` as a legacy alias.
- Use an independent PHP class, asset names, translation domain, and `lpg_` cache keys.
- Warn administrators if the previous Technickr plugin is still active.
- Support a side-by-side migration to the new plugin directory without sharing transient cache keys.

## 1.1.0 — 2026-10-17

- Filter repeated video IDs in API results and in data cached by v1.0.0.
- Enqueue grid CSS only for pages using the shortcode, with a fallback for dynamic shortcode rendering.
- Make administrator-facing and API diagnostics translatable; ship a German translation.
- Expire fallback data within 29 days and purge unbounded v1.0.0 backup options during migration.
- Clean up the plugin's own transients/backups on uninstall, including multisite sites.
- Provide documentation and smoke tests for public development and releases.
- Preserve the legacy `technickr_playlist` shortcode and existing 12-hour cache/fallback behavior.

## 1.0.0 — 2026-10-17

- Initial WordPress/LYTE playlist-grid implementation.
