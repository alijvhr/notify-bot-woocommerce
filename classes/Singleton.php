<?php

namespace WoocommerceTelegramBot\classes;

abstract class Singleton
{

    protected static $instance = null;

    private final function __construct()
    {
        $this->init();
    }

    abstract function init();

    public static function getInstance(): Singleton
    {
        return self::$instance ?? new static();

    }

    private final function __clone()
    {
    }
}