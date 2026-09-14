<?php
/**
 * Plugin bootstrap, settings, activation.
 *
 * @package OralHistoryArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OHA_Plugin {

	const OPTION = 'oha_settings';

	public static function init() {
		add_action( 'init', array( 'OHA_Interview', 'register' ) );
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrites' ) );
		OHA_Admin::init();
		OHA_Frontend::init();
		OHA_Shortcodes::init();
	}

	public static function load_textdomain() {
		load_plugin_textdomain(
			'oral-history-archive',
			false,
			dirname( plugin_basename( OHA_FILE ) ) . '/languages'
		);
	}

	public static function activate() {
		OHA_Interview::register();
		self::ensure_reading_room_page();
		update_option( 'oha_flush_rewrites', '1' );
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public static function maybe_flush_rewrites() {
		if ( '1' === get_option( 'oha_flush_rewrites' ) ) {
			flush_rewrite_rules();
			delete_option( 'oha_flush_rewrites' );
		}
	}

	public static function defaults() {
		return array(
			'institution'     => get_bloginfo( 'name' ),
			'rights_contact'  => get_option( 'admin_email' ),
			'show_restricted' => '1',
			'intro'           => __( 'This archive collects recorded interviews with people whose work is usually left out of the official record. You can listen where the narrator allowed it. Where they did not, the catalog still shows that the interview exists.', 'oral-history-archive' ),
		);
	}

	public static function settings() {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return array_merge( self::defaults(), $stored );
	}

	public static function setting( $key, $fallback = '' ) {
		$settings = self::settings();
		return isset( $settings[ $key ] ) && '' !== $settings[ $key ] ? $settings[ $key ] : $fallback;
	}

	public static function ensure_reading_room_page() {
		$existing = (int) get_option( 'oha_page_id' );
		if ( $existing && get_post( $existing ) ) {
			return $existing;
		}

		$page_id = wp_insert_post(
			array(
				'post_title'   => __( 'Reading room', 'oral-history-archive' ),
				'post_name'    => 'reading-room',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<!-- Oral History Archive reading room -->',
			)
		);

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( 'oha_page_id', (int) $page_id );
			return (int) $page_id;
		}

		return 0;
	}
}
