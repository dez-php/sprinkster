<?php
namespace Paymentgateway;

use \Base\Model\Reference;

class Fee extends Reference {

	protected $_name = 'order_fee';

	protected $_referenceMap = [

		'Order' => [
			'columns' => 'order_id',
			'refTableClass' => 'Paymentgateway\Order',
			'refColumns' => 'id',
			'singleRow' => TRUE,
		],
		
	];

	public static function register($order_id, $percent, $value, $status_id = NULL)
	{
		$order_id = (int) $order_id;
		$percent = (float) $percent;
		$value = (float) $value;
		$status_id = (int) $status_id ?: NULL;

		if(0 >= $order_id || 0 >= $percent || 100 <= $percent || 0 >= $value)
			return;

		$fee = (new self)->fetchRow([ 'order_id = ?' => $order_id ]) ?: (new self)->fetchNew();

		if(!$fee->id)
		{
			$fee->order_id = $order_id;
			$fee->percent = $percent;
			$fee->value = $value;

			$fee->created_at = date('Y-m-d H:i:s');
		}

		$fee->status_id = $status_id;

		if(!$fee->save())
			return;

		$order = (new OrderManager)->fetchRow([ 'id = ?' => $fee->order_id ]);

		if(!$order)
			return;

		// $order->net = $order->getSubTotal() - $fee->value;
		// $order->fee = $fee->value;

		// $order->save();
	}

}