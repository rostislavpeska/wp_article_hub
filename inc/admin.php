<?php
/**
 * Admin: meta boxes for external_article + settings page for RSS feeds.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ========================================================================
   META BOXES — article fields (external URL, excerpt, source, date)
   ======================================================================== */

add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'wah_article_details',
		__( 'Article Details', 'wp-article-hub' ),
		'wah_render_article_metabox',
		'external_article',
		'normal',
		'high'
	);
} );

function wah_render_article_metabox( $post ) {
	wp_nonce_field( 'wah_article_meta', 'wah_article_nonce' );

	$url     = get_post_meta( $post->ID, '_wah_url', true );
	$excerpt = get_post_meta( $post->ID, '_wah_excerpt', true );
	$source  = get_post_meta( $post->ID, '_wah_source_name', true );
	$author  = get_post_meta( $post->ID, '_wah_author', true );
	$date    = get_post_meta( $post->ID, '_wah_published', true );
	$thumb   = get_post_meta( $post->ID, '_wah_thumbnail_url', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="wah_url"><?php _e( 'External URL', 'wp-article-hub' ); ?></label></th>
			<td><input type="url" id="wah_url" name="wah_url" value="<?php echo esc_url( $url ); ?>" class="large-text" placeholder="https://medium.com/@you/article-slug" required></td>
		</tr>
		<tr>
			<th><label for="wah_excerpt"><?php _e( 'Excerpt', 'wp-article-hub' ); ?></label></th>
			<td><textarea id="wah_excerpt" name="wah_excerpt" rows="3" class="large-text" placeholder="Short description of the article..."><?php echo esc_textarea( $excerpt ); ?></textarea></td>
		</tr>
		<tr>
			<th><label for="wah_source_name"><?php _e( 'Source Name', 'wp-article-hub' ); ?></label></th>
			<td><input type="text" id="wah_source_name" name="wah_source_name" value="<?php echo esc_attr( $source ); ?>" class="regular-text" placeholder="e.g. Medium, YouTube, AI Founders"></td>
		</tr>
		<tr>
			<th><label for="wah_author"><?php _e( 'Author', 'wp-article-hub' ); ?></label></th>
			<td><input type="text" id="wah_author" name="wah_author" value="<?php echo esc_attr( $author ); ?>" class="regular-text" placeholder="e.g. Rostislav Peška">
			<p class="description"><?php _e( 'Auto-imported articles use the feed author. Manual entries: type the author name.', 'wp-article-hub' ); ?></p></td>
		</tr>
		<tr>
			<th><label for="wah_published"><?php _e( 'Published Date', 'wp-article-hub' ); ?></label></th>
			<td><input type="date" id="wah_published" name="wah_published" value="<?php echo esc_attr( $date ); ?>"></td>
		</tr>
		<tr>
			<th><label for="wah_thumbnail_url"><?php _e( 'Thumbnail URL (external)', 'wp-article-hub' ); ?></label></th>
			<td><input type="url" id="wah_thumbnail_url" name="wah_thumbnail_url" value="<?php echo esc_url( $thumb ); ?>" class="large-text" placeholder="https://img.youtube.com/vi/VIDEO_ID/maxresdefault.jpg">
			<p class="description"><?php _e( 'For external images (YouTube thumbnails, etc.). For local images, use <strong>Featured Image</strong> in the sidebar instead — it uses the media gallery.', 'wp-article-hub' ); ?></p></td>
		</tr>
	</table>
	<?php
}

add_action( 'save_post_external_article', function ( $post_id ) {
	if ( ! isset( $_POST['wah_article_nonce'] ) || ! wp_verify_nonce( $_POST['wah_article_nonce'], 'wah_article_meta' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$fields = array(
		'wah_url'           => '_wah_url',
		'wah_excerpt'       => '_wah_excerpt',
		'wah_source_name'   => '_wah_source_name',
		'wah_author'        => '_wah_author',
		'wah_published'     => '_wah_published',
		'wah_thumbnail_url' => '_wah_thumbnail_url',
	);

	foreach ( $fields as $input => $meta_key ) {
		if ( isset( $_POST[ $input ] ) ) {
			$value = sanitize_text_field( $_POST[ $input ] );
			if ( $meta_key === '_wah_url' || $meta_key === '_wah_thumbnail_url' ) {
				$value = esc_url_raw( $_POST[ $input ] );
			}
			if ( $meta_key === '_wah_excerpt' ) {
				$value = sanitize_textarea_field( $_POST[ $input ] );
			}
			update_post_meta( $post_id, $meta_key, $value );
		}
	}
} );

/* ========================================================================
   SETTINGS PAGE — RSS feed URLs
   ======================================================================== */

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=external_article',
		__( 'Feed Settings', 'wp-article-hub' ),
		__( 'Feed Settings', 'wp-article-hub' ),
		'manage_options',
		'wah-settings',
		'wah_render_settings_page'
	);
} );

add_action( 'admin_init', function () {
	register_setting( 'wah_settings', 'wah_feeds', array(
		'type'              => 'array',
		'sanitize_callback' => 'wah_sanitize_feeds',
		'default'           => array(),
	) );
	register_setting( 'wah_settings', 'wah_show_author', array(
		'type'              => 'boolean',
		'sanitize_callback' => 'rest_sanitize_boolean',
		'default'           => false,
	) );
	register_setting( 'wah_settings', 'wah_custom_css', array(
		'type'              => 'string',
		'sanitize_callback' => 'wp_strip_all_tags',
		'default'           => '',
	) );
} );

function wah_sanitize_feeds( $input ) {
	if ( ! is_array( $input ) ) return array();

	$clean = array();
	foreach ( $input as $feed ) {
		if ( empty( $feed['url'] ) ) continue;
		$clean[] = array(
			'url'    => esc_url_raw( $feed['url'] ),
			'name'   => sanitize_text_field( $feed['name'] ?? '' ),
			'active' => ! empty( $feed['active'] ),
		);
	}
	return $clean;
}

function wah_render_settings_page() {
	$feeds = get_option( 'wah_feeds', array() );
	?>
	<div class="wrap">
		<h1><?php _e( 'Article Hub — Feed Settings', 'wp-article-hub' ); ?></h1>

		<form method="post" action="options.php">
			<?php settings_fields( 'wah_settings' ); ?>

			<h2><?php _e( 'RSS Feeds (auto-import)', 'wp-article-hub' ); ?></h2>
			<p class="description"><?php _e( 'Articles from these feeds are imported automatically twice daily.', 'wp-article-hub' ); ?></p>

			<table class="widefat" id="wah-feeds-table">
				<thead>
					<tr>
						<th><?php _e( 'Active', 'wp-article-hub' ); ?></th>
						<th><?php _e( 'Source Name', 'wp-article-hub' ); ?></th>
						<th><?php _e( 'Feed URL', 'wp-article-hub' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php
				if ( empty( $feeds ) ) {
					$feeds = array(
						array( 'url' => '', 'name' => 'Medium', 'active' => true ),
						array( 'url' => '', 'name' => 'AI Founders', 'active' => true ),
					);
				}
				foreach ( $feeds as $i => $feed ) : ?>
					<tr>
						<td><input type="checkbox" name="wah_feeds[<?php echo $i; ?>][active]" value="1" <?php checked( $feed['active'] ?? false ); ?>></td>
						<td><input type="text" name="wah_feeds[<?php echo $i; ?>][name]" value="<?php echo esc_attr( $feed['name'] ); ?>" class="regular-text"></td>
						<td><input type="url" name="wah_feeds[<?php echo $i; ?>][url]" value="<?php echo esc_url( $feed['url'] ); ?>" class="large-text" placeholder="https://medium.com/feed/@username"></td>
						<td><button type="button" class="button wah-remove-feed">&times;</button></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<p><button type="button" class="button" id="wah-add-feed">+ <?php _e( 'Add Feed', 'wp-article-hub' ); ?></button></p>

			<hr>

			<h2><?php _e( 'Display Settings', 'wp-article-hub' ); ?></h2>

			<table class="form-table">
				<tr>
					<th><?php _e( 'Show Author', 'wp-article-hub' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wah_show_author" value="1" <?php checked( get_option( 'wah_show_author', false ) ); ?>>
							<?php _e( 'Display author name on article cards', 'wp-article-hub' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><label for="wah_custom_css"><?php _e( 'Custom CSS', 'wp-article-hub' ); ?></label></th>
					<td>
						<textarea id="wah_custom_css" name="wah_custom_css" rows="10" class="large-text code" placeholder=".wah-embed .wah-title { font-size: 24px !important; }&#10;.wah-embed .wah-button { color: #ff0000 !important; }"><?php echo esc_textarea( get_option( 'wah_custom_css', '' ) ); ?></textarea>
						<p class="description">
							<?php _e( 'Override default styles. All selectors must start with <code>.wah-embed</code>. Use <code>!important</code> to override defaults.', 'wp-article-hub' ); ?>
							<br><a href="https://github.com/rostislavpeska/wp-article-hub#custom-css" target="_blank" rel="noopener"><?php _e( 'CSS reference &rarr;', 'wp-article-hub' ); ?></a>
						</p>
					</td>
				</tr>
			</table>

			<p>
				<?php submit_button( __( 'Save Settings', 'wp-article-hub' ), 'primary', 'submit', false ); ?>
				&nbsp;
				<button type="button" class="button" id="wah-clear-cache"><?php _e( 'Clear RSS Cache', 'wp-article-hub' ); ?></button>
				<span id="wah-cache-status"></span>
			</p>
		</form>
	</div>

	<script>
	(function() {
		// Add feed row
		document.getElementById('wah-add-feed').addEventListener('click', function() {
			var tbody = document.querySelector('#wah-feeds-table tbody');
			var idx = tbody.children.length;
			var tr = document.createElement('tr');
			tr.innerHTML = '<td><input type="checkbox" name="wah_feeds[' + idx + '][active]" value="1" checked></td>' +
				'<td><input type="text" name="wah_feeds[' + idx + '][name]" value="" class="regular-text" placeholder="Source name"></td>' +
				'<td><input type="url" name="wah_feeds[' + idx + '][url]" value="" class="large-text" placeholder="https://..."></td>' +
				'<td><button type="button" class="button wah-remove-feed">&times;</button></td>';
			tbody.appendChild(tr);
		});

		// Remove feed row
		document.addEventListener('click', function(e) {
			if (e.target.classList.contains('wah-remove-feed')) {
				e.target.closest('tr').remove();
			}
		});

		// Clear RSS cache
		document.getElementById('wah-clear-cache').addEventListener('click', function() {
			var btn = this;
			var status = document.getElementById('wah-cache-status');
			btn.disabled = true;
			status.textContent = 'Clearing...';

			fetch(ajaxurl + '?action=wah_clear_cache&_wpnonce=' + '<?php echo wp_create_nonce( "wah_clear_cache" ); ?>')
				.then(function(r) { return r.json(); })
				.then(function(data) {
					status.textContent = data.data || 'Done';
					btn.disabled = false;
				})
				.catch(function() {
					status.textContent = 'Error';
					btn.disabled = false;
				});
		});
	})();
	</script>
	<?php
}

// AJAX handler for "Clear RSS Cache" button
add_action( 'wp_ajax_wah_clear_cache', function () {
	check_ajax_referer( 'wah_clear_cache', '_wpnonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

	delete_transient( 'wah_rss_articles' );
	wp_send_json_success( __( 'Cache cleared. RSS feeds will refresh on next page load.', 'wp-article-hub' ) );
} );
