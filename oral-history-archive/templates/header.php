<?php
/**
 * Shared reading-room document chrome.
 *
 * @package OralHistoryArchive
 *
 * @var string $oha_title Document title.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$institution = OHA_Plugin::setting( 'institution', get_bloginfo( 'name' ) );
$filters     = OHA_Frontend::current_filters();
$home_url    = home_url( '/' );
$aid_url     = get_post_type_archive_link( OHA_Interview::POST_TYPE );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'oha-room' ); ?>>
<a class="oha-skip" href="#oha-main">Skip to catalog</a>
<header class="oha-masthead">
	<div class="oha-masthead-inner">
		<p class="oha-institution"><a href="<?php echo esc_url( $home_url ); ?>"><?php echo esc_html( $institution ); ?></a></p>
		<nav class="oha-nav" aria-label="Archive">
			<a href="<?php echo esc_url( $home_url ); ?>">Finding aid</a>
			<a href="<?php echo esc_url( $aid_url ); ?>">All interviews</a>
		</nav>
	</div>
</header>
<main id="oha-main" class="oha-main">
