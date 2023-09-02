<?php

namespace WoocommerceTelegramBot\classes;

class TelegramKeyboard implements \JsonSerializable {

	protected $buttons = [];

	protected $row = 0;

	protected $cols = 2;

	public function __construct( $cols = 2 ) {
		$this->cols = $cols;
	}

	public function add_inline_callback_button( $text, $callback_data ) {
		if ( count( $this->buttons ) >= $this->cols ) {
			$this->row ++;
		}
		if ( is_array( $callback_data ) || is_object( $callback_data ) ) {
			$callback_data = json_encode( $callback_data );
		}
		$button = [ "text" => $text, "callback_data" => $callback_data ];
		if ( ! isset( $this->buttons[ $this->row ] ) ) {
			$this->buttons[ $this->row ] = [ $button ];
		} else {
			$this->buttons[ $this->row ][] = $button;
		}
	}

	public function set_cols( $cols = 2 ) {
		$this->cols = $cols;
	}

	public function get_keyboard() {
		return [ "inline_keyboard" => $this->buttons ];
	}

	public function jsonSerialize() {
		return $this->get_keyboard();
	}
}