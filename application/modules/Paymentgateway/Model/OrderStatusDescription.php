<?php

namespace Paymentgateway;

class OrderStatusDescription extends \Base\Model\Reference {
	
	protected $_name = 'order_status_description';
	

	protected $_referenceMap = [
		'OrderStatus' => [
			'columns'           => 'order_status_id',
			'refTableClass'     => 'Paymentgateway\OrderStatus',
			'refColumns'        => 'id',
			'singleRow'         => TRUE,
		],

		
		'Language' => [
			'columns'           => 'language_id',
			'refTableClass'     => 'Language\Language',
			'refColumns'        => 'id',
		],
	];
	
}

?>