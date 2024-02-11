<?php

namespace WOOTB\includes;

class TelegramKeyboard implements \JsonSerializable
{

    protected $buttons = [];

    protected $cols = 2;

    public function __construct($cols = 2)
    {
        $this->cols = $cols;
    }

    public function add_inline_callback_button($text, $callback_data)
    {
        if (is_array($callback_data) || is_object($callback_data)) {
            $callback_data = wp_json_encode($callback_data);
        }
        $button = ["text" => $text, "callback_data" => $callback_data];
        $this->buttons[] = $button;
    }

    public function set_cols($cols)
    {
        $this->cols = $cols;
    }

    public function jsonSerialize()
    {
        return $this->get_keyboard();
    }

    public function get_keyboard()
    {
        $inline_buttons = [];
        $row = -1;
        $count = 0;
        foreach ($this->buttons as $button) {
            if ($count++ % $this->cols == 0) $inline_buttons[++$row] = [];
            $inline_buttons[$row][] = $button;
        }
        return ["inline_keyboard" => $inline_buttons];
    }
}