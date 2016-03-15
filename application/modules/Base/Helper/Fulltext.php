<?php

namespace Base\Helper;

class Fulltext {
	
	protected $column_data = array();
	
	public function __construct($column_data) {
		$this->column_data = $column_data;
		$this->column_data['type'] = null;
	}
	
	public function form() {
		$this->column_data['type'] = 'Single';
		$this->column_data['list']['0'] = 'No';
		$db = \Core\Db\Init::getDefaultAdapter();
		if( version_compare($db->getServerVersion(), '5.6','>=') ) {
			$this->column_data['list']['1'] = 'Yes';
		}
		return $this->column_data;
	}
	
}