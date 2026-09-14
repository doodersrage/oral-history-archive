<?php
/**
 * Public reading room templates and assets.
 *
 * @package OralHistoryArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OHA_Frontend {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 20 );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
		add_filter( 'document_title_parts', array( __CLASS__, 'title_parts' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'finding_aid_query' ) );
	}

	public static function is_reading_room() {
		if ( is_post_type_archive( OHA_Interview::POST_TYPE ) || is_singular( OHA_Interview::POST_TYPE ) ) {
			return true;
		}
		if ( is_tax( array( OHA_Interview::COLLECTION, OHA_Interview::TOPIC ) ) ) {
			return true;
		}
		$page_id = (int) get_option( 'oha_page_id' );
		if ( $page_id && is_page( $page_id ) ) {
			return true;
		}
		return false;
	}

	public static function assets() {
		if ( ! self::is_reading_room() ) {
			return;
		}

		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'classic-theme-styles' );
		wp_dequeue_style( 'core-block-supports' );
		wp_dequeue_style( 'twentytwentyfive-style' );
		wp_dequeue_style( 'twentytwentyfive-style-inline' );

		wp_enqueue_style(
			'oha-reading-room',
			OHA_URL . 'assets/css/reading-room.css',
			array(),
			OHA_VERSION
		);

		if ( is_singular( OHA_Interview::POST_TYPE ) && OHA_Interview::is_playable( get_queried_object_id() ) ) {
			wp_enqueue_script(
				'oha-player',
				OHA_URL . 'assets/js/player.js',
				array(),
				OHA_VERSION,
				true
			);
		}
	}

	public static function template_include( $template ) {
		if ( is_singular( OHA_Interview::POST_TYPE ) ) {
			return OHA_DIR . 'templates/single.php';
		}
		if ( is_post_type_archive( OHA_Interview::POST_TYPE ) || is_tax( array( OHA_Interview::COLLECTION, OHA_Interview::TOPIC ) ) ) {
			return OHA_DIR . 'templates/archive.php';
		}
		$page_id = (int) get_option( 'oha_page_id' );
		if ( $page_id && is_page( $page_id ) ) {
			return OHA_DIR . 'templates/archive.php';
		}
		return $template;
	}

	public static function title_parts( $parts ) {
		if ( is_post_type_archive( OHA_Interview::POST_TYPE ) || ( (int) get_option( 'oha_page_id' ) && is_front_page() ) ) {
			$parts['title'] = __( 'Finding aid', 'oral-history-archive' );
		}
		return $parts;
	}

	public static function finding_aid_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! $query->is_post_type_archive( OHA_Interview::POST_TYPE )
			&& ! $query->is_tax( array( OHA_Interview::COLLECTION, OHA_Interview::TOPIC ) ) ) {
			return;
		}

		$query->set( 'posts_per_page', 50 );
		$query->set( 'orderby', 'meta_value' );
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Finding aid sorts by accession.
		$query->set( 'meta_key', '_oha_accession' );
		$query->set( 'order', 'ASC' );

		self::apply_filters_to_query( $query );
	}

	/**
	 * Public catalog filters from the query string (read-only; no nonce).
	 *
	 * @return array{q:string,collection:string,rights:string}
	 */
	public static function request_filters() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Public catalog filters; sanitized below.
		$q          = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$collection = isset( $_GET['collection'] ) ? sanitize_title( wp_unslash( $_GET['collection'] ) ) : '';
		$rights     = isset( $_GET['rights'] ) ? sanitize_key( wp_unslash( $_GET['rights'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return array(
			'q'          => $q,
			'collection' => $collection,
			'rights'     => $rights,
		);
	}

	public static function apply_filters_to_query( $query ) {
		$tax_query  = $query->get( 'tax_query' );
		$meta_query = $query->get( 'meta_query' );
		$tax_query  = is_array( $tax_query ) ? $tax_query : array();
		$meta_query = is_array( $meta_query ) ? $meta_query : array();
		$filters    = self::request_filters();

		if ( $filters['collection'] ) {
			$tax_query[] = array(
				'taxonomy' => OHA_Interview::COLLECTION,
				'field'    => 'slug',
				'terms'    => $filters['collection'],
			);
		}

		if ( 'restricted' === $filters['rights'] ) {
			$meta_query[] = array(
				'key'   => '_oha_consent',
				'value' => 'restricted',
			);
		} elseif ( 'embargoed' === $filters['rights'] ) {
			$meta_query[] = array(
				'key'   => '_oha_consent',
				'value' => 'embargoed',
			);
		} elseif ( 'open' === $filters['rights'] ) {
			$meta_query[] = array(
				'key'   => '_oha_consent',
				'value' => 'public',
			);
		}

		if ( $filters['q'] ) {
			$query->set( 's', $filters['q'] );
		}

		if ( '1' !== OHA_Plugin::setting( 'show_restricted', '1' ) ) {
			$meta_query[] = array(
				'key'   => '_oha_consent',
				'value' => 'public',
			);
		}

		if ( $tax_query ) {
			$query->set( 'tax_query', $tax_query );
		}
		if ( $meta_query ) {
			$query->set( 'meta_query', $meta_query );
		}
	}

	public static function interview_query_args() {
		$filters = self::request_filters();
		$args    = array(
			'post_type'      => OHA_Interview::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'meta_value',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Finding aid sorts by accession.
			'meta_key'       => '_oha_accession',
			'order'          => 'ASC',
		);

		$tax_query  = array();
		$meta_query = array();

		if ( $filters['collection'] ) {
			$tax_query[] = array(
				'taxonomy' => OHA_Interview::COLLECTION,
				'field'    => 'slug',
				'terms'    => $filters['collection'],
			);
		}

		if ( 'restricted' === $filters['rights'] ) {
			$meta_query[] = array(
				'key'   => '_oha_consent',
				'value' => 'restricted',
			);
		} elseif ( 'embargoed' === $filters['rights'] ) {
			$meta_query[] = array(
				'key'   => '_oha_consent',
				'value' => 'embargoed',
			);
		} elseif ( 'open' === $filters['rights'] ) {
			$meta_query[] = array(
				'key'   => '_oha_consent',
				'value' => 'public',
			);
		}

		if ( $filters['q'] ) {
			$args['s'] = $filters['q'];
		}

		if ( '1' !== OHA_Plugin::setting( 'show_restricted', '1' ) ) {
			$meta_query[] = array(
				'key'   => '_oha_consent',
				'value' => 'public',
			);
		}

		if ( $tax_query ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Collection filter for finding aid.
			$args['tax_query'] = $tax_query;
		}
		if ( $meta_query ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Rights filter for finding aid.
			$args['meta_query'] = $meta_query;
		}

		return $args;
	}

	public static function current_filters() {
		return self::request_filters();
	}
}
