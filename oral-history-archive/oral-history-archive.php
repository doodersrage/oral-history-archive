<?php
/**
 * Plugin Name: Oral History Archive
 * Description: Interviews as archival records: timed transcripts, narrator credit, and consent that can withhold the tape.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: Oral History Archive
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: oral-history-archive
 *
 * @package OralHistoryArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OHA_VERSION', '1.0.3' );
define( 'OHA_FILE', __FILE__ );
define( 'OHA_DIR', plugin_dir_path( __FILE__ ) );
define( 'OHA_URL', plugin_dir_url( __FILE__ ) );

require_once OHA_DIR . 'includes/class-transcript.php';
require_once OHA_DIR . 'includes/class-interview.php';
require_once OHA_DIR . 'includes/class-plugin.php';
require_once OHA_DIR . 'includes/class-admin.php';
require_once OHA_DIR . 'includes/class-frontend.php';
require_once OHA_DIR . 'includes/class-shortcodes.php';

register_activation_hook( __FILE__, array( 'OHA_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'OHA_Plugin', 'deactivate' ) );

OHA_Plugin::init();
