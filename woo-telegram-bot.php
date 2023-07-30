<?php


/**
 * Woocommerce Telegram Bot
 *
 * @package WoocommerceTelegramBot
 * @version 1.0.1
 * @license MIT
 * @author  Ali Javaheri
 *
 * @wordpress-plugin
 * Plugin Name: Woocommerce Telegram Bot
 * Description: Send order details to telegram bot
 * Author: Ali Javaheri
 * Version: 1.0.1
 * Author URI: https://alijvhr.com
 * Requires at least: 5.2
 * Requires PHP: 7.3
 * WC requires at least: 3.2
 * WC tested up to: 7.9
 * Text Domain: wootb
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

namespace WoocommerceTelegramBot;

use WoocommerceTelegramBot\classes\Initializer;

if (!defined('ABSPATH')) {
    exit;
}

function wootb_auto_loader($class)
{
    if (substr($class, 0, 22) == 'WoocommerceTelegramBot')
        require __DIR__ . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 22)) . '.php';
}

spl_autoload_register('WoocommerceTelegramBot\\wootb_auto_loader', true, true);

define('WOOTB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WOOTB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WOOTB_PLUGIN_FILE', __FILE__);
define('WOOTB_PLUGIN_ICON', plugins_url("images/ic.png", __FILE__));

Initializer::getInstance();