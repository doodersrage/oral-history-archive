#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
WP_DIR="${WP_DIR:-$ROOT/.wp-dev}"
PORT="${PORT:-47261}"
URL="http://127.0.0.1:${PORT}"
WP_CLI="${WP_CLI:-}"

if [[ -z "$WP_CLI" ]]; then
  if [[ -x /tmp/wp-dl/wp-cli.phar ]]; then
    WP_CLI="php /tmp/wp-dl/wp-cli.phar"
  elif command -v wp >/dev/null 2>&1; then
    WP_CLI="wp"
  else
    mkdir -p /tmp/wp-dl
    curl -fsSL -o /tmp/wp-dl/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    WP_CLI="php /tmp/wp-dl/wp-cli.phar"
  fi
fi

download_wordpress() {
  mkdir -p /tmp/wp-dl
  if [[ ! -f /tmp/wp-dl/wordpress.tar.gz ]]; then
    curl -fsSL -o /tmp/wp-dl/wordpress.tar.gz https://wordpress.org/latest.tar.gz
  fi
  if [[ ! -d /tmp/wp-src/wordpress ]]; then
    mkdir -p /tmp/wp-src
    tar -xzf /tmp/wp-dl/wordpress.tar.gz -C /tmp/wp-src
  fi
  if [[ ! -d /tmp/wp-src/sqlite-database-integration ]]; then
    curl -fsSL -o /tmp/wp-dl/sqlite-plugin.zip https://downloads.wordpress.org/plugin/sqlite-database-integration.latest-stable.zip
    unzip -q -o /tmp/wp-dl/sqlite-plugin.zip -d /tmp/wp-src
  fi
}

if [[ ! -f "$WP_DIR/wp-load.php" ]]; then
  echo "Extracting WordPress…"
  download_wordpress
  mkdir -p "$WP_DIR"
  cp -a /tmp/wp-src/wordpress/. "$WP_DIR/"
fi

mkdir -p "$WP_DIR/wp-content/plugins" "$WP_DIR/wp-content/database"
if [[ ! -d "$WP_DIR/wp-content/plugins/sqlite-database-integration" ]]; then
  download_wordpress
  cp -a /tmp/wp-src/sqlite-database-integration "$WP_DIR/wp-content/plugins/"
fi

if [[ ! -f "$WP_DIR/wp-content/db.php" ]]; then
  cp "$WP_DIR/wp-content/plugins/sqlite-database-integration/db.copy" "$WP_DIR/wp-content/db.php"
fi

ln -sfn "$ROOT" "$WP_DIR/wp-content/plugins/oral-history-archive"
cp "$ROOT/scripts/router.php" "$WP_DIR/router.php"

if [[ ! -f "$WP_DIR/wp-config.php" ]]; then
  $WP_CLI config create \
    --path="$WP_DIR" \
    --dbname=wordpress \
    --dbuser=root \
    --dbpass="" \
    --dbhost=localhost \
    --skip-check \
    --force
  $WP_CLI config set DB_ENGINE sqlite --path="$WP_DIR" --type=constant
  $WP_CLI config set WP_HOME "$URL" --path="$WP_DIR" --type=constant
  $WP_CLI config set WP_SITEURL "$URL" --path="$WP_DIR" --type=constant
  $WP_CLI config set WP_DEBUG false --path="$WP_DIR" --type=constant --raw
fi

if ! $WP_CLI core is-installed --path="$WP_DIR" >/dev/null 2>&1; then
  $WP_CLI core install \
    --path="$WP_DIR" \
    --url="$URL" \
    --title="East Bay Labor and Family Business Archive" \
    --admin_user=archivist \
    --admin_password=reading-room \
    --admin_email=archivist@example.com \
    --skip-email
fi

$WP_CLI plugin activate sqlite-database-integration --path="$WP_DIR" || true
$WP_CLI plugin activate oral-history-archive --path="$WP_DIR"
$WP_CLI rewrite structure '/%postname%/' --path="$WP_DIR" --hard
$WP_CLI option update permalink_structure '/%postname%/' --path="$WP_DIR"
$WP_CLI eval-file "$ROOT/scripts/seed.php" --path="$WP_DIR"

echo
echo "Reading room is installed."
echo "  Public catalog: $URL/"
echo "  wp-admin:       $URL/wp-admin/  (archivist / reading-room)"
echo "Start with: PORT=$PORT $ROOT/scripts/start-server.sh"
