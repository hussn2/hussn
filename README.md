# Hussn — Self-hosted WordPress + custom theme

This repository runs a **self-hosted WordPress** install via Docker and ships a
custom theme (`Hussn`) as version-controlled code under
`wp-content/themes/hussn`.

## What's inside

| Path | Purpose |
| --- | --- |
| `docker-compose.yml` | WordPress + MySQL 8 + phpMyAdmin services |
| `.env.example` | Copy to `.env` to configure ports / DB credentials |
| `wp-content/themes/hussn/` | The custom theme (the part tracked in git) |

WordPress core and uploads live in Docker volumes, **not** in the repo — only the
theme is tracked, so the repo stays clean and portable.

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) and Docker Compose

## Quick start

```bash
# 1. Configure environment
cp .env.example .env

# 2. Start WordPress, MySQL, and phpMyAdmin
docker compose up -d

# 3. Open the installer and finish the 2-minute setup wizard
open http://localhost:8080
```

Then in the WordPress admin:

1. Go to **Appearance → Themes** and **activate "Hussn"**.
2. (Optional) **Settings → Reading →** set a static page as your homepage to get
   the full hero landing layout from `front-page.php`.
3. **Appearance → Customize → Front Page Hero** to edit the hero headline,
   subtitle, and call-to-action button.
4. **Appearance → Menus** — create a menu and assign it to the *Primary* location.

| Service | URL | Notes |
| --- | --- | --- |
| WordPress | http://localhost:8080 | Change the port with `WORDPRESS_PORT` |
| phpMyAdmin | http://localhost:8081 | DB browser for development |

Stop everything with `docker compose down` (add `-v` to also wipe the database
and start fresh).

## Theme features

- **Front-page hero** with feature cards and a latest-posts grid (`front-page.php`)
- **Customizer controls** for the hero text/button and an accent color, with live preview
- **Custom logo**, **two nav menus** (primary + footer), and a **right sidebar**
- **Four widget areas** (sidebar + three footer columns)
- **Full set of templates**: `index`, `single`, `page`, `archive`, `search`,
  `404`, plus a **Full Width** page template
- **Block-editor support** via `theme.json` (color palette, font sizes, wide/full
  alignment, editor styles)
- **Responsive** layout with an accessible mobile menu toggle
- Translation-ready (`hussn` text domain)

## Theme structure

```
wp-content/themes/hussn/
├── style.css                 # Theme header + baseline styles
├── functions.php             # Setup, enqueues, menus, widgets
├── theme.json                # Block editor settings
├── header.php / footer.php
├── front-page.php            # Hero landing template
├── index.php / single.php / page.php
├── archive.php / search.php / 404.php
├── sidebar.php / comments.php
├── inc/
│   ├── template-tags.php     # hussn_post_meta(), hussn_post_taxonomies()
│   └── customizer.php        # Hero + accent color settings
├── template-parts/
│   ├── content.php / content-card.php / content-none.php
├── page-templates/
│   └── full-width.php        # "Full Width" page template
└── assets/
    ├── css/main.css
    └── js/main.js, customizer.js
```

## Editing the theme

The theme directory is mounted directly into the container, so edits to files
under `wp-content/themes/hussn/` show up immediately on refresh — no rebuild
needed. Commit your changes as usual.
