<?php

namespace Local\Helper;

class Select {
	
	protected $column_data = array();
	protected $translate;
	
	public function __construct($column_data) {
		$this->column_data = $column_data;
		$this->column_data['type'] = null;
		$this->translate = new \Translate\Locale('Front\\'.__NAMESPACE__, \Core\Base\Action::getModule('Language')->getLanguageId());
	}
	
	public function form() {
		$currencyTable = new \Base\UploadMethod();
		$rows = $currencyTable->fetchAll();
		if($rows->count()) {
			foreach($rows AS $row) {
				if($row->sys ? true : \Base\Config::get( strtolower($row->module) . '_status')) {
					$this->column_data['list'][$row->module] = $this->translate->_($row->title);
				}
			}
			$this->column_data['type'] = 'Single';
		}
		return $this->column_data;
	}
	
}