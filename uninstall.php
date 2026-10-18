<?php
/** Remove data created by LYTE Playlist Grid when uninstalled. */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/** Delete only this plugin's own cache, error and backup options. */
function lyte_playlist_grid_remove_data() {
    global $wpdb;

    $prefixes = array(
        '_transient_lpg_backup_',
        '_transient_timeout_lpg_backup_',
        '_transient_lpg_cache_',
        '_transient_timeout_lpg_cache_',
        '_transient_lpg_error_',
        '_transient_timeout_lpg_error_',
    );
    $where = array();
    $args  = array();
    foreach ( $prefixes as $prefix ) {
        $where[] = 'option_name LIKE %s';
        $args[]  = $wpdb->esc_like( $prefix ) . '%';
    }
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is supplied by WordPress.
    $sql = "SELECT option_name FROM {$wpdb->options} WHERE " . implode( ' OR ', $where );
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Placeholders come from a fixed list above.
    $names = $wpdb->get_col( $wpdb->prepare( $sql, $args ) );

    foreach ( $names as $name ) {
        if ( preg_match( '/^_transient_(?:timeout_)?(lpg_(?:cache|error|backup)_[a-f0-9]{32})$/D', $name, $matches ) ) {
            delete_transient( $matches[1] );
        }
    }
    delete_option( 'lpg_storage_migration_12' );
}

if ( is_multisite() ) {
    foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $site_id ) {
        switch_to_blog( $site_id );
        lyte_playlist_grid_remove_data();
        restore_current_blog();
    }
} else {
    lyte_playlist_grid_remove_data();
}
