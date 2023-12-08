<?php

namespace WOOTB\includes;

abstract class Singleton {

	protected static $instances = [];

	private final function __construct() {
		$this->init();
	}

	abstract function init();

	public static function getInstance(): Singleton {
		$cls = static::class;
		if (!isset(self::$instances[$cls])) {
			self::$instances[$cls] = new static();
		}

		return self::$instances[$cls];

	}

	private final function __clone() {
	}
}