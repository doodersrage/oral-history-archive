<?php
/**
 * Uninstall: remove plugin options. Interview posts are left in place.
 *
 * @package OralHistoryArchive
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'oha_settings' );
delete_option( 'oha_page_id' );
delete_option( 'oha_flush_rewrites' );
