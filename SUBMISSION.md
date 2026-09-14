# WordPress.org submission checklist

Plugin slug (proposed): `oral-history-archive`  
Author / Contributors: `doodersrage`  
Stable tag: `1.0.3`

The distributable plugin is the **repository root** (`oral-history-archive.php`, `includes/`, `assets/`, etc.). Dev-only paths (`scripts/`, `tests/`, `.wordpress-org/`, …) stay out of the upload zip.

## Before you submit

1. **Enable 2FA** on the WordPress.org account that will own the plugin (required for new submissions).
2. Confirm the WordPress.org username matches **Contributors** in [`readme.txt`](readme.txt).
3. Build a marketplace zip (excludes `scripts/`, `tests/`, `.wordpress-org/`, etc.):

   ```bash
   ./scripts/build-release.sh
   ```

   Output: `dist/oral-history-archive-1.0.3.zip` (version comes from the plugin header).
   Install that zip in a clean site and run [Plugin Check](https://wordpress.org/plugins/plugin-check/) on it — not on a full git checkout.
4. Fix any **error**-level Plugin Repo findings before submitting.
5. Smoke-test: activate without changing the front page, create one interview with audio + tape log, confirm finding aid and player, confirm a restricted interview hides audio.

## Submit for review

1. Go to [Add your plugin](https://wordpress.org/plugins/developers/add/).
2. Upload `dist/oral-history-archive-1.0.3.zip` from `./scripts/build-release.sh`.
3. Wait for the review queue email. Do not commit to SVN until the plugin is approved.

## After approval (SVN)

WordPress.org hosts plugins in Subversion. Typical layout:

```
/oral-history-archive/
  assets/          ← banners, icons (from this repo’s .wordpress-org/)
  trunk/           ← current development copy of the plugin
  tags/1.0.3/      ← release matching Stable tag
```

1. Check out the empty SVN repo from the approval email.
2. Copy plugin files (not `scripts/`, `tests/`, `.wordpress-org/`) into `trunk/`.
3. Copy `.wordpress-org/*` into SVN `assets/`:
   - `banner-772x250.png`
   - `banner-1544x500.png`
   - `icon-128x128.png`
   - `icon-256x256.png`
4. Copy `trunk` to `tags/1.0.3`.
5. Ensure `readme.txt` **Stable tag** is `1.0.3` and the plugin header **Version** is `1.0.3`.
6. Commit. Directory pages can take a few hours to refresh.

## Repo map

| Path | Purpose |
| --- | --- |
| `oral-history-archive.php` + `includes/`, `assets/`, … | Distributable plugin (repo root) |
| `.wordpress-org/` | Directory artwork for SVN `assets/` |
| `.distignore` | Paths to omit from release zips |
| `scripts/`, `tests/` | Local demo / tests — not for wordpress.org |

## License

GPL-2.0-or-later — see [`LICENSE`](LICENSE).
