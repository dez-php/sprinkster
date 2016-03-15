<?php
namespace Paypal;

use \Core\Log;
use \Core\Text\String;

use \Base\Config;

use \Paymentgateway\Exception;
use \Paymentgateway\Payment;
use \Paymentgateway\AbstractCheckoutHandler;

use \Paymentgateway\Order;
use \Paymentgateway\OrderMeta;
use \Paymentgateway\OrderManager;

use \Paypal\Settings;

class SubscriptionHandler extends AbstractCheckoutHandler {

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

		if(!is_object($order) || !$order->is('Paymentgateway\Order') || $order->getItems()->is_empty())
			return Payment::STATUS_FAILURE;

		$settings = Settings::get($order->seller_id);
		$item = $order->getItems()->at(0);
		
		if(!$settings->active || !$settings->email)
			return Payment::STATUS_NO_CREDENTIALS;

		$currency = PaymentProvider::validateCurrency($order->getCurrency());

		if($order->getCurrency() !== $currency)
			$order->switchCurrency($currency);

		$description = [
			String::plainify($order->getRoute()->getTitle()),
			String::plainify($item->name),
			String::plainify($item->length . ' ' . $this->_->_($item->period . (1 !== $item->length ? 's' : ''))),
		];

		$this->curl->setParams([
			'METHOD' => 'SetExpressCheckout',
			'L_BILLINGTYPE0' => 'RecurringPayments',
			'L_BILLINGAGREEMENTDESCRIPTION0' => implode(' - ', $description),
			//'L_BILLINGAGREEMENTCUSTOM0' => serialize([ 'order' => $order->number ]),
			'AMT' => $order->getAmount(),
			'INITAMT' => $order->getAmount(),
			'CURRENCYCODE' => $order->getCurrency(),
			'PAYMENTREQUEST_0_AMT' => $order->getAmount(),
			'PAYMENTREQUEST_0_CURRENCYCODE' => $order->getCurrency(),
			'PAYMENTREQUEST_0_ITEMAMT' => $order->getSubTotal(),
			'PAYMENTREQUEST_0_SELLERPAYPALACCOUNTID' => $settings->email,
			//'PAYMENTREQUEST_0_CUSTOM' => serialize([ 'order' => $order->number ]),

			'REQCONFIRMSHIPPING' => 0,
			'NOSHIPPING' => 1,
	
			'cancelUrl' => $this->meta('cancel_url'),
			'returnUrl' => $this->meta('return_url'),
		]);

		foreach(unserialize($order->items) as $idx => $item)
		{
			$this->curl->setParams([
				'L_PAYMENTREQUEST_0_NAME' . $idx => String::plainify($item->name),
				'L_PAYMENTREQUEST_0_DESC' . $idx => String::plainify($item->description),
				'L_PAYMENTREQUEST_0_AMT' . $idx => $item->price,
				'L_PAYMENTREQUEST_0_QTY' . $idx => $item->qty,
				// 'L_PAYMENTREQUEST_0_ITEMCATEGORY' . $idx => 'Digital',
			]);
		}

		// print_r($this->curl->getParams());
		// exit;

		$this->curl->execute();

		$error = $this->curl->getError();
		$result = $this->curl->getResult();

		parse_str(html_entity_decode(urldecode($result)), $result);

		if(!$this->validateResponse($result))
			return Payment::STATUS_INVALID_RESPONSE;

		if (\Base\Config::get('paypal_debug'))
			Log::write('Subscription SetExpressCheckout: ' . print_r($result, TRUE));

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

		if(!is_object($order) || !$order->is('Paymentgateway\Order') || $order->getItems()->is_empty())
			return Payment::STATUS_FAILURE;

		$settings = Settings::get($order->seller_id);
		$item = $order->getItems()->at(0);

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

		$description = [
			String::plainify($order->getRoute()->getTitle()),
			String::plainify($item->name),
			String::plainify($item->length . ' ' . $this->_->_($item->period . (1 !== $item->length ? 's' : ''))),
		];

		$this->curl->setParams([
			'METHOD' => 'CreateRecurringPaymentsProfile',
			'PAYERID' => $payer_id,
			'TOKEN' => $token,
			'PROFILESTARTDATE' => date('Y-m-d\TH:i:s\Z'),
			'DESC' => implode(' - ', $description),
			'BILLINGPERIOD' => ucfirst($item->period),
			'BILLINGFREQUENCY' => $item->length,
			'AMT' => $order->getAmount(),
			'INITAMT' => $order->getAmount(),
			'FAILEDINITAMTACTION' => 'ContinueOnFailure',
			'CURRENCYCODE' => $order->getCurrency(),
			'MAXFAILEDPAYMENTS' => 3,
			'PAYMENTREQUEST_0_SELLERPAYPALACCOUNTID' => $settings->email,
			// 'PAYMENTREQUEST_0_AMT' => $order->getAmount(),
			// 'PAYMENTREQUEST_0_CURRENCYCODE' => $order->getCurrency(),
			// 'PAYMENTREQUEST_0_ITEMAMT' => $order->getSubTotal(),
			// 'PAYMENTREQUEST_0_SHIPPINGAMT' => $order->getShipping(),
		]);

		// print_r($this->curl->getParams());
		// exit;

		$this->curl->execute();

		$error = $this->curl->getError();
		$result = $this->curl->getResult();

		parse_str(html_entity_decode(urldecode($result)), $result);

		if (\Base\Config::get('paypal_debug'))
			Log::write('Subscription CreateRecurringPaymentsProfile: ' . print_r($result, TRUE));

		// var_dump($result);
		// exit;

		if(!$this->validateResponse($result, [ 'TIMESTAMP', 'ACK', 'PROFILEID', 'PROFILESTATUS' ]))
			return Payment::STATUS_INVALID_RESPONSE;

		$amt = isset($result['PAYMENTINFO_0_AMT']) ? (float) $result['PAYMENTINFO_0_AMT'] : 0;
		$amt = round($amt, 2, PHP_ROUND_HALF_DOWN);

		if (\Base\Config::get('paypal_debug'))
			Log::write(sprintf("[ PAYPAL DEBUG] Confirmation\n PayPal Total %.2f: System Total %.2f", $amt, $order->getAmount()));

		$profileID = isset($result['PROFILEID']) ? $result['PROFILEID'] : NULL;

		if(!$profileID)
			return Payment::STATUS_FAILURE;

		$order->custom($result);
		$order->setMeta('ESID', $profileID);

		if(!in_array(mb_strtoupper($result['ACK']), [ 'SUCCESS', 'SUCCESSWITHWARNING' ]))
		{
			$order->setStatus(Config::get('paypal_failed_status_id'));
			$order->snapshot($this->_->_('Order failed due to invalid PayPal response.'));
			return Payment::STATUS_FAILURE;
		}

		$status = isset($result['PROFILESTATUS']) ? $result['PROFILESTATUS'] : NULL;

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
		$status = $response['initial_payment_status'];

		$upgrade = $order->meta('Upgrade') ?: FALSE;
		
		if($upgrade)
			$upgrade = OrderManager::get($upgrade);

		$amt = isset($response['initial_payment_amount']) ? (float) $response['initial_payment_amount'] : 0;
		$amtr = isset($response['amount']) ? (float) $response['amount'] : 0;
		$amtc = isset($response['amount_per_cycle']) ? (float) $response['amount_per_cycle'] : 0;
		$amt = round($amt, 2, PHP_ROUND_HALF_DOWN);
		$amtr = round($amtr, 2, PHP_ROUND_HALF_DOWN);
		$amtc = round($amtc, 2, PHP_ROUND_HALF_DOWN);

		if (\Base\Config::get('paypal_debug'))
			Log::write(sprintf("[ PAYPAL DEBUG] IPN\nPayPal Total %.2f: System Total %.2f", $amt, $order->getAmount()));
		
		if(0 >= $amt || $order->getAmount() !== $amt || $order->getAmount() !== $amtr || $order->getAmount() !== $amtc)
			return Payment::STATUS_FAILURE;

		if(Config::get('config_complete_status_id') !== $order->status_id)
		{
			$order->setStatus(PaymentProvider::translateStatus($status));
			$order->snapshot(sprintf($this->_->_('Order status updated to %s with IPN request.'), $order->StatusDescription()->title));

			if(Config::get('config_complete_status_id') == $order->status_id)
			{
				$cancellation = $this->cancel($upgrade);
				
				if(!$cancellation)
					$this->cancel($order);
			}

			return Payment::STATUS_OK;
		}

		$child = $order->respawn();
		$child->setStatus(PaymentProvider::translateStatus($status));
		$order->snapshot(sprintf($this->_->_('New extensive order #%s created with status %s for the current order #%s.'), $child->number, $order->StatusDescription()->title, $order->number));

		$order = $child;

		// $order->setStatus(PaymentProvider::translateStatus($status));
		// $order->snapshot(sprintf($this->_->_('Order status updated to %s with IPN request.'), ));

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
			case 'CancelledProfile':   return \Base\Config::get('paypal_canceled_reversal_status_id');
			case 'ActiveProfile':      return \Base\Config::get('paypal_processed_status_id');
			case 'SuspendedProfile':   return \Base\Config::get('paypal_failed_status_id');
			case 'PendingProfile':     return \Base\Config::get('paypal_pending_status_id');
			case 'Processed':          return \Base\Config::get('paypal_processed_status_id');
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

		return (strcmp($response, 'VERIFIED') == 0 || strcmp($response, 'UNVERIFIED') == 0) && isset($post['initial_payment_status']);
	}

	public function detect()
	{
		$data = \Core\Http\Request::getInstance()->getPost();

		if(isset($data['recurring_payment_id']) && $data['recurring_payment_id'])
			return TRUE;

		return FALSE;
	}

	public function detectOrder()
	{
		$data = \Core\Http\Request::getInstance()->getPost();

		if(!isset($data['recurring_payment_id']) || !$data['recurring_payment_id'])
			return NULL;

		return Order::locate('ESID', $data['recurring_payment_id']);
	}

	public function cancel(&$order)
	{
		if(!$this->username || !$this->password || !$this->signature)
			return Payment::STATUS_NO_CREDENTIALS;

		if(!is_object($order) || !$order->is('Paymentgateway\Order') || $order->getItems()->is_empty())
			return Payment::STATUS_FAILURE;

		$settings = Settings::get($order->seller_id);
		$item = $order->getItems()->at(0);

		if(!$settings->active || !$settings->email)
			return Payment::STATUS_NO_CREDENTIALS;

		$custom = $order->getCustom();

		$this->curl->setParams([
			'METHOD' => 'ManageRecurringPaymentsProfileStatus',
			'PROFILEID' => $custom['PROFILEID'],
			'ACTION' => 'Cancel',
			'NOTE' => $this->_->_('Subscription cancellation from the website interface.'),
		]);

		$this->curl->execute();

		$error = $this->curl->getError();
		$result = $this->curl->getResult();

		parse_str(html_entity_decode(urldecode($result)), $result);

		if(!$this->validateResponse($result, [ 'PROFILEID', 'CORRELATIONID', 'ACK', 'TIMESTAMP' ]))
			return Payment::STATUS_INVALID_RESPONSE;

		if(!isset($result['PROFILEID']) || $result['PROFILEID'] != $custom['PROFILEID'])
			return Payment::STATUS_FAILURE;

		if(!in_array(mb_strtoupper($result['ACK']), [ 'SUCCESS', 'SUCCESSWITHWARNING' ]))
			return Payment::STATUS_FAILURE;

		return Payment::STATUS_OK;
	}

	public function upgrade(&$order, &$upgrade, &$response)
	{
		if(!$this->username || !$this->password || !$this->signature)
			return Payment::STATUS_NO_CREDENTIALS;

		if(!is_object($upgrade) || !$upgrade->is('Paymentgateway\Order') || $upgrade->getItems()->is_empty())
			return Payment::STATUS_FAILURE;

		$settings = Settings::get($upgrade->seller_id);
		$item = $upgrade->getItems()->at(0);
		
		if(!$settings->active || !$settings->email)
			return Payment::STATUS_NO_CREDENTIALS;

		$currency = PaymentProvider::validateCurrency($upgrade->getCurrency());

		if($upgrade->getCurrency() !== $currency)
			$upgrade->switchCurrency($currency);

		$description = [
			String::plainify($upgrade->getRoute()->getTitle()),
			String::plainify($item->name),
			String::plainify($item->length . ' ' . $this->_->_($item->period . (1 !== $item->length ? 's' : ''))),
		];

		$this->curl->setParams([
			'METHOD' => 'SetExpressCheckout',
			'L_BILLINGTYPE0' => 'RecurringPayments',
			'L_BILLINGAGREEMENTDESCRIPTION0' => implode(' - ', $description),
			//'L_BILLINGAGREEMENTCUSTOM0' => serialize([ 'order' => $upgrade->number, 'upgrade' => $order->number ]),
			'AMT' => $upgrade->getAmount(),
			'INITAMT' => $upgrade->getAmount(),
			'CURRENCYCODE' => $upgrade->getCurrency(),
			'PAYMENTREQUEST_0_AMT' => $upgrade->getAmount(),
			'PAYMENTREQUEST_0_CURRENCYCODE' => $upgrade->getCurrency(),
			'PAYMENTREQUEST_0_ITEMAMT' => $upgrade->getSubTotal(),
			'PAYMENTREQUEST_0_SELLERPAYPALACCOUNTID' => $settings->email,
			//'PAYMENTREQUEST_0_CUSTOM' => serialize([ 'order' => $upgrade->number, 'upgrade' => $order->number ]),

			'REQCONFIRMSHIPPING' => 0,
			'NOSHIPPING' => 1,
	
			'cancelUrl' => $this->meta('cancel_url'),
			'returnUrl' => $this->meta('return_url'),
		]);

		foreach(unserialize($upgrade->items) as $idx => $item)
		{
			$this->curl->setParams([
				'L_PAYMENTREQUEST_0_NAME' . $idx => String::plainify($item->name),
				'L_PAYMENTREQUEST_0_DESC' . $idx => String::plainify($item->description),
				'L_PAYMENTREQUEST_0_AMT' . $idx => $item->price,
				'L_PAYMENTREQUEST_0_QTY' . $idx => $item->qty,
				// 'L_PAYMENTREQUEST_0_ITEMCATEGORY' . $idx => 'Digital',
			]);
		}

		// print_r($this->curl->getParams());
		// exit;

		$this->curl->execute();

		$error = $this->curl->getError();
		$result = $this->curl->getResult();

		parse_str(html_entity_decode(urldecode($result)), $result);

		if(!$this->validateResponse($result))
			return Payment::STATUS_INVALID_RESPONSE;

		if (\Base\Config::get('paypal_debug'))
			Log::write('Subscription SetExpressCheckout: ' . print_r($result, TRUE));

		$response = $result;
		$response['redirect'] = sprintf(
			'https://www.%spaypal.com/cgi-bin/webscr?cmd=_express-checkout&token=%s',
			\Base\Config::get('paypal_sandbox') ? 'sandbox.' : '',
			$response['TOKEN']
		);

		$upgrade->setMeta('Upgrade', $order->number);

		return Payment::STATUS_OK;
	}

}