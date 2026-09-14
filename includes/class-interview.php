<?php
/**
 * Interview post type, taxonomies, and rights helpers.
 *
 * @package OralHistoryArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OHA_Interview {

	const POST_TYPE   = 'oha_interview';
	const COLLECTION  = 'oha_collection';
	const TOPIC       = 'oha_topic';
	const RIGHTS_META = array(
		'_oha_accession',
		'_oha_narrator',
		'_oha_interviewer',
		'_oha_interview_date',
		'_oha_location',
		'_oha_language',
		'_oha_audio_id',
		'_oha_duration',
		'_oha_consent',
		'_oha_embargo_until',
		'_oha_release_date',
		'_oha_restriction',
		'_oha_tape_log',
		'_oha_cues',
	);

	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Interviews', 'oral-history-archive' ),
					'singular_name'      => __( 'Interview', 'oral-history-archive' ),
					'add_new'            => __( 'Add interview', 'oral-history-archive' ),
					'add_new_item'       => __( 'Add interview', 'oral-history-archive' ),
					'edit_item'          => __( 'Edit interview', 'oral-history-archive' ),
					'new_item'           => __( 'New interview', 'oral-history-archive' ),
					'view_item'          => __( 'View interview', 'oral-history-archive' ),
					'search_items'       => __( 'Search interviews', 'oral-history-archive' ),
					'not_found'          => __( 'No interviews in the catalog.', 'oral-history-archive' ),
					'not_found_in_trash' => __( 'No interviews in the trash.', 'oral-history-archive' ),
					'all_items'          => __( 'All interviews', 'oral-history-archive' ),
					'menu_name'          => __( 'Oral History', 'oral-history-archive' ),
				),
				'public'              => true,
				'show_in_rest'        => true,
				'has_archive'         => 'interviews',
				'rewrite'             => array(
					'slug'       => 'interview',
					'with_front' => false,
				),
				'menu_icon'           => 'dashicons-microphone',
				'menu_position'       => 5,
				'supports'            => array( 'title', 'editor', 'thumbnail' ),
				'show_in_nav_menus'   => true,
				'exclude_from_search' => false,
			)
		);

		register_taxonomy(
			self::COLLECTION,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Collections', 'oral-history-archive' ),
					'singular_name' => __( 'Collection', 'oral-history-archive' ),
					'search_items'  => __( 'Search collections', 'oral-history-archive' ),
					'all_items'     => __( 'All collections', 'oral-history-archive' ),
					'edit_item'     => __( 'Edit collection', 'oral-history-archive' ),
					'add_new_item'  => __( 'Add collection', 'oral-history-archive' ),
					'menu_name'     => __( 'Collections', 'oral-history-archive' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'collection',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			self::TOPIC,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Topics', 'oral-history-archive' ),
					'singular_name' => __( 'Topic', 'oral-history-archive' ),
					'search_items'  => __( 'Search topics', 'oral-history-archive' ),
					'all_items'     => __( 'All topics', 'oral-history-archive' ),
					'edit_item'     => __( 'Edit topic', 'oral-history-archive' ),
					'add_new_item'  => __( 'Add topic', 'oral-history-archive' ),
				),
				'public'            => true,
				'hierarchical'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'topic',
					'with_front' => false,
				),
			)
		);
	}

	public static function get_meta( $post_id ) {
		$post_id = (int) $post_id;
		$cues    = get_post_meta( $post_id, '_oha_cues', true );
		if ( ! is_array( $cues ) ) {
			$cues = OHA_Transcript::parse( (string) get_post_meta( $post_id, '_oha_tape_log', true ) );
		}

		$language = (string) get_post_meta( $post_id, '_oha_language', true );

		return array(
			'accession'       => (string) get_post_meta( $post_id, '_oha_accession', true ),
			'narrator'        => (string) get_post_meta( $post_id, '_oha_narrator', true ),
			'interviewer'     => (string) get_post_meta( $post_id, '_oha_interviewer', true ),
			'interview_date'  => (string) get_post_meta( $post_id, '_oha_interview_date', true ),
			'location'        => (string) get_post_meta( $post_id, '_oha_location', true ),
			'language'        => $language ? $language : __( 'English', 'oral-history-archive' ),
			'audio_id'        => (int) get_post_meta( $post_id, '_oha_audio_id', true ),
			'duration'        => (float) get_post_meta( $post_id, '_oha_duration', true ),
			'consent'         => (string) get_post_meta( $post_id, '_oha_consent', true ) ?: 'public',
			'embargo_until'   => (string) get_post_meta( $post_id, '_oha_embargo_until', true ),
			'release_date'    => (string) get_post_meta( $post_id, '_oha_release_date', true ),
			'restriction'     => (string) get_post_meta( $post_id, '_oha_restriction', true ),
			'tape_log'        => (string) get_post_meta( $post_id, '_oha_tape_log', true ),
			'cues'            => $cues,
		);
	}

	public static function is_playable( $post_id ) {
		$meta = self::get_meta( $post_id );
		if ( 'restricted' === $meta['consent'] ) {
			return false;
		}
		if ( 'embargoed' === $meta['consent'] && self::is_embargo_active( $meta['embargo_until'] ) ) {
			return false;
		}
		if ( $meta['audio_id'] <= 0 ) {
			return false;
		}
		return (bool) wp_get_attachment_url( $meta['audio_id'] );
	}

	public static function is_embargo_active( $until ) {
		if ( ! $until ) {
			return true;
		}
		$ts = strtotime( $until . ' 23:59:59' );
		return $ts && $ts > time();
	}

	public static function rights_label( $post_id ) {
		$meta = self::get_meta( $post_id );
		if ( 'restricted' === $meta['consent'] ) {
			return __( 'Restricted', 'oral-history-archive' );
		}
		if ( 'embargoed' === $meta['consent'] && self::is_embargo_active( $meta['embargo_until'] ) ) {
			/* translators: %s: embargo lift date */
			return sprintf( __( 'Embargoed until %s', 'oral-history-archive' ), self::format_date( $meta['embargo_until'] ) );
		}
		return __( 'Open for listening', 'oral-history-archive' );
	}

	public static function rights_code( $post_id ) {
		$meta = self::get_meta( $post_id );
		if ( 'restricted' === $meta['consent'] ) {
			return 'restricted';
		}
		if ( 'embargoed' === $meta['consent'] && self::is_embargo_active( $meta['embargo_until'] ) ) {
			return 'embargoed';
		}
		return 'open';
	}

	public static function format_date( $date ) {
		if ( ! $date ) {
			return '';
		}
		$ts = strtotime( $date );
		return $ts ? date_i18n( 'F j, Y', $ts ) : $date;
	}

	public static function citation( $post_id ) {
		$meta        = self::get_meta( $post_id );
		$institution = OHA_Plugin::setting( 'institution', get_bloginfo( 'name' ) );
		$parts       = array();

		if ( $meta['narrator'] ) {
			$parts[] = $meta['narrator'] . '.';
		}
		if ( $meta['interviewer'] ) {
			/* translators: %s: interviewer name */
			$parts[] = sprintf( __( 'Interview by %s.', 'oral-history-archive' ), $meta['interviewer'] );
		}
		if ( $meta['interview_date'] ) {
			$parts[] = self::format_date( $meta['interview_date'] ) . '.';
		}
		$parts[] = $institution . ( $meta['accession'] ? ', ' . $meta['accession'] : '' ) . '.';

		return trim( implode( ' ', $parts ) );
	}

	public static function duration_seconds( $post_id ) {
		$meta = self::get_meta( $post_id );
		if ( $meta['duration'] > 0 ) {
			return $meta['duration'];
		}
		if ( ! empty( $meta['cues'] ) ) {
			$last = end( $meta['cues'] );
			return (float) $last['start'] + 8;
		}
		return 0;
	}
}
