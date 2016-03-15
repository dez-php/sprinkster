<?php

namespace Core\Locale;

class Translate extends AbstractTranslate {
	public function getTranslate($namespace) {
		return $this;
	}
	public function toString($message) {
		return isset ( self::$_data [$this->_namespace] [$message] ) ? self::$_data [$this->_namespace] [$message] : $message;
	}
	public function _($message) {
		return $this->toString ( $message );
	}
}