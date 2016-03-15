<?php

namespace Core\Db\Statement\Db2;

class Exception extends \Core\Db\Statement\Exception {
	/**
	 *
	 * @var string
	 */
	protected $code = '00000';
	
	/**
	 *
	 * @var string
	 */
	protected $message = 'unknown exception';
	
	/**
	 *
	 * @param string $msg        	
	 * @param string $state        	
	 */
	function __construct($msg = 'unknown exception', $state = '00000') {
		$this->message = $msg;
		$this->code = $state;
	}
}

