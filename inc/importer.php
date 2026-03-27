<?php
/**
 * RSS feed importer — fetches articles from configured feeds.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Import all active feeds. Returns total count of new articles.
 */
function wah_run_import() {
	$feeds = get_option( 'wah_feeds', array() );
	$total = 0;

	foreach ( $feeds as $feed ) {
		if ( empty( $feed['active'] ) || empty( $feed['url'] ) ) continue;
		$total += wah_import_feed( $feed['url'], $feed['name'] ?? '' );
	}

	return $total;
}

/**
 * Import a single RSS feed.
 *
 * @param string $url  Feed URL.
 * @param string $name Source name (e.g. "Medium", "AI Founders").
 * @return int Number of new articles imported.
 */
function wah_import_feed( $url, $name = '' ) {
	// WordPress built-in RSS parser (SimplePie)
	include_once ABSPATH . WPINC . '/feed.php';

	$rss = fetch_feed( $url );
	if ( is_wp_error( $rss ) ) {
		error_log( 'WP Article Hub: Failed to fetch ' . $url . ' — ' . $rss->get_error_message() );
		return 0;
	}

	$max_items = $rss->get_item_quantity( 20 );
	$items     = $rss->get_items( 0, $max_items );
	$count     = 0;

	foreach ( $items as $item ) {
		$link = $item->get_permalink();
		if ( ! $link ) continue;

		// Dedup by URL — skip if already imported
		$existing = get_posts( array(
			'post_type'      => 'external_article',
			'post_status'    => 'any',
			'meta_key'       => '_wah_url',
			'meta_value'     => $link,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		) );

		if ( ! empty( $existing ) ) continue;

		// Extract data
		$title   = $item->get_title() ?: '(untitled)';
		$excerpt = $item->get_description() ?: '';
		$excerpt = wp_strip_all_tags( $excerpt );
		if ( mb_strlen( $excerpt ) > 300 ) {
			$excerpt = mb_substr( $excerpt, 0, 297 ) . '...';
		}

		$pub_date = $item->get_date( 'Y-m-d' ) ?: current_time( 'Y-m-d' );

		// Author from feed (dc:creator or author tag)
		$author_obj = $item->get_author();
		$author = $author_obj ? $author_obj->get_name() : '';

		// Try to extract thumbnail from enclosure, media:content, or content
		$thumb = '';
		$enclosure = $item->get_enclosure();
		if ( $enclosure && strpos( $enclosure->get_type(), 'image' ) !== false ) {
			$thumb = $enclosure->get_link();
		}
		if ( ! $thumb ) {
			// Try media:content or media:thumbnail
			$media = $item->get_item_tags( 'http://search.yahoo.com/mrss/', 'content' );
			if ( ! empty( $media[0]['attribs']['']['url'] ) ) {
				$thumb = $media[0]['attribs']['']['url'];
			}
		}
		if ( ! $thumb ) {
			// Try szn:image (Seznam feeds)
			$szn_img = $item->get_item_tags( 'http://www.seznam.cz', 'image' );
			if ( ! empty( $szn_img[0]['data'] ) ) {
				$thumb = $szn_img[0]['data'];
			}
		}
		if ( ! $thumb ) {
			// Fallback: extract first <img> from content
			$content = $item->get_content();
			if ( preg_match( '/<img[^>]+src=["\']([^"\']+)/i', $content, $m ) ) {
				$thumb = $m[1];
			}
		}

		// Create the article
		$post_id = wp_insert_post( array(
			'post_type'   => 'external_article',
			'post_title'  => $title,
			'post_status' => 'publish',
			'post_date'   => $pub_date . ' 00:00:00',
		) );

		if ( is_wp_error( $post_id ) || ! $post_id ) continue;

		// Save meta
		update_post_meta( $post_id, '_wah_url', esc_url_raw( $link ) );
		update_post_meta( $post_id, '_wah_excerpt', $excerpt );
		update_post_meta( $post_id, '_wah_source_name', $name );
		update_post_meta( $post_id, '_wah_author', $author );
		update_post_meta( $post_id, '_wah_published', $pub_date );
		if ( $thumb ) {
			update_post_meta( $post_id, '_wah_thumbnail_url', esc_url_raw( $thumb ) );
		}

		// Assign source taxonomy
		if ( $name ) {
			wp_set_object_terms( $post_id, $name, 'article_source' );
		}

		$count++;
	}

	return $count;
}
