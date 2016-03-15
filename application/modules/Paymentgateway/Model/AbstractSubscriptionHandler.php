<?php
namespace Paymentgateway;

use \Core\Base\Action;

abstract class AbstractSubscriptionHandler extends AbstractHandler {

	public abstract function checkout(&$order, &$response);
	public abstract function confirm(&$order);
	public abstract function notify(&$order);
	public abstract function refund($order_id);

	public abstract function cancel(&$order);
	public abstract function upgrade(&$order, &$upgrade, &$response);
}