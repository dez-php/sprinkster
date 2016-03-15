<?php

namespace Core\Shell;

class Argv {
	private static $_instance;
	private $argv = array ();
	
	/**
	 *
	 * @param array $options        	
	 * @return \Core\Shell\Argv
	 */
	public static function getInstance($options = array()) {
		if (self::$_instance == null) {
			self::$_instance = new self ( $options );
		}
		return self::$_instance;
	}
	public function __construct() {
	}
	public static function set($argv) {
		self::getInstance ();
		if (is_array ( $argv )) {
			self::$_instance->argv = self::$_instance->Args ( $argv );
		}
		return self::$_instance;
	}
	public static function isCmd() {
		self::getInstance ();
		return self::$_instance->argv ? true : false;
	}
	public static function get($key = null) {
		self::getInstance ();
		if ($key === null) {
			return self::$_instance->argv;
		} else {
			return isset ( self::$_instance->argv [$key] ) ? self::$_instance->argv [$key] : '';
		}
	}
	
	/* cmd part */
	public function Args($argv) {
		array_shift ( $argv );
		$out = array ();
		foreach ( $argv as $arg ) {
			if (substr ( $arg, 0, 2 ) == '--') {
				$eqPos = strpos ( $arg, '=' );
				if ($eqPos === false) {
					$key = substr ( $arg, 2 );
					$out [$key] = isset ( $out [$key] ) ? $out [$key] : true;
				} else {
					$key = substr ( $arg, 2, $eqPos - 2 );
					$out [$key] = substr ( $arg, $eqPos + 1 );
				}
			} else if (substr ( $arg, 0, 1 ) == '-') {
				if (substr ( $arg, 2, 1 ) == '=') {
					$key = substr ( $arg, 1, 1 );
					$out [$key] = substr ( $arg, 3 );
				} else {
					$chars = str_split ( substr ( $arg, 1 ) );
					foreach ( $chars as $char ) {
						$key = $char;
						$out [$key] = isset ( $out [$key] ) ? $out [$key] : true;
					}
				}
			} else {
				$out [] = $arg;
			}
		}
		return $out;
	}
}