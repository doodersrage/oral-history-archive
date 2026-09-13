#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
WP_DIR="${WP_DIR:-$ROOT/.wp-dev}"
PORT="${PORT:-47261}"

if [[ ! -f "$WP_DIR/wp-load.php" ]]; then
  "$ROOT/scripts/bootstrap-wp.sh"
fi

cd "$WP_DIR"
echo "Oral History Archive reading room on http://127.0.0.1:${PORT}/"
exec php -S "0.0.0.0:${PORT}" router.php
