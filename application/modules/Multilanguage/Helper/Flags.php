<?php

namespace Multilanguage\Helper;

class Flags {
	
	protected $column_data = array();
	
	public function __construct($column_data) {
		$this->column_data = $column_data;
		$this->column_data['type'] = null;
	}
	
	public function form() {
		$images = glob(BASE_PATH . '/assets/images/flags/*');
		if($images) {
			foreach($images AS $image) {
				$name = basename($image);
				$this->column_data['list'][$name] = $name;
			}
			$this->column_data['type'] = 'Single';
		}
		return $this->column_data;
	}
	
}