#!/usr/bin/env bash
# Build a WordPress.org / Plugin Check zip (plugin folder at archive root).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

VERSION="$(
  sed -n 's/^ \* Version:[[:space:]]*//p' oral-history-archive.php | head -n1 | tr -d '\r'
)"
if [[ -z "$VERSION" ]]; then
  echo "Could not read Version from oral-history-archive.php" >&2
  exit 1
fi

SLUG="oral-history-archive"
OUT_DIR="${OUT_DIR:-$ROOT/dist}"
STAGE="$OUT_DIR/.stage"
ZIP_NAME="${SLUG}-${VERSION}.zip"
ZIP_PATH="$OUT_DIR/$ZIP_NAME"

mkdir -p "$OUT_DIR"
rm -rf "$STAGE"
mkdir -p "$STAGE/$SLUG"

# .distignore uses rsync exclude syntax (# comments allowed).
rsync -a \
  --delete \
  --exclude-from="$ROOT/.distignore" \
  --exclude 'dist' \
  "$ROOT/" \
  "$STAGE/$SLUG/"

# Safety: never ship local demo / VCS leftovers if patterns drift.
rm -rf \
  "$STAGE/$SLUG/.git" \
  "$STAGE/$SLUG/.wp-dev" \
  "$STAGE/$SLUG/.wordpress-org" \
  "$STAGE/$SLUG/scripts" \
  "$STAGE/$SLUG/tests" \
  "$STAGE/$SLUG/dist"

rm -f "$ZIP_PATH"
(
  cd "$STAGE"
  zip -rq "$ZIP_PATH" "$SLUG"
)

rm -rf "$STAGE"

echo "Built $ZIP_PATH"
unzip -l "$ZIP_PATH" | head -n 40
echo "…"
echo "Files: $(unzip -l "$ZIP_PATH" | tail -n1)"
