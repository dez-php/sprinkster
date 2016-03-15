<?php

namespace Cart\Helper;

class Statusselect {
	
	protected $column_data = array();
	
	public function __construct($column_data = null) {
		$this->column_data = $column_data;
		$this->column_data['type'] = null;
	}
	
	public function form() {
		$currencyTable = new \Paymentgateway\OrderStatus();
		$rows = $currencyTable->fetchAll()
			->findMapData('Paymentgateway\OrderStatusDescription');

		if($rows->count()) {
			foreach($rows AS $row) {
				$this->column_data['list'][$row->id] = $row->getMapData('paymentgateway\orderstatusdescription')->title;
			}
			$this->column_data['type'] = 'Single';
		}
		return $this->column_data;
	}
	
	public function filter($name) {
		$this->form();
		$list = array();
		$list['*'] = '';
		$list['null'] = 'Abandoned Orders';
		if(isset($this->column_data['list']) && $this->column_data['list']) {
			foreach($this->column_data['list'] AS $k=>$v) {
				$list[$k] = $v;
			}
		}
		return new \Core\Htmlform\Elements\Single($name, array(
			'list' => $list,
			'label' => '',
			'skin' => 'select',
			'defaultValue' => \Core\Http\Request::getInstance()->getParam($name)
		), new \Core\Htmlform\Htmlform('form'));
	}
	
	public function history($name) {
		$this->form();
		$list = array();
		$list[''] = '';
		foreach($this->column_data['list'] AS $k=>$v) {
			$list[$k] = $v;
		}
		return new \Core\Htmlform\Elements\Single($name, array(
			'list' => $list,
			'label' => '',
			'skin' => 'select',
			'defaultValue' => \Core\Http\Request::getInstance()->getParam($name)
		), new \Core\Htmlform\Htmlform('form'));
	}
	
}