# WP Article Hub

Multi-source article aggregator for WordPress. Auto-imports from RSS feeds (Medium, AI Founders, etc.) and supports manual entries (YouTube, Seznam Medium, conference talks). Displays via `[article_hub]` shortcode.

## Features

- **Auto-import** from any RSS feed (Medium, WordPress, Substack, etc.)
- **Manual entry** for sources without RSS (YouTube interviews, Seznam Medium, etc.)
- **Deduplication** — won't import the same article twice (matched by URL)
- **External links** — click goes to original source, not a local copy
- **Shortcode** — `[article_hub count="6"]` renders a responsive card grid
- **Source filtering** — `[article_hub source="Medium"]` shows only Medium articles
- **Works with any theme** — self-contained CSS, no theme dependency
- **WP Cron** — auto-imports twice daily

## Installation

1. Upload the `wp-article-hub` folder to `wp-content/plugins/`
2. Activate in WP Admin → Plugins
3. Go to Articles → Feed Settings
4. Add your RSS feed URLs
5. Click "Import Now" to test
6. Add `[article_hub]` shortcode to any page

## Shortcode

```
[article_hub]                          — 6 articles, grid, all sources
[article_hub count="3"]                — 3 articles
[article_hub source="Medium"]          — only Medium articles
[article_hub source="AI Founders"]     — only AI Founders articles
[article_hub layout="single"]          — single featured article
```

## Manual Articles

Go to Articles → Add Article in WP Admin. Fill in:
- Title
- External URL (where the article lives)
- Excerpt
- Source Name (e.g. "YouTube", "Seznam Medium")
- Published Date
- Thumbnail URL (or set a Featured Image)

## Feed Settings

Articles → Feed Settings in WP Admin. Configure:
- RSS feed URLs with source names
- Enable/disable individual feeds
- "Import Now" button for manual trigger

## License

MIT
