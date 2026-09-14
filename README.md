# Oral History Archive

A WordPress plugin for **interviews**, not episodes. Each record is a finding-aid entry: narrator, interviewer, accession number, timed tape log, and a consent status that can withhold the recording while still listing that the conversation exists.

Podcast plugins assume the audio is the product. This one assumes the opposite: the catalog is public, the tape is a privilege the narrator can refuse.

## What you get

- Interview post type with collections and topics
- Rights: open for listening, restricted (catalog only), or embargoed until a date
- Tape log parser (`00:00:12 Narrator: …`) stored as timed cues
- Reading-room frontend: finding aid table, restriction stamps, synced transcript player
- Citation block in oral-history form
- Clip shortcode for quoting a time range: `[oha_clip accession="OH-2019-014" start="12" end="48"]`

Restricted interviews never print the audio URL in public HTML.

## Install on an existing WordPress site

```bash
cd wp-content/plugins
git clone https://github.com/doodersrage/oral-history-archive.git
```

That creates `wp-content/plugins/oral-history-archive/` with the main plugin file at the top level. Then activate **Oral History Archive**.

Or upload a release zip of the plugin root (see [SUBMISSION.md](SUBMISSION.md)).

Activation creates a **Reading room** page. It does **not** change your site front page. Optionally set that page under **Settings → Reading**, or visit `/interviews/`.

`scripts/` and `tests/` are local-demo tooling. Leave them for development; omit them when building the WordPress.org zip (see `.distignore`). Plugin Check on a full git checkout will flag those files — that is expected.

WordPress.org directory assets live in [`.wordpress-org/`](.wordpress-org/).

### Tape log format

```
00:00:00 Interviewer: Today is March 18, 2019.
00:00:12 Maria Chen: I still open the gate at three thirty.
01:02:03 Interviewer: What changed?
```

Hours are optional. Lines that do not match are ignored. `#` starts a comment.

## Local demo (no MySQL)

Requires PHP 8+, the SQLite PDO extension, `espeak-ng`, and `ffmpeg` (only for synthesizing the sample tapes).

```bash
./scripts/bootstrap-wp.sh
./scripts/start-server.sh
```

- Catalog: http://127.0.0.1:47261/
- wp-admin: http://127.0.0.1:47261/wp-admin/
- User: `archivist` / `reading-room`

The demo seeds four records from a fictional East Bay labor archive: two open for listening, one sealed during the narrator’s lifetime, one embargoed until 2028. The voices are synthesized stand-ins so the player and tape log can be tested without depositing real interviews.

```bash
php tests/test-transcript.php
```

## Rights model

| Status | Catalog | Audio + transcript |
| --- | --- | --- |
| Open for listening | Listed | Playable |
| Restricted | Listed, with the public restriction statement | Hidden |
| Embargoed | Listed, with the lift date | Hidden until that date |

That is the difference from a podcast: a sealed interview is still part of the archive.

## License

GPL-2.0-or-later
