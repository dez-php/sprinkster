<?php
namespace Paymentgateway;

use \Core\Log;

use \Paymentgateway\Payment;
use \Paymentgateway\PaymentManager;

use \Paymentgateway\Order;
use \Paymentgateway\OrderManager;

class PaymentController extends \Core\Base\Action
{
	
	public function init()
	{
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function indexAction()
	{
		$this->forward('error404');
	}
	
	public function payAction()
	{
		$request = $this->getRequest();
		$number = $request->getParam('order_number');

		if(!$number)
			return $this->render('failure');

		$order = OrderManager::get($number);

		if(!$order)
			return $this->render('failure');

		$provider = PaymentManager::getProvider($order->provider_code);

		if(!$provider)
			return $this->render('failure');

		$handler = $provider->getHandler($order->handler_code);

		$handler->meta('notify_url', $this->url([ 'order_number' => $order->number ], 'paymentgateway-notify_url'));
		$handler->meta('seller_id', $order->seller_id);

		$result = $handler->confirm($order);

		if(Payment::STATUS_OK !== $result)
			return $this->render('failure');

		$order->getRoute()->completeAction(['action' => 'pay' ]);
		
		$this->render('success', [
			'order' => $order,
			'handler_type' => $order->getHandlerTranslate(),
		]);
	}

	public function notifyAction()
	{
		$this->noLayout(true);
		//\Core\Base\Action::getInstance()->getComponent('document')->reset();

		$request = $this->getRequest();
		$number = $request->getParam('order_number');
		$order = NULL;
		// $custom = $request->getPost('custom');

		// if($custom)
		// 	$custom = unserialize($custom);

		// if(!$number && $custom)
		// 	$number = isset($custom['order']) ? $custom['order'] : NULL;

		if(!$number)
			return $this->forward('anonymous');

		$order = OrderManager::get($number);

		if(!$order)
			return;

		$provider = PaymentManager::getProvider($order->provider_code);

		if(!$provider)
			return;

		$previous = $order->status_id;

		$handler = $provider->getHandler($order->handler_code);

		$result = $handler->notify($order);

		if(Payment::STATUS_OK !== $result)
			return;

		$this->notifySellerForChangedOrderStatus($order);
		//$this->purchase($order);
		$order->getRoute()->completeAction([
			'previous' => $previous,
			'action' => 'notify'
		]);

// 		if($previous != $order->status_id && \Base\Config::get('paypal_completed_status_id') == $order->status_id)
// 			$this->updateQuantity($order);
	}

	private function notifySellerForChangedOrderStatus($order) {
		$seller = $order->Seller();
		(new \Notification\Notification)->send('store_payment_status_change', [
			'user_fullname' => $seller->getUserFullname(),
			'purchase_url' => $this->url([ 'controller' => 'purchase', 'action' => 'detail', 'id' => $order->id ], 'store_c_a_id'),
			'language_id' => $seller->language_id,
			'email' => $seller->email,
			'fullname' => $seller->getUserFullname(),
			'notify' => 1
		]);
		\Activity\Activity::setFromTo($order->user_id, $seller->id, 'SALE_COMPLETED', null, null, serialize([ 'controller' => 'purchase', 'action' => 'detail', 'id' => $order->id ]));
	}

	public function anonymousAction()
	{

		$this->noLayout(true);
		//\Core\Base\Action::getInstance()->getComponent('document')->reset();

		$handler = PaymentManager::detect();

		if(!$handler)
			return \Core\Log::write('Could not detect proper handler for dealing with anonymous request.');

		$order = $handler->detectOrder();

		if(!$order)
			return \Core\Log::write('Could not find order by anonymous request.');

		$previous = $order->status_id;
		$result = $handler->notify($order);

		if(Payment::STATUS_OK !== $result)
			return Log::write("Failed to process anonymous notification for Order #{$order->number}.");

		$order->getRoute()->completeAction([
			'previous' => $previous,
			'action' => 'notify'
		]);	
	}

	public function afterAction()
	{
		$request = $this->getRequest();
		$number = $request->getParam('order_number');

		if(!$number)
			return $this->render('failure');

		$order = OrderManager::get($number);

		if(!$order)
			return $this->render('failure');

		$provider = PaymentManager::getProvider($order->provider_code);

		if(!$provider)
			return $this->render('failure');

		$handler = $provider->getHandler($order->handler_code);

		$handler->meta('notify_url', $this->url([ 'order_number' => $order->number ], 'paymentgateway-notify_url'));

		$result = $handler->confirm($order);

		if(Payment::STATUS_OK !== $result)
		 	return $this->render('failure');

		// if(Payment::STATUS_OK === $result) {
		// 	$order->getRoute()->completeAction([
		// 		'action' => 'after'
		// 	]);
		// }
		
		if(Payment::STATUS_OK === $result)
			return $this->render('success', [
				'order' => $order,
				'handler_type' => $order->getHandlerTranslate(),
			]);
		
		$this->render('failure');
	}

	public function manualAction()
	{
		$request = $this->getRequest();
		$number = $request->getParam('order_number');

		if(!$number)
			return $this->render('failure');

		$order = OrderManager::get($number);
		$custom = $order->getCustom();

		if(!$order || !is_array($custom))
			return $this->render('failure');

		if(!isset($custom['payment_details']) || !is_array($custom['payment_details']) || empty($custom['payment_details']))
			return $this->render('failure');

		$provider = PaymentManager::getProvider($order->provider_code);

		if(!$provider)
			return $this->render('failure');

		$handler = $provider->getHandler($order->handler_code);

		$result = $handler->confirm($order);

		if(Payment::STATUS_OK !== $result)
		 	return $this->render('failure');

		return $this->render('manual', [
			'order' => $order,
			'details' => $custom['payment_details'],
			'handler_type' => $order->getHandlerTranslate(),
		]);
	}

	public function ordercancelAction() {
		$request = $this->getRequest();
		$number = $request->getParam('order_number');

		if(!$number)
			return $this->render('failure');

		$order = OrderManager::get($number);

		if(!$order)
			return $this->render('failure');

		if(($cancel_redirect = self::getModuleConfig($order->module)->get('cancel_redirect')) && $router = $cancel_redirect->get('router')) {
			$options = $cancel_redirect->get('options') ? $cancel_redirect->get('options')->toArray() : [];
			$this->redirect($this->url($options, $router));
		}
		return $this->render('failure');
	}

	public function subscriptionscancelAction() {
		$this->render('cancel');
	}

	public function cancelAction() {
		$request = $this->getRequest();
		$number = $request->getParam('order_number');

		if(!$number)
			return $this->render('failure');

		$order = OrderManager::get($number);

		if(!$order)
			return $this->render('failure');

		if(($cancel_redirect = self::getModuleConfig($order->module)->get('cancel_redirect')) && $router = $cancel_redirect->get('router')) {
			$options = $cancel_redirect->get('options') ? $cancel_redirect->get('options')->toArray() : [];
			$this->redirect($this->url($options, $router));
		}
		return $this->render('failure');


		///////// where go on cancel
	}
	
	public function successAction()
	{
		$request = $this->getRequest();
		$number = $request->getParam('order_number');

		if(!$number)
			return $this->render('failure');

		$order = OrderManager::get($number);

		if(!$order)
			return $this->render('failure');

		$this->render('success', [
			'order' => $order,
			'handler_type' => $order->getHandlerTranslate(),
			'pseudo_type' => $order->meta('pseudo_type'),
		]);
	}

	public function cancelsubscriptionAction($data = null)
	{

		$order_number = isset($data['order_number']) ? $data['order_number'] : NULL;
		$number = $order_number ? $order_number : $this->getRequest()->getParam('order_number');
		$order = OrderManager::get($number);
		
		$self = \User\User::getUserData();
		
		if(!$order || !$order->is('Paymentgateway\Order') || $order->getHandlerCode() != Payment::SUBSCRIPTION || $order->user_id != $self->id) {
			if($order_number) 
				return $this->_('Unable find your current subscription');
			$this->forward('error404');
		}

		if('FREE' === $order->getProviderCode())
		{
			$order->getRoute()->removeAction();
			if($order_number)
				return true;

			$this->forward('subscriptionscancel');
		}

		$handler = $order->getHandler();

		if(!$handler) {
			if($order_number)
				return sprintf($this->_('Unable remove your current subscription from %s'), $order->getProvider()->getName());
			$this->forward('error404');
		}

		$result = $handler->cancel($order);

		if(Payment::STATUS_OK !== $result) {
			if($order_number)
				return sprintf($this->_('Unable remove your current subscription from %s'), $order->getProvider()->getName());
			$this->forward('ordercancel');
//			return $this->render('failure');
		}

		$order->getRoute()->cancelAction();

		if($order_number)
			return true;

		$this->forward('subscriptionscancel');
	}

}
