<?php
namespace Paymentgateway\Widget;

use \Paymentgateway\Payment;
use \Paymentgateway\PaymentManager;

use \Paymentgateway\ItemCollection;
use \Paymentgateway\Order;
use \Paymentgateway\OrderManager;

use \Base\Traits\FormInputPopulator;

use \Store\Cart;
use \Store\CartContent;
use \Store\CartAddress;
use \Store\Shipping;

use \Store\Settings;
use \Store\StoreItem;

class Checkout extends \Core\Base\Widget {

	use FormInputPopulator;

	/**
	 * @var Paymentgateway\Order
	 */
	protected $order = NULL;

	public function init()
	{
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result()
	{
		if(!$this->order)
			return;

		$me = \User\User::getUserData();
		
		$user_id = $me->id;
		$items = $this->order->getItems();
		
		if($user_id != $this->order->user_id)
			return;

		if(!isset($items))
			return;

		if(!is_array($items) && !is_object($items))
			return;

		if(is_array($items) && empty($items))
			return;

		if(!is_object($items) || !$items->is('Paymentgateway\ItemCollection') || $items->is_empty())
			return;

		if($me->id !== $user_id)
			return;

		$providers = PaymentManager::getSupportedProviders($this->order->getHandlerTranslateSupport(), NULL, \Paymentgateway\AbstractPaymentProvider::Online);

		$this->render('checkout', [ 'providers' => $providers, 'me' => $me, 'collection' => $items ]);
	}

}