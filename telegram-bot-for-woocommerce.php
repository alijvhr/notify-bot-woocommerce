<?php


/**
 * Telegram bot for WooCommerce
 *
 * @package WoocommerceTelegramBot
 * @version 1.0.1
 * @license GPL-2.0-or-later
 * @author  Ali Javaheri
 *
 * @wordpress-plugin
 * Plugin Name: Telegram bot for WooCommerce
 * Description: Receive order details and manage them using your telegram
 * Author: Ali Javaheri
 * Version: 1.0.1
 * Author URI: https://alijvhr.com
 * Requires at least: 5.2
 * Requires PHP: 7.3
 * WC requires at least: 3.2
 * WC tested up to: 7.9
 * Text Domain: telegram-bot-for-woocommerce
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace WoocommerceTelegramBot;

use WoocommerceTelegramBot\includes\Initializer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wootb_auto_loader( $class ) {
	if ( substr( $class, 0, 22 ) == 'WoocommerceTelegramBot' ) {
		require __DIR__ . str_replace( '\\', DIRECTORY_SEPARATOR, substr( $class, 22 ) ) . '.php';
	}
}

spl_autoload_register( 'WoocommerceTelegramBot\\wootb_auto_loader', true, true );

define( 'WOOTB_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOOTB_PLUGIN_VERSION', '1.0.3' );
define( 'WOOTB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOTB_PLUGIN_DIR', __DIR__ );
define( 'WOOTB_PLUGIN_ICON', plugins_url( "images/ic.png", __FILE__ ) );

/**@var \WoocommerceTelegramBot\includes\Initializer $wootb */

$wootb   = Initializer::getInstance();

$wootb->schedule_events();
function wooSendUpdates() {
	global $wootb;
	$wootb->sendUpdatesToBot();
}

add_action( 'wootb_send_updates', 'wooSendUpdates' );
register_deactivation_hook( __FILE__, [ $wootb, 'deactivate' ] );
