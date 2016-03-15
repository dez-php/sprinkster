<?php

namespace Base\Helper;

class Template extends \Core\Base\Core {
	
	protected $column_data = array();
	
	public function __construct($column_data) {
		$this->column_data = $column_data;
		$this->column_data['type'] = null;
	}
	
	public function form() {
		$this->column_data['type'] = 'Single';
		$this->column_data['list'] = [];
		//$this->column_data['list'] = array_map(function($array) { $tmp = array(); foreach($array AS $a) { $tmp[$a] = $a; } return $tmp; }, \Core\Date\Timezones::getTimezonse());
		$theme_dir = $this->getFrontController()->getThemeDirectory();
		if(file_exists($theme_dir) && is_dir($theme_dir)) {
			$themes = glob($theme_dir . '/*');
			if($themes) {
				foreach($themes AS $theme) {
					$name = basename($theme);
					$this->column_data['list'][$name] = $name;
				}
			}
		}
		return $this->column_data;
	}
	
}