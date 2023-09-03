<?php

namespace WoocommerceTelegramBot\classes;

class OptionPanel extends \WC_Settings_Page {

	/**
	 * @var TelegramAdaptor
	 */
	protected $telegram;
	protected $current_section;

	public function __construct( $telegram ) {
		global $current_section;

		$this->id              = 'wootb';
		$this->label           = __( 'Telegram bot', 'woo-telegram-bot' );
		$this->current_section = $current_section;
		$this->telegram        = $telegram;
		parent::__construct();
	}

	function get_own_sections() {
		return [
			''         => __( 'telegram', 'woo-telegram-bot' ),
			'register' => __( 'register', 'woo-telegram-bot' ),
			'users'    => __( 'users', 'woo-telegram-bot' ),
			'template' => __( 'message', 'woo-telegram-bot' )
		];


	}

	public function get_settings_for_default_section() {
		return [
			'section_title_1' => [
				'name' => __( 'Telegram Configuration', 'woo-telegram-bot' ),
				'type' => 'title',
				'id'   => 'wc_settings_tab_wootb_title'
			],
			'token'           => [
				'name'     => __( 'bot token', 'woo-telegram-bot' ),
				'type'     => 'text',
				'id'       => 'wootb_setting_token',
				'desc_tip' => true,
				'desc'     => __( 'Enter your bot token', 'woo-telegram-bot' )
			],
			'use_proxy'       => [
				'name'     => __( 'use proxy', 'woo-telegram-bot' ),
				'type'     => 'checkbox',
				'id'       => 'wootb_use_proxy',
				'desc_tip' => false,
				'desc'     => __( "It is particularly useful if your server is located in a country that has banned Telegram.", 'woo-telegram-bot' )
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
				'name' => __( 'Link to register', 'woo-telegram-bot' ),
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
				'name' => __( 'Users management', 'woo-telegram-bot' ),
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
		$users = json_decode( get_option( 'wootb_setting_users' ), true );
		$table = "<table class='wootb-table'><tr><th>id</th><th>username</th><th>first name</th><th>last name</th><th>remove</th></tr>";
		foreach ( $users as $user ) {
			$table .= "<tr><td>{$user['id']}</td><td>{$user['uname']}</td><td>{$user['fname']}</td><td>{$user['lname']}</td><td>{$user['lname']}</td></tr>";
		}
		$table .= "</table>";

		return $table;
	}

	public function get_settings_for_template_section() {
		return [
			'section_title_1'  => [
				'name' => __( 'Message settings', 'woo-telegram-bot' ),
				'type' => 'title',
				'id'   => 'wc_settings_tab_wootb_title_1'
			],
			'message_template' => [
				'name'              => __( 'template', 'woo-telegram-bot' ),
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