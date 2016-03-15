<?php
namespace Paypal\Helper;

class Withdraw {

	public function __construct($options = [])
	{
		$me = \User\User::getUserData();
		$request = \Core\Http\Request::getInstance();
		$transaction = isset($options['transaction']) ? $options['transaction'] : NULL;

		if(!$me->id || !is_object($transaction) || !$transaction instanceof \Wallet\Transaction)
			return;

		$transaction->setMeta('details', $request->getPost('withdraw[data]'));
	}

}