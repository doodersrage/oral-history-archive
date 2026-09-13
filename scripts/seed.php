<?php
/**
 * Demo seed: collections, interviews, synthesized tape audio.
 *
 * Run: wp eval-file scripts/seed.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( get_option( 'oha_demo_seeded' ) && ! getenv( 'OHA_RESEED' ) ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::success( 'Demo archive already seeded. Set OHA_RESEED=1 to rebuild.' );
	}
	return;
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

update_option(
	OHA_Plugin::OPTION,
	array(
		'institution'     => 'East Bay Labor and Family Business Archive',
		'rights_contact'  => 'rights@example.com',
		'show_restricted' => '1',
		'intro'           => 'This archive collects recorded interviews with people whose work is usually left out of the official record. You can listen where the narrator allowed it. Where they did not, the catalog still shows that the interview exists.',
	)
);

OHA_Plugin::ensure_reading_room_page();

$collections = array(
	'family-businesses' => array(
		'name'        => 'Family businesses of the East Bay',
		'description' => 'Shopkeepers, wholesalers, and the people who kept a stall open after the founder died.',
	),
	'oakland-waterfront' => array(
		'name'        => 'Oakland waterfront',
		'description' => 'Night engineers, ferry crews, and longshore families.',
	),
	'restricted-holdings' => array(
		'name'        => 'Restricted holdings',
		'description' => 'Interviews whose recordings are sealed. The catalog record remains.',
	),
);

$collection_ids = array();
foreach ( $collections as $slug => $term ) {
	$existing = get_term_by( 'slug', $slug, OHA_Interview::COLLECTION );
	if ( $existing ) {
		$collection_ids[ $slug ] = (int) $existing->term_id;
		continue;
	}
	$created = wp_insert_term(
		$term['name'],
		OHA_Interview::COLLECTION,
		array(
			'slug'        => $slug,
			'description' => $term['description'],
		)
	);
	if ( ! is_wp_error( $created ) ) {
		$collection_ids[ $slug ] = (int) $created['term_id'];
	}
}

$topics = array(
	'produce-markets' => 'Produce markets',
	'night-work'      => 'Night work',
	'immigration'     => 'Immigration',
	'unions'          => 'Unions',
	'textile-work'    => 'Textile work',
	'housing'         => 'Housing',
);

foreach ( $topics as $slug => $name ) {
	if ( ! get_term_by( 'slug', $slug, OHA_Interview::TOPIC ) ) {
		wp_insert_term( $name, OHA_Interview::TOPIC, array( 'slug' => $slug ) );
	}
}

/**
 * Build a timed mp3 from speaker cues using espeak-ng.
 *
 * @param array  $cues    Array of speaker/text.
 * @param string $outfile Destination mp3.
 * @return array{cues: array, duration: float}
 */
function oha_synth_tape( $cues, $outfile ) {
	$work = sys_get_temp_dir() . '/oha-tape-' . uniqid();
	wp_mkdir_p( $work );
	$list     = $work . '/list.txt';
	$lines    = array();
	$timed    = array();
	$cursor   = 0.0;
	$voices   = array(
		'Interviewer' => 'en-us',
		'Luis Ortega' => 'en-us',
		'Aisha Grant' => 'en-us+f3',
	);

	foreach ( $cues as $i => $cue ) {
		$speaker = $cue['speaker'];
		$text    = $cue['text'];
		$voice   = $voices[ $speaker ] ?? ( str_contains( strtolower( $speaker ), 'interviewer' ) ? 'en-us' : 'en-us+f2' );
		$wav     = sprintf( '%s/%02d.wav', $work, $i );
		$cmd     = sprintf(
			'espeak-ng -v %s -s 132 -w %s %s 2>/dev/null',
			escapeshellarg( $voice ),
			escapeshellarg( $wav ),
			escapeshellarg( $text )
		);
		passthru( $cmd, $code );
		if ( $code !== 0 || ! file_exists( $wav ) ) {
			throw new RuntimeException( 'espeak-ng failed for cue ' . $i );
		}

		$norm = sprintf( '%s/%02d-n.wav', $work, $i );
		passthru(
			sprintf(
				'ffmpeg -y -i %s -ar 22050 -ac 1 %s 2>/dev/null',
				escapeshellarg( $wav ),
				escapeshellarg( $norm )
			),
			$code
		);
		if ( $code !== 0 ) {
			throw new RuntimeException( 'ffmpeg normalize failed' );
		}

		$probe = trim( (string) shell_exec( 'ffprobe -v error -show_entries format=duration -of csv=p=0 ' . escapeshellarg( $norm ) ) );
		$dur   = (float) $probe;
		$timed[] = array(
			'start'   => round( $cursor, 2 ),
			'speaker' => $speaker,
			'text'    => $text,
		);
		$lines[]  = "file '" . str_replace( "'", "'\\''", $norm ) . "'";
		$cursor  += $dur + 0.35;

		$silence = sprintf( '%s/s%02d.wav', $work, $i );
		passthru(
			sprintf(
				'ffmpeg -y -f lavfi -i anullsrc=r=22050:cl=mono -t 0.35 %s 2>/dev/null',
				escapeshellarg( $silence )
			)
		);
		$lines[] = "file '" . str_replace( "'", "'\\''", $silence ) . "'";
	}

	file_put_contents( $list, implode( "\n", $lines ) . "\n" );
	passthru(
		sprintf(
			'ffmpeg -y -f concat -safe 0 -i %s -c:a aac -b:a 96k -ar 22050 -ac 1 -movflags +faststart %s 2>/dev/null',
			escapeshellarg( $list ),
			escapeshellarg( $outfile )
		),
		$code
	);
	if ( $code !== 0 || ! file_exists( $outfile ) ) {
		throw new RuntimeException( 'ffmpeg concat failed' );
	}

	return array(
		'cues'     => $timed,
		'duration' => round( $cursor, 2 ),
	);
}

function oha_attach_audio( $path, $parent_id ) {
	$bits = wp_upload_bits( basename( $path ), null, file_get_contents( $path ) );
	if ( ! empty( $bits['error'] ) ) {
		throw new RuntimeException( $bits['error'] );
	}
	$filetype = wp_check_filetype( $bits['file'] );
	$id       = wp_insert_attachment(
		array(
			'post_mime_type' => $filetype['type'] ?: 'audio/mpeg',
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $path ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$bits['file'],
		$parent_id
	);
	if ( is_wp_error( $id ) ) {
		throw new RuntimeException( $id->get_error_message() );
	}
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $bits['file'] ) );
	return (int) $id;
}

function oha_upsert_interview( $data ) {
	$found = get_posts(
		array(
			'post_type'      => OHA_Interview::POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_key'       => '_oha_accession',
			'meta_value'     => $data['accession'],
			'fields'         => 'ids',
		)
	);
	$postarr = array(
		'post_type'    => OHA_Interview::POST_TYPE,
		'post_status'  => 'publish',
		'post_title'   => $data['title'],
		'post_name'    => $data['slug'],
		'post_content' => $data['abstract'],
		'post_date'    => $data['interview_date'] . ' 10:00:00',
	);
	if ( $found ) {
		$postarr['ID'] = (int) $found[0];
		$post_id       = wp_update_post( $postarr, true );
	} else {
		$post_id = wp_insert_post( $postarr, true );
	}
	if ( is_wp_error( $post_id ) ) {
		throw new RuntimeException( $post_id->get_error_message() );
	}

	wp_set_object_terms( $post_id, $data['collection'], OHA_Interview::COLLECTION );
	wp_set_object_terms( $post_id, $data['topics'], OHA_Interview::TOPIC );

	$tmp = sys_get_temp_dir() . '/' . $data['slug'] . '.m4a';
	$tape = oha_synth_tape( $data['spoken'], $tmp );

	$audio_id = oha_attach_audio( $tmp, $post_id );
	@unlink( $tmp );

	$tape_log = OHA_Transcript::to_tape_log( $tape['cues'] );

	update_post_meta( $post_id, '_oha_accession', $data['accession'] );
	update_post_meta( $post_id, '_oha_narrator', $data['narrator'] );
	update_post_meta( $post_id, '_oha_interviewer', $data['interviewer'] );
	update_post_meta( $post_id, '_oha_interview_date', $data['interview_date'] );
	update_post_meta( $post_id, '_oha_location', $data['location'] );
	update_post_meta( $post_id, '_oha_language', 'English' );
	update_post_meta( $post_id, '_oha_audio_id', $audio_id );
	update_post_meta( $post_id, '_oha_duration', $tape['duration'] );
	update_post_meta( $post_id, '_oha_consent', $data['consent'] );
	update_post_meta( $post_id, '_oha_embargo_until', $data['embargo_until'] ?? '' );
	update_post_meta( $post_id, '_oha_release_date', $data['release_date'] ?? '' );
	update_post_meta( $post_id, '_oha_restriction', $data['restriction'] ?? '' );
	update_post_meta( $post_id, '_oha_tape_log', $tape_log );
	update_post_meta( $post_id, '_oha_cues', $tape['cues'] );

	return $post_id;
}

$interviews = array(
	array(
		'accession'      => 'OH-2019-014',
		'slug'           => 'maria-chen-produce-wholesaler',
		'title'          => 'Maria Chen, produce wholesaler',
		'narrator'       => 'Maria Chen',
		'interviewer'    => 'Luis Ortega',
		'interview_date' => '2019-03-18',
		'location'       => 'Oakland Produce Market, California',
		'collection'     => 'family-businesses',
		'topics'         => array( 'produce-markets', 'immigration' ),
		'consent'        => 'public',
		'release_date'   => '2019-03-18',
		'abstract'       => "Maria Chen runs Chen Produce, a third-generation stall at the Oakland Produce Market. She describes arriving before dawn, the collapse of the old credit book, and why she still writes names on boxes in pencil. The recording was made in the stall office after the last truck left.",
		'spoken'         => array(
			array( 'speaker' => 'Luis Ortega', 'text' => 'Today is March eighteenth, two thousand nineteen. I am Luis Ortega. Would you say your name and what you do here?' ),
			array( 'speaker' => 'Maria Chen', 'text' => 'Maria Chen. I run Chen Produce. My grandfather took this stall in nineteen fifty-eight. I still open the gate at three thirty in the morning.' ),
			array( 'speaker' => 'Luis Ortega', 'text' => 'What does three thirty actually look like?' ),
			array( 'speaker' => 'Maria Chen', 'text' => 'The forklifts are already moving. You can smell the cilantro before you see it. If I am late, the restaurants send someone else, and they do not come back.' ),
			array( 'speaker' => 'Luis Ortega', 'text' => 'People talk about the market as if it were disappearing.' ),
			array( 'speaker' => 'Maria Chen', 'text' => 'The building is still here. The families are not. When a stall goes to a broker who never stands at the scale, you lose the memory of who pays on Fridays.' ),
			array( 'speaker' => 'Luis Ortega', 'text' => 'Do you still keep a credit book?' ),
			array( 'speaker' => 'Maria Chen', 'text' => 'A pencil book. Names, not companies. If I typed it into a system, I would forget the face that goes with the debt.' ),
		),
	),
	array(
		'accession'      => 'OH-2004-007',
		'slug'           => 'ellis-rowan-night-ferry-engineer',
		'title'          => 'Ellis Rowan, night ferry engineer',
		'narrator'       => 'Ellis Rowan',
		'interviewer'    => 'Aisha Grant',
		'interview_date' => '2004-11-02',
		'location'       => 'Alameda, California',
		'collection'     => 'oakland-waterfront',
		'topics'         => array( 'night-work', 'unions' ),
		'consent'        => 'public',
		'release_date'   => '2004-11-02',
		'abstract'       => "Ellis Rowan spent twenty-six years on the night boats between Oakland and San Francisco. He talks about the engine room after midnight, the union hall on Clay Street, and the particular quiet of a crossing with no passengers in the cabin.",
		'spoken'         => array(
			array( 'speaker' => 'Aisha Grant', 'text' => 'This is Aisha Grant with Ellis Rowan, November second, two thousand four. Ellis, when did you start on the boats?' ),
			array( 'speaker' => 'Ellis Rowan', 'text' => 'Nineteen seventy-eight. I was a wiper first. You clean what other people pretend is not dirty.' ),
			array( 'speaker' => 'Aisha Grant', 'text' => 'And nights?' ),
			array( 'speaker' => 'Ellis Rowan', 'text' => 'Nights are honest. No brass from the company walking through. Just the diesels and the tide against the hull.' ),
			array( 'speaker' => 'Aisha Grant', 'text' => 'What do you remember about the hall?' ),
			array( 'speaker' => 'Ellis Rowan', 'text' => 'Clay Street. Coffee that could strip paint. If your name was not on the board, you went home. Nobody made a speech about it.' ),
			array( 'speaker' => 'Aisha Grant', 'text' => 'Is there a crossing you still think about?' ),
			array( 'speaker' => 'Ellis Rowan', 'text' => 'Fog in eighty-two. We had no passengers. The skipper still rang the bells as if the cabin were full. You do the work whether anyone is watching.' ),
		),
	),
	array(
		'accession'      => 'OH-2011-031',
		'slug'           => 'narrator-a-textile-cutter',
		'title'          => 'Narrator A, textile cutter',
		'narrator'       => 'Narrator A',
		'interviewer'    => 'Luis Ortega',
		'interview_date' => '2011-06-09',
		'location'       => 'Richmond, California',
		'collection'     => 'restricted-holdings',
		'topics'         => array( 'textile-work', 'immigration' ),
		'consent'        => 'restricted',
		'release_date'   => '',
		'restriction'    => 'The narrator asked that the recording remain sealed during their lifetime. The catalog lists the interview so the labor is not erased from the record.',
		'abstract'       => "A cutter from a Richmond shop describes piece rates, heat in the loft, and why coworkers used numbers instead of names on the floor. The audio is not released. The abstract is.",
		'spoken'         => array(
			array( 'speaker' => 'Luis Ortega', 'text' => 'For the tape, we will not use your name. What did you do in the shop?' ),
			array( 'speaker' => 'Narrator A', 'text' => 'I cut. If the marker was wrong, the whole bundle was wrong, and they took it from your envelope.' ),
			array( 'speaker' => 'Luis Ortega', 'text' => 'Why numbers instead of names?' ),
			array( 'speaker' => 'Narrator A', 'text' => 'Names get you in trouble. A number is just a number until someone wants to fire you.' ),
		),
	),
	array(
		'accession'      => 'OH-2024-002',
		'slug'           => 'dolores-kline-longshore-housing',
		'title'          => 'Dolores Kline, longshore housing organizer',
		'narrator'       => 'Dolores Kline',
		'interviewer'    => 'Aisha Grant',
		'interview_date' => '2024-01-16',
		'location'       => 'West Oakland, California',
		'collection'     => 'oakland-waterfront',
		'topics'         => array( 'housing', 'unions' ),
		'consent'        => 'embargoed',
		'embargo_until'  => '2028-01-01',
		'release_date'   => '2024-01-16',
		'restriction'    => 'Donor agreement: recording opens to the public on January 1, 2028.',
		'abstract'       => "Dolores Kline organized around housing for longshore families after the 2008 crash. She deposited the interview with an embargo so living neighbors named in the tape would not be pulled into a public argument. The finding aid is open; the tape is not.",
		'spoken'         => array(
			array( 'speaker' => 'Aisha Grant', 'text' => 'Dolores, why deposit this now if we cannot play it yet?' ),
			array( 'speaker' => 'Dolores Kline', 'text' => 'Because if I wait until everyone is gone, the street names will still be here and the people will not. The embargo is for the living. The archive is for later.' ),
			array( 'speaker' => 'Aisha Grant', 'text' => 'What should a researcher know in the meantime?' ),
			array( 'speaker' => 'Dolores Kline', 'text' => 'That we met in kitchens. That the union hall was not where the housing work happened. That is all I will put on the public record today.' ),
		),
	),
);

foreach ( $interviews as $interview ) {
	$id = oha_upsert_interview( $interview );
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::log( 'Seeded ' . $interview['accession'] . ' as post ' . $id );
	}
}

update_option( 'oha_demo_seeded', 1 );
update_option( 'blogdescription', 'Recorded interviews, timed transcripts, and consent that can withhold the tape.' );

if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::success( 'Reading room seeded with four interviews.' );
}
