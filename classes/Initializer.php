<?php

namespace WoocommerceTelegramBot\classes;

use Safe\Exceptions\VarException;

class Initializer extends Singleton {
	/**@var TelegramAdaptor */
	public $telegram;


	protected $queue = [];

	function init() {
		$path = dirname( plugin_basename( __FILE__ ), 2 );
		load_plugin_textdomain( 'woo-telegram-bot', false, "$path/languages/" );
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

		if ( in_array( 'woocommerce/woocommerce.php', $active_plugins ) ) {
			$this->run();
		} else {
			add_action( 'admin_notices', [ $this, 'woocommerceNotice' ] );
		}
	}

	private function run() {
		add_action( 'plugins_loaded', [ $this, 'loadHooks' ], 26 );
		add_filter( 'plugin_action_links_WoocommerceTelegramBot/WoocommerceTelegramBot.php', [
			$this,
			'add_action_links'
		] );
	}

	function woocommerceNotice() {
		$message = __( 'Please activate woocommerce on your wp installation in order to use Woocommerce Telegram Bot plugin', 'woo-telegram-bot' );
		echo "<div class=\"notice notice-error\"><p>$message</p></div>";
	}

	function add_action_links( $actions ) {
		$configure = __( 'Configure', 'woo-telegram-bot' );
		$url       = admin_url( 'admin.php?page=wc-settings&tab=wootb' );
		$actions[] = "<a href=\"$url\">$configure</a>";

		return $actions;
	}

	function loadHooks() {
		$this->initTelegramBot();
		add_action( 'wp_ajax_wootb_send_test_message', [ $this, 'sendTestMessage' ] );
		add_filter( 'woocommerce_get_settings_pages', [ $this, 'addWooSettingSection' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_script' ] );
		add_action( 'save_post_shop_order', [ $this, 'woocommerce_new_order' ] );
		add_action( 'save_post_shop_order', [ $this, 'sendUpdatesToBot' ] );
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'woocommerce_new_order' ] );
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'sendUpdatesToBot' ] );
		add_action( 'woocommerce_order_status_changed', [ $this, 'woocommerce_new_order' ] );
		add_action( 'woocommerce_order_status_changed', [ $this, 'sendUpdatesToBot' ] );
		add_filter( 'cron_schedules', [ $this, 'add_minute_cron' ] );
	}

	private function initTelegramBot() {
		$this->telegram = new TelegramAdaptor( get_option( 'wootb_setting_token' ) );
		$use_proxy      = get_option( 'wootb_use_proxy' );
		$this->telegram->use_proxy( $use_proxy );
		TelegramAPI::getInstance()->setAdaptor( $this->telegram );
	}

	public function schedule_events() {
		if ( ! wp_next_scheduled( 'wootb_send_updates' ) ) {
			wp_schedule_event( time(), 'hourly', 'wootb_send_updates' );
		}
	}

	function add_minute_cron( $schedules ) {
		if ( ! isset( $schedules['every_minute'] ) ) {
			$schedules['every_minute'] = [
				'interval' => 60,
				'display'  => __( 'Every 1 Minute', 'woo-telegram-bot' ),
			];
		}

		return $schedules;
	}

	function admin_enqueue_script() {
		wp_enqueue_script( 'wootb_admin_script', plugin_dir_url( __FILE__ ) . '../assets/js/admin.js', array( 'jquery' ), '1.5', true );
		wp_register_style( 'wootb_css_script', plugin_dir_url( __FILE__ ) . '../assets/css/admin.css', false, '1.5' );
		wp_enqueue_style( 'wootb_css_script' );
	}

	function sendTestMessage() {
		try {
			$this->sendToAll( [], __( 'This is a test message from Woocommerce telegram bot Plugin!', 'woo-telegram-bot' ) );
			echo json_encode( [ 'error' => 0, 'message' => __( 'Message successfully sent', 'woo-telegram-bot' ) ] );
			wp_die();
		} catch ( \Exception $ex ) {
			echo json_encode( [ 'error' => 1, 'message' => $ex->getMessage() ] );
			wp_die();
		}
	}

	public function sendToAll( $messageIds, $text, $keyboard = null ) {
		$chats = json_decode( get_option( 'wootb_setting_users' ), true );
		foreach ( $chats as $id => $chat ) {
			$tries = $message = 0;
			do {
				try {
					if ( isset( $messageIds[ $id ] ) ) {
						$message = $this->telegram->updateMessage( $id, $messageIds[ $id ], $text, $keyboard );
						if ( ! $message->ok && $message->description == "Bad Request: message to edit not found" ) {
							unset( $messageIds[ $id ], $message->ok );
						}
					} else {
						$message = $this->telegram->sendMessage( $id, $text, $keyboard );
					}
				} catch ( \Exception $e ) {

				}
			} while ( ! isset( $message->ok ) && sleep( 1 ) !== false && $tries ++ < 5 );
			if ( isset( $message->result->message_id ) ) {
				$messageIds[ $id ] = $message->result->message_id;
			}
		}

		return $messageIds;
	}

	public function woocommerce_new_order( $order_id ) {
		$this->addToUpdateQueue( $order_id );
	}

	public function addToUpdateQueue( $order_id ) {
		$this->get_queue();
		if ( ! in_array( $order_id, $this->queue ) ) {
			$this->queue[] = $order_id;
		}
	}

	public function get_queue() {
		if ( ! isset( $this->queue ) ) {
			$this->queue = json_decode( get_option( 'wootb_send_queue', "[]" ), true );
		}

		return $this->queue;
	}

	public function set_queue() {
		$this->queue = array_values( $this->queue );
		update_option( 'wootb_send_queue', json_encode( $this->queue ) );
	}

	public function deactivate() {
		$this->unschedule_events();
	}

	public function unschedule_events() {
		$timestamp = wp_next_scheduled( 'wootb_send_updates' );
		wp_unschedule_event( $timestamp, 'wootb_send_updates' );
	}

	public function sendUpdatesToBot() {
		$this->queue = $this->get_queue();
		$chatIds     = json_decode( get_option( 'wootb_setting_users' ), true );
		$chatCount   = count( $chatIds );
		foreach ( $this->queue as $key => $order_id ) {
			$sentCount = count( $this->sendUpdateToBot( $order_id ) );
			if ( $chatCount == $sentCount ) {
				unset( $this->queue[ $key ] );
			}
		}
		$this->set_queue();

	}

	public function sendUpdateToBot( $order_id ) {
		$wc         = new WooCommerceAdaptor( $order_id );
		$text       = $wc->interpolate( self::getTemplate() );
		$messageIds = json_decode( get_post_meta( $order_id, 'WooTelegramMessageIds', true ) ?: "[]", true );
		$status     = $wc->get_status();
		$keyboard   = new TelegramKeyboard( 2 );
		if ( $status != 'completed' ) {
			$keyboard->add_inline_callback_button( '✅ ' . __( 'Complete', 'woo-telegram-bot' ), [
				"cmd" => "status",
				"oid" => $order_id,
				"st"  => 0
			] );
		}
		if ( $status != 'refunded' ) {
			$keyboard->add_inline_callback_button( '💸 ' . __( 'Refund', 'woo-telegram-bot' ), [
				"cmd" => "status",
				"oid" => $order_id,
				"st"  => 1
			] );
		}
		if ( $status != 'processing' ) {
			$keyboard->add_inline_callback_button( '🕙 ' . __( 'Process', 'woo-telegram-bot' ), [
				"cmd" => "status",
				"oid" => $order_id,
				"st"  => 2
			] );
		}
		if ( $status != 'cancelled' ) {
			$keyboard->add_inline_callback_button( '❌ ' . __( 'Cancel', 'woo-telegram-bot' ), [
				"cmd" => "status",
				"oid" => $order_id,
				"st"  => 3
			] );
		}
		$messageIds = $this->sendToAll( $messageIds, $text, $keyboard );
		update_post_meta( $order_id, 'WooTelegramMessageIds', json_encode( $messageIds ) );

		return $messageIds;
	}

	private static function getTemplate() {
		return str_replace( [
			"billing_first_name",
			"billing_last_name",
			"billing_address_1",
			"billing_address_2",
			"billing_city",
			"billing_state",
			"billing_postcode",
			"billing_email",
			"billing_phone"
		], [
			"billing-first_name",
			"billing-last_name",
			"billing-address_1",
			"billing-address_2",
			"billing-city",
			"billing-state",
			"billing-postcode",
			"billing-email",
			"billing-phone"
		], get_option( 'wootb_setting_template' ) );
	}

	public function addWooSettingSection( $settings ) {
		$settings[] = new OptionPanel( $this->telegram );

		return $settings;
	}

	public function update() {
		$chatIds = get_option( 'wootb_setting_chatid', false );
		if ( $chatIds ) {
			$chatIds  = explode( ',', $chatIds );
			$complete = true;
			foreach ( $chatIds as $chat_id ) {
				$response = $this->telegram->request( 'getChat', [ 'chat_id' => $chat_id ] );
				if ( $response->ok ) {
					$chat = $response->result;
					$this->registerUser( $chat );
				} else {
					$complete = false;
				}
			}
			if ( $complete ) {
				delete_option( 'wootb_setting_chatid' );
			}
		}
	}

	public function registerUser( $chat ) {
		$users              = json_decode( get_option( 'wootb_setting_users' ), true );
		$users[ $chat->id ] = [
			'id'    => $chat->id,
			'uname' => $chat->username,
			'fname' => $chat->first_name,
			'lname' => $chat->last_name
		];
		update_option( 'wootb_setting_users', json_encode( $users ) );
	}
}