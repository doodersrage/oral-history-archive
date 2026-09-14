<?php
/**
 * Transcript parser tests. Run: php tests/test-transcript.php
 */

define( 'ABSPATH', __DIR__ );
require dirname( __DIR__ ) . '/includes/class-transcript.php';

$failures = 0;

function oha_assert( $ok, $message ) {
	global $failures;
	if ( $ok ) {
		echo "ok  $message\n";
		return;
	}
	$failures++;
	echo "FAIL  $message\n";
}

$parsed = OHA_Transcript::parse(
	"00:00:00 Interviewer: Today is March 18.\n" .
	"00:12 Maria Chen: I grew up on the floor of the market.\n" .
	"[01:02:03] Interviewer: What changed?\n" .
	"# comment\n" .
	"not a cue\n"
);

oha_assert( 3 === count( $parsed ), 'parses three valid cues and skips junk' );
oha_assert( 0.0 === $parsed[0]['start'], 'hh:mm:ss start is 0' );
oha_assert( 12.0 === $parsed[1]['start'], 'mm:ss becomes seconds' );
oha_assert( 3723.0 === $parsed[2]['start'], 'hour timecode converts' );
oha_assert( 'Maria Chen' === $parsed[1]['speaker'], 'speaker preserved' );
oha_assert( abs( OHA_Transcript::timecode_to_seconds( '1:02:03' ) - 3723 ) < 0.01, 'timecode_to_seconds' );
oha_assert( '00:12.00' === OHA_Transcript::seconds_to_timecode( 12 ), 'seconds_to_timecode mm:ss' );

$at = OHA_Transcript::cue_at( $parsed, 20 );
oha_assert( $at && 'Maria Chen' === $at['speaker'], 'cue_at picks the last started line' );

$slice = OHA_Transcript::slice( $parsed, 10, 20 );
oha_assert( 1 === count( $slice ) && 'Maria Chen' === $slice[0]['speaker'], 'slice keeps in-range cues' );

oha_assert( '12 min 4 sec' === OHA_Transcript::human_duration( 724 ), 'human duration' );

if ( $failures ) {
	echo "\n$failures failed\n";
	exit( 1 );
}

echo "\nAll tests passed\n";
