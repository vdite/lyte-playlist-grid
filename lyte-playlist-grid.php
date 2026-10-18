<?php
/**
 * Plugin Name: LYTE Playlist Grid
 * Description: Display public YouTube playlists as responsive grids using WP YouTube Lyte players.
 * Version: 1.2.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Technickr
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lyte-playlist-grid
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class LYTE_Playlist_Grid {
    const VERSION   = '1.2.0';
    const CACHE_TTL = 12 * HOUR_IN_SECONDS;
    const ERROR_TTL = 15 * MINUTE_IN_SECONDS;
    const MAX_PAGES = 20; // 20 pages of 50 entries, at most 1,000 playlist items.
    const BACKUP_TTL = 29 * DAY_IN_SECONDS; // YouTube API data must be refreshed/deleted within 30 days.

    public static function init() {
        add_shortcode( 'lyte_playlist_grid', array( __CLASS__, 'render' ) );
        add_shortcode( 'technickr_playlist', array( __CLASS__, 'render' ) ); // Legacy alias (v1.x).
        add_action( 'init', array( __CLASS__, 'load_textdomain' ) );
        add_action( 'init', array( __CLASS__, 'cleanup_legacy_backups' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_styles' ) );
        add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
    }

    public static function load_textdomain() {
        load_plugin_textdomain(
            'lyte-playlist-grid',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages'
        );
    }

    /**
     * V1.0.0 saved indefinite options (tpg_backup_<md5>). Clear them once on
     * migration: their age is unknown, so we must not reset their retention clock.
     */
    public static function cleanup_legacy_backups() {
        if ( get_option( 'lpg_storage_migration_12', false ) ) {
            return;
        }
        global $wpdb;
        $like = $wpdb->esc_like( 'tpg_backup_' ) . '%';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- WordPress table name.
        $sql   = $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $like );
        $names = $wpdb->get_col( $sql );
        foreach ( (array) $names as $name ) {
            if ( preg_match( '/^tpg_backup_[a-f0-9]{32}$/D', $name ) ) {
                delete_option( $name );
            }
        }
        update_option( 'lpg_storage_migration_12', 'done', false );
    }

    /**
     * Pre-enqueue in the document head for regular post/page shortcodes.
     * render() also enqueues styles for shortcode uses in builders/widgets.
     */
    public static function maybe_enqueue_styles() {
        if ( ! is_singular() ) {
            return;
        }
        $post = get_post();
        if ( $post instanceof WP_Post && ( has_shortcode( $post->post_content, 'lyte_playlist_grid' ) || has_shortcode( $post->post_content, 'technickr_playlist' ) ) ) {
            self::enqueue_styles();
        }
    }

    private static function enqueue_styles() {
        wp_enqueue_style(
            'lyte-playlist-grid',
            plugins_url( 'assets/grid.css', __FILE__ ),
            array(),
            self::VERSION
        );
    }

    private static function lyte_available() {
        // LYTE registers its shortcode only for the frontend.
        return shortcode_exists( 'lyte' ) && function_exists( 'lyte_parse' );
    }

    private static function api_key() {
        // Reuse LYTE's setting, including its documented filter.
        $key = get_option( 'lyte_yt_api_key', '' );
        $key = apply_filters( 'lyte_filter_yt_api_key', $key );
        return is_string( $key ) ? trim( $key ) : '';
    }

    public static function dependency_notice() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'technickr-lyte-playlist-grid/technickr-lyte-playlist-grid.php' ) ) {
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'Deactivate the old Technickr LYTE Playlist Grid plugin to avoid duplicate shortcode registrations.', 'lyte-playlist-grid' ) . '</p></div>';
        }
        if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'wp-youtube-lyte/wp-youtube-lyte.php' ) ) {
            return;
        }
        if ( function_exists( 'lyte_parse' ) ) {
            return;
        }
        echo '<div class="notice notice-warning"><p>' . esc_html__( 'LYTE Playlist Grid requires the active WP YouTube Lyte plugin.', 'lyte-playlist-grid' ) . '</p></div>';
    }

    public static function render( $atts, $content = null, $tag = 'lyte_playlist_grid' ) {
        $atts = shortcode_atts(
            array( 'id' => '', 'columns' => '3' ),
            $atts,
            in_array( $tag, array( 'lyte_playlist_grid', 'technickr_playlist' ), true ) ? $tag : 'lyte_playlist_grid'
        );
        $id = trim( (string) $atts['id'] );
        if ( ! preg_match( '/^[A-Za-z0-9_-]{10,80}$/D', $id ) ) {
            return self::editor_message( __( 'Invalid or missing playlist ID.', 'lyte-playlist-grid' ) );
        }
        if ( ! self::lyte_available() ) {
            return self::editor_message( __( 'Please activate WP YouTube Lyte.', 'lyte-playlist-grid' ) );
        }
        $key = self::api_key();
        if ( '' === $key || 'none' === $key ) {
            return self::editor_message( __( 'Add a YouTube API key in the WP YouTube Lyte settings first.', 'lyte-playlist-grid' ) );
        }

        $columns = max( 1, min( 4, absint( $atts['columns'] ) ) );
        $videos  = self::fetch( $id, $key );
        if ( is_wp_error( $videos ) ) {
            return self::editor_message(
                sprintf(
                    /* translators: %s: details returned by the plugin's API request. */
                    __( 'Unable to load YouTube playlist: %s', 'lyte-playlist-grid' ),
                    $videos->get_error_message()
                )
            );
        }
        if ( ! $videos ) {
            return self::editor_message( __( 'This playlist has no publicly accessible videos.', 'lyte-playlist-grid' ) );
        }

        self::enqueue_styles();
        $markup = '<div class="lyte-playlist-grid" style="--lpg-columns:' . esc_attr( (string) $columns ) . '" role="list">';
        $seen   = array();
        foreach ( $videos as $video ) {
            if ( ! is_array( $video ) || ! isset( $video['id'], $video['title'] ) ) {
                continue;
            }
            $video_id = (string) $video['id'];
            if ( ! preg_match( '/^[A-Za-z0-9_-]{11}$/D', $video_id ) || isset( $seen[ $video_id ] ) ) {
                continue;
            }
            $seen[ $video_id ] = true; // Also filter duplicate IDs from incoming cached lists.
            $title = (string) $video['title'];

            // WP YouTube Lyte owns the actual click-to-load video player.
            $player  = do_shortcode( '[lyte id="' . $video_id . '" /]' );
            $markup .= '<article class="lyte-playlist-item" role="listitem">';
            $markup .= '<div class="lyte-playlist-player">' . $player . '</div>';
            $markup .= '<h3 class="lyte-playlist-title">' . esc_html( $title ) . '</h3>';
            $markup .= '</article>';
        }
        $markup .= '</div>';
        return $markup;
    }

    /** Only administrators see diagnostic messages, never public visitors. */
    private static function editor_message( $message ) {
        if ( current_user_can( 'manage_options' ) ) {
            return '<p class="lyte-playlist-message">' . esc_html( $message ) . '</p>';
        }
        return '';
    }

    private static function fetch( $playlist_id, $key ) {
        $suffix     = md5( $playlist_id );
        $cache_key  = 'lpg_cache_' . $suffix;
        $backup_key = 'lpg_backup_' . $suffix;
        $error_key  = 'lpg_error_' . $suffix;
        $cached     = get_transient( $cache_key );
        if ( false !== $cached && is_array( $cached ) ) {
            return $cached;
        }

        $backup = get_transient( $backup_key );
        $error  = get_transient( $error_key );
        if ( false !== $error ) {
            return is_array( $backup ) ? $backup : new WP_Error( 'lpg_fetch_error', (string) $error );
        }

        $items      = array();
        $seen       = array();
        $page_token = '';
        $completed  = false;
        for ( $page = 0; $page < self::MAX_PAGES; $page++ ) {
            $query = array(
                'part'       => 'snippet',
                'playlistId' => $playlist_id,
                'maxResults' => 50,
                'key'        => $key,
            );
            if ( '' !== $page_token ) {
                $query['pageToken'] = $page_token;
            }
            $url = add_query_arg( $query, 'https://www.googleapis.com/youtube/v3/playlistItems' );
            $response = wp_remote_get( $url, array(
                'timeout' => 10,
                'headers' => array( 'Referer' => home_url( '/' ) ),
            ) );
            if ( is_wp_error( $response ) ) {
                return self::fetch_error( __( 'Network error connecting to the YouTube API.', 'lyte-playlist-grid' ), $error_key, $backup );
            }
            $status = (int) wp_remote_retrieve_response_code( $response );
            if ( 200 !== $status ) {
                return self::fetch_error(
                    sprintf(
                        /* translators: %d: HTTP response status. */
                        __( 'YouTube API error (HTTP %d). Check the API key and permissions.', 'lyte-playlist-grid' ),
                        $status
                    ),
                    $error_key,
                    $backup
                );
            }
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! is_array( $data ) || ! isset( $data['items'] ) || ! is_array( $data['items'] ) ) {
                return self::fetch_error( __( 'Invalid response from the YouTube API.', 'lyte-playlist-grid' ), $error_key, $backup );
            }
            foreach ( $data['items'] as $item ) {
                if ( ! is_array( $item ) ) {
                    continue;
                }
                $snippet  = isset( $item['snippet'] ) && is_array( $item['snippet'] ) ? $item['snippet'] : array();
                $video_id = isset( $snippet['resourceId']['videoId'] ) ? (string) $snippet['resourceId']['videoId'] : '';
                $title    = isset( $snippet['title'] ) ? (string) $snippet['title'] : '';
                // YouTube returns placeholders for private/deleted playlist entries.
                if ( ! preg_match( '/^[A-Za-z0-9_-]{11}$/D', $video_id ) || '' === $title || in_array( $title, array( 'Private video', 'Deleted video' ), true ) || isset( $seen[ $video_id ] ) ) {
                    continue;
                }
                $seen[ $video_id ] = true;
                $items[] = array( 'id' => $video_id, 'title' => sanitize_text_field( $title ) );
            }
            $page_token = isset( $data['nextPageToken'] ) ? (string) $data['nextPageToken'] : '';
            if ( '' === $page_token ) {
                $completed = true;
                break;
            }
        }
        if ( ! $completed ) {
            return self::fetch_error( __( 'Playlist exceeds 1,000 entries; the safety limit was reached.', 'lyte-playlist-grid' ), $error_key, $backup );
        }

        set_transient( $cache_key, $items, self::CACHE_TTL );
        // Expiring fallback: YouTube API data must not be stored indefinitely.
        set_transient( $backup_key, $items, self::BACKUP_TTL );
        delete_transient( $error_key );
        return $items;
    }

    private static function fetch_error( $message, $error_key, $backup ) {
        set_transient( $error_key, $message, self::ERROR_TTL );
        return is_array( $backup ) ? $backup : new WP_Error( 'lpg_fetch_error', $message );
    }
}

LYTE_Playlist_Grid::init();
