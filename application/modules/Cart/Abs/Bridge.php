<?php
namespace Cart\Abs;

use \Base\Traits\Util;

abstract class Bridge {
	
	use Util;
	
	/**
	 * @var \Paymentgateway\Order
	 */
	protected $order;
	
	public function __construct(\Paymentgateway\Order $order) {
		$this->order = $order;
	}
	
	/**
	 * @return \Translate\Locale
	 */
	public function getTranslate() {
		return new \Translate\Locale('Front\\' . get_called_class(), \Core\Base\Action::getModule('Language')->getLanguageId());
	}
	
	/**
	 * @return \Paymentgateway\Order
	 */
	public function getOrder() {
		return $this->order;
	}
	
	/**
	 * 
	 * generate new order with parent_id for subscription
	 * 
	 * @param array $data
	 */
	abstract public function addAction($data = []);
	
	/**
	 * 
	 * complete payment
	 * 
	 * @param array $data
	 */
	abstract public function completeAction($data = []);
	
	/**
	 * 
	 * remove action
	 * 
	 * @param array $data
	 */
	abstract public function removeAction($data = []);
	
	abstract public function getTitle();
	
	abstract public function getInformation();
	
	public function isActiveSubscription() {
		return false;
	}
	
	public function isRenew() {
		return false;
	}
	
}