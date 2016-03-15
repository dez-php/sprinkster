<?php

namespace Core\Db\Adapter\Db2;

class Exception extends \Core\Db\Adapter\Exception {
	protected $code = '00000';
	protected $message = 'unknown exception';
	function __construct($message = 'unknown exception', $code = '00000', Exception $e = null) {
		parent::__construct ( $message, $code, $e );
	}
}
