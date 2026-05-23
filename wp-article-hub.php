<?php
/**
 * Plugin Name: WP Article Hub
 * Plugin URI:  https://github.com/rostislavpeska/wp_article_hub
 * Description: Multi-source article aggregator. RSS feeds are cached live (no DB import). Manual entries (YouTube, Seznam, etc.) stored as CPT. Displays via shortcode [article_hub].
 * Version:     2.1.0
 * Author:      Rostislav Peška
 * Author URI:  https://rostislavpeska.com
 * License:     MIT
 * Text Domain: wp-article-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WAH_VERSION', '2.1.0' );
define( 'WAH_PATH', plugin_dir_path( __FILE__ ) );
define( 'WAH_URL', plugin_dir_url( __FILE__ ) );

// Load components
require_once WAH_PATH . 'inc/cpt.php';
require_once WAH_PATH . 'inc/admin.php';
require_once WAH_PATH . 'inc/shortcode.php';

// Activation: flush rewrite rules
register_activation_hook( __FILE__, function () {
	wah_register_cpt();
	flush_rewrite_rules();
} );

// Deactivation: clean up
register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
	// Legacy global cache key (2.0.0) + 2.1.0 per-owner keys.
	delete_transient( 'wah_rss_articles' );
	wah_clear_all_owner_caches();
} );

/**
 * Sweep every per-owner RSS cache transient. Called by deactivation and
 * by the admin "Clear RSS Cache" button.
 */
function wah_clear_all_owner_caches() {
	global $wpdb;
	// 'wah_rss_articles_o*' transients live as _transient_wah_rss_articles_o*
	// in wp_options; the matching timeout rows have a _timeout_ prefix.
	// One direct query is faster than iterating known owner IDs (we don't
	// know which owners have caches).
	$prefix_value   = $wpdb->esc_like( '_transient_wah_rss_articles_o' ) . '%';
	$prefix_timeout = $wpdb->esc_like( '_transient_timeout_wah_rss_articles_o' ) . '%';
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $prefix_value, $prefix_timeout ) );
}

// Register translatable strings with Polylang (uses saved label as base)
add_action( 'init', function () {
	if ( function_exists( 'pll_register_string' ) ) {
		$label = get_option( 'wah_button_label', 'Read' );
		pll_register_string( 'wah_read_button', $label, 'WP Article Hub' );
	}
} );
