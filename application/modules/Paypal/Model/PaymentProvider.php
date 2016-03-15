<?php

namespace Paypal;

use \Paymentgateway\IPaymentProvider;

use \Paymentgateway\ICheckoutSupport;
use \Paymentgateway\ISubscriptionSupport;
use \Paymentgateway\IChainSupport;
use \Paymentgateway\IDepositSupport;
use \Paymentgateway\IWithdrawSupport;

use \Paymentgateway\Exception;
use \Paymentgateway\AbstractPaymentProvider;

class PaymentProvider extends AbstractPaymentProvider implements IPaymentProvider, ICheckoutSupport, ISubscriptionSupport, IChainSupport, IDepositSupport, IWithdrawSupport {

	private $checkout = NULL;
	private $subscription = NULL;
	private $withdraw = NULL;

	protected $type = AbstractPaymentProvider::Online;
	protected $manual = FALSE;

	public function getCheckoutHandler()
	{
		if(!$this->checkout)
		{
			$this->checkout = new CheckoutHandler;
			$this->checkout->setProvider($this);
		}

		return $this->checkout;
	}

	public function getSubscriptionHandler()
	{
		if(!$this->subscription)
		{
			$this->subscription = new SubscriptionHandler;
			$this->subscription->setProvider($this);
		}

		return $this->subscription;
	}

	public function getChainHandler()
	{
		throw new Exception(Exception::NotUsed);
	}

	public function getDepositHandler()
	{
		return $this->getCheckoutHandler();
	}

	public function getWithdrawHandler()
	{
		if(!$this->withdraw)
		{
			$this->withdraw = new WithdrawHandler;
			$this->withdraw->setProvider($this);
		}

		return $this->withdraw;
	}

	public static function validateCurrency($value)
	{
		$currencies = [
			'AUD', 'CAD', 'EUR', 'GBP', 'JPY',
			'USD', 'NZD', 'CHF', 'HKD', 'SGD',
			'SEK', 'DKK', 'PLN', 'NOK', 'HUF',
			'CZK', 'ILS', 'MXN', 'MYR', 'BRL',
			'PHP', 'TWD', 'THB', 'TRY',
		];

		if (in_array(strtoupper($value), $currencies))
			$currency = strtoupper($value);
		elseif (in_array(strtoupper(\Base\Config::get('config_currency')), $currencies))
			$currency = strtoupper(\Base\Config::get('config_currency'));
		else
			$currency = 'EUR';

		return $currency;
	}

	public static function translateStatus($status)
	{
		switch ($status)
		{
			case 'Canceled_Reversal':  return \Base\Config::get('paypal_canceled_reversal_status_id');
			case 'Completed':          return \Base\Config::get('paypal_completed_status_id');
			case 'Denied':             return \Base\Config::get('paypal_denied_status_id');
			case 'Expired':            return \Base\Config::get('paypal_expired_status_id');
			case 'Failed':             return \Base\Config::get('paypal_failed_status_id');
			case 'Pending':            return \Base\Config::get('paypal_pending_status_id');
			case 'Processed':          return \Base\Config::get('paypal_processed_status_id');
			case 'Refunded':           return \Base\Config::get('paypal_refunded_status_id');
			case 'Reversed':           return \Base\Config::get('paypal_reversed_status_id');
			case 'Voided':             return \Base\Config::get('paypal_voided_status_id');
		}

		return NULL;
	}

//	public static function validateIPN()
//	{
//		$request = \Core\Http\Request::getInstance();
//
//		if(!$request->isPost())
//			return FALSE;
//
//		$post = $request->getPost();
//
//		$request = 'cmd=_notify-validate';
//
//		foreach ($post as $key => $value)
//		 	$request .= '&' . $key . '=' . urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
//
//		if(!is_callable('curl_init'))
//		{
//			Log::write('IPN ERROR: Curl extension is missing or not enabled.');
//			return FALSE;
//		}
//
//		$error = $this->curl->getError();
//		$result = $this->curl->getResult();
//
//		$curl = curl_init($this->sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr');
//
//		curl_setopt($curl, CURLOPT_POST, TRUE);
//		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
//		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
//		curl_setopt($curl, CURLOPT_HEADER, FALSE);
//		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
//		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
//
//		$response = curl_exec($curl);
//
//		curl_close($curl);
//
//		if (\Base\Config::get('paypal_debug'))
//		{
//			Log::write('IPN REQUEST: ' . is_array($post) ? print_r($post, TRUE) : $post);
//			Log::write('IPN RESPONSE: ' . is_array($response) ? print_r($response, TRUE) : $response);
//		}
//
//		if (!$response)
//			return FALSE;
//
//		return (strcmp($response, 'VERIFIED') == 0 || strcmp($response, 'UNVERIFIED') == 0) && isset($post['payment_status']);
//	}

	public function detect()
	{
		$this->getCheckoutHandler();
		$this->getSubscriptionHandler();
		// $this->getChainHandler();
		// $this->getDepositHandler();

		if($this->checkout->detect())
			return $this->checkout;

		if($this->subscription->detect())
			return $this->subscription;
	}

}