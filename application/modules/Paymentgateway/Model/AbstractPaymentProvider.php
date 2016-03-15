<?php

namespace Paymentgateway;

use \Core\Base\Model;

abstract class AbstractPaymentProvider extends Model {

	const Any = 'any';
	const Online = 'online';
	const Offline = 'offline';

	protected $module = '';
	protected $name = '';
	protected $code = '';
	protected $priority = NULL;
	protected $type = self::Online;
	protected $manual = FALSE;

	public function getSupport()
	{
		return 0
			| ($this->is('Paymentgateway\ICheckoutSupport') ? Payment::PURCHASE_SUPPORT : 0)
			| ($this->is('Paymentgateway\ISubscriptionSupport') ? Payment::SUBSCRIPTION_SUPPORT : 0)
			| ($this->is('Paymentgateway\IChainSupport') ? Payment::CHAIN_SUPPORT : 0)
			| ($this->is('Paymentgateway\IDepositSupport') ? Payment::DEPOSIT_SUPPORT : 0)
			| ($this->is('Paymentgateway\IWithdrawSupport') ? Payment::WITHDRAW_SUPPORT : 0);
	}

	public function isOnline()
	{
		return self::Online === $this->type;
	}

	public function isOffline()
	{
		return self::Offline === $this->type;
	}

	public function getType()
	{
		return $this->type;
	}

	public function isManual()
	{
		return $this->manual;
	}

	public function supports($flag = Payment::PURCHASE_SUPPORT)
	{
		return $this->getSupport() & $flag;
	}

	public function metadata($record)
	{
		if(!is_object($record))
			return;

		$this->module = isset($record->module) ? $record->module : '';
		$this->name = isset($record->name) ? $record->name : '';
		$this->code = isset($record->code) ? $record->code : '';
		$this->priority = isset($record->priority) ? $record->priority : NULL;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getCode()
	{
		return $this->code;
	}
	
	public function getPriority()
	{
		return $this->priority;
	}

	public function getHandler($code)
	{
		switch($code)
		{
			case Payment::PURCHASE_SUPPORT:
			case Payment::PURCHASE:
				return $this->is('Paymentgateway\ICheckoutSupport') ? $this->getCheckoutHandler() : NULL;
			
			case Payment::SUBSCRIPTION_SUPPORT:
			case Payment::SUBSCRIPTION:
				return $this->is('Paymentgateway\ISubscriptionSupport') ? $this->getSubscriptionHandler() : NULL;

			case Payment::CHAIN_SUPPORT:
			case Payment::CHAIN:
				throw new Exception(Exception::NotUsed);

			case Payment::DEPOSIT_SUPPORT:
			case Payment::DEPOSIT:
				return $this->is('Paymentgateway\IDepositSupport') ? $this->getDepositHandler() : NULL;

			case Payment::DEPOSIT_SUPPORT:
			case Payment::DEPOSIT:
				return $this->is('Paymentgateway\IWithdrawSupport') ? $this->getWithdrawHander() : NULL;
		}

		return NULL;
	}

}