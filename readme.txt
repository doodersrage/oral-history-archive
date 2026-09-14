=== Oral History Archive ===
Contributors: doodersrage
Tags: oral-history, archive, transcript, interviews, finding-aid
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Interviews as archival records: timed transcripts, narrator credit, and consent that can withhold the tape.

== Description ==

Oral History Archive treats each interview as a finding-aid entry, not a podcast episode. The catalog can stay public while the recording stays sealed.

= What you get =

* Interview post type with collections and topics
* Rights: open for listening, restricted (catalog only), or embargoed until a date
* Tape log parser (`00:00:12 Narrator: …`) stored as timed cues
* Reading-room frontend: finding aid table, restriction stamps, synced transcript player
* Citation block in oral-history form
* Clip shortcode for quoting a time range: `[oha_clip accession="OH-2019-014" start="12" end="48"]`

Restricted interviews never print the audio URL in public HTML.

= Rights model =

* **Open for listening** — listed and playable
* **Restricted** — listed with a public restriction statement; audio and transcript hidden
* **Embargoed** — listed with the lift date; audio hidden until that date

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/oral-history-archive/`, or `git clone https://github.com/doodersrage/oral-history-archive.git` into `wp-content/plugins/`, or install the zip via **Plugins → Add New → Upload Plugin**. The main file `oral-history-archive.php` must sit directly inside that folder.
2. Activate **Oral History Archive**.
3. On activation the plugin creates a **Reading room** page. It does **not** change your site front page. Optionally set that page as your front page under **Settings → Reading**, or visit the Interviews archive at `/interviews/`.
4. Add an interview: fill the record, attach audio, paste a tape log, set consent.
5. Configure institution name and rights contact under **Oral History → Settings**.

= Tape log format =

    00:00:00 Interviewer: Today is March 18, 2019.
    00:00:12 Maria Chen: I still open the gate at three thirty.
    01:02:03 Interviewer: What changed?

Hours are optional. Lines that do not match are ignored. `#` starts a comment.

== Frequently Asked Questions ==

= Does activation change my homepage? =

No. Activation creates a Reading room page and stores its ID. You choose whether to use it as the front page.

= Can restricted interviews appear in the catalog? =

Yes. By default they are listed without the recording. Turn that off under **Oral History → Settings** if you prefer.

= How do I embed a clip on another page? =

Use `[oha_clip accession="OH-2019-014" start="12" end="48"]`. The interview must be open for listening and have audio attached.

== Changelog ==

= 1.0.3 =
* WordPress.org packaging: readme, license, i18n wrappers, Domain Path
* Activation no longer rewrites the site front page
* Validate that attached media is an audio file
* Remove incomplete finding-aid shortcode stub
* Align plugin header version with runtime version

== Upgrade Notice ==

= 1.0.3 =
Marketplace-ready packaging and safer activation. Existing front-page settings are left unchanged.
