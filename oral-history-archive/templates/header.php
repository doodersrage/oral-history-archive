<?php
/**
 * Shared reading-room document chrome.
 *
 * @package OralHistoryArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$institution = OHA_Plugin::setting( 'institution', get_bloginfo( 'name' ) );
$home_url    = home_url( '/' );
$aid_url     = get_post_type_archive_link( OHA_Interview::POST_TYPE );
$page_id     = (int) get_option( 'oha_page_id' );
$finding_url = ( $page_id && get_post( $page_id ) ) ? get_permalink( $page_id ) : $aid_url;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'oha-room' ); ?>>
<a class="oha-skip" href="#oha-main"><?php esc_html_e( 'Skip to catalog', 'oral-history-archive' ); ?></a>
<header class="oha-masthead">
	<div class="oha-masthead-inner">
		<p class="oha-institution"><a href="<?php echo esc_url( $home_url ); ?>"><?php echo esc_html( $institution ); ?></a></p>
		<nav class="oha-nav" aria-label="<?php esc_attr_e( 'Archive', 'oral-history-archive' ); ?>">
			<a href="<?php echo esc_url( $finding_url ); ?>"><?php esc_html_e( 'Finding aid', 'oral-history-archive' ); ?></a>
			<a href="<?php echo esc_url( $aid_url ); ?>"><?php esc_html_e( 'All interviews', 'oral-history-archive' ); ?></a>
		</nav>
	</div>
</header>
<main id="oha-main" class="oha-main">
