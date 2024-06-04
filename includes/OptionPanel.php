<?php

namespace WOOTB\includes;

use WC_Admin_Settings;
use WC_Settings_Page;

class OptionPanel extends WC_Settings_Page {

	/**
	 * @var TelegramAdaptor
	 */
	protected $telegram;
	protected $current_section;

	public function __construct( $telegram ) {
		global $current_section;

		$this->id              = 'wootb';
		$this->label           = __( 'Telegram bot', 'notify-bot-woocommerce' );
		$this->current_section = $current_section;
		$this->telegram        = $telegram;
		parent::__construct();
	}

	function get_own_sections() {
		$tabs = get_option( 'wootb_bot_username', false ) ? [
			'register' => __( 'register', 'notify-bot-woocommerce' ),
			'users'    => __( 'users', 'notify-bot-woocommerce' ),
			'template' => __( 'message', 'notify-bot-woocommerce' )
		] : [];

		return [
			       '' => __( 'telegram', 'notify-bot-woocommerce' ),
		       ] + $tabs;


	}

	public function get_settings_for_default_section() {
		return [
			'section_title_1' => [
				'name' => __( 'Telegram Configuration', 'notify-bot-woocommerce' ),
				'type' => 'title',
				'id'   => 'wc_settings_tab_wootb_title'
			],
			'token'           => [
				'name'     => __( 'bot token', 'notify-bot-woocommerce' ),
				'type'     => 'text',
				'id'       => 'wootb_setting_token',
				'desc_tip' => true,
				'desc'     => __( 'Enter your bot token', 'notify-bot-woocommerce' )
			],
			'use_proxy'       => [
				'name'     => __( 'use proxy', 'notify-bot-woocommerce' ),
				'type'     => 'checkbox',
				'id'       => 'wootb_use_proxy',
				'desc_tip' => false,
				'desc'     => __( "It is particularly useful if your server is located in a country that has banned Telegram.", 'notify-bot-woocommerce' )
			],
			'section_end'     => [
				'type' => 'sectionend',
				'id'   => 'wc_settings_tab_wootb_end_section'
			],
		];
	}

	public function get_settings_for_register_section() {
		$otp        = get_option( 'wootb_setting_otp' );
		$bot_uname  = get_option( 'wootb_bot_username' );
		$link       = "https://t.me/$bot_uname?start=$otp";
		$link_group = "https://t.me/$bot_uname?startgroup=$otp";
		if ( extension_loaded( 'gd' ) && function_exists( 'gd_info' ) ) {
			$file_link  = wp_upload_dir() . '/../wootb_link.png';
			$file_group = wp_upload_dir() . '/../wootb_group.png';
			QRcode::png( $link, $file_link, QR_ECLEVEL_H, 7 );
			$qr = base64_encode( file_get_contents( $file_link ) );
			QRcode::png( $link_group, $file_group, QR_ECLEVEL_H, 7 );
			$group_qr = base64_encode( file_get_contents( $file_group ) );
		} else {
			$qr = $group_qr = '';
		}

		return [
			'link_title'    => [
				'name' => __( 'Registration link (bot start)', 'notify-bot-woocommerce' ),
				'id'   => 'wc_settings_tab_wootb_title_2',
				'type' => 'title'
			],
			'register_link' => [
				'name' => __( 'Bot start link', 'notify-bot-woocommerce' ),
				'type' => 'info',
				'text' => "<a href=\"$link\">$link</a>"
			],
			'register_qr'   => [
				'type' => 'info',
				'text' => "<a href=\"$link\"><img src=\"data:image/png;base64,$qr\"></a>"
			],
			'group_link'    => [
				'name' => __( 'Bot in group start link', 'notify-bot-woocommerce' ),
				'type' => 'info',
				'text' => "<a href=\"$link_group\">$link_group</a>"
			],
			'group_qr'      => [
				'type' => 'info',
				'text' => "<a href=\"$link_group\"><img src=\"data:image/png;base64,$group_qr\"></a>"
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
				'name' => __( 'Users management', 'notify-bot-woocommerce' ),
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
		$users   = json_decode( get_option( 'wootb_setting_users', '[]' ), true );
		$headers = [
			__( 'ID', 'notify-bot-woocommerce' ),
			__( 'username', 'notify-bot-woocommerce' ),
			__( 'first name', 'notify-bot-woocommerce' ),
			__( 'last name', 'notify-bot-woocommerce' ),
			__( 'remove', 'notify-bot-woocommerce' )
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
			'section_title_1'          => [
				'name' => __( 'Message settings', 'notify-bot-woocommerce' ),
				'type' => 'title',
				'id'   => 'wc_settings_tab_wootb_title_1'
			],
			'message_template'         => [
				'name'              => __( 'template', 'notify-bot-woocommerce' ),
				'type'              => 'textarea',
				'id'                => 'wootb_setting_template',
				'class'             => 'code',
				'css'               => 'max-width:550px;width:100%;',
				'default'           => file_get_contents( WOOTB_PLUGIN_DIR . '/views/defaultMessage.php' ),
				'custom_attributes' => [ 'rows' => 10 ],
			],
			'link_products'            => [
				'name'    => __( 'Link products to', 'notify-bot-woocommerce' ),
				'type'    => 'select',
				'id'      => 'wootb_link_mode',
				'options' => [
					'none' => __( 'none', 'notify-bot-woocommerce' ),
					'edit' => __( 'edit', 'notify-bot-woocommerce' ),
					'view' => __( 'view', 'notify-bot-woocommerce' )
				],
			],
			'remove_buttons_on_status' => [
				'name'     => __( 'Remove btn on status', 'notify-bot-woocommerce' ),
				'type'     => 'multiselect',
				'id'       => 'wootb_remove_btn_statuses',
				'options'  => wc_get_order_statuses(),
				'class'    => 'wc-enhanced-select',
				'desc_tip' => true,
				'desc'     => __( "Remove message keyboards on order complete.", 'notify-bot-woocommerce' )
			],
			'update_on_status'         => [
				'name'     => __( 'Send on specific status', 'notify-bot-woocommerce' ),
				'type'     => 'multiselect',
				'id'       => 'wootb_update_statuses',
				'options'  => wc_get_order_statuses(),
				'class'    => 'wc-enhanced-select',
				'desc_tip' => true,
				'desc'     => __( "Remove message keyboards on order complete.", 'notify-bot-woocommerce' )
			],
			'section_end'              => [
				'type' => 'sectionend',
				'id'   => 'wc_settings_tab_wootb_end_section_2'
			],
		];
	}

	public function save() {
		$old_token = get_option( 'wootb_setting_token', 0 );
		parent::save();
		$new_token = get_option( 'wootb_setting_token', 0 );
		$time      = get_option( 'wootb_bot_update_time', 0 );
		if ( $time < time() - 3600 || $old_token != $new_token ) {
			$this->telegram = new TelegramAdaptor( $new_token );
			$response       = $this->telegram->setWebhook( site_url( "/wp-json/wootb/telegram/hook" ) );
			$uname          = '';
			if ( $response->ok ) {
				$info  = $this->telegram->getInfo();
				$uname = $info->result->username;
				update_option( 'wootb_bot_update_time', time() );
			} else {
				$desc = $response->description ?? '';
				WC_Admin_Settings::add_error( __( 'Error on validating bot api_key. ', 'notify-bot-woocommerce' ) . $desc );
			}
			update_option( 'wootb_bot_username', $uname );
			if(!$uname) update_option( 'wootb_setting_users', '[]' );
			update_option( 'wootb_setting_otp', md5( time() ) );
		} else {
			$desc = $response->description ?? '';
			WC_Admin_Settings::add_error( __( 'Error on validating bot api_key. ', 'notify-bot-woocommerce' ) . $desc );
		}
	}
}