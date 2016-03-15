<?php
namespace Paymentgateway;

use \Core\Db\Init;
use \Core\Base\MemcachedManager;

use \Base\Config;

class OrderStatus extends \Base\Model\Reference {

	protected $_name = 'order_status';

	public function __construct($config = [])
	{
		parent::__construct($config);
		$this->setRowClass('Paymentgateway\OrderStatusRow');
	}

	protected $_referenceMap = [
		'OrderStatusDescription' => [
			'columns'           => 'id',
			'refTableClass'     => 'Paymentgateway\OrderStatusDescription',
			'refColumns'        => 'order_status_id',
			'where'             => '"language_id = " . \Core\Base\Action::getModule(\'Language\')->getLanguageId()'
		],
	];

	public static function getOrderStatuses() {

		$db = \Core\Db\Init::getDefaultAdapter();

		$sql = $db->select()
			->from('order_status',array('order_status_description.*', 'order_status.*'))
			->joinLeft('order_status_description', 'order_status_description.order_status_id=order_status.id', '')
			->where('order_status_description.language_id = ' . \Core\Base\Action::getModule('Language')->getLanguageId());
		return $db->fetchAll($sql);
	}

	public static function getOrderStatus($id) {

		$db = Init::getDefaultAdapter();

		$sql = $db->select()
			->from('order_status',array('order_status_description.*', 'order_status.*'))
			->joinLeft('order_status_description', 'order_status_description.order_status_id=order_status.id', '')
			->where('order_status.id = ' . $id)
			->where('order_status_description.language_id = ' . \Core\Base\Action::getModule('Language')->getLanguageId());
		return $db->fetchRow($sql);
	}

	public static function getLockingStatusesIds()
	{
		return MemcachedManager::get(MemcachedManager::key(__CLASS__, __METHOD__), function() {
			$ids = Init::getDefaultAdapter()->fetchCol('SELECT s.id FROM order_status s WHERE s.locking = 1');
			array_walk($ids, 'intval');
			return !empty($ids) ? $ids : [ 0 ];
		});
	}

	public static function getLockingStatuses()
	{
		return (new self)->fetchAll([ 'locking = 1' ]);
	}

}