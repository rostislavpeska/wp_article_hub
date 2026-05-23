# Owner binding — WP Article Hub 2.1.0

**Status:** spec for v2.1.0.
**Goal:** allow callers to scope `[article_hub]` to "feeds + manual articles belonging to a specific post" (e.g. a persona, a project, a brand) without the plugin knowing what that post represents.

---

## What changes

### Shortcode

```
[article_hub]                              ← unchanged: global feeds + all manual entries
[article_hub owner_post_id="152"]          ← NEW: scoped to whatever owns post 152
[article_hub owner_post_id="152" count="9"]
```

### Two new filter hooks

The shortcode resolves its data source through filters. Anyone (theme / mu-plugin / another plugin) can hook them to inject data.

```php
/**
 * Return an array of {url, name, active} rows for an owner post, OR
 * NULL to fall through to the plugin's default (global wah_feeds option).
 *
 * @param array|null $feeds       Whatever the previous filter returned, or null.
 * @param int        $owner_post_id  Owner post ID, or 0 for the unscoped/global call.
 * @return array|null
 */
$feeds = apply_filters( 'wah_feeds_for_owner', null, $owner_post_id );

/**
 * Return an array of article items (already normalised to the same shape
 * wah_get_manual_articles produces — title/url/excerpt/author/date/thumb/
 * source/type) for an owner post, OR NULL to fall through to the default
 * (all external_article CPT posts).
 *
 * @param array|null $articles
 * @param int        $owner_post_id
 * @return array|null
 */
$articles = apply_filters( 'wah_manual_articles_for_owner', null, $owner_post_id );
```

### Cache

Cache key is now derived per owner: `wah_rss_articles_o{N}` where N is owner_post_id (or `_global` when 0). Per-owner 6h independent lifetime. Existing `wah_rss_articles` transient is preserved as the global cache key (renamed internally — same semantics).

### Backward compatibility

- No `owner_post_id` attribute → exact 2.0.0 behaviour. Global feeds, global manual articles.
- Filter not hooked by anyone → exact 2.0.0 behaviour, including for owner-scoped calls (plugin's defaults apply).
- Existing `wah_feeds` global option, existing `external_article` CPT, existing admin pages — unchanged.

---

## Why this shape

Rejected alternative — per-row "Owner" picker on the global Feeds admin page:
- Admin has to remember the feed table exists and filter it by owner.
- Plugin couples its admin UX to a concept (owner) it doesn't really understand.

Filter-based shape:
- Plugin remains a generic engine: RSS fetch + parse + cache + render.
- Theme owns the data model (e.g. for personas, the feeds live as an ACF repeater on the persona post).
- Admin UX is owner-centric (you edit Rostislav, you see his feeds on his edit screen — wherever the theme places them).
- Plugin reusable on any site without the owner concept.

---

## Implementation surface

| File | Change |
|---|---|
| `inc/shortcode.php` | Add `owner_post_id` to shortcode_atts. Pass it through `wah_get_rss_articles($owner_post_id)` + `wah_get_manual_articles($source_filter, $owner_post_id)`. Add the two `apply_filters` calls and the fall-through to existing defaults. Cache key includes owner. |
| `wp-article-hub.php` | `WAH_VERSION` → 2.1.0. |
| `README.md` | Short "Owner binding" section + example filter implementation. |
| `docs/owner-binding.md` | This file. |

No new admin screens, no new wp_options, no new metaboxes, no DB migration.

---

## Acceptance criteria

1. `[article_hub]` on a page → renders exactly as 2.0.0 (global feeds + all manual articles, sorted, deduped).
2. `[article_hub owner_post_id="999999"]` with no filter hooked → renders global feeds + global articles (default fall-through behaviour).
3. With a hooked `wah_feeds_for_owner` returning a custom list for owner 152, `[article_hub owner_post_id="152"]` → renders only those feeds (plus default manual articles unless `wah_manual_articles_for_owner` is also hooked).
4. With both filters hooked for owner 152 → renders only that owner's feeds + that owner's manual articles.
5. Calling `[article_hub owner_post_id="152"]` twice on the same page → uses the same per-owner cache (no double fetch). Different owner IDs → independent caches.
6. Plugin's "Clear RSS Cache" button → clears every per-owner transient (sweep all keys matching `wah_rss_articles_o%`).

---

## Theme-side implementation (separate spec — aiguild-blue)

The theme will hook both filters in a small `inc/persona-article-hub.php` that:
- routes `wah_feeds_for_owner` to a `persona_rss_feeds` ACF repeater on persona posts;
- routes `wah_manual_articles_for_owner` to `external_article` CPT posts where `_wah_owner_persona` post_meta points to the persona.

That's documented in the theme's `docs/persona-rework.md` round 2 spec, not here.

---

## Deploy

Two files to upload to a production WordPress install of the plugin:
```
wp-article-hub.php  (version bump)
inc/shortcode.php   (the actual work)
```
Plus `README.md` and `docs/owner-binding.md` for the GitHub side. No DB changes.
