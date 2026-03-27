<?php
/**
 * Register external_article CPT.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'wah_register_cpt' );

function wah_register_cpt() {
	register_post_type( 'external_article', array(
		'labels' => array(
			'name'               => __( 'Articles', 'wp-article-hub' ),
			'singular_name'      => __( 'Article', 'wp-article-hub' ),
			'add_new'            => __( 'Add Article', 'wp-article-hub' ),
			'add_new_item'       => __( 'Add New Article', 'wp-article-hub' ),
			'edit_item'          => __( 'Edit Article', 'wp-article-hub' ),
			'all_items'          => __( 'All Articles', 'wp-article-hub' ),
			'search_items'       => __( 'Search Articles', 'wp-article-hub' ),
			'not_found'          => __( 'No articles found.', 'wp-article-hub' ),
			'not_found_in_trash' => __( 'No articles found in trash.', 'wp-article-hub' ),
		),
		'public'       => false,
		'show_ui'      => true,
		'show_in_rest' => true,
		'rest_base'    => 'external-articles',
		'menu_position' => 26,
		'menu_icon'    => 'dashicons-rss',
		'supports'     => array( 'title', 'thumbnail' ),
		'has_archive'  => false,
		'rewrite'      => false,
	) );

	// Source taxonomy — auto/manual tagging
	register_taxonomy( 'article_source', 'external_article', array(
		'labels' => array(
			'name'          => __( 'Sources', 'wp-article-hub' ),
			'singular_name' => __( 'Source', 'wp-article-hub' ),
			'add_new_item'  => __( 'Add Source', 'wp-article-hub' ),
		),
		'public'       => false,
		'show_ui'      => false,
		'show_in_rest' => true,
		'hierarchical' => false,
		'rewrite'      => false,
	) );
}
