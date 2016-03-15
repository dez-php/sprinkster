<?php
namespace Paymentgateway;

use \Core\Db\Table\Row\AbstractRow;
use \Core\Text\String;
use \Base\Traits\Util;
use \Paymentgateway\Payment;
use \Paymentgateway\PaymentManager;

class Order extends AbstractRow {

	use Util;

	private $statusChanged = false;

	const NUMBER_LENGTH = 20;

	public function __get($name)
	{
		switch($name)
		{
			case 'uid': return $this->number;
			case 'user': return $this->user_id;
			case 'from_user': return $this->seller_id;
			case 'date_added': return $this->created_at;
			case 'cart_status_id': return $this->status_id;

			case 'amount': return $this->getAmount();
			case 'status': return $this->Status();
		}

		return parent::__get($name);
	}

	public function setStatusId($status_id) {
		$this->status_id = $status_id;
	}

	/**
	 * Allows post-insert logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _postInsert()
	{
		foreach ($this->getItems()->items() as $item)
			if (isset($item->stock->auction_id) && $item->stock->auction_id)
				\Auction\PinAuction::setPurchase($item->stock->auction_id, $this->id);
	}

	/**
	 * Allows pre-insert logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _insert()
	{
		$this->created_at = date('Y-m-d H:i:s');
		$this->modified_at = NULL;
		$this->deleted_at = NULL;
	}

	/**
	 * Allows pre-update logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _update()
	{
		$this->modified_at = date('Y-m-d H:i:s');
	}

	/**
	 * Allows post-update logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _postUpdate()
	{

	}

	/**
	 * Allows post-delete logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _postDelete()
	{

	}

	protected function round($value)
	{
		// return floor((float) $value * 100) / 100;
		return round($value, 2, PHP_ROUND_HALF_DOWN);
	}

	/**
	 * @return \Paymentgateway\ItemCollection
	 */
	public function getItems()
	{
		return $this->items ? unserialize($this->items) : NULL;
	}

	public function getShippingStatus() {
		$status = $this->meta('shipping_status');
		$shipping_status = \Core\Db\Init::getDefaultAdapter()->fetchOne('
				SELECT ssd.title
				FROM shipping_status_description ssd
				WHERE
					ssd.ship_status_id = ?
				AND
					ssd.language_id = ?
			',

			[ (int) $status, (int)\Core\Base\Action::getModule('Language')->getLanguageId() ]
		);
		return $shipping_status;
	}

	public function getStatus() {
		$status = $this->cart_status_id;
		$order_status = (new OrderStatusDescription)->fetchRow(array('order_status_id = ?' => $status));
		return $order_status;
	}

	public function getCustom()
	{
		return $this->custom ? unserialize($this->custom) : NULL;
	}

	public function getSubTotal()
	{
		return $this->round((float) $this->sub_total - (float) $this->discount);
	}

	public function getShipping()
	{
		return $this->round((float) $this->shipping);
	}

	public function getDiscount()
	{
		return $this->round((float) $this->discount);
	}

	public function getAmount()
	{
		return $this->getSubTotal() + $this->getShipping();
	}

	public function getCurrency()
	{
		return $this->currency;
	}

	public function getNumber()
	{
		return $this->number;
	}

	public function getHandlerCode() {
		return $this->handler_code;
	}

	public function switchCurrency($currency)
	{
		$test = TRUE;

		$this->shipping = \Currency\Helper\Format::convert((float) $this->shipping, $this->currency, $currency, $test);

		if(!$test)
			throw new Exception(Exception::ConversionFailed);

		$this->discount = \Currency\Helper\Format::convert((float) $this->discount, $this->currency, $currency, $test);

		if(!$test)
			throw new Exception(Exception::ConversionFailed);

		if(!$test)
			throw new Exception(Exception::ConversionFailed);

		$items = unserialize($this->items);
		$sub_total = 0;

		foreach($items as $item)
		{
			$item->switchCurrency($currency);
			$sub_total += $item->price * $item->qty;
		}

		$this->items = serialize($items);
		$this->currency = $currency;
		$this->sub_total = $sub_total;

		if(!$this->save())
			throw new Exception(Exception::ConversionFailed);
	}

	public function setStatus($status)
	{
		if(0 >= (int) $status)
			return;

		$this->status_id = (int) $status;

		if(!$this->save())
			throw new Exception(Exception::Corrupted);
	}

	public function custom($custom)
	{
		$this->custom = serialize($custom);
		$this->save();
	}

	public function snapshot($notes = '')
	{
		$user = $this->User();
		$seller = $this->Seller();

		$snapshot = (new OrderHistory)->fetchNew();
		$snapshot->order_id = $this->id;
		$snapshot->user_id = $this->user_id;
		$snapshot->user = $user ? $user->getUserFullName() . ' - ' . $user->username : NULL;
		$snapshot->seller_id = $this->seller_id;
		$snapshot->seller = $seller ? $seller->getUserFullName() . ' - ' . $seller->username : NULL;
		$snapshot->status_id = $this->status_id;
		$snapshot->created_at = date('Y-m-d H:i:s');
		$snapshot->module = $this->module;
		$snapshot->provider_code = $this->provider_code;
		$snapshot->handler_code = $this->handler_code;
		$snapshot->number = $this->number;
		$snapshot->currency = $this->currency;
		$snapshot->sub_total = $this->sub_total;
		$snapshot->discount = $this->discount;
		$snapshot->shipping = $this->shipping;
		$snapshot->items = $this->items;
		$snapshot->custom = $this->custom;
		$snapshot->system = $this->system;
		$snapshot->notes = $notes;

		if(!$snapshot->save())
			throw new \Core\Exception('Failed to create order snapshot in history.');
	}

	public function getHandlerTranslate() {
		switch($this->handler_code) {
			case Payment::PURCHASE:
				return 'purchase';
			case Payment::SUBSCRIPTION:
				return 'subscription';
			case Payment::CHAIN:
				return 'chain';
			case Payment::DEPOSIT:
				return 'deposit';
			default:
				return null;
		}
	}

	public function getHandlerTranslateSupport() {
		switch($this->handler_code) {
			case Payment::PURCHASE:
				return Payment::PURCHASE_SUPPORT;
			case Payment::SUBSCRIPTION:
				return Payment::SUBSCRIPTION_SUPPORT;
			case Payment::CHAIN:
				return Payment::CHAIN_SUPPORT;
			case Payment::DEPOSIT:
				return Payment::DEPOSIT_SUPPORT;
			default:
				return 0;
		}
	}

	/**
	 * @throws \Core\Exception
	 * @return \Cart\Abs\Bridge
	 */
	public function getRoute() {
		if(!$this->module || !$this->handler_code)
			throw new \Core\Exception('Missing Route data!');
		$callTo = \Core\Base\Front::getInstance()->formatHelperName('\\' . $this->module . '\payment\\' . $this->getHandlerTranslate());

		if(\Core\Loader\Loader::isLoadable($callTo) && (new \Core\Base\Core)->isModuleAccessible((new \Core\Base\Core)->getModuleBaseNamespace($callTo)) && class_exists($callTo)) {
			$object = new $callTo($this);
			if($object->is('Cart\Abs\Bridge'))
				return $object;
		}
		return new \Cart\Abs\NoBridge($this);
	}

	public function meta($name)
	{
		$meta = (new OrderMeta)->fetchRow([ 'order_id = ?' => $this->id, 'name = ?' => $name ]);

		if(!$meta)
			return NULL;

		return $meta->serialized ? unserialize($meta->value) : $meta->value;
	}

	public static function getOrdersAmount() {
		$totalSubscriptionIncome = \Core\Db\Init::getDefaultAdapter()->fetchAll('
		SELECT SUM(o.sub_total) as amount, o.currency
		FROM `order` as o
		WHERE o.system != 1
		AND o.status_id = ?
		GROUP BY o.currency',
			[\Base\Config::get ( 'config_complete_status_id' )]);
		return $totalSubscriptionIncome;
	}

	public static function getCompletedPurchasesCount() {
		$totalSubscriptionIncome = \Core\Db\Init::getDefaultAdapter()->fetchOne('
		SELECT COUNT(*) as order_count
		FROM `order` as o
		WHERE o.system != 1
		AND o.status_id = ?',
			[\Base\Config::get ( 'config_complete_status_id' )]);
		return $totalSubscriptionIncome;
	}
	public static function getCompletedPurchasesWithFeeCount() {
		$totalSubscriptionIncome = \Core\Db\Init::getDefaultAdapter()->fetchOne('
		SELECT COUNT(*) as order_count
		FROM `order` as o
		WHERE o.system != 1
		AND o.status_id = ?
		AND o.fee != ""',
			[\Base\Config::get ( 'config_complete_status_id' )]);
		return $totalSubscriptionIncome;
	}

	public static function getTotalSubscriptionIncome() {
		$totalSubscriptionIncome = \Core\Db\Init::getDefaultAdapter()->fetchAll('
				SELECT SUM(o.sub_total) as amount, o.currency
				FROM `order` as o
				WHERE
					o.system = 1
				AND
					o.status_id = ?
				GROUP BY o.currency
			', [\Base\Config::get ( 'config_complete_status_id' )]);
		return $totalSubscriptionIncome;
	}

	public static function getTotalFeeIncome() {
		$totalFeeIncome = \Core\Db\Init::getDefaultAdapter()->fetchAll('
				SELECT SUM(o.fee) as amount, o.currency
				FROM `order` as o
				WHERE
					fee != ""
				AND
					o.status_id = ?
				GROUP BY o.currency
			', [\Base\Config::get ( 'config_complete_status_id' )]);
		return $totalFeeIncome;
	}

	public static function getLatestYearSubscriptionIncomeByMonth()
	{
		$totalSubscriptionIncomeByMonth = \Core\Db\Init::getDefaultAdapter()->fetchAll('
		SELECT SUM(o.sub_total) as amount, MONTH(o.created_at) as month_amount, o.currency
		FROM `order` as o
		WHERE
			o.system = 1
			AND
			o.status_id = ?
		GROUP BY MONTH(o.created_at), o.currency
		HAVING MAX(YEAR(o.created_at))', [\Base\Config::get('config_complete_status_id')]);
		return $totalSubscriptionIncomeByMonth;
	}

	public static function getLatestYearFeeIncomeByMonth()
	{
		$totalSubscriptionIncomeByMonth = \Core\Db\Init::getDefaultAdapter()->fetchAll('
		SELECT SUM(o.fee) as amount, MONTH(o.created_at) as month_amount, o.currency
		FROM `order` as o
		WHERE
			fee != ""
		AND
			o.status_id = ?
		GROUP BY  MONTH(o.created_at),o.currency
		HAVING MAX(YEAR(o.created_at))', [\Base\Config::get('config_complete_status_id')]);
		return $totalSubscriptionIncomeByMonth;
	}

	public function setMeta($name, $value)
	{
		if(!$this->id)
			return FALSE;

		$meta = (new OrderMeta)->fetchRow([ 'order_id = ?' => $this->id, 'name = ?' => $name ]);

		if(!$meta)
			$meta = (new OrderMeta)->fetchNew();

		$field = $meta->id ? 'modified_at' : 'created_at';

		$meta->order_id = $this->id;
		$meta->$field = date('Y-m-d H:i:s');
		$meta->name = $name;
		$meta->value = is_scalar($value) ? $value : serialize($value);
		$meta->serialized = (int) !is_scalar($value);

		if(!$meta->save())
			return FALSE;

		return TRUE;
	}

	public static function locate($name, $value)
	{
		$meta = (new OrderMeta)->fetchRow([ 'name = ?' => $name, 'value = ?' => $value ]);

		if(!$meta)
			return NULL;

		return (new OrderManager)->fetchRow([ 'id = ?' => $meta->order_id ]);
	}

	public function respawn()
	{
		$order = $this->toArray();
		unset($order['id']);

		$result = $this->getTable()->fetchNew();
		$result->setFromArray($order);
		$result->id = NULL;
		$result->parent_id = $this->id;
		$result->number = OrderManager::generateNumber();

		if(!$result->save())
			throw new \Core\Exception('Order raspawn failed.');

		return $result;
	}

	public function getProviderCode() {
		return $this->provider_code;
	}
	
	public function getProvider() {
		return (new PaymentManager())->getProvider($this->provider_code, NULL);
	}
	public function getHandler()
	{
		if(!$this->provider_code || !$this->handler_code)
			return NULL;

		$provider = PaymentManager::getProvider($this->provider_code);

		if(!$provider)
			return NULL;

		return $provider->getHandler($this->handler_code);
	}
}