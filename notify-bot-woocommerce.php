<?php


/**
 * Notify Bot for WooCommerce
 *
 * @package WOOTB
 * @version 2.1.1
 * @license GPL-2.0-or-later
 * @author  Ali Javaheri
 *
 * @wordpress-plugin
 * Plugin Name: Notify Bot for WooCommerce
 * Description: Receive order details and manage them using your telegram
 * Author: Ali Javaheri
 * Version: 2.1.1
 * Author URI: https://alijvhr.com
 * Requires at least: 5.2
 * Requires PHP: 7.3
 * WC requires at least: 3.2
 * WC tested up to: 8.8.3
 * Text Domain: notify-bot-woocommerce
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace WOOTB;

use WOOTB\includes\Initializer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WOOTB_PLUGIN_VERSION', '2.1.1' );

function wootb_auto_loader( $class ) {
	if ( preg_match( "/^WOOTB(.*)$/", $class, $matches ) ) {
		require __DIR__ . str_replace( '\\', DIRECTORY_SEPARATOR, $matches[1] ) . '.php';
	}
}

spl_autoload_register( 'WOOTB\\wootb_auto_loader', true, true );

define( 'WOOTB_PLUGIN_FILE', __FILE__ );
define( 'WOOTB_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOOTB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOTB_PLUGIN_DIR', __DIR__ );
define( 'WOOTB_PLUGIN_ICON', plugins_url( "images/ic.png", __FILE__ ) );

/**@var Initializer $wootb */

$wootb = Initializer::getInstance();

//$wootb->schedule_events();
//function wootb_SendUpdates() {
//	global $wootb;
//	if ( isset( $wootb ) ) {
//		$wootb->sendUpdatesToBot();
//	}
//}

//add_action( 'wootb_send_updates', 'WOOTB\\wootb_SendUpdates' );
register_deactivation_hook( __FILE__, [ $wootb, 'deactivate' ] );
