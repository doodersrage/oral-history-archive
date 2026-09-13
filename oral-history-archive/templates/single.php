<?php
/**
 * Single interview record.
 *
 * @package OralHistoryArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id   = get_the_ID();
$meta      = OHA_Interview::get_meta( $post_id );
$code      = OHA_Interview::rights_code( $post_id );
$playable  = OHA_Interview::is_playable( $post_id );
$seconds   = OHA_Interview::duration_seconds( $post_id );
$audio_url = $playable ? wp_get_attachment_url( $meta['audio_id'] ) : '';
$cues      = is_array( $meta['cues'] ) ? $meta['cues'] : array();
$start_at  = isset( $_GET['t'] ) ? max( 0, (float) $_GET['t'] ) : 0;
$end_at    = isset( $_GET['end'] ) ? max( 0, (float) $_GET['end'] ) : 0;
$coll      = get_the_terms( $post_id, OHA_Interview::COLLECTION );
$topics    = get_the_terms( $post_id, OHA_Interview::TOPIC );
$abstract  = trim( wp_strip_all_tags( get_the_content( null, false, $post_id ) ) );

require OHA_DIR . 'templates/header.php';
?>

<article class="oha-record">
	<p class="oha-kicker">Interview record</p>
	<p class="oha-accession oha-mono"><?php echo esc_html( $meta['accession'] ?: 'Unnumbered' ); ?></p>
	<h1><?php the_title(); ?></h1>
	<p class="oha-stamp oha-stamp-<?php echo esc_attr( $code ); ?>"><?php echo esc_html( OHA_Interview::rights_label( $post_id ) ); ?></p>

	<dl class="oha-meta">
		<div>
			<dt>Narrator</dt>
			<dd><?php echo esc_html( $meta['narrator'] ?: '—' ); ?></dd>
		</div>
		<div>
			<dt>Interviewer</dt>
			<dd><?php echo esc_html( $meta['interviewer'] ?: '—' ); ?></dd>
		</div>
		<div>
			<dt>Date</dt>
			<dd><?php echo esc_html( $meta['interview_date'] ? OHA_Interview::format_date( $meta['interview_date'] ) : '—' ); ?></dd>
		</div>
		<div>
			<dt>Place</dt>
			<dd><?php echo esc_html( $meta['location'] ?: '—' ); ?></dd>
		</div>
		<div>
			<dt>Language</dt>
			<dd><?php echo esc_html( $meta['language'] ?: '—' ); ?></dd>
		</div>
		<div>
			<dt>Duration</dt>
			<dd><?php echo $seconds ? esc_html( OHA_Transcript::human_duration( $seconds ) ) : 'Not timed'; ?></dd>
		</div>
		<?php if ( $coll && ! is_wp_error( $coll ) ) : ?>
			<div>
				<dt>Collection</dt>
				<dd><?php echo esc_html( implode( ', ', wp_list_pluck( $coll, 'name' ) ) ); ?></dd>
			</div>
		<?php endif; ?>
		<?php if ( $topics && ! is_wp_error( $topics ) ) : ?>
			<div>
				<dt>Topics</dt>
				<dd><?php echo esc_html( implode( ', ', wp_list_pluck( $topics, 'name' ) ) ); ?></dd>
			</div>
		<?php endif; ?>
	</dl>

	<?php if ( $abstract ) : ?>
		<section class="oha-abstract">
			<h2>Abstract</h2>
			<?php echo wp_kses_post( apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) ) ); ?>
		</section>
	<?php endif; ?>

	<?php if ( ! $playable ) : ?>
		<section class="oha-sealed" aria-live="polite">
			<h2>The tape is not on the table</h2>
			<?php if ( $meta['restriction'] ) : ?>
				<p><?php echo esc_html( $meta['restriction'] ); ?></p>
			<?php elseif ( 'embargoed' === $code ) : ?>
				<p>This recording is under embargo until <?php echo esc_html( OHA_Interview::format_date( $meta['embargo_until'] ) ); ?>. The catalog record remains so researchers know the interview exists.</p>
			<?php else : ?>
				<p>The narrator did not release the recording for public listening. The finding aid still lists the interview.</p>
			<?php endif; ?>
			<?php if ( OHA_Plugin::setting( 'rights_contact' ) ) : ?>
				<p>Questions about access: <a href="mailto:<?php echo esc_attr( OHA_Plugin::setting( 'rights_contact' ) ); ?>"><?php echo esc_html( OHA_Plugin::setting( 'rights_contact' ) ); ?></a></p>
			<?php endif; ?>
		</section>
	<?php else : ?>
		<section class="oha-player" data-oha-player>
			<div class="oha-deck">
				<div class="oha-deck-status">
					<p class="oha-now-speaker" data-oha-speaker>Ready</p>
					<p class="oha-now-time oha-mono"><span data-oha-current>00:00.00</span> / <span data-oha-total><?php echo esc_html( OHA_Transcript::seconds_to_timecode( $seconds ) ); ?></span></p>
				</div>
				<audio
					id="oha-audio"
					data-oha-audio
					preload="auto"
					src="<?php echo esc_url( $audio_url ); ?>"
					data-start="<?php echo esc_attr( (string) $start_at ); ?>"
					data-end="<?php echo esc_attr( (string) $end_at ); ?>"
				></audio>
				<div class="oha-controls">
					<button type="button" class="oha-play" data-oha-play aria-label="Play">Play</button>
					<input type="range" data-oha-seek min="0" max="<?php echo esc_attr( (string) max( 1, $seconds ) ); ?>" value="<?php echo esc_attr( (string) $start_at ); ?>" step="0.01" aria-label="Seek in recording" />
					<label class="oha-rate">
						<span>Speed</span>
						<select data-oha-rate>
							<option value="0.75">0.75×</option>
							<option value="1" selected>1×</option>
							<option value="1.25">1.25×</option>
							<option value="1.5">1.5×</option>
						</select>
					</label>
				</div>
				<p class="oha-player-msg" data-oha-msg hidden></p>
			</div>

			<script type="application/json" id="oha-transcript-data"><?php echo wp_json_encode( $cues ); ?></script>

			<?php if ( $cues ) : ?>
				<div class="oha-transcript-wrap">
					<h2>Tape log</h2>
					<p class="oha-help-inline">Click a line to jump the recording. The current sentence follows the tape.</p>
					<ol class="oha-transcript" data-oha-log>
						<?php foreach ( $cues as $index => $cue ) : ?>
							<li>
								<button type="button" class="oha-cue" data-start="<?php echo esc_attr( (string) $cue['start'] ); ?>" data-index="<?php echo esc_attr( (string) $index ); ?>">
									<span class="oha-mono oha-time"><?php echo esc_html( OHA_Transcript::seconds_to_timecode( $cue['start'] ) ); ?></span>
									<span class="oha-speaker"><?php echo esc_html( $cue['speaker'] ); ?></span>
									<span class="oha-line"><?php echo esc_html( $cue['text'] ); ?></span>
								</button>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>
			<?php else : ?>
				<div class="oha-empty oha-empty-compact">
					<h2>No tape log yet</h2>
					<p>The recording is open, but nobody has timed the transcript. You can still play the audio.</p>
				</div>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<section class="oha-cite">
		<h2>Cite this interview</h2>
		<blockquote class="oha-citation"><p><?php echo esc_html( OHA_Interview::citation( $post_id ) ); ?></p></blockquote>
		<p class="oha-help-inline">If you quote a passage, add the timecode from the tape log (for example 00:01:12–00:01:48).</p>
	</section>
</article>

<?php
require OHA_DIR . 'templates/footer.php';
