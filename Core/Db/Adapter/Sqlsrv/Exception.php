<?php

namespace Core\Db\Adapter\Sqlsrv;

class Exception extends \Core\Db\Adapter\Exception {
	/**
	 * Constructor
	 *
	 * If $message is an array, the assumption is that the return value of
	 * sqlsrv_errors() was provided. If so, it then retrieves the most recent
	 * error from that stack, and sets the message and code based on it.
	 *
	 * @param null|array|string $message        	
	 * @param null|int $code        	
	 */
	public function __construct($message = null, $code = 0) {
		if (is_array ( $message )) {
			// Error should be array of errors
			// We only need first one (?)
			if (isset ( $message [0] )) {
				$message = $message [0];
			}
			
			$code = ( int ) $message ['code'];
			$message = ( string ) $message ['message'];
		}
		parent::__construct ( $message, $code, new Exception ( $message, $code ) );
	}
}
