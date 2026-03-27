<?php
/**
 * Plugin Name: WP Article Hub
 * Plugin URI:  https://github.com/rostislavpeska/wp-article-hub
 * Description: Multi-source article aggregator. Auto-imports from RSS feeds (Medium, AI Founders, etc.) and supports manual entries (YouTube, Seznam Medium). Displays via shortcode [article_hub].
 * Version:     1.0.0
 * Author:      Rostislav Peška
 * Author URI:  https://rostislavpeska.com
 * License:     MIT
 * Text Domain: wp-article-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WAH_VERSION', '1.0.0' );
define( 'WAH_PATH', plugin_dir_path( __FILE__ ) );
define( 'WAH_URL', plugin_dir_url( __FILE__ ) );

// Load components
require_once WAH_PATH . 'inc/cpt.php';
require_once WAH_PATH . 'inc/admin.php';
require_once WAH_PATH . 'inc/importer.php';
require_once WAH_PATH . 'inc/shortcode.php';

// Activation: flush rewrite rules + schedule cron
register_activation_hook( __FILE__, function () {
	wah_register_cpt();
	flush_rewrite_rules();

	if ( ! wp_next_scheduled( 'wah_import_feeds' ) ) {
		wp_schedule_event( time(), 'twicedaily', 'wah_import_feeds' );
	}
} );

// Deactivation: clear cron
register_deactivation_hook( __FILE__, function () {
	wp_clear_scheduled_hook( 'wah_import_feeds' );
	flush_rewrite_rules();
} );

// Hook cron action
add_action( 'wah_import_feeds', 'wah_run_import' );

// Register translatable strings with Polylang
add_action( 'init', function () {
	if ( function_exists( 'pll_register_string' ) ) {
		pll_register_string( 'wah_read_button', 'Read', 'WP Article Hub' );
	}
} );
