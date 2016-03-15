<?php
namespace Paymentgateway;

use \Core\Text\String;

class OrderManager extends \Base\Model\Reference {

	protected $_name = 'order';

	protected $_referenceMap = [
		'User' => [
			'columns'           => 'user_id',
			'refTableClass'     => 'User\User',
			'refColumns'        => 'id',
			'singleRow'         => TRUE,
		],

		'Seller' => [
			'columns'           => 'seller_id',
			'refTableClass'     => 'User\User',
			'refColumns'        => 'id',
			'singleRow'         => TRUE,
		],

		'Status' => [
			'columns'           => 'status_id',
			'refTableClass'     => 'Paymentgateway\OrderStatus',
			'refColumns'        => 'id',
			'singleRow'         => TRUE,
		],

		'StatusDescription' => [
			'columns'           => 'status_id',
			'refTableClass'     => 'Paymentgateway\OrderStatusDescription',
			'refColumns'        => 'order_status_id',
			'where'             => '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()',
			'singleRow'         => TRUE,
		],

		'Currency' => [
			'columns'           => 'currency',
			'refTableClass'     => 'Currency\Currency',
			'refColumns'        => 'code',
			'singleRow'         => TRUE,
		],

	];

	//virtual map for reference
	protected $_referenceReverseMap = [
		'User\User' => [
			'columns'           => 'id',
			'refTableClass'     => 'Paymentgateway\OrderManager',
			'refColumns'        => 'user_id',
			'singleRow'         => TRUE,
		],
		'Paymentgateway\OrderStatus' => [
			'columns'           => 'id',
			'refTableClass'     => 'Paymentgateway\OrderStatus',
			'refColumns'        => 'status_id',
			'singleRow'         => TRUE,
		],
		'Paymentgateway\OrderStatusDescription' => [
			'columns'           => 'order_status_id',
			'refTableClass'     => 'Paymentgateway\OrderManager',
			'refColumns'        => 'status_id',
			'where'             => '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()',
			'singleRow'         => TRUE,
		],
	];

	public function __construct($config = [])
	{
		parent::__construct($config);
		$this->setRowClass('\Paymentgateway\Order');
	}

	public static function create($user_id, $seller_id, $module, $provider_code, $handler_code, $items, $shipping, $discount, $currency, $autosave = TRUE, $parent_id = NULL, $system = 0)
	{
		$user_id = (int) $user_id;
		$seller_id = 0 < (int) $seller_id ? (int) $seller_id : NULL;

		$order = (new self)->fetchNew();

		if(0 >= $user_id)
			throw new Exception(Exception::Corrupted);

		if(!is_object($items) || !$items->is('Paymentgateway\ItemCollection') || $items->is_empty())
			throw new Exception(Exception::InvalidCollection);

		$user = (new \User\User())->fetchRow(['id = ?' => $user_id]);
		
		$order->user_id = $user_id;
		$order->firstname = $user->firstname;
		$order->lastname = $user->lastname;
		$order->email = $user->email;
		
		$order->seller_id = $seller_id;
		$order->parent_id = 0 < (int) $parent_id ? (int) $parent_id : NULL;

		$order->module = $module;
		$order->provider_code = $provider_code;
		$order->handler_code = $handler_code;

		$sub_total = 0;
		
		foreach($items as $item)
			$sub_total += $item->getValue();

		$order->shipping = $shipping;
		$order->discount = $discount;

		$order->sub_total = $sub_total;
		$order->currency = $currency;

		$order->number = self::generateNumber();
		$order->items = serialize($items);
		
		$order->system = (int)$system;

		$order->net = $order->getSubTotal();
		$order->fee = 0;

		if($autosave && !$order->save())
			throw new Exception(Exception::Corrupted);

		return $order;
	}

	/*
	 * @return \Paymentgateway\Order
	 */
	public static function get($number)
	{
		return (new self)->fetchRow([ 'number = ?' => $number ]);
	}
	
	public static function generateNumber()
	{
		$number = mb_strtoupper(String::alphanum(Order::NUMBER_LENGTH));

		if((new self)->countByNumber($number))
			return self::generateNumber();
	
		return $number;
	}

}