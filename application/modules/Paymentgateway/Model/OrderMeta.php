<?php
namespace Paymentgateway;

class OrderMeta extends \Base\Model\Reference {

	protected $_referenceMap = [
		'Order' => [
			'columns'           => 'order_id',
			'refTableClass'     => 'Paymentgateway\OrderManager',
			'refColumns'        => 'id',
			'singleRow'         => TRUE,
		],
	];

	public static function matchOrder($name)
	{
		if(!$name)
			return NULL;

		$meta = (new self)->fetchRow(['name = ?' => $name ]);

		if(!$meta)
			return FALSE;

		return (new OrderManager)->fetchRow([ 'id = ?' => $meta->order_id ]);
	}

}