<?php
namespace Paymentgateway;

class Purchase extends \Base\Model\Reference {

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

		'Currency' => [
			'columns'           => 'currency',
			'refTableClass'     => 'Currency\Currency',
			'refColumns'        => 'code',
			'singleRow'         => TRUE,
		],

	];

	// virtual map for reference
	protected $_referenceReverseMap = [
		'Paymentgateway\OrderStatus' => [
			'columns'           => 'id',
			'refTableClass'     => 'Paymentgateway\OrderStatus',
			'refColumns'        => 'status_id',
			'singleRow'         => TRUE,
		],
		'Paymentgateway\OrderStatusDescription' => [
			'columns'           => 'order_status_id',
			'refTableClass'     => 'Paymentgateway\Purchase',
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

	public static function get($number)
	{
		return (new self)->fetchRow([ 'number = ?' => $number, 'status_id IS NOT NULL' ]);
	}

	public static function getAll(array $where = [], $order = null, $limit = null, $offset = null)
	{
		$where[] = 'status_id IS NOT NULL';
		return (new self)->fetchAll($where, $order, $limit, $offset);
	}

	public static function countTransactions($buyer_id, $seller_id)
	{
		$table = new self;
		$buyer_id = (int) $buyer_id;
		$seller_id = (int) $seller_id ?: NULL;

		$sql = $table->select()
			->from($table, 'COUNT(id)')
			->where("user_id = {$buyer_id} AND seller_id = {$seller_id} AND status IS NOT NULL")
			->orWhere("user = {$seller_id} AND from_user = {$buyer_id} AND status IS NOT NULL");

		return $table->getAdapter()->fetchOne($sql);
	}

}