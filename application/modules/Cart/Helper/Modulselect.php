<?php

namespace Cart\Helper;

class Modulselect {
	
	protected $column_data = array();
	
	public function __construct($column_data) {
		$this->column_data = $column_data;
		$this->column_data['type'] = 'Single';
	}
	
	public function filter($name) {
		$list = array();
		$list['*'] = '';
		
		$table = new \Paymentgateway\OrderManager();
		$sql = $table->select()
					->group('module')
					->order('module ASC')
					->where('system = 1')
					->where('status_id > 0');
		$rows = $table->fetchAll($sql);
		
		foreach($rows AS $row) {
			$list[$row->module] = strip_tags($row->getRoute()->getTitle());
		}
		
		return new \Core\Htmlform\Elements\Single($name, array(
			'list' => $list,
			'label' => '',
			'skin' => 'select',
			'defaultValue' => \Core\Http\Request::getInstance()->getParam($name)
		), new \Core\Htmlform\Htmlform('form'));
	}
	
}