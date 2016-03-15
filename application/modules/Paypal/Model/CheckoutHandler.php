<?php
namespace Paypal;

use \Core\Log;
use \Core\Text\String;

use \Base\Config;

use \Paymentgateway\Exception;
use \Paymentgateway\Payment;
use \Paymentgateway\AbstractCheckoutHandler;

use \Paymentgateway\Order;
use \Paymentgateway\Fee;

use \Paypal\Settings;

class CheckoutHandler extends AbstractCheckoutHandler {

	private $curl = NULL;

	public function __construct()
	{
		$this->sandbox = \Base\Config::get('paypal_sandbox');

		$this->curl = new \Core\Http\Curl;
		$this->curl->useCurl(function_exists('curl_init'));
		$this->curl->setTarget($this->sandbox ? 'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp');

		$this->curl->setMethod('POST');

		$this->username = \Base\Config::get('paypal_api_credentials_username');
		$this->password = \Base\Config::get('paypal_api_credentials_password');
		$this->signature = \Base\Config::get('paypal_api_credentials_signature');

		$this->curl->setParams([
			'USER' => $this->username,
			'PWD' => $this->password,
			'SIGNATURE' => $this->signature,
			'VERSION' => '78',
		]);

		$action = \Core\Base\Action::getInstance();
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, $action->getModule('Language')->getLanguageId());
	}

	public function checkout(&$order, &$response)
	{
		if(!$this->username || !$this->password || !$this->signature)
			return Payment::STATUS_NO_CREDENTIALS;

		$settings = Settings::get($order->seller_id);
		
		if(!$settings->active || !$settings->email)
			return Payment::STATUS_NO_CREDENTIALS;

		$currency = PaymentProvider::validateCurrency($order->getCurrency());
		$commission = $this->commission($order);

		if($order->getCurrency() !== $currency)
			$order->switchCurrency($currency);

		$this->curl->setParams([
			'METHOD' => 'SetExpressCheckout',
			'PAYMENTREQUEST_0_PAYMENTACTION' => 'SALE',
			'PAYMENTREQUEST_0_SELLERPAYPALACCOUNTID' => $settings->email,
			'PAYMENTREQUEST_0_AMT' => $order->getAmount(),
			'PAYMENTREQUEST_0_CURRENCYCODE' => $order->getCurrency(),
			'PAYMENTREQUEST_0_ITEMAMT' => $order->getSubTotal(),
			'PAYMENTREQUEST_0_SHIPPINGAMT' => $order->getShipping(),
			'PAYMENTREQUEST_0_DESC' => 'Order #' . $order->number,
			'PAYMENTREQUEST_0_CUSTOM' => serialize([ 'order' => $order->number, 'type' => 'purchase', 'percent' => $commission ]),
			'PAYMENTREQUEST_0_PAYMENTREQUESTID' => $order->number . '-' . 'PURCHASE',

			
			'cancelUrl' => $this->meta('cancel_url'),
			'returnUrl' => $this->meta('return_url'),
			'notifyUrl' => $this->meta('notify_url'),
		]);

		foreach(unserialize($order->items) as $idx => $item)
		{
			$this->curl->setParams([
				'L_PAYMENTREQUEST_0_NAME' . $idx => String::cut(String::plainify($item->name), 127),
				'L_PAYMENTREQUEST_0_DESC' . $idx => String::cut(String::plainify($item->description), 127),
				'L_PAYMENTREQUEST_0_AMT' . $idx => $item->getUnitValue(),
				'L_PAYMENTREQUEST_0_QTY' . $idx => $item->qty,
			]);
		}

		if($order->getDiscount())
			$this->curl->setParams([
				'L_PAYMENTREQUEST_0_NAME' . ++$idx => String::cut(String::plainify($this->meta('discount_item_name')), 127),
				'L_PAYMENTREQUEST_0_DESC' . $idx => String::cut(String::plainify($this->meta('discount_item_description')), 127),
				'L_PAYMENTREQUEST_0_AMT' . $idx => -1 * $order->getDiscount(),
				'L_PAYMENTREQUEST_0_QTY' . $idx => 1,
			]);

		if(0 < $commission)
		{
			$commission_value = round($order->getSubTotal() * $commission / 100, 2);

			$this->curl->setParams([
				'PAYMENTREQUEST_0_AMT' => $order->getAmount() - $commission_value,
				'PAYMENTREQUEST_0_ITEMAMT' => $order->getSubTotal() - $commission_value,

				'PAYMENTREQUEST_1_PAYMENTACTION' => 'SALE',
				'PAYMENTREQUEST_1_SELLERPAYPALACCOUNTID' => Config::get('paypal_business'),
				'PAYMENTREQUEST_1_AMT' => $commission_value,
				'PAYMENTREQUEST_1_CURRENCYCODE' => $order->getCurrency(),
				'PAYMENTREQUEST_1_ITEMAMT' => 0,
				'PAYMENTREQUEST_1_SHIPPINGAMT' => 0,
				'PAYMENTREQUEST_1_DESC' => $this->_->_('Market Fee'),
				'PAYMENTREQUEST_1_CUSTOM' => serialize([ 'order' => $order->number, 'type' => 'commission', 'percent' => $commission ]),
				'PAYMENTREQUEST_1_PAYMENTREQUESTID' => $order->number . '-' . 'COMMISSION',

				'L_PAYMENTREQUEST_0_NAME' . ++$idx => String::cut(String::plainify($this->_->_('Market Fee Transfer')), 127),
				'L_PAYMENTREQUEST_0_DESC' . $idx => String::cut(String::plainify($this->_->_('Fee is reduced from this order and added to the second.')), 127),
				'L_PAYMENTREQUEST_0_AMT' . $idx => -1 * $commission_value,
				'L_PAYMENTREQUEST_0_QTY' . $idx => 1,

				'L_PAYMENTREQUEST_1_NAME0' => String::cut(String::plainify($this->_->_('Marketplace Fee')), 127),
				'L_PAYMENTREQUEST_1_DESC0' => String::cut(String::plainify($this->_->_('Seller fee - its charged from the seller himself')), 127),
				'L_PAYMENTREQUEST_1_AMT0'  => $commission_value,
				'L_PAYMENTREQUEST_1_QTY0'  => 1,


			]);
		}

		// print_r($this->curl->getParams());
		// exit;

		//die('PAYMENTREQUEST_0_NOTIFYURL' . '=>' . $this->meta('notify_url'));

		$this->curl->execute();

		$error = $this->curl->getError();
		$result = $this->curl->getResult();

		parse_str(html_entity_decode(urldecode($result)), $result);

		// var_dump($result);

		if(!$this->validateResponse($result))
			return Payment::STATUS_INVALID_RESPONSE;

		$response = $result;
		$response['redirect'] = sprintf(
			'https://www.%spaypal.com/cgi-bin/webscr?cmd=_express-checkout&token=%s',
			\Base\Config::get('paypal_sandbox') ? 'sandbox.' : '',
			$response['TOKEN']
		);

		return Payment::STATUS_OK;
	}

	public function confirm(&$order)
	{
		if(!$this->username || !$this->password || !$this->signature)
			return Payment::STATUS_NO_CREDENTIALS;

		$settings = Settings::get($order->seller_id);

		if(!$settings->active || !$settings->email)
			return Payment::STATUS_NO_CREDENTIALS;

		$request = \Core\Http\Request::getInstance();
		$token = $request->getRequest('token');
		$payer_id = $request->getRequest('PayerID');

		if(!$token || !$payer_id)
			return Payment::STATUS_NO_CREDENTIALS;

		$currency = PaymentProvider::validateCurrency($order->getCurrency());

		if($order->getCurrency() !== $currency)
		{
			$order->snapshot(sprintf($this->_->_('Switching currencies from %s to %s.'), $order->getCurrency(), $currency));
			$order->switchCurrency($currency);
		}

		$this->curl->setParams([
			'METHOD' => 'DoExpressCheckoutPayment',
			'PAYERID' => $payer_id,
			'TOKEN' => $token,
			'PAYMENTREQUEST_0_PAYMENTACTION' => 'SALE',
			'PAYMENTREQUEST_0_SELLERPAYPALACCOUNTID' => $settings->email,
			'PAYMENTREQUEST_0_AMT' => $order->getAmount(),
			'PAYMENTREQUEST_0_CURRENCYCODE' => $order->getCurrency(),
			'PAYMENTREQUEST_0_ITEMAMT' => $order->getSubTotal(),
			'PAYMENTREQUEST_0_SHIPPINGAMT' => $order->getShipping(),
			'PAYMENTREQUEST_0_NOTIFYURL' => $this->meta('notify_url'),
			'PAYMENTREQUEST_0_PAYMENTREQUESTID' => $order->number . '-' . 'PURCHASE',
		]);

		$commission = $this->commission($order);
		$commission_value = 0;

		if(0 < $commission)
		{
			$commission_value = round($order->getSubTotal() * $commission / 100, 2);

			$this->curl->setParams([
				'PAYMENTREQUEST_0_AMT' => $order->getAmount() - $commission_value,
				'PAYMENTREQUEST_0_ITEMAMT' => $order->getSubTotal() - $commission_value,

				'PAYMENTREQUEST_1_PAYMENTACTION' => 'SALE',
				'PAYMENTREQUEST_1_SELLERPAYPALACCOUNTID' => Config::get('paypal_business'),
				'PAYMENTREQUEST_1_AMT' => $commission_value,
				'PAYMENTREQUEST_1_CURRENCYCODE' => $order->getCurrency(),
				'PAYMENTREQUEST_1_ITEMAMT' => $commission_value,
				'PAYMENTREQUEST_1_SHIPPINGAMT' => 0,
				'PAYMENTREQUEST_1_NOTIFYURL' => $this->meta('notify_url'),
				'PAYMENTREQUEST_1_PAYMENTREQUESTID' => $order->number . '-' . 'COMMISSION',
			]);
		}

		// print_r($this->curl->getParams());
		// exit;

		$this->curl->execute();

		$error = $this->curl->getError();
		$result = $this->curl->getResult();

		parse_str(html_entity_decode(urldecode($result)), $result);

		// var_dump($result);

		if (\Base\Config::get('paypal_debug'))
			Log::write(sprintf("[ PAYPAL DEBUG] Confirmation\nResponse: %s", print_r($result, TRUE)));

		if(!$this->validateResponse($result, [ 'TIMESTAMP', 'ACK', 'PAYMENTINFO_0_TRANSACTIONID' ]))
			return Payment::STATUS_INVALID_RESPONSE;

		$amt = isset($result['PAYMENTINFO_0_AMT']) ? (float) $result['PAYMENTINFO_0_AMT'] : 0;
		$amt = round($amt, 2, PHP_ROUND_HALF_DOWN);

		if (\Base\Config::get('paypal_debug'))
			Log::write(sprintf("[ PAYPAL DEBUG] Confirmation\n PayPal Total %.2f: System Total %.2f", $amt, $order->getAmount()));

		if(0 >= $amt || $order->getAmount() - $commission_value !== $amt)
			return Payment::STATUS_FAILURE;

		$order->custom($result);

		if(!in_array(mb_strtoupper($result['ACK']), [ 'SUCCESS', 'SUCCESSWITHWARNING' ]))
		{
			$order->setStatus(Config::get('paypal_failed_status_id'));
			$order->snapshot($this->_->_('Order failed due to invalid PayPal response.'));
			return Payment::STATUS_FAILURE;
		}

		$status = isset($result['PAYMENTINFO_0_PAYMENTSTATUS']) ? $result['PAYMENTINFO_0_PAYMENTSTATUS'] : NULL;

		if(!$status)
		{
			$order->setStatus(Config::get('paypal_failed_status_id'));
			$order->snapshot($this->_->_('Order failed due to lack of PayPal status.'));
			return Payment::STATUS_FAILURE;	
		}

		$order->setStatus($this->translateStatus($status));

		$order->snapshot(sprintf($this->_->_('Order status updated to %s.'), $order->StatusDescription()->title));

		return Payment::STATUS_OK;
	}

	public function notify(&$order)
	{
		$request = \Core\Http\Request::getInstance();

		if (\Base\Config::get('paypal_debug'))
			Log::write(sprintf("[ PAYPAL DEBUG] IPN\nValidation: %d", (int) $this->validateIPN()));

		if(!$this->validateIPN())
			return Payment::STATUS_FAILURE;

		$response = $request->getPost();
		$status = $response['payment_status'];
		$custom = unserialize($response['custom']);
		$type = isset($custom['type']) ? $custom['type'] : NULL;
		$commission = isset($custom['percent']) ? (float) $custom['percent'] : NULL;

		if(NULL === $commission || !$type || !in_array($type, [ 'purchase', 'commission' ]))
			$type = 'purchase';

		$amt = isset($response['mc_gross']) ? (float) $response['mc_gross'] : 0;
		$amt = round($amt, 2, PHP_ROUND_HALF_DOWN);

		if (\Base\Config::get('paypal_debug'))
			Log::write(sprintf('[ PAYPAL DEBUG] IPN\nPayPal Total %.2f: System Total %.2f', $amt, $order->getAmount()));

		$commission_value = round($order->getSubTotal() * $commission / 100, 2);

		switch($type)
		{
			case 'purchase':

				if(0 >= $amt || $order->getAmount() - $commission_value !== $amt)
					return Payment::STATUS_FAILURE;

				$order->setStatus($this->translateStatus($status));
				$order->snapshot(sprintf($this->_->_('Order status updated to %s with IPN request.'), $order->StatusDescription()->title));
				break;

			case 'commission':
				if(0 >= $amt || $commission_value !== $amt)
					return Payment::STATUS_FAILURE;

				$order->snapshot(sprintf($this->_->_('Order commission processed with status %s by IPN request.'), $order->StatusDescription()->title));
				Fee::register($order->id, $commission, $amt, $this->translateStatus($status));
				break;

			default:
				return Payment::STATUS_INVALID_RESPONSE;
		}

		return Payment::STATUS_OK;
	}

	public function refund($order_id)
	{
		throw new Exception(Exception::NotImplemented);
	}

	protected function validateResponse(&$response, $keys = NULL)
	{
		if(!$response || !is_array($response) || empty($response))
			return FALSE;

		if(!$keys)
			$keys = [ 'TOKEN', 'CORRELATIONID', 'ACK', 'TIMESTAMP' ];

		foreach($keys as $key)
			if(!isset($response[ $key ]))
				return FALSE;

		return TRUE;
	}

	protected function translateStatus($status)
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

	protected function validateIPN()
	{
		$request = \Core\Http\Request::getInstance();

		if(!$request->isPost())
			return FALSE;

		$post = $request->getPost();

		$request = 'cmd=_notify-validate';

		foreach ($post as $key => $value)
		 	$request .= '&' . $key . '=' . urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));

		if(!is_callable('curl_init'))
		{
			Log::write('IPN ERROR: Curl extension is missing or not enabled.');
			return FALSE;
		}

		$error = $this->curl->getError();
		$result = $this->curl->getResult();

		$curl = curl_init($this->sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr');
		
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_HEADER, FALSE);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

		$response = curl_exec($curl);

		curl_close($curl);

		if (\Base\Config::get('paypal_debug'))
		{
			Log::write('IPN REQUEST: ' . is_array($post) ? print_r($post, TRUE) : $post);
			Log::write('IPN RESPONSE: ' . is_array($response) ? print_r($response, TRUE) : $response);
		}

		if (!$response)
			return FALSE;

		return (strcmp($response, 'VERIFIED') == 0 || strcmp($response, 'UNVERIFIED') == 0) && isset($post['payment_status']);
	}

	public function detect()
	{
		return FALSE;
	}

	/**
	 * @todo
	 * @return [type] [description]
	 */
	public function detectOrder()
	{
		return NULL;
	}

}