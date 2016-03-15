<?php

namespace Paymentgateway;

class Exception extends \Core\Exception {

	const Unknown                      = 0x0000;
	const InvalidProvider              = 0x0001;
	const NotImplemented               = 0x0002;
	const InvalidItem                  = 0x0003;
	const InvalidCollection            = 0x0004;
	const Corrupted                    = 0x0005;
	const ConversionFailed             = 0x0006;
	const RegisterFailed               = 0x0007;
	const NotUsed                      = 0x0008;

	protected $messages = [
		0x0000 => 'Unknown problem occured.',
		0x0001 => 'Invalid payment provider.',
		0x0002 => 'Not Implemented Exception',
		0x0003 => 'Item is invalid.',
		0x0004 => 'Collection invalid.',
		0x0005 => 'Payment data corrupted.',
		0x0006 => 'Currency conversion failed.',
		0x0007 => 'Payment registration failed.',
		0x0008 => 'Not Used Exception',
	];

	public function __construct($code)
	{
		if(!isset($this->messages[$code]))
			$code = self::Unknown;

		$this->code = $code;
		$this->message = $this->messages[$code];
	}

}

?>