# PRC Homepages

Manages the Pew Research Center homepage through a custom `homepage` post type, enabling editors to draft, schedule, and version-control the front page without touching the block theme template. The most recently published `homepage` post is rendered on the front page via the `prc-platform/latest-homepage` block.

## What it does

- Registers the `homepage` custom post type with full block editor, revision, and custom fields support
- Provides the `prc-platform/latest-homepage` block, which renders the content of the most recently published `homepage` post on the frontend; in the editor it uses `InnerBlocksAsSyncedContent` so the homepage layout is editable in-place inside the block theme template
- Exposes inspector controls in the block editor for creating a new draft homepage and for switching the editor preview to any existing homepage
- Rewrites the permalink of a published `homepage` post to `home_url()` so the post link always points to the site root
- Replaces the generic "Edit Page" admin bar item with "Edit Homepage" when a logged-in user is viewing the front page
- Registers a custom RSS feed at `/feed/homepage` that enumerates all `prc-block/story-item` post IDs found in the latest published homepage, cached in transients for 1 hour and flushed on save

## Key files

| File | Purpose |
| --- | --- |
| `prc-homepages.php` | Plugin entry point; defines constants, registers activation/deactivation hooks, boots `Plugin` |
| `includes/class-plugin.php` | Core class — registers the post type, block, permalink filter, admin bar item, and block render callback |
| `includes/class-feed.php` | Generates and caches the `/feed/homepage` RSS feed; extracts story post IDs by recursively walking parsed blocks |
| `includes/utils.php` | Global helpers: `get_latest_homepage_id()`, `get_latest_homepage_title()`, `get_latest_homepage_permalink()` |
| `src/block.json` | Metadata for `prc-platform/latest-homepage` |
| `src/edit.jsx` | Block editor component; fetches the latest homepage via `useEntityRecords` and renders it with `InnerBlocksAsSyncedContent` |
| `src/controls.jsx` | Inspector panel wiring up the preview and create modals |
| `src/preview-selection-modal.jsx` | Modal to switch the editor preview to any existing homepage post |
| `src/create-new-homepage-modal.jsx` | Modal to create a new draft homepage (pre-seeded with the default grid layout) |
| `src/constants.js` | `POST_TYPE`, `POST_TYPE_LABEL`, and `DEFAULT_CONTENT` (the default block template serialized as block markup) |
| `tests/create-homepage-post.spec.ts` | Playwright e2e test for homepage creation |

## Filters / hooks

| Hook | Type | Direction | Description |
| --- | --- | --- | --- |
| `init` | Action | Registers | Registers the `homepage` post type |
| `init` | Action | Registers | Registers the `prc-platform/latest-homepage` block via `register_block_type` |
| `init` | Action | Registers | Registers the custom `/feed/homepage` feed endpoint via `add_feed` |
| `post_link` | Filter | Modifies | Rewrites the permalink of any published `homepage` post to `home_url()` |
| `admin_bar_menu` | Action | Modifies | Swaps the default "Edit Page" node for "Edit Homepage" when on the front page |
| `save_post_homepage` | Action | Listener | Flushes the `prc_homepage_feed_content` and `prc_homepage_story_ids` transients |

## Architecture notes

### How the front page is served

The block theme's front-page template contains a single `prc-platform/latest-homepage` block. On the frontend, its `render_callback` runs a `WP_Query` for the most recently published `homepage` post, pipes its `post_content` through `apply_filters('the_content', ...)`, and returns the result. No page is set as the WordPress "front page" in Settings — the template + block combination owns that responsibility entirely.

### Scheduling a new homepage

Create a `homepage` post in the block editor, build the layout, then schedule it for a future publish date. Once WordPress publishes it, the render callback will automatically return its content because it always queries by `orderby=date DESC limit=1 post_status=publish`.

### Default block template

When creating a homepage (either through the admin or via the "Create New Draft" modal in the block editor), the post is seeded with a 3-column `prc-block/grid-controller` layout containing five `prc-block/story-item` blocks. The serialized block markup lives in `src/constants.js` as `DEFAULT_CONTENT` and the PHP equivalent is defined in `Plugin::get_template()`.

### Feed caching

The `/feed/homepage` endpoint caches its output in two transients:

| Transient key | Contents | TTL |
| --- | --- | --- |
| `prc_homepage_feed_content` | Full rendered RSS XML string | 1 hour |
| `prc_homepage_story_ids` | Array of post IDs from `prc-block/story-item` blocks | 1 hour |

Both are deleted on `save_post_homepage`. The feed is generated only once per hour unless a homepage is saved.

## Dependencies

- `prc-platform-core` (required plugin)
- `@prc/components` — `InnerBlocksAsSyncedContent`, `EntityPatternModal`, `EntityCreateNewModal` (editor only)

## Build

```bash
npm run build -w @prc/homepages
```
