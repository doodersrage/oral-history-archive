<?php
/**
 * Shortcodes: timed clips from open interviews.
 *
 * @package OralHistoryArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OHA_Shortcodes {

	public static function init() {
		add_shortcode( 'oha_clip', array( __CLASS__, 'clip' ) );
	}

	/**
	 * Embed a timed excerpt from an interview that is open for listening.
	 *
	 * [oha_clip accession="OH-2019-014" start="12" end="48"]
	 */
	public static function clip( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'        => 0,
				'accession' => '',
				'start'     => 0,
				'end'       => 0,
			),
			$atts,
			'oha_clip'
		);

		$post_id = (int) $atts['id'];
		if ( ! $post_id && $atts['accession'] ) {
			$found = get_posts(
				array(
					'post_type'      => OHA_Interview::POST_TYPE,
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Lookup by accession number is intentional.
					'meta_key'       => '_oha_accession',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Lookup by accession number is intentional.
					'meta_value'     => sanitize_text_field( $atts['accession'] ),
					'fields'         => 'ids',
				)
			);
			$post_id = $found ? (int) $found[0] : 0;
		}

		if ( ! $post_id || ! OHA_Interview::is_playable( $post_id ) ) {
			return '<p class="oha-clip-unavailable">' . esc_html__( 'This clip is not available. The interview may be restricted or missing a recording.', 'oral-history-archive' ) . '</p>';
		}

		$start = (float) $atts['start'];
		$end   = (float) $atts['end'];
		$meta  = OHA_Interview::get_meta( $post_id );
		$cues  = OHA_Transcript::slice( $meta['cues'], $start, $end > $start ? $end : $start + 30 );
		$url   = add_query_arg(
			array(
				't'   => $start,
				'end' => $end > $start ? $end : false,
			),
			get_permalink( $post_id )
		);

		ob_start();
		?>
		<aside class="oha-clip">
			<p class="oha-clip-kicker">
				<?php
				/* translators: %s: accession number or interview title */
				echo esc_html( sprintf( __( 'Clip from %s', 'oral-history-archive' ), $meta['accession'] ?: get_the_title( $post_id ) ) );
				?>
			</p>
			<p class="oha-clip-cite"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a>
				· <?php echo esc_html( OHA_Transcript::seconds_to_timecode( $start ) ); ?>
				<?php if ( $end > $start ) : ?>
					– <?php echo esc_html( OHA_Transcript::seconds_to_timecode( $end ) ); ?>
				<?php endif; ?>
			</p>
			<?php if ( $cues ) : ?>
				<blockquote>
					<?php foreach ( $cues as $cue ) : ?>
						<p><span class="speaker"><?php echo esc_html( $cue['speaker'] ); ?></span> <?php echo esc_html( $cue['text'] ); ?></p>
					<?php endforeach; ?>
				</blockquote>
			<?php endif; ?>
			<p><a class="oha-text-link" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Listen in the reading room', 'oral-history-archive' ); ?></a></p>
		</aside>
		<?php
		return ob_get_clean();
	}
}
