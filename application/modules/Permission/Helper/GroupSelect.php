<?php

namespace Permission\Helper;

class GroupSelect {
	
	protected $column_data = [];
	
	public function __construct($column_data = [])
	{
		$this->column_data = $column_data;
		$this->column_data['type'] = NULL;
	}
	
	public function form()
	{
		$groupTable = new \Permission\PermissionGroup();
		$rows = $groupTable->fetchAll(NULL, 'name ASC');

		if(0 >= $rows->count())
			return $this->column_data;

		foreach($rows AS $row)
			$this->column_data['list'][$row->id] = $row->name;

		$this->column_data['type'] = 'Single';

		return $this->column_data;
	}
	
}