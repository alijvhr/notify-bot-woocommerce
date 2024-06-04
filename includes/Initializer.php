<?php

namespace WOOTB\includes;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Exception;

class Initializer extends Singleton {

	const STATUS_CANCEL = 1, STATUS_REFUND = 2, STATUS_COMPLETE = 4, STATUS_PROCESS = 8;

	/**@var TelegramAdaptor */
	public $telegram;


	protected $queue = [];

	public function init() {
		$path = dirname( plugin_basename( __FILE__ ), 2 );
		load_plugin_textdomain( 'notify-bot-woocommerce', false, "$path/languages/" );
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

		if ( in_array( 'woocommerce/woocommerce.php', $active_plugins ) ) {
			$this->run();
		} else {
			add_action( 'admin_notices', [ $this, 'woocommerceNotice' ] );
		}
	}

	private function run() {
		add_action( 'plugins_loaded', [ $this, 'loadHooks' ], 26 );
		add_filter( 'plugin_action_links_notify-bot-woocommerce/notify-bot-woocommerce.php', [
			$this,
			'add_action_links'
		] );
		add_filter( 'woocommerce_product_variation_title_include_attributes', '__return_true' );
		add_filter( 'woocommerce_is_attribute_in_product_name', '__return_false' );
		add_action( 'before_woocommerce_init', [ $this, 'HPOS_compatible' ] );
	}

	function woocommerceNotice() {
		$message = __( 'Please activate woocommerce on your wp installation in order to use Notify Bot for WooCommerce plugin', 'notify-bot-woocommerce' );
		echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
	}

	public function add_action_links( $actions ) {
		$configure = __( 'Configure', 'notify-bot-woocommerce' );
		$url       = admin_url( 'admin.php?page=wc-settings&tab=wootb' );
		$actions[] = "<a href=\"$url\">$configure</a>";

		return $actions;
	}

	public function loadHooks() {
		$this->initTelegramBot();
		if ( version_compare( get_option( 'wootb_version', '0.0.0' ), WOOTB_PLUGIN_VERSION, '<' ) ) {
			$this->update();
		}
		add_action( 'wp_ajax_wootb_send_test_message', [ $this, 'sendTestMessage' ] );
		add_filter( 'woocommerce_get_settings_pages', [ $this, 'addWooSettingSection' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_script' ] );
		add_action( 'woocommerce_update_order', [ $this, 'woocommerce_new_order' ] );
		add_action( 'woocommerce_new_order', [ $this, 'woocommerce_new_order' ] );
//		add_action( 'save_post_shop_order', [ $this, 'sendUpdatesToBot' ] );
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'woocommerce_new_order' ] );
//		add_action( 'woocommerce_checkout_order_processed', [ $this, 'sendUpdatesToBot' ] );
		add_action( 'woocommerce_order_status_changed', [ $this, 'woocommerce_new_order' ] );
//		add_action( 'woocommerce_order_status_changed', [ $this, 'sendUpdatesToBot' ] );
//		add_filter( 'cron_schedules', [ $this, 'add_minute_cron' ] );
		add_action( 'admin_action_remove_wootb_user', [ $this, 'remove_wootb_user' ] );
		add_action( 'shutdown', [ $this, 'sendUpdatesToBot' ] );
	}

	public function HPOS_compatible() {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', WOOTB_PLUGIN_FILE );
		}
	}

	private function initTelegramBot() {
		$this->telegram = new TelegramAdaptor( get_option( 'wootb_setting_token' ) );
		$use_proxy      = get_option( 'wootb_use_proxy', false );
		$this->telegram->use_proxy( $use_proxy );
		TelegramAPI::getInstance()->setAdaptor( $this->telegram );
	}

	public function update() {
		$old_version = get_option( 'wootb_version', '0.0.0' );
		if ( $old_version != WOOTB_PLUGIN_VERSION ) {
			update_option( 'wootb_version', WOOTB_PLUGIN_VERSION );
			update_option( 'wootb_setting_otp', md5( time() ) );
		}
	}

	public function registerUser( $chat ) {
		$users              = json_decode( get_option( 'wootb_setting_users' ), true );
		$users[ $chat->id ] = [
			'id'      => $chat->id,
			'uname'   => $chat->username ?? '',
			'fname'   => $chat->first_name ?? $chat->title,
			'lname'   => $chat->last_name ?? '',
			'enabled' => true
		];
		update_option( 'wootb_setting_users', wp_json_encode( $users ) );
	}

	public function remove_wootb_user() {
		$uid = intval( $_REQUEST['uid'] );
		$this->unregisterUser( $uid );
		wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=wootb&section=users' ) );
		exit;
	}

	public function unregisterUser( $chat_id ) {
		$users = json_decode( get_option( 'wootb_setting_users' ), true );
		unset( $users[ $chat_id ] );
		update_option( 'wootb_setting_users', wp_json_encode( $users ) );
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
				'display'  => __( 'Every 1 Minute', 'notify-bot-woocommerce' ),
			];
		}

		return $schedules;
	}

	public function admin_enqueue_script() {
		wp_enqueue_script( 'wootb_admin_script', plugin_dir_url( __FILE__ ) . '../assets/js/admin.js', array( 'jquery' ), '1.5', true );
		wp_register_style( 'wootb_css_script', plugin_dir_url( __FILE__ ) . '../assets/css/admin.css', false, '1.5' );
		wp_enqueue_style( 'wootb_css_script' );
	}

	public function sendTestMessage() {
		try {
			$this->sendToAll( [], __( 'This is a test message from Notify Bot for WooCommerce Plugin!', 'notify-bot-woocommerce' ) );
			echo wp_json_encode( [
				'error'   => 0,
				'message' => __( 'Message successfully sent', 'notify-bot-woocommerce' )
			] );
			wp_die();
		} catch ( \Exception $ex ) {
			echo wp_json_encode( [ 'error' => 1, 'message' => $ex->getMessage() ] );
			wp_die();
		}
	}

	public function sendToAll( $messageIds, $text, $keyboard = null ) {
		$chats = json_decode( get_option( 'wootb_setting_users' ), true );
		$md5   = md5( $text );
		foreach ( $chats as $id => $chat ) {
			$message = null;
			$tries   = 0;
			do {
				try {
					if ( isset( $messageIds[ $id ] ) ) {
						$msg = explode( ',', $messageIds[ $id ] );
						if ( isset( $msg[1] ) && $msg[1] == $md5 ) {
							break;
						}
						$message = $this->telegram->updateMessage( $id, $msg[0], $text, $keyboard );
						if ( isset( $message->ok ) && $message->ok ) {
							break;
						} elseif ( ! isset( $message->error_code ) || $message->error_code != 400 ) {
							continue;
						}
						unset( $messageIds[ $id ], $message->ok );
					}
					$message = $this->telegram->sendMessage( $id, $text, $keyboard );
				} catch ( Exception $exception ) {
					trigger_error( $exception->getMessage(), 512 );
				}
			} while ( ! isset( $message->ok ) && $tries ++ < 3 );
			if ( isset( $message->result->message_id ) ) {
				$messageIds[ $id ] = "{$message->result->message_id},$md5";
			}

		}

		return $messageIds;
	}

	public function woocommerce_new_order( $order_id ) {
		$this->addToUpdateQueue( $order_id );
		ob_flush();
		flush();
//		$this->sendUpdatesToBot();
//		get_headers( site_url( "/wp-json/wootb/telegram/sendmsgs" ) );
	}

	public function addToUpdateQueue( $order_id ) {
		ignore_user_abort( true );
//		$this->get_queue();
		if ( ! in_array( $order_id, $this->queue ) ) {
			$this->queue[] = $order_id;
		}
//		$this->set_queue();
	}

	public function deactivate() {
		$this->unschedule_events();
	}

	public function unschedule_events() {
		$timestamp = wp_next_scheduled( 'wootb_send_updates' );
		wp_unschedule_event( $timestamp, 'wootb_send_updates' );
	}

	public function sendUpdatesToBot() {
		if ( ! $this->queue ) {
			$this->get_queue();
			if ( ! $this->queue ) {
				return;
			}
			ignore_user_abort( true );
			$queue       = $this->queue;
			$this->queue = [];
			$this->set_queue();
		} else {
			$queue = $this->queue;
		}
		$chatIds   = json_decode( get_option( 'wootb_setting_users' ), true );
		$chatCount = count( $chatIds );
		foreach ( $queue as $key => $order_id ) {
			$sentCount = count( $this->sendUpdateToBot( $order_id ) );
			if ( $chatCount <= $sentCount ) {
				unset( $queue[ $key ] );
			}
		}
		$this->queue = $queue;
		$this->set_queue();

	}

	public function get_queue() {
		$this->queue = json_decode( get_option( 'wootb_send_queue', "[]" ), true );

		return $this->queue;
	}

	public function set_queue() {
		$this->queue = array_values( $this->queue );
		update_option( 'wootb_send_queue', wp_json_encode( $this->queue ) );
	}

	public function sendUpdateToBot( $order_id ) {
		$wc   = new WooCommerceAdaptor( $order_id );
		$text = $wc->interpolate( self::getTemplate() );

		$messageIds = json_decode( $wc->order->get_meta( 'WooTelegramMessageIds' ) ?: "[]", true );
		$status     = $wc->get_status();
		if ( isset( $text ) ) {
			$remove_buttons  = get_option( 'wootb_remove_btn_statuses', false );
			$update_statuses = get_option( 'wootb_update_statuses', false );
			$update_always   = ! $update_statuses;
			if ( $update_always || in_array( $status, $update_statuses ) ) {
				$keyboard       = new TelegramKeyboard( 2 );
				$status_buttons = [
					'processing'      => self::STATUS_CANCEL | self::STATUS_COMPLETE | self::STATUS_REFUND,
					'cancelled'       => self::STATUS_PROCESS | self::STATUS_REFUND,
					'refunded'        => self::STATUS_PROCESS,
					'completed'       => self::STATUS_PROCESS,
					'pending payment' => self::STATUS_PROCESS | self::STATUS_CANCEL,
					'pending'         => self::STATUS_PROCESS | self::STATUS_CANCEL,
					'on-hold'         => self::STATUS_PROCESS | self::STATUS_CANCEL,
				];
				if ( $status != 'completed' || ! $remove_buttons ) {
					if ( $status != 'processing' ) {
						$keyboard->add_inline_callback_button( '🕙 ' . __( 'Process', 'notify-bot-woocommerce' ), [
							"cmd" => "status",
							"oid" => $order_id,
							"st"  => 2
						] );
					}
					if ( isset( $status_buttons[ $status ] ) ) {
						if ( $status_buttons[ $status ] & self::STATUS_CANCEL ) {
							$keyboard->add_inline_callback_button( '❌ ' . __( 'Cancel', 'notify-bot-woocommerce' ), [
								"cmd" => "status",
								"oid" => $order_id,
								"st"  => 3
							] );
						}
						if ( $status_buttons[ $status ] & self::STATUS_REFUND ) {
							$keyboard->add_inline_callback_button( '💸 ' . __( 'Refund', 'notify-bot-woocommerce' ), [
								"cmd" => "status",
								"oid" => $order_id,
								"st"  => 1
							] );
						}
						if ( $status_buttons[ $status ] & self::STATUS_COMPLETE ) {
							$keyboard->add_inline_callback_button( '✅ ' . __( 'Complete', 'notify-bot-woocommerce' ), [
								"cmd" => "status",
								"oid" => $order_id,
								"st"  => 0
							] );
						}
					}
				}
				$messageIds = $this->sendToAll( $messageIds, $text, $keyboard );
				$wc->order->update_meta_data( 'WooTelegramMessageIds', wp_json_encode( $messageIds ) );
				$wc->order->save();
			}
		}

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
		], get_option( 'wootb_setting_template', file_get_contents( WOOTB_PLUGIN_DIR . '/views/defaultMessage.php' ) ) );
	}

	public function addWooSettingSection( $settings ) {
		$settings[] = new OptionPanel( $this->telegram );

		return $settings;
	}
}