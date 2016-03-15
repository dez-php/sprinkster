<?php

namespace Base\Helper;

class Timezone {
	
	protected $column_data = array();
	
	public function __construct($column_data) {
		$this->column_data = $column_data;
		$this->column_data['type'] = null;
	}
	
	public function form() {
		$this->column_data['type'] = 'Single';
		$this->column_data['list'] = array_map(function($array) { $tmp = array(); foreach($array AS $a) { $tmp[$a] = $a; } return $tmp; }, \Core\Date\Timezones::getTimezonse());
		return $this->column_data;
	}
	
}