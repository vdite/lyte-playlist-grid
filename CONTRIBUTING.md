# Contributing

Bug reports and reproducible examples are welcome. Include WordPress/PHP versions, WP YouTube Lyte version, relevant shortcode (without confidential data), and whether a page cache or CDN is active. Never share API keys or private playlist links in issues.

For code changes:

1. Keep WordPress 6.0+ and PHP 7.4+ compatibility.
2. Do not add a replacement video player; WP YouTube Lyte should handle playback.
3. Run `php tests/smoke.php` and `php -l` on modified PHP files.
4. Keep public-facing behavior and the shortcode backwards-compatible when possible.
5. Open a pull request describing behavior, edge cases and any manual WordPress tests.

This repository uses the GPL-2.0-or-later license.
