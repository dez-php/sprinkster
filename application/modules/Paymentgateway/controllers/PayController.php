<?php

namespace Paymentgateway;

use \Base\Traits\FormInputPopulator;

use \Paymentgateway\Payment;
use \Paymentgateway\PaymentManager;

use \Paymentgateway\ItemCollection;
use \Paymentgateway\Order;
use \Paymentgateway\OrderManager;

class PayController extends \Base\PermissionController {
	
	use FormInputPopulator;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		$self = \User\User::getUserData();
		if(!$self->id) 
			$this->forward('error404');
		
		$number = \Core\Session\Base::get('order_number');
		$cart = OrderManager::get($number ? $number : '-1');
		if(!$cart || $cart->user_id != $self->id)
			$this->forward('error404');

		$this->render('index', [
// 			'providers' => $providers,
			'order' => $cart,
			'items' => $cart->getItems()
		]);
	}

	public function buttonAction()
	{
		$request = $this->getRequest();

		$provider = PaymentManager::getProvider((string)$request->getPost('checkout[provider]'));

		if(!$provider || !$provider->is('Paymentgateway\IPaymentProvider'))
			return $this->responseJsonCallback([ 'result' => FALSE, 'message' => $this->_('Invalide payment provider') ]);

		$number = \Core\Session\Base::get('order_number');
		$order = OrderManager::get($number ? $number : '-1');
		
		if(!$order)
			return $this->responseJsonCallback([ 'result' => FALSE, 'message' => $this->_('This order is not found') ]);

		if(!$provider->supports($order->getHandlerTranslateSupport()))
			return $this->responseJsonCallback([ 'result' => FALSE, 'message' => sprintf($this->_('The payment provider does NOT support %s method'), $order->getHandlerTranslate()) ]);

		$items = $order->getItems();
		if(!is_object($items) || !$items->is('Paymentgateway\ItemCollection') || $items->is_empty())
			return $this->responseJsonCallback([ 'result' => FALSE, 'message' => $this->_('Order is not found.') ]);

		$widget = $this->widget(strtolower($provider->getCurrentModule()) . '.widget.checkout', [
			'seller' => $order->seller_id ? (new \User\User())->get($order->seller_id) : NULL,
			'order' => $order,
			'upgrade' => $items->at(0)->is('Cart\SubscribeItem') ? $items->at(0)->getParent() : FALSE,
		]);
		
		return $this->responseJsonCallback([ 'result' => TRUE, 'html' => (string) $widget ]);
	}
	
	public function checkoutAction() {

		$request = $this->getRequest();

		$provider = PaymentManager::getProvider((string)$request->getPost('checkout[provider]'));

		if(!$provider || !$provider->is('Paymentgateway\IPaymentProvider'))
			return $this->responseJsonCallback([ 'result' => FALSE, 'message' => $this->_('Invalide payment provider') ]);

		$number = \Core\Session\Base::get('order_number');
		$order = OrderManager::get($number ? $number : '-1');
		
		if(!$order)
			return $this->responseJsonCallback([ 'result' => FALSE, 'message' => $this->_('This order is not found') ]);

		if(!$provider->supports($order->getHandlerTranslateSupport()))
			return $this->responseJsonCallback([ 'result' => FALSE, 'message' => sprintf($this->_('The payment provider does NOT support %s method'), $order->getHandlerTranslate()) ]);

		$items = $order->getItems();
		if(!is_object($items) || !$items->is('Paymentgateway\ItemCollection') || $items->is_empty())
			return $this->responseJsonCallback([ 'result' => FALSE, 'message' => $this->_('Order is not found.') ]);
		
		$order->provider_code = $provider->getCode();
		try {
			$order_id = $order->save();
		} catch (\Core\Exception $e) {
			return $this->responseJsonCallback([ 'result' => FALSE, 'message' => $e->getMessage() ]);
		}

		$handler = $provider->getHandler($order->getHandlerTranslateSupport());
		
		$handler->meta('order_title', sprintf($this->_('Order #%s'), $order->number));
		
		if($order->discount)
		{
			$handler->meta('discount_item_name', $this->_('Coupon #') . $this->discount_code);
			$handler->meta('discount_item_description', $this->_('Coupon Discount'));
		}
		
		$handler->meta('return_url', $this->url([ 'order_number' => $order->number ], 'paymentgateway-return_url'));
		$handler->meta('cancel_url', $this->url([ 'order_number' => $order->number ], 'paymentgateway-ordercancel_url'));
		$handler->meta('notify_url', $this->url([ 'order_number' => $order->number ], 'paymentgateway-notify_url'));
		$handler->meta('after_url', $this->url([ 'order_number' => $order->number ], 'paymentgateway-after_url'));
		$handler->meta('manual_url', $this->url([ 'order_number' => $order->number ], 'paymentgateway-manual_url'));

		$response = FALSE;
		$previous = NULL;

		if($items->at(0)->is('Cart\SubscribeItem') && $items->at(0)->getParent())
			$previous = $previous = OrderManager::get($items->at(0)->getParent());

		$status = $previous ? $handler->upgrade($previous, $order, $response) : $handler->checkout($order, $response);

		if(Payment::STATUS_OK !== $status || !$response || !isset($response['redirect']))
			return $this->responseJsonCallback([ 'result' => 0 < $status, 'message' => $this->_('Payment failed.'), 'redirect' => $response['redirect'] ]);
		
		return $this->responseJsonCallback([ 'result' => $status, 'message' => $this->_('Redirecting&hellip; Please wait.'), 'redirect' => $response['redirect'] ]);
		
	}
	
}