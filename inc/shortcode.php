<?php
/**
 * Shortcode: [article_hub]
 *
 * Attributes:
 *   count  — number of articles (default 6)
 *   source — filter by source name, e.g. "Medium" (default: all)
 *   layout — "grid" or "single" (default: grid)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'article_hub', 'wah_render_shortcode' );

function wah_render_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'count'  => 6,
		'source' => '',
		'layout' => 'grid',
	), $atts, 'article_hub' );

	$args = array(
		'post_type'      => 'external_article',
		'post_status'    => 'publish',
		'posts_per_page' => absint( $atts['count'] ),
		'orderby'        => 'meta_value',
		'meta_key'       => '_wah_published',
		'order'          => 'DESC',
	);

	if ( $atts['source'] ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'article_source',
				'field'    => 'name',
				'terms'    => $atts['source'],
			),
		);
	}

	$query = new WP_Query( $args );

	if ( ! $query->have_posts() ) {
		return '<p class="wah-empty">' . __( 'No articles found.', 'wp-article-hub' ) . '</p>';
	}

	// Inline CSS — once per page, directly in output (immune to enqueue timing, caching, page builders)
	static $css_printed = false;
	$css_html = '';
	if ( ! $css_printed ) {
		$css_file = WAH_PATH . 'assets/css/style.css';
		if ( file_exists( $css_file ) ) {
			$css_html = '<style id="wah-inline-styles">' . file_get_contents( $css_file ) . '</style>';
		}
		// Custom CSS from settings
		$custom_css = get_option( 'wah_custom_css', '' );
		if ( $custom_css ) {
			$css_html .= '<style id="wah-custom-styles">' . $custom_css . '</style>';
		}
		$css_printed = true;
	}

	$show_author = get_option( 'wah_show_author', false );

	$is_grid = $atts['layout'] === 'grid' && $query->post_count > 1;
	$wrapper_class = $is_grid ? 'wah-grid' : 'wah-single';

	$html = $css_html . '<div class="wah-embed"><div class="' . $wrapper_class . '">';

	while ( $query->have_posts() ) {
		$query->the_post();
		$post_id = get_the_ID();

		$url     = get_post_meta( $post_id, '_wah_url', true );
		$excerpt = get_post_meta( $post_id, '_wah_excerpt', true );
		$source  = get_post_meta( $post_id, '_wah_source_name', true );
		$author  = get_post_meta( $post_id, '_wah_author', true );
		$date    = get_post_meta( $post_id, '_wah_published', true );
		$thumb   = '';

		// Thumbnail: Featured Image → external URL → none
		if ( has_post_thumbnail( $post_id ) ) {
			$thumb = get_the_post_thumbnail_url( $post_id, 'medium_large' );
		}
		if ( ! $thumb ) {
			$thumb = get_post_meta( $post_id, '_wah_thumbnail_url', true );
		}

		// Format date
		$formatted_date = '';
		if ( $date ) {
			$timestamp = strtotime( $date );
			if ( $timestamp ) {
				$formatted_date = date_i18n( get_option( 'date_format' ), $timestamp );
			}
		}

		// Source icon hint
		$source_lower = strtolower( $source );
		$source_class = 'wah-source--other';
		if ( strpos( $source_lower, 'medium' ) !== false ) $source_class = 'wah-source--medium';
		if ( strpos( $source_lower, 'youtube' ) !== false ) $source_class = 'wah-source--youtube';
		if ( strpos( $source_lower, 'ai founders' ) !== false ) $source_class = 'wah-source--aifounders';
		if ( strpos( $source_lower, 'seznam' ) !== false ) $source_class = 'wah-source--seznam';

		$html .= '<article class="wah-card ' . esc_attr( $source_class ) . '">';

		// Thumbnail (grid only)
		if ( $thumb && $is_grid ) {
			$html .= '<div class="wah-image">';
			$html .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">';
			$html .= '<img src="' . esc_url( $thumb ) . '" alt="' . esc_attr( get_the_title() ) . '" loading="lazy">';
			$html .= '</a></div>';
		}

		$html .= '<div class="wah-content">';

		if ( $source ) {
			$html .= '<div class="wah-overtitle">' . esc_html( $source ) . '</div>';
		}

		$html .= '<h3 class="wah-title"><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( get_the_title() ) . '</a></h3>';

		if ( $excerpt ) {
			$html .= '<p class="wah-description">' . esc_html( $excerpt ) . '</p>';
		}

		$html .= '<div class="wah-meta">';
		$meta_parts = array();
		if ( $show_author && $author ) {
			$meta_parts[] = '<span class="wah-author">' . esc_html( $author ) . '</span>';
		}
		if ( $formatted_date ) {
			$meta_parts[] = '<span class="wah-date">' . esc_html( $formatted_date ) . '</span>';
		}
		if ( ! empty( $meta_parts ) ) {
			$html .= implode( ' <span class="wah-meta-sep">&middot;</span> ', $meta_parts );
		}
		$html .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" class="wah-button">' . __( 'Read', 'wp-article-hub' ) . '</a>';
		$html .= '</div>'; // .wah-meta

		$html .= '</div>'; // .wah-content
		$html .= '</article>';
	}

	wp_reset_postdata();

	$html .= '</div></div>';

	return $html;
}
