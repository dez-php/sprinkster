<?php

namespace Cart\Helper;

class Providerselect {
	
	protected $column_data = array();
	
	public function __construct($column_data) {
		$this->column_data = $column_data;
		$this->column_data['type'] = 'Single';
	}
	
	public function filter($name) {
		$list = array();
		$list['*'] = '';
		
		$rows = (new \Paymentgateway\PaymentProvider)->fetchAll(NULL, 'name ASC');

		$list['FREE'] = 'Free order';
		foreach($rows AS $row) {
			$list[$row->code] = $row->name;
		}
		
		return new \Core\Htmlform\Elements\Single($name, array(
			'list' => $list,
			'label' => '',
			'skin' => 'select',
			'defaultValue' => \Core\Http\Request::getInstance()->getParam($name)
		), new \Core\Htmlform\Htmlform('form'));
	}
	
}