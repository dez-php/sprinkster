<?php

namespace Currency\Helper;

class Select {
	
	protected $column_data = array();
	
	public function __construct($column_data) {
		$this->column_data = $column_data;
		$this->column_data['type'] = null;
	}
	
	public function form() {
		$currencyTable = new \Currency\Currency();
		$rows = $currencyTable->fetchAll(array('status = 1'));
		if($rows->count()) {
			foreach($rows AS $row) {
				$this->column_data['list'][$row->code] = $row->title;
			}
			$this->column_data['type'] = 'Single';
		}
		return $this->column_data;
	}
	
}