# Local WordPress + Flatsome (development sandbox)

This sets up a **local, disposable WordPress** with the **Flatsome** parent theme
installed and the [`flatsome-child`](../flatsome-child) child theme active, so you
can modify and preview the theme safely.

## Why it's built this way

This environment has **PHP 8.4 + SQLite** but **no MySQL** and **no running Docker
daemon**, and `wordpress.org` is network-blocked. So `wp-local/setup.sh`:

- pulls **WordPress core 6.9.1**, the **SQLite Database Integration** drop-in, and
  **WooCommerce** from **GitHub** mirrors (which are reachable),
- runs the site on **PHP's built-in web server** backed by **SQLite** — no Docker,
  no MySQL,
- installs the **Flatsome parent theme from a zip you supply** (the licensed
  commercial theme is intentionally **not committed** to this repo), and
- copies in and activates the committed `flatsome-child` child theme.

The running site lives at `~/wp-local` (outside the repo) and is **ephemeral** —
it's wiped when the container is reclaimed. Re-run the script to rebuild it.

## Usage

```bash
./wp-local/setup.sh /path/to/your-flatsome.zip [port]
```

Then open **http://localhost:8080** (or the port you passed).

- Admin: `http://localhost:8080/wp-admin` — user **admin**, password **flatsome-dev-2026**

## What's the actual deliverable?

The **child theme** in [`../flatsome-child`](../flatsome-child) is the version-controlled
artifact. Put all your customizations there:

- **CSS** → `flatsome-child/style.css`
- **PHP** (hooks, filters, snippets) → `flatsome-child/functions.php`
- **Template overrides** → copy a file from the parent (e.g.
  `flatsome/woocommerce/...`) into the same path under `flatsome-child/` and edit.

It ships with a small green **"Flatsome Child active"** badge (footer) so you can
confirm the child theme is live; delete that sample block in `functions.php` and
its CSS rule in `style.css` once you've verified it.

## Installing the child theme on your real site

The child theme is host-agnostic. To use it on your production WordPress:

1. Make sure the **Flatsome parent theme** is installed there.
2. Zip the `flatsome-child` folder and upload it via
   **Appearance → Themes → Add New → Upload Theme**, or copy it into
   `wp-content/themes/flatsome-child`.
3. Activate **Flatsome Child**.
