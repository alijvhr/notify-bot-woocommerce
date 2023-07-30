<?php

namespace WoocommerceTelegramBot\classes;

class TelegramAdaptor {

	public $chatID;
	public $token;
	public $parseMode;
	public $accessTags;

	public function __construct() {
		$this->chatID     = '';
		$this->token      = '';
		$this->parseMode  = 'HTML';
		$this->accessTags = '<b><strong><i><u><em><ins><s><strike><del><a><code><pre>';
	}

	public function sendMessage( $text ) {
		$text = strip_tags( $text, $this->accessTags );

		$chatIds = explode( ',', $this->chatID );

		if ( is_array( $chatIds ) && count( $chatIds ) > 1 ) {
			foreach ( $chatIds as $chatId ) {
				$this->request( $chatId, $text );
			}
		} else {
			$this->request( $this->chatID, $text );
		}
	}

	private function request( $chatId, $text ) {
		$data = [
			'chat_id'    => $chatId,
			'text'       => stripcslashes( html_entity_decode( $text ) ),
			'parse_mode' => $this->parseMode,
        ];

        $data=http_build_query($data);

        $return = wp_remote_get( "https://tlbot.devnow.ir/bot$this->token/sendMessage?$data", [
            'timeout'     => 5,
            'redirection' => 5,
            'blocking'    => false
        ]);

		if ( is_wp_error( $return ) ) {
			return json_encode( [ 'ok' => false, 'curl_error_code' => $return->get_error_message() ] );
		} else {
			return json_decode( $return['body'], true );
		}
	}
}