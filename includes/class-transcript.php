<?php
/**
 * Tape-log parser and timecode helpers.
 *
 * @package OralHistoryArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OHA_Transcript {

	/**
	 * Parse a tape log into cue objects.
	 *
	 * Accepted lines:
	 *   00:00:12 Speaker: text
	 *   00:12 Speaker: text
	 *   [00:00:12] Speaker: text
	 *
	 * @param string $text Raw tape log.
	 * @return array<int, array{start: float, speaker: string, text: string}>
	 */
	public static function parse( $text ) {
		$cues  = array();
		$lines = preg_split( '/\R/', (string) $text ) ?: array();

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line || str_starts_with( $line, '#' ) ) {
				continue;
			}

			if ( ! preg_match(
				'/^\[?((?:\d{1,2}:)?\d{1,2}:\d{2}(?:\.\d+)?)\]?\s+([^:]+):\s*(.+)$/u',
				$line,
				$match
			) ) {
				continue;
			}

			$start   = self::timecode_to_seconds( $match[1] );
			$speaker = trim( $match[2] );
			$body    = trim( $match[3] );

			if ( '' === $speaker || '' === $body || null === $start ) {
				continue;
			}

			$cues[] = array(
				'start'   => $start,
				'speaker' => $speaker,
				'text'    => $body,
			);
		}

		usort(
			$cues,
			static function ( $a, $b ) {
				return $a['start'] <=> $b['start'];
			}
		);

		return array_values( $cues );
	}

	/**
	 * Convert cue list back to a tape log.
	 *
	 * @param array $cues Cue objects.
	 * @return string
	 */
	public static function to_tape_log( $cues ) {
		$lines = array();
		foreach ( $cues as $cue ) {
			$lines[] = sprintf(
				'%s %s: %s',
				self::seconds_to_timecode( (float) $cue['start'] ),
				$cue['speaker'],
				$cue['text']
			);
		}
		return implode( "\n", $lines );
	}

	/**
	 * @param string $code Timecode.
	 * @return float|null
	 */
	public static function timecode_to_seconds( $code ) {
		$code  = trim( $code, "[] \t" );
		$parts = explode( ':', $code );
		if ( count( $parts ) < 2 || count( $parts ) > 3 ) {
			return null;
		}

		$parts = array_map( 'floatval', $parts );
		if ( 2 === count( $parts ) ) {
			return ( $parts[0] * 60 ) + $parts[1];
		}

		return ( $parts[0] * 3600 ) + ( $parts[1] * 60 ) + $parts[2];
	}

	/**
	 * @param float $seconds Seconds.
	 * @return string
	 */
	public static function seconds_to_timecode( $seconds ) {
		$seconds = max( 0, (float) $seconds );
		$hours   = (int) floor( $seconds / 3600 );
		$minutes = (int) floor( ( $seconds % 3600 ) / 60 );
		$secs    = $seconds - ( $hours * 3600 ) - ( $minutes * 60 );

		if ( $hours > 0 ) {
			return sprintf( '%02d:%02d:%05.2f', $hours, $minutes, $secs );
		}

		return sprintf( '%02d:%05.2f', $minutes, $secs );
	}

	/**
	 * Human duration like 12 min 4 sec.
	 *
	 * @param float $seconds Seconds.
	 * @return string
	 */
	public static function human_duration( $seconds ) {
		$seconds = (int) round( max( 0, (float) $seconds ) );
		if ( $seconds < 60 ) {
			return sprintf( '%d sec', $seconds );
		}
		$minutes = (int) floor( $seconds / 60 );
		$remain  = $seconds % 60;
		if ( $seconds < 3600 ) {
			return $remain ? sprintf( '%d min %d sec', $minutes, $remain ) : sprintf( '%d min', $minutes );
		}
		$hours   = (int) floor( $seconds / 3600 );
		$minutes = (int) floor( ( $seconds % 3600 ) / 60 );
		return sprintf( '%d hr %d min', $hours, $minutes );
	}

	/**
	 * Cue active at a timestamp.
	 *
	 * @param array $cues Cue list.
	 * @param float $time Seconds.
	 * @return array|null
	 */
	public static function cue_at( $cues, $time ) {
		$current = null;
		foreach ( $cues as $cue ) {
			if ( $cue['start'] <= $time ) {
				$current = $cue;
			} else {
				break;
			}
		}
		return $current;
	}

	/**
	 * Slice cues to a time range for clip embeds.
	 *
	 * @param array $cues  Cues.
	 * @param float $start Start seconds.
	 * @param float $end   End seconds.
	 * @return array
	 */
	public static function slice( $cues, $start, $end ) {
		$out = array();
		foreach ( $cues as $cue ) {
			if ( $cue['start'] >= $end ) {
				break;
			}
			if ( $cue['start'] >= $start ) {
				$out[] = $cue;
			}
		}
		return $out;
	}
}
