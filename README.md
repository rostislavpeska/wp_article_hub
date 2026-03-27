# WP Article Hub

Multi-source article aggregator for WordPress. Combines RSS feeds and manual entries into a unified, responsive card grid via a single shortcode. Zero theme dependency — works with Divi, Elementor, Astra, GeneratePress, or any theme.

## How It Works

- **RSS feeds** are fetched live and cached for 6 hours (WordPress transients). No database import — articles stay at the source.
- **Manual entries** (YouTube, conferences, magazines without RSS) are stored as a lightweight custom post type.
- **One shortcode** merges both sources, deduplicates by URL, and sorts by date.

## Features

- **Live RSS** — fetches from any RSS/Atom feed (Medium, Substack, WordPress, etc.)
- **Manual entries** — CPT for sources without RSS (YouTube interviews, print magazines, etc.)
- **Transient cache** — RSS cached 6h, no DB bloat, "Clear Cache" button in admin
- **External links** — clicks go to the original source, not a local copy
- **Responsive grid** — auto-fit cards, collapses to single column on mobile
- **Source filtering** — show only articles from a specific source
- **Author display** — optional, toggle in settings
- **Image support** — media library picker, external URL, or auto-extracted from RSS
- **Custom CSS** — textarea in settings to override styles per site
- **Translatable** — Polylang (`pll__`), WPML, and standard `.po/.mo` support
- **Theme-proof CSS** — `!important` isolation, works even with aggressive themes
- **No cron jobs** — RSS is fetched on page load, cached in transients

## Installation

1. Upload the `wp-article-hub` folder to `wp-content/plugins/`
2. Activate in WP Admin → Plugins
3. Go to **Article Hub → RSS Feeds** — add your feed URLs
4. Go to **Article Hub → Settings** — configure display options
5. Add `[article_hub]` shortcode to any page or widget

## Shortcode

```
[article_hub]                     — 6 articles, grid layout, all sources
[article_hub count="3"]           — limit to 3 articles
[article_hub source="Medium"]     — filter by source name
[article_hub layout="single"]     — single-column list layout
[article_hub count="1" layout="single"]  — featured single article
```

Drop into any page builder: Divi Code Module, Elementor Shortcode widget, Gutenberg Shortcode block, or plain HTML.

## Admin Pages

### Article Hub → All Articles
Manual entries only. RSS articles are not stored in the database.

### Article Hub → Add New Article
For sources without RSS feeds. Fields:
- **Title** — article headline
- **External URL** — link to the original article (required)
- **Excerpt** — short description
- **Source Name** — e.g. "YouTube", "Seznam Medium"
- **Author** — article author name
- **Published Date** — original publication date
- **Image** — two options:
  - **Media Library** — select from WordPress media gallery
  - **External URL** — paste an image URL (e.g. YouTube thumbnail: `https://img.youtube.com/vi/VIDEO_ID/maxresdefault.jpg`)

### Article Hub → RSS Feeds
Configure RSS feed URLs. Each feed has:
- **Active** checkbox — enable/disable without deleting
- **Source Name** — label shown on cards (e.g. "Medium", "My Blog")
- **Feed URL** — any valid RSS or Atom feed URL

**Clear RSS Cache** button forces re-fetch on next page load.

### Article Hub → Settings
- **Show Author** — display author name on article cards
- **Custom CSS** — override default styles (see below)

## Custom CSS

Override any style via the Custom CSS textarea in Settings. All selectors must start with `.wah-embed`. Use `!important` to override defaults.

### Available Classes

| Class | Element |
|-------|---------|
| `.wah-embed` | Outer wrapper |
| `.wah-grid` | Grid container |
| `.wah-single` | Single/list container |
| `.wah-card` | Article card |
| `.wah-image` | Thumbnail container |
| `.wah-content` | Text content area |
| `.wah-overtitle` | Source label |
| `.wah-title` | Article title (h3) |
| `.wah-title a` | Title link |
| `.wah-description` | Excerpt text |
| `.wah-meta` | Date + button row |
| `.wah-author` | Author name |
| `.wah-date` | Published date |
| `.wah-button` | "Read" CTA button |

### Source-Specific Classes

Cards get an additional class based on source name:
- `.wah-source--medium`
- `.wah-source--youtube`
- `.wah-source--aifounders`
- `.wah-source--seznam`
- `.wah-source--other` (default)

### Example: Custom Branding

```css
/* Change title font */
.wah-embed .wah-title { font-family: 'Georgia', serif !important; }

/* Change button color */
.wah-embed a.wah-button { border-color: #ff6600 !important; color: #ff6600 !important; }
.wah-embed a.wah-button:hover { background-color: #ff6600 !important; color: #fff !important; }

/* Hide thumbnails */
.wah-embed .wah-image { display: none !important; }

/* Style YouTube cards differently */
.wah-embed .wah-source--youtube .wah-overtitle { color: #ff0000 !important; }
```

## Image Priority

For manual entries, images are resolved in this order:
1. **Media Library pick** (via "Select Image" button in metabox)
2. **Featured Image** (WordPress sidebar)
3. **External URL** (Thumbnail URL field)

For RSS articles, images are extracted from:
1. RSS `<enclosure>` (if image type)
2. `<media:content>` (MRSS standard)
3. `<szn:image>` (Seznam feeds)
4. First `<img>` tag in content

## Translation

The plugin supports three translation methods:

| Method | How |
|--------|-----|
| **Polylang** | WP Admin → Languages → String translations → "WP Article Hub" group |
| **WPML** | WPML → String Translation → domain `wp-article-hub` |
| **.po/.mo files** | Text domain: `wp-article-hub` |

Translatable strings: "Read" (button label).

Date formatting uses `date_i18n()` — automatically localized based on WordPress locale.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- No plugin dependencies
- No theme dependencies

## License

MIT
