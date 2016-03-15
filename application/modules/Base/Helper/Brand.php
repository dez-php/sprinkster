<?php

namespace Base\Helper;

class Brand {
	
	protected $column_data = array();
	
	public function __construct($column_data) {
		$this->column_data = $column_data;
		$this->column_data['type'] = null;
	}
	
	public function form() {
		$this->column_data['type'] = 'Single';
		$this->column_data['list']['0'] = 'No';
		if(\Core\Registry::isRegistered('module_powered_check') && \Core\Registry::get('module_powered_check') == 'false') {
			$this->column_data['list']['1'] = 'Yes';
		}
		return $this->column_data;
	}
	
}