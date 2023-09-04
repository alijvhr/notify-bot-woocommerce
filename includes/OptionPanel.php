<?php

namespace WoocommerceTelegramBot\includes;

class OptionPanel extends \WC_Settings_Page {

	/**
	 * @var TelegramAdaptor
	 */
	protected $telegram;
	protected $current_section;

	public function __construct( $telegram ) {
		global $current_section;

		$this->id              = 'wootb';
		$this->label           = __( 'Telegram bot', 'telegram-bot-for-woocommerce' );
		$this->current_section = $current_section;
		$this->telegram        = $telegram;
		parent::__construct();
	}

	function get_own_sections() {
		return [
			''         => __( 'telegram', 'telegram-bot-for-woocommerce' ),
			'register' => __( 'register', 'telegram-bot-for-woocommerce' ),
			'users'    => __( 'users', 'telegram-bot-for-woocommerce' ),
			'template' => __( 'message', 'telegram-bot-for-woocommerce' )
		];


	}

	public function get_settings_for_default_section() {
		return [
			'section_title_1' => [
				'name' => __( 'Telegram Configuration', 'telegram-bot-for-woocommerce' ),
				'type' => 'title',
				'id'   => 'wc_settings_tab_wootb_title'
			],
			'token'           => [
				'name'     => __( 'bot token', 'telegram-bot-for-woocommerce' ),
				'type'     => 'text',
				'id'       => 'wootb_setting_token',
				'desc_tip' => true,
				'desc'     => __( 'Enter your bot token', 'telegram-bot-for-woocommerce' )
			],
			'use_proxy'       => [
				'name'     => __( 'use proxy', 'telegram-bot-for-woocommerce' ),
				'type'     => 'checkbox',
				'id'       => 'wootb_use_proxy',
				'desc_tip' => false,
				'desc'     => __( "It is particularly useful if your server is located in a country that has banned Telegram.", 'telegram-bot-for-woocommerce' )
			],
			'section_end'     => [
				'type' => 'sectionend',
				'id'   => 'wc_settings_tab_wootb_end_section'
			],
		];
	}

	public function get_settings_for_register_section() {
		$otp          = get_option( 'wootb_setting_otp' );
		$bot_uname    = get_option( 'wootb_bot_username' );
		$link         = "https://t.me/$bot_uname?start=$otp";
		$encoded_link = urlencode( $link );

		return [
			'link_title'    => [
				'name' => __( 'Registration link (bot start)', 'telegram-bot-for-woocommerce' ),
				'id'   => 'wc_settings_tab_wootb_title_2',
				'type' => 'title'
			],
			'register_link' => [
				'type' => 'info',
				'text' => "<a href=\"$link\">$link</a>"
			],
			'register_qr'   => [
				'type' => 'info',
				'text' => "<a href=\"$link\"><img src=\"https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=$encoded_link&choe=UTF-8\"></a>"
			],
			'section_end'   => [
				'type' => 'sectionend',
				'id'   => 'wc_settings_tab_wootb_end_section_2'
			],
		];
	}

	public function get_settings_for_users_section() {
		return [
			'section_title_1' => [
				'name' => __( 'Users management', 'telegram-bot-for-woocommerce' ),
				'type' => 'title',
				'id'   => 'wc_settings_tab_wootb_title_1',
				'desc' => $this->render_users_table()
			],
			'section_end'     => [
				'type' => 'sectionend',
				'id'   => 'wc_settings_tab_wootb_end_section_2'
			],
		];
	}

	public function render_users_table() {
		$users   = json_decode( get_option( 'wootb_setting_users' ), true );
		$headers = [
			__( 'ID', 'telegram-bot-for-woocommerce' ),
			__( 'username', 'telegram-bot-for-woocommerce' ),
			__( 'first name', 'telegram-bot-for-woocommerce' ),
			__( 'last name', 'telegram-bot-for-woocommerce' ),
			__( 'remove', 'telegram-bot-for-woocommerce' )
		];
		$table   = "<table class=\"wootb-table\"><tr><th>$headers[0]</th><th>$headers[1]</th><th>$headers[2]</th><th>$headers[3]</th><th>$headers[4]</th></tr>";
		foreach ( $users as $uid => $user ) {
			$remove_url = admin_url( "admin.php?action=remove_wootb_user&uid=$uid" );
			$table      .= "<tr><td>{$user['id']}</td><td>{$user['uname']}</td><td>{$user['fname']}</td><td>{$user['lname']}</td><td><a href=\"$remove_url\"><span class=\"dashicons dashicons-trash\"></span></a></td></tr>";
		}
		$table .= "</table>";

		return $table;
	}

	public function get_settings_for_template_section() {
		return [
			'section_title_1'  => [
				'name' => __( 'Message settings', 'telegram-bot-for-woocommerce' ),
				'type' => 'title',
				'id'   => 'wc_settings_tab_wootb_title_1'
			],
			'message_template' => [
				'name'              => __( 'template', 'telegram-bot-for-woocommerce' ),
				'type'              => 'textarea',
				'id'                => 'wootb_setting_template',
				'class'             => 'code',
				'css'               => 'max-width:550px;width:100%;',
				'default'           => file_get_contents( WOOTB_PLUGIN_DIR . '/views/default-msg.php' ),
				'custom_attributes' => [ 'rows' => 10 ],
			],
			'section_end'      => [
				'type' => 'sectionend',
				'id'   => 'wc_settings_tab_wootb_end_section_2'
			],
		];
	}

	public function save() {
		parent::save();
		if ( in_array( $this->current_section, [ '', 'register' ] ) ) {
			Initializer::getInstance()->update();
			$response = $this->telegram->setWebhook( site_url( "/wp-json/wootb/telegram/hook" ) );
			if ( $response->ok ) {
				$info = $this->telegram->getInfo();
				update_option( 'wootb_bot_username', $info->result->username );
				update_option( 'wootb_setting_otp', md5( time() ) );
			}
		}
	}
}