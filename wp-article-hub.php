<?php
/**
 * Plugin Name: WP Article Hub
 * Plugin URI:  https://github.com/rostislavpeska/wp_article_hub
 * Description: Multi-source article aggregator. RSS feeds are cached live (no DB import). Manual entries (YouTube, Seznam, etc.) stored as CPT. Displays via shortcode [article_hub].
 * Version:     2.0.0
 * Author:      Rostislav Peška
 * Author URI:  https://rostislavpeska.com
 * License:     MIT
 * Text Domain: wp-article-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WAH_VERSION', '2.0.0' );
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
	delete_transient( 'wah_rss_articles' );
} );

// Register translatable strings with Polylang (uses saved label as base)
add_action( 'init', function () {
	if ( function_exists( 'pll_register_string' ) ) {
		$label = get_option( 'wah_button_label', 'Read' );
		pll_register_string( 'wah_read_button', $label, 'WP Article Hub' );
	}
} );
