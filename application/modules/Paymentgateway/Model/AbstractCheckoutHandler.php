<?php
namespace Paymentgateway;

use \Core\Base\Action;

abstract class AbstractCheckoutHandler extends AbstractHandler {

	public abstract function checkout(&$order, &$response);
	public abstract function confirm(&$order);
	public abstract function notify(&$order);
	public abstract function refund($order_id);

	/**
	 * Calculates the commission % fee for seller.
	 * It returns NULL when no or invalid one is set.
	 * Should be between 0 and 100. Cannot be 100.
	 * @param  int   $seller_id The user ID of the seller
	 * @return mixed            NULL on none or failure, floating point percent otherwise
	 */
	public function commission($order)
	{
		if(!Action::getInstance()->isModuleAccessible('Seller') || 0 >= (int) $order->seller_id)
			return NULL;
		
		$seller = \Seller\Helper\Subscription::enableAddItem($order->seller_id);
		
		if(!$seller)
			return NULL;

		$commission = is_object($seller) ? (float) $seller->commission : 0;

		if(0 >= $commission || 100 <= $commission)
			return NULL;

		return $commission;
	}

}