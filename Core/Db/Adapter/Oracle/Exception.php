<?php

namespace Core\Db\Adapter\Oracle;

class Exception extends \Core\Db\Adapter\Exception {
	protected $message = 'Unknown exception';
	protected $code = 0;
	function __construct($error = null, $code = 0) {
		if (is_array ( $error )) {
			if (! isset ( $error ['offset'] )) {
				$this->message = $error ['code'] . ' ' . $error ['message'];
			} else {
				$this->message = $error ['code'] . ' ' . $error ['message'] . " " . substr ( $error ['sqltext'], 0, $error ['offset'] ) . "*" . substr ( $error ['sqltext'], $error ['offset'] );
			}
			$this->code = $error ['code'];
		} else if (is_string ( $error )) {
			$this->message = $error;
		}
		if (! $this->code && $code) {
			$this->code = $code;
		}
	}
}
