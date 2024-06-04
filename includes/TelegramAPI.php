<?php

namespace WOOTB\includes;

class TelegramAPI extends Singleton {
	/**
	 * @var TelegramAdaptor $adaptor
	 */
	protected $adaptor;

	public function init() {
		add_action( 'rest_api_init', array( $this, 'rest_routes' ) );
	}

	public function setAdaptor( $adaptor ) {
		$this->adaptor = $adaptor;
	}

	public function rest_routes() {
		register_rest_route( 'wootb/telegram', "/hook", [
			[
				'methods'             => \WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'update' ],
				'permission_callback' => '__return_true',
				'args'                => []
			]
		] );
		register_rest_route( 'wootb/telegram', "/sendmsgs", [
			[
				'methods'             => \WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'sendmsgs' ],
				'permission_callback' => '__return_true',
				'args'                => []
			]
		] );
	}


	public function update(): \WP_REST_Response {
		$object = json_decode( \WP_REST_Server::get_raw_data() );
		if ( isset( $object->callback_query ) ) {
			$this->callback( $object );
		} elseif ( isset( $object->message->reply_to_message->message_id ) ) {
			$this->reply( $object );
		} elseif ( substr( $object->message->text ?? '', 0, 1 ) == '/' ) {
			$this->command( $object );
		} else {
			$this->simple( $object );
		}

		return new \WP_REST_Response( $object, 200 );
	}

	public function callback( $object ) {
		$data  = json_decode( $object->callback_query->data );
		$stats = [ 'completed', 'refunded', 'processing', 'cancelled' ];
		switch ( $data->cmd ) {
			case 'status':
				$order = new \WC_Order( $data->oid );
				$order->update_status( $stats[ $data->st ] );
				$notif = 'status changed successfully...';
				break;
		}
		$this->adaptor->callback( $object->callback_query->id, $notif );
	}

	public function reply( $object ) {
//        $object->message = ;

	}

	public function command( $object ) {
		preg_match( '/^\/(\w++)((?:\S++)?)\s++(.++)$/ism', $object->message->text, $matches );
		$cmd = $matches[1];
		$arg = $matches[3];
		switch ( $cmd ) {
			case 'start':
				$otp = get_option( 'wootb_setting_otp' );
				if ( $arg == $otp ) {
					$chat = $object->message->chat;
					Initializer::getInstance()->registerUser( $chat );
				}
				break;
		}
	}

	public function simple( $object ) {

	}
}