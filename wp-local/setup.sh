#!/usr/bin/env bash
#
# setup.sh — Stand up a local WordPress with the Flatsome parent theme and the
# committed `flatsome-child` child theme active, for development/preview.
#
# Why this exists: this sandbox has PHP + SQLite but no MySQL and no running
# Docker daemon, and wordpress.org is network-blocked. So we pull WordPress
# core, the SQLite drop-in, and WooCommerce from GitHub mirrors and run the
# site on PHP's built-in web server backed by SQLite — no Docker, no MySQL.
#
# The licensed Flatsome PARENT theme is intentionally NOT committed to this
# repo, so you must supply the Flatsome zip you purchased.
#
# Usage:
#   ./wp-local/setup.sh /path/to/flatsome-theme.zip [port]
#
# Then open http://localhost:<port>  (admin: admin / flatsome-dev-2026)
#
set -euo pipefail

FLATSOME_ZIP="${1:?Usage: setup.sh /path/to/flatsome.zip [port]}"
PORT="${2:-8080}"
WP_VERSION="6.9.1"

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP="${WP_LOCAL_DIR:-$HOME/wp-local}"
DL="$HOME/wp-dl"
URL="http://localhost:${PORT}"
UA="Mozilla/5.0 (X11; Linux x86_64)"

echo "==> Workspace: $WP   (downloads cached in $DL)"
mkdir -p "$DL"

# --- 1. Tooling + sources (GitHub; wordpress.org is blocked here) -----------
[ -f "$DL/wp-cli.phar" ] || curl -sSL -o "$DL/wp-cli.phar" \
  https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x "$DL/wp-cli.phar"
wp() { php "$DL/wp-cli.phar" "$@" --path="$WP" --allow-root; }

[ -f "$DL/wp.zip" ]     || curl -sSL -A "$UA" -o "$DL/wp.zip" \
  "https://codeload.github.com/WordPress/WordPress/zip/refs/tags/${WP_VERSION}"
[ -f "$DL/sqlite.zip" ] || curl -sSL -A "$UA" -o "$DL/sqlite.zip" \
  "https://codeload.github.com/WordPress/sqlite-database-integration/zip/refs/heads/main"
[ -f "$DL/woocommerce.zip" ] || curl -sSL -A "$UA" -o "$DL/woocommerce.zip" \
  "https://github.com/woocommerce/woocommerce/releases/latest/download/woocommerce.zip"

# --- 2. Lay down WordPress core --------------------------------------------
echo "==> Installing WordPress core ${WP_VERSION}"
rm -rf "$WP" "$DL/wpx"; mkdir -p "$WP" "$DL/wpx"
unzip -q "$DL/wp.zip" -d "$DL/wpx"
cp -a "$DL/wpx/WordPress-${WP_VERSION}/." "$WP/"
mkdir -p "$WP/wp-content/plugins" "$WP/wp-content/themes" "$WP/wp-content/database"

# --- 3. Plugins: SQLite drop-in + WooCommerce ------------------------------
rm -rf "$DL/sqlitex"; mkdir -p "$DL/sqlitex"
unzip -q "$DL/sqlite.zip" -d "$DL/sqlitex"
cp -a "$DL/sqlitex/sqlite-database-integration-main" \
      "$WP/wp-content/plugins/sqlite-database-integration"
unzip -q -o "$DL/woocommerce.zip" -d "$WP/wp-content/plugins/"

# --- 4. Themes: Flatsome parent (from your zip) + committed child ----------
echo "==> Installing Flatsome parent from: $FLATSOME_ZIP"
rm -rf "$DL/fsx"; mkdir -p "$DL/fsx"
unzip -q "$FLATSOME_ZIP" -d "$DL/fsx"
# The zip may contain flatsome/ directly or wrapped one level down.
PARENT_SRC="$(dirname "$(find "$DL/fsx" -maxdepth 3 -name style.css -path '*flatsome*' | head -1)")"
cp -a "$PARENT_SRC" "$WP/wp-content/themes/flatsome"
cp -a "$REPO_DIR/flatsome-child" "$WP/wp-content/themes/flatsome-child"

# --- 5. Config + SQLite drop-in --------------------------------------------
php "$DL/wp-cli.phar" config create --path="$WP" --allow-root --force --skip-check \
  --dbname=wordpress --dbuser=root --dbpass='' --dbhost=localhost --dbprefix=wp_ \
  --extra-php <<PHP
define( 'WP_HOME', '${URL}' );
define( 'WP_SITEURL', '${URL}' );
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );
define( 'FS_METHOD', 'direct' );
PHP

PD="$WP/wp-content/plugins/sqlite-database-integration"
sed -e "s|{SQLITE_IMPLEMENTATION_FOLDER_PATH}|$PD|g" \
    -e "s|{SQLITE_PLUGIN}|sqlite-database-integration/load.php|g" \
    "$PD/db.copy" > "$WP/wp-content/db.php"

# --- 6. Install + activate --------------------------------------------------
echo "==> Installing WordPress"
wp core install --url="$URL" --title="Flatsome Dev" \
  --admin_user="admin" --admin_password="flatsome-dev-2026" \
  --admin_email="hussn@me.com" --skip-email
wp plugin activate sqlite-database-integration >/dev/null 2>&1 || true
wp plugin activate woocommerce
wp theme activate flatsome-child

# --- 7. Router + server -----------------------------------------------------
cat > "$WP/router.php" <<'PHP'
<?php
$uri  = urldecode( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
$path = __DIR__ . $uri;
if ( $uri !== '/' && is_file( $path ) ) { return false; }
require_once __DIR__ . '/index.php';
PHP

echo "==> Starting server at ${URL}"
pkill -f "0.0.0.0:${PORT}" 2>/dev/null || true
nohup php -d display_errors=0 -d error_reporting=0 \
  -S "0.0.0.0:${PORT}" -t "$WP" "$WP/router.php" >/tmp/wp-server.log 2>&1 &

sleep 2
echo "==> Done. ${URL}  (admin / flatsome-dev-2026)  →  $(curl -s -o /dev/null -w '%{http_code}' "$URL")"
