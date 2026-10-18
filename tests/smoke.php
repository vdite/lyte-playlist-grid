<?php
/**
 * Self-contained smoke test for plugin behavior using a small mock WP boundary.
 * Run `php tests/smoke.php`; does not require a real WordPress installation.
 */
error_reporting( E_ALL );
if ( defined( 'ABSPATH' ) ) {
    exit( 'Run in a standalone PHP process.' );
}
define( 'ABSPATH', __DIR__ );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'DAY_IN_SECONDS', 86400 );

$GLOBALS['opts']      = array( 'lyte_yt_api_key' => 'test-api-key', 'lpg_storage_migration_12' => 'done' );
$GLOBALS['tr']        = array();
$GLOBALS['tr_expiry'] = array();
$GLOBALS['api_queue'] = array();
$GLOBALS['api_calls'] = array();
$GLOBALS['css_calls'] = array();
$GLOBALS['actions']   = array();
$GLOBALS['shortcodes'] = array();
$GLOBALS['admin']     = true;
$GLOBALS['lyte']      = true;
$GLOBALS['singular']  = true;
$GLOBALS['post']      = null;
$GLOBALS['tests']     = 0;

class FakeWpdb { public $options = 'wp_options'; public function esc_like( $text ) { return $text; } public function prepare( $sql, $param ) { return str_replace( '%s', $param, $sql ); } public function get_col( $sql ) { return array_values( array_filter( array_keys( $GLOBALS['opts'] ), function( $key ) { return strpos( $key, 'tpg_backup_' ) === 0; } ) ); } }
$GLOBALS['wpdb'] = new FakeWpdb();
class WP_Post { public $post_content; public function __construct( $text ) { $this->post_content = $text; } }
class WP_Error { private $message; public function __construct( $code, $message ) { $this->message = $message; } public function get_error_message() { return $this->message; } }
function add_shortcode( $tag, $fn ) { $GLOBALS['shortcodes'][ $tag ] = $fn; }
function add_action( $tag, $fn ) { $GLOBALS['actions'][ $tag ] = $fn; }
function shortcode_atts( $defaults, $atts, $tag = '' ) { return array_merge( $defaults, is_array( $atts ) ? $atts : array() ); }
function shortcode_exists( $tag ) { return 'lyte' === $tag ? $GLOBALS['lyte'] : isset( $GLOBALS['shortcodes'][ $tag ] ); }
function lyte_parse() { return ''; }
function do_shortcode( $str ) { return '<span class="lyte-mock">' . htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' ) . '</span>'; }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
function apply_filters( $tag, $value ) { return $value; }
function get_option( $key, $default = false ) { return isset( $GLOBALS['opts'][ $key ] ) ? $GLOBALS['opts'][ $key ] : $default; }
function add_option( $key, $value, $deprecated = '', $autoload = null ) { $GLOBALS['opts'][ $key ] = $value; return true; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['opts'][ $key ] = $value; return true; }
function delete_option( $key ) { unset( $GLOBALS['opts'][ $key ] ); return true; }
function get_transient( $key ) { return isset( $GLOBALS['tr'][ $key ] ) ? $GLOBALS['tr'][ $key ] : false; }
function set_transient( $key, $value, $expiry ) { $GLOBALS['tr'][ $key ] = $value; $GLOBALS['tr_expiry'][ $key ] = $expiry; return true; }
function delete_transient( $key ) { unset( $GLOBALS['tr'][ $key ] ); return true; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_remote_get( $url, $args ) { $GLOBALS['api_calls'][] = array( $url, $args ); if ( ! $GLOBALS['api_queue'] ) { throw new Exception( 'Unexpected API request: ' . $url ); } return array_shift( $GLOBALS['api_queue'] ); }
function wp_remote_retrieve_response_code( $response ) { return $response['status']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function home_url( $path ) { return 'https://example.org' . $path; }
function current_user_can( $cap ) { return $GLOBALS['admin']; }
function absint( $value ) { return abs( (int) $value ); }
function esc_attr( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $message, $domain ) { return $message; }
function __( $message, $domain ) { return $message; }
function sanitize_text_field( $value ) { return trim( strip_tags( $value ) ); }
function plugins_url( $path, $file ) { return 'https://example.org/plugins/' . $path; }
function wp_enqueue_style( $handle, $src, $dependencies, $version ) { $GLOBALS['css_calls'][] = array( $handle, $version ); }
function is_singular() { return $GLOBALS['singular']; }
function get_post() { return $GLOBALS['post']; }
function has_shortcode( $content, $tag ) { return strpos( $content, '[' . $tag ) !== false; }
function load_plugin_textdomain( $domain, $deprecated, $path ) { return true; }
function plugin_basename( $file ) { return basename( dirname( $file ) ) . '/' . basename( $file ); }
function is_plugin_active( $plugin ) { return $GLOBALS['lyte']; }
function mock_response( $items, $next = '' ) { $data = array( 'items' => $items ); if ( $next ) { $data['nextPageToken'] = $next; } return array( 'status' => 200, 'body' => json_encode( $data ) ); }
function mock_item( $id, $title ) { return array( 'snippet' => array( 'resourceId' => array( 'videoId' => $id ), 'title' => $title ) ); }
function test_ok( $condition, $message ) { $GLOBALS['tests']++; if ( ! $condition ) { throw new Exception( 'FAIL: ' . $message ); } }
function assert_contains( $needle, $haystack, $message ) { test_ok( false !== strpos( $haystack, $needle ), $message ); }

require dirname( __DIR__ ) . '/lyte-playlist-grid.php';
$render = array( 'LYTE_Playlist_Grid', 'render' );
$id = 'PL-DHsM-Gc8c9rWf4gqzhkBbdwtpuDLOaB';
$cache = 'lpg_cache_' . md5( $id );
$backup = 'lpg_backup_' . md5( $id );
$error = 'lpg_error_' . md5( $id );

test_ok( isset( $GLOBALS['shortcodes']['lyte_playlist_grid'] ), 'Neutral shortcode registered.' );
test_ok( isset( $GLOBALS['shortcodes']['technickr_playlist'] ), 'Legacy shortcode alias registered.' );
test_ok( isset( $GLOBALS['actions']['wp_enqueue_scripts'] ), 'Style hook registered.' );
assert_contains( 'Invalid or missing', call_user_func( $render, array( 'id' => '#bad' ) ), 'Invalid IDs rejected.' );
$GLOBALS['opts']['lyte_yt_api_key'] = '';
assert_contains( 'Add a YouTube API key', call_user_func( $render, array( 'id' => $id ) ), 'Missing key diagnosed.' );
$GLOBALS['opts']['lyte_yt_api_key'] = 'test-api-key';
$GLOBALS['lyte'] = false;
assert_contains( 'Please activate WP YouTube Lyte', call_user_func( $render, array( 'id' => $id ) ), 'Missing LYTE diagnosed.' );
$GLOBALS['lyte'] = true;
$GLOBALS['admin'] = false;
test_ok( '' === call_user_func( $render, array( 'id' => '#bad' ) ), 'Visitor cannot see API/plugin diagnostics.' );
$GLOBALS['admin'] = true;

$GLOBALS['api_queue'][] = mock_response( array(
    mock_item( 'AAAAAAAAAAA', 'First & video' ),
    mock_item( 'AAAAAAAAAAA', 'Repeated video' ),
    mock_item( 'BBBBBBBBBBB', '<b>Second</b>' ),
    mock_item( 'CCCCCCCCCCC', 'Private video' ),
), 'page2' );
$GLOBALS['api_queue'][] = mock_response( array(
    mock_item( 'BBBBBBBBBBB', 'Repeated on second page' ),
    mock_item( 'DDDDDDDDDDD', 'Third video' ),
    mock_item( 'invalid', 'Invalid identifier' ),
) );
$out = call_user_func( $render, array( 'id' => $id, 'columns' => '4' ) );
test_ok( count( $GLOBALS['api_calls'] ) === 2, 'Pagination does two API requests.' );
assert_contains( 'pageToken=page2', $GLOBALS['api_calls'][1][0], 'Second page token forwarded.' );
test_ok( substr_count( $out, 'role="listitem"' ) === 3, 'Duplicate IDs and private entries filtered.' );
assert_contains( 'First &amp; video', $out, 'Titles are HTML escaped.' );
assert_contains( 'Second', $out, 'Video title present.' );
test_ok( false === strpos( $out, '<b>Second</b>' ), 'HTML tags are not injected.' );
assert_contains( '--lpg-columns:4', $out, 'Grid columns option is rendered.' );
test_ok( count( $GLOBALS['css_calls'] ) === 1, 'CSS loaded for rendered grid.' );
test_ok( count( $GLOBALS['tr'][ $backup ] ) === 3, 'Expiring backup stored without duplicates.' );
test_ok( $GLOBALS['tr_expiry'][ $backup ] === 29 * DAY_IN_SECONDS, 'Fallback has 29-day expiration.' );
test_ok( 'https://example.org/' === $GLOBALS['api_calls'][0][1]['headers']['Referer'], 'Site URL sent in API request header.' );

$out2 = call_user_func( $render, array( 'id' => $id ) );
test_ok( count( $GLOBALS['api_calls'] ) === 2, 'Warm transient requires no further API calls.' );
test_ok( substr_count( $out2, 'role="listitem"' ) === 3, 'Cached results rendered.' );
// Both shortcode callbacks are actually executable and render the same cached grid.
$out_new = call_user_func( $GLOBALS['shortcodes']['lyte_playlist_grid'], array( 'id' => $id ), null, 'lyte_playlist_grid' );
$out_legacy = call_user_func( $GLOBALS['shortcodes']['technickr_playlist'], array( 'id' => $id ), null, 'technickr_playlist' );
test_ok( $out_new === $out_legacy, 'New and legacy shortcodes render identical output.' );
assert_contains( 'class="lyte-playlist-grid"', $out_new, 'Public markup uses neutral CSS classes.' );
test_ok( count( $GLOBALS['api_calls'] ) === 2, 'Neither shortcode triggers requests on a warm cache.' );

// Expire successful cache. Simulate a quota/API outage; backup remains visible.
unset( $GLOBALS['tr'][ $cache ] );
$GLOBALS['api_queue'][] = array( 'status' => 403, 'body' => '{}' );
$out3 = call_user_func( $render, array( 'id' => $id ) );
test_ok( substr_count( $out3, 'role="listitem"' ) === 3, 'Offline fallback continues showing videos.' );
test_ok( isset( $GLOBALS['tr'][ $error ] ), 'Errors have a separate cooldown cache.' );
test_ok( count( $GLOBALS['api_calls'] ) === 3, 'Exactly one refresh request attempted.' );
call_user_func( $render, array( 'id' => $id ) );
test_ok( count( $GLOBALS['api_calls'] ) === 3, 'Cooldown avoids repeated failing API calls.' );

// v1.0.0 cached duplicate IDs should be filtered without a new API request.
$GLOBALS['tr'][ $cache ] = array(
    array( 'id' => 'AAAAAAAAAAA', 'title' => 'First' ),
    array( 'id' => 'AAAAAAAAAAA', 'title' => 'First again' ),
);
$old = call_user_func( $render, array( 'id' => $id ) );
test_ok( substr_count( $old, 'role="listitem"' ) === 1, 'Old cache duplicates filtered at render time.' );

$GLOBALS['css_calls'] = array();
$GLOBALS['post'] = new WP_Post( 'Post with [lyte_playlist_grid id="abc"]' );
LYTE_Playlist_Grid::maybe_enqueue_styles();
test_ok( count( $GLOBALS['css_calls'] ) === 1, 'Style pre-enqueued only for containing post.' );
$GLOBALS['css_calls'] = array();
$GLOBALS['post'] = new WP_Post( 'Ordinary post without grid' );
LYTE_Playlist_Grid::maybe_enqueue_styles();
test_ok( count( $GLOBALS['css_calls'] ) === 0, 'Style not loaded for unrelated post.' );
$GLOBALS['post'] = new WP_Post( 'Old page with [technickr_playlist id="abc"]' );
LYTE_Playlist_Grid::maybe_enqueue_styles();
test_ok( count( $GLOBALS['css_calls'] ) === 1, 'Legacy shortcode also pre-enqueues CSS.' );

// No previous success and an API failure: admin is notified, visitor is not.
$id2 = 'PL00000000000000000000000000000000';
$GLOBALS['api_queue'][] = array( 'status' => 403, 'body' => '{}' );
$err = call_user_func( $render, array( 'id' => $id2 ) );
assert_contains( 'HTTP 403', $err, 'Admin receives a bounded API error.' );
$GLOBALS['admin'] = false;
test_ok( '' === call_user_func( $render, array( 'id' => $id2 ) ), 'Visitor sees no error string.' );
$GLOBALS['admin'] = true;

// The one-time upgrade migration must remove v1.0.0 indefinite options.
unset( $GLOBALS['opts']['lpg_storage_migration_12'] );
$GLOBALS['opts'][ 'tpg_backup_' . md5( 'old-id' ) ] = array( 'legacy stale data' );
LYTE_Playlist_Grid::cleanup_legacy_backups();
test_ok( ! isset( $GLOBALS['opts'][ 'tpg_backup_' . md5( 'old-id' ) ] ), 'Legacy indefinite backup deleted.' );
test_ok( isset( $GLOBALS['opts']['lpg_storage_migration_12'] ), 'Migration marker created.' );

echo "OK: {$GLOBALS['tests']} smoke assertions passed.\n";
