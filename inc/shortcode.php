<?php
/**
 * Shortcode: [article_hub]
 *
 * Merges two sources into one sorted list:
 *   1. RSS feeds — fetched live, cached in transients (6h)
 *   2. Manual entries — CPT posts (YouTube, Seznam, etc.)
 *
 * Attributes:
 *   count  — number of articles (default 6)
 *   source — filter by source name, e.g. "Medium" (default: all)
 *   layout — "grid" or "single" (default: grid)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'article_hub', 'wah_render_shortcode' );

/**
 * Fetch RSS articles from all active feeds, cached in transients.
 * Returns normalized array of article data.
 */
function wah_get_rss_articles() {
	$cache_key = 'wah_rss_articles';
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return $cached;
	}

	include_once ABSPATH . WPINC . '/feed.php';

	$feeds    = get_option( 'wah_feeds', array() );
	$articles = array();

	foreach ( $feeds as $feed ) {
		if ( empty( $feed['active'] ) || empty( $feed['url'] ) ) continue;

		$rss = fetch_feed( $feed['url'] );
		if ( is_wp_error( $rss ) ) continue;

		$items = $rss->get_items( 0, 20 );
		foreach ( $items as $item ) {
			$link = $item->get_permalink();
			if ( ! $link ) continue;

			$excerpt = wp_strip_all_tags( $item->get_description() ?: '' );
			if ( mb_strlen( $excerpt ) > 300 ) {
				$excerpt = mb_substr( $excerpt, 0, 297 ) . '...';
			}

			$author_obj = $item->get_author();

			// Thumbnail extraction: enclosure → media:content → szn:image → content <img>
			$thumb = '';
			$enclosure = $item->get_enclosure();
			if ( $enclosure && $enclosure->get_type() && strpos( $enclosure->get_type(), 'image' ) !== false ) {
				$thumb = $enclosure->get_link();
			}
			if ( ! $thumb ) {
				$media = $item->get_item_tags( 'http://search.yahoo.com/mrss/', 'content' );
				if ( ! empty( $media[0]['attribs']['']['url'] ) ) {
					$thumb = $media[0]['attribs']['']['url'];
				}
			}
			if ( ! $thumb ) {
				$szn_img = $item->get_item_tags( 'http://www.seznam.cz', 'image' );
				if ( ! empty( $szn_img[0]['data'] ) ) {
					$thumb = $szn_img[0]['data'];
				}
			}
			if ( ! $thumb ) {
				$content = $item->get_content();
				if ( preg_match( '/<img[^>]+src=["\']([^"\']+)/i', $content, $m ) ) {
					$thumb = $m[1];
				}
			}

			$articles[] = array(
				'title'   => $item->get_title() ?: '(untitled)',
				'url'     => $link,
				'excerpt' => $excerpt,
				'author'  => $author_obj ? $author_obj->get_name() : '',
				'date'    => $item->get_date( 'Y-m-d' ) ?: '',
				'thumb'   => $thumb,
				'source'  => $feed['name'] ?? '',
				'type'    => 'rss',
			);
		}
	}

	// Cache for 6 hours
	set_transient( $cache_key, $articles, 6 * HOUR_IN_SECONDS );

	return $articles;
}

/**
 * Get manual CPT articles, normalized to same format as RSS.
 */
function wah_get_manual_articles( $source_filter = '' ) {
	$args = array(
		'post_type'      => 'external_article',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'orderby'        => 'meta_value',
		'meta_key'       => '_wah_published',
		'order'          => 'DESC',
	);

	if ( $source_filter ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'article_source',
				'field'    => 'name',
				'terms'    => $source_filter,
			),
		);
	}

	$query    = new WP_Query( $args );
	$articles = array();

	while ( $query->have_posts() ) {
		$query->the_post();
		$post_id = get_the_ID();

		// Image priority: media library pick → featured image → external URL
		$thumb = '';
		$media_id = get_post_meta( $post_id, '_wah_media_image', true );
		if ( $media_id ) {
			$thumb = wp_get_attachment_image_url( $media_id, 'medium_large' );
		}
		if ( ! $thumb && has_post_thumbnail( $post_id ) ) {
			$thumb = get_the_post_thumbnail_url( $post_id, 'medium_large' );
		}
		if ( ! $thumb ) {
			$thumb = get_post_meta( $post_id, '_wah_thumbnail_url', true );
		}

		$articles[] = array(
			'title'   => get_the_title(),
			'url'     => get_post_meta( $post_id, '_wah_url', true ),
			'excerpt' => get_post_meta( $post_id, '_wah_excerpt', true ),
			'author'  => get_post_meta( $post_id, '_wah_author', true ),
			'date'    => get_post_meta( $post_id, '_wah_published', true ),
			'thumb'   => $thumb,
			'source'  => get_post_meta( $post_id, '_wah_source_name', true ),
			'type'    => 'manual',
		);
	}
	wp_reset_postdata();

	return $articles;
}

/**
 * Render a single article card.
 */
function wah_render_card( $article, $is_grid, $show_author ) {
	$source_lower = strtolower( $article['source'] );
	$source_class = 'wah-source--other';
	if ( strpos( $source_lower, 'medium' ) !== false ) $source_class = 'wah-source--medium';
	if ( strpos( $source_lower, 'youtube' ) !== false ) $source_class = 'wah-source--youtube';
	if ( strpos( $source_lower, 'ai founders' ) !== false ) $source_class = 'wah-source--aifounders';
	if ( strpos( $source_lower, 'seznam' ) !== false ) $source_class = 'wah-source--seznam';

	$html = '<article class="wah-card ' . esc_attr( $source_class ) . '">';

	// Thumbnail (grid only)
	if ( $article['thumb'] && $is_grid ) {
		$html .= '<div class="wah-image">';
		$html .= '<a href="' . esc_url( $article['url'] ) . '" target="_blank" rel="noopener noreferrer">';
		$html .= '<img src="' . esc_url( $article['thumb'] ) . '" alt="' . esc_attr( $article['title'] ) . '" loading="lazy">';
		$html .= '</a></div>';
	}

	$html .= '<div class="wah-content">';

	if ( $article['source'] ) {
		$html .= '<div class="wah-overtitle">' . esc_html( $article['source'] ) . '</div>';
	}

	$html .= '<h3 class="wah-title"><a href="' . esc_url( $article['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $article['title'] ) . '</a></h3>';

	if ( $article['excerpt'] ) {
		$html .= '<p class="wah-description">' . esc_html( $article['excerpt'] ) . '</p>';
	}

	$html .= '<div class="wah-meta">';
	$meta_parts = array();
	if ( $show_author && ! empty( $article['author'] ) ) {
		$meta_parts[] = '<span class="wah-author">' . esc_html( $article['author'] ) . '</span>';
	}
	if ( ! empty( $article['date'] ) ) {
		$timestamp = strtotime( $article['date'] );
		if ( $timestamp ) {
			$meta_parts[] = '<span class="wah-date">' . esc_html( date_i18n( get_option( 'date_format' ), $timestamp ) ) . '</span>';
		}
	}
	if ( ! empty( $meta_parts ) ) {
		$html .= implode( ' <span class="wah-meta-sep">&middot;</span> ', $meta_parts );
	}
	// Button label: Settings field → Polylang/WPML override if active
		$base_label = get_option( 'wah_button_label', 'Read' );
		$read_label = $base_label;
		if ( function_exists( 'pll__' ) ) {
			$translated = pll__( $base_label );
			if ( $translated !== $base_label ) $read_label = $translated;
		} elseif ( function_exists( '__' ) ) {
			$translated = __( $base_label, 'wp-article-hub' );
			if ( $translated !== $base_label ) $read_label = $translated;
		}
	$html .= '<a href="' . esc_url( $article['url'] ) . '" target="_blank" rel="noopener noreferrer" class="wah-button">' . esc_html( $read_label ) . '</a>';
	$html .= '</div>'; // .wah-meta
	$html .= '</div>'; // .wah-content
	$html .= '</article>';

	return $html;
}

/**
 * Main shortcode handler — merges RSS + manual, sorts by date, renders cards.
 */
function wah_render_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'count'  => 6,
		'source' => '',
		'layout' => 'grid',
	), $atts, 'article_hub' );

	$count         = absint( $atts['count'] );
	$source_filter = $atts['source'];

	// Gather articles from both sources
	$rss_articles    = wah_get_rss_articles();
	$manual_articles = wah_get_manual_articles( $source_filter );

	// Filter RSS by source if specified
	if ( $source_filter ) {
		$rss_articles = array_filter( $rss_articles, function ( $a ) use ( $source_filter ) {
			return stripos( $a['source'], $source_filter ) !== false;
		} );
	}

	// Merge and sort by date descending
	$all_articles = array_merge( $rss_articles, $manual_articles );
	usort( $all_articles, function ( $a, $b ) {
		return strcmp( $b['date'], $a['date'] );
	} );

	// Deduplicate by URL
	$seen = array();
	$all_articles = array_filter( $all_articles, function ( $a ) use ( &$seen ) {
		$key = $a['url'];
		if ( isset( $seen[ $key ] ) ) return false;
		$seen[ $key ] = true;
		return true;
	} );

	// Limit
	$all_articles = array_slice( $all_articles, 0, $count );

	if ( empty( $all_articles ) ) {
		return '<p class="wah-empty">' . __( 'No articles found.', 'wp-article-hub' ) . '</p>';
	}

	// Inline CSS
	static $css_printed = false;
	$css_html = '';
	if ( ! $css_printed ) {
		$css_file = WAH_PATH . 'assets/css/style.css';
		if ( file_exists( $css_file ) ) {
			$css_html = '<style id="wah-inline-styles">' . file_get_contents( $css_file ) . '</style>';
		}
		$custom_css = get_option( 'wah_custom_css', '' );
		if ( $custom_css ) {
			$css_html .= '<style id="wah-custom-styles">' . $custom_css . '</style>';
		}
		$css_printed = true;
	}

	$show_author   = get_option( 'wah_show_author', false );
	$is_grid       = $atts['layout'] === 'grid' && count( $all_articles ) > 1;
	$wrapper_class = $is_grid ? 'wah-grid' : 'wah-single';

	$html = $css_html . '<div class="wah-embed"><div class="' . $wrapper_class . '">';

	foreach ( $all_articles as $article ) {
		$html .= wah_render_card( $article, $is_grid, $show_author );
	}

	$html .= '</div></div>';

	return $html;
}
