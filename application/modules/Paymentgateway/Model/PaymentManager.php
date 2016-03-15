<?php

namespace Paymentgateway;

use \Paymentgateway\AbstractPaymentProvider;

class PaymentManager {
	
	public static function getProviderSupport($provider)
	{
		if(!$provider->is('Paymentgateway\IPaymentProvider'))
			throw new Exception(Exception::InvalidProvider);

		return $provider->getSupport();
	}

	public static function getSupportedProviders($flag, $seller_id = NULL, $type = AbstractPaymentProvider::Any)
	{
		$records = (new PaymentProvider)->fetchAll([ 'active = 1', 'support & ?' => (int) $flag ], 'priority');
		$result = [];

		foreach($records as $record)
		{
			$class = $record->module . NS . 'PaymentProvider';
			$settings = $record->module . NS . 'Settings';

			if(!class_exists($class))
				continue;

			$provider = new $class;

			if(AbstractPaymentProvider::Any !== $type)
				if($provider->getType() !== $type)
					continue;

			if(0 < (int) $seller_id)
			{
				$settings = (new $settings)->get((int) $seller_id);

				if(!$settings->active)
					continue;
			}

			$provider->metadata($record);

			$result[] = $provider;
		}

		return $result;
	}

	public static function getProviders()
	{
		$records = (new PaymentProvider)->fetchAll([ 'active = 1' ], 'priority');
		$result = [];

		foreach($records as $record)
		{
			$class = $record->module . NS . 'PaymentProvider';

			if(!class_exists($class))
				continue;

			$result[] = new $class;

			$result[count($result) - 1]->metadata($record);
		}

		return $result;
	}

	/**
	 * @param string $code
	 * @return NULL|\Paymentgateway\AbstractPaymentProvider
	 */
	public static function getProvider($code, $active = true)
	{
		$result = NULL;
		$filter = [ 'active = ?' => (int)$active, 'code = ?' => mb_strtoupper($code) ];
		if(is_null($active))
			unset($filter['active = ?']);
		
		$record = (new PaymentProvider)->fetchRow($filter);

		if(!$record || !$record->module)
			return NULL;

		$class = $record->module . NS . 'PaymentProvider';

		if(!class_exists($class))
			return NULL;

		$result = new $class;
		$result->metadata($record);

		return $result;
	}

	/**
	 * Detect Provider or handler by request
	 * @return mixed PaymentProvider, Handler or NULL
	 */
	public static function detect()
	{
		$providers = self::getProviders();

		foreach($providers as $provider)
			if($result = $provider->detect())
				return $result;

		return NULL;
	}

	public static function sellerCanAcceptPayments($seller_id)
	{
		$records = (new PaymentProvider)->fetchAll([ 'active = 1' ], 'priority');

		foreach($records as $record)
		{
			$class = $record->module . NS . 'Settings';

			if(!class_exists($class))
				continue;

			$settings = call_user_func_array([ $class, 'get' ], [ $seller_id ]);

			if(!$settings)
				continue;

			if($settings->active)
				return TRUE;
		}

		return FALSE;
	}

}