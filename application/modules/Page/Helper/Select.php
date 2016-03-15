<?php

namespace Page\Helper;

class Select {
	
	protected $column_data = array();
	
	public function __construct($column_data) {
		$this->column_data = $column_data;
		$this->column_data['type'] = null;
	}
	
	public function form() {
		$pageTable = new \Page\Page();
		$rows = $pageTable->fetchAll(array('status = 1'))
				->findMapData('Page\PageDescription');
		
		if($rows->count()) {
			foreach($rows AS $row) {
				$this->column_data['list'][$row->id] = $row->getMapData('page\pagedescription')->title;
			}
			$this->column_data['type'] = 'Single';
		}
		return $this->column_data;
	}
	
}