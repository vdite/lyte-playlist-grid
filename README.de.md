# LYTE Playlist Grid (Deutsch)

Eine schlanke Erweiterung für **[WP YouTube Lyte](https://wordpress.org/plugins/wp-youtube-lyte/)**. Sie stellt alle öffentlich zugänglichen Videos einer YouTube-Playlist als responsives Grid dar – mit dem bereits vorhandenen LYTE-Player statt eines zusätzlichen Videoplayers.

Unabhängige Erweiterung, ohne Verbindung zu YouTube/Google oder den Entwicklern von WP YouTube Lyte.

![Beispiel: YouTube-Playlist als 3-spaltiges LYTE-Grid](docs/screenshot.jpg)

## Funktionen

- Liest öffentliche Playlists über die **YouTube Data API v3** aus.
- Aktualisiert die Playlist automatisch nach **12 Stunden Cache-Laufzeit**, sobald danach die Seite abgerufen wird.
- Zeigt ein Vorschaubild samt LYTE-Player und Titel pro Video.
- Standardmäßig **3 Spalten** am Desktop, 2 auf Tablets, 1 mobil; am Desktop sind 1–4 Spalten wählbar.
- Lädt das eigene CSS nur auf Seiten mit dem Shortcode.
- Nutzt den vorhandenen API-Schlüssel von WP YouTube Lyte und dessen Filter.
- Unterstützt bis zu 1.000 Playlist-Einträge; ignoriert doppelte IDs und Platzhalter für private/gelöschte Videos.
- Verwendet bei vorübergehenden API-Ausfällen die letzte erfolgreiche Liste für maximal 29 Tage.
- Fehlermeldungen sind nur für Administratoren sichtbar.

## Voraussetzungen

- WordPress ab 6.0 und PHP ab 7.4
- Aktiviertes Plugin **WP YouTube Lyte**
- Gültiger **YouTube Data API v3**-Schlüssel in WP YouTube Lyte, der serverseitig `playlistItems.list` aufrufen darf
- Öffentliche YouTube-Playlist

Achtung: Reine HTTP-Referrer-Beschränkungen eines API-Keys können bei serverseitigen Anfragen scheitern. API-Quotas und Einschränkungen werden von Google vorgegeben.

## Installation

1. WP YouTube Lyte installieren/aktivieren und dort den API-Key hinterlegen.
2. ZIP unter **Plugins → Installieren → Plugin hochladen** installieren und aktivieren.
3. In einem WordPress-Shortcode-Block einfügen:

```text
[lyte_playlist_grid id="PL-DHsM-Gc8c9rWf4gqzhkBbdwtpuDLOaB"]
```

Optional vier Spalten am Desktop:

```text
[lyte_playlist_grid id="PL-DHsM-Gc8c9rWf4gqzhkBbdwtpuDLOaB" columns="4"]
```

Die Playlist-ID steht in der YouTube-URL nach `list=`.

## Cache und Fehlerbehebung

- Erfolgreiche Antworten werden **12 Stunden** als WordPress-Transient zwischengespeichert.
- Fehlgeschlagene Aktualisierungen werden **15 Minuten** zwischengespeichert.
- Die letzte erfolgreiche Playlist bleibt maximal **29 Tage** als Backup-Transient erhalten und wird bei API-Ausfällen verwendet.
- Seiten-Caches/CDNs können die Sichtbarkeit neuer Videos zusätzlich verzögern.
- Falls nichts erscheint: als WordPress-Administrator prüfen, ob ein Fehlerhinweis angezeigt wird. API-Key, öffentliche Playlist und LYTE-Aktivierung kontrollieren.
- Neue/entfernte Videos erscheinen nach dem nächsten Abruf bei abgelaufenem Cache; es gibt keinen dauerhaften Hintergrundabgleich.

Das Plugin legt keine eigenen API-Schlüssel ab und ändert keine Einstellungen von LYTE. Bei **Deinstallation** (nicht Deaktivierung) entfernt es die eigenen Cache- und Backup-Daten.

### Wechsel vom bisherigen Technickr-Plugin (Version 1.0/1.1)

**Achtung: Wegen des neuen Plugin-Verzeichnisnamens ist dies keine normale Aktualisierung.** Das alte Plugin erst **deaktivieren**, dann **LYTE Playlist Grid installieren und aktivieren**. Funktion prüfen und anschließend das alte Plugin deinstallieren. Nicht beide gleichzeitig aktiv lassen. Bereits eingebundene `[technickr_playlist]`-Shortcodes funktionieren als **Alias** weiter; für neue Einbindungen `[lyte_playlist_grid]` verwenden.

Das neue Plugin verwendet eigene `lpg_`-Cache-Einträge. Gegebenenfalls verbliebene, unbegrenzt gespeicherte alte Backup-Optionen werden einmalig bereinigt.

Lizenz: **GPL-2.0-or-later**; siehe [LICENSE](LICENSE). Technische Informationen und Tests: [README.md](README.md).

Autor und Maintainer: **Technickr**. Unabhängige Erweiterung, keine Änderung am Plugin WP YouTube Lyte.
