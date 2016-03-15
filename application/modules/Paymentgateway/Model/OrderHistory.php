<?php
namespace Paymentgateway;

class OrderHistory extends \Base\Model\Reference {

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
			'refTableClass'     => 'Paymentgateway\OrderHistory',
			'refColumns'        => 'user_id',
			'singleRow'         => TRUE,
		],
		'Paymentgateway\OrderStatus' => [
			'columns'           => 'id',
			'refTableClass'     => 'Paymentgateway\OrderHistory',
			'refColumns'        => 'status_id',
			'singleRow'         => TRUE,
		],
		'Paymentgateway\OrderStatusDescription' => [
			'columns'           => 'order_status_id',
			'refTableClass'     => 'Paymentgateway\OrderHistory',
			'refColumns'        => 'status_id',
			'where'             => '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()',
			'singleRow'         => TRUE,
		],
	];

}