<?php

namespace WOOTB\includes;

class TelegramAdaptor {

	private $tel_url = 'https://api.telegram.org';
	/**
	 * @var string
	 */
	private $token = '';
	/**
	 * @var string
	 */
	private $accessTags = '<b><strong><i><u><em><ins><s><strike><del><a><code><pre>';

	private $reqUrl = '';

	public function __construct( $token ) {
		$this->token = $token;
	}

	public function sendMessage( $chatId, $text, $keyboard = null ) {
		$data = $this->prepare( $chatId, $text, $keyboard );

		return $this->request( 'sendMessage', $data );
	}

	public function prepare( $chatId, $text, $keyboard = null ): array {
		$text = strip_tags( $text, $this->accessTags );
		$data = [ 'chat_id'                     => $chatId,
		          'text'                        => $text,
		          'allow_sending_without_reply' => true,
		          'parse_mode'                  => 'HTML'
		];
		if ( $keyboard ?? 0 ) {
			$data['reply_markup'] = wp_json_encode( $keyboard );
		}

		return $data;
	}

	public function request( $method, $data = [] ) {
		if(!$this->token) return new \stdClass();
		if ( ! $this->reqUrl ) {
			$this->reqUrl = "$this->tel_url/bot$this->token";
		}
		$data = http_build_query( $data );

		$response = wp_remote_get( "$this->reqUrl/$method?$data", [ 'timeout', 1 ] );
//		if ( wp_remote_retrieve_response_code( $response ) != 200 ) {
//			throw new \Exception( 'Cannot connect to telegram!' );
//		}

		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	public function use_proxy( $use = true ) {
		$this->tel_url = $use ? 'https://tlbot.devnow.ir' : 'https://api.telegram.org';
	}

	public function updateMessage( $chatId, $message_id, $text, $keyboard = null ) {
		$data               = $this->prepare( $chatId, $text, $keyboard );
		$data['message_id'] = $message_id;
		return $this->request( 'editMessageText', $data );
	}

	public function callback( $callbackQueryId, $text ) {
		$data = [ 'callback_query_id' => $callbackQueryId, 'text' => $text ];

		return $this->request( 'answerCallbackQuery', $data );
	}

	public function setWebhook( $url ) {
		return $this->request( 'setWebhook', [ 'url' => $url ] );
	}

	public function getInfo() {
		return $this->request( 'getMe' );
	}
}