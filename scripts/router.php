<?php
/**
 * Router for PHP's built-in server.
 *
 * @package OralHistoryArchive
 */

$root = $_SERVER['DOCUMENT_ROOT'];
$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$file = $root . $path;

if ( $path !== '/' && is_file( $file ) ) {
	return false;
}

require $root . '/index.php';
