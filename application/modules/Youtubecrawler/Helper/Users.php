<?php

namespace Youtubecrawler\Helper;

class Users {
	
	protected $column_data = array();
	
	public function __construct($column_data) {
		$this->column_data = $column_data;
		$this->column_data['type'] = null;
	}
	
	public function form() {
		$this->column_data['list'][] = '';
		$this->column_data['type'] = 'Single';
		$userTable = new \User\User();
		$users = $userTable->fetchAll(array('status = 1'), new \Core\Db\Expr('firstname ASC, lastname ASC, username ASC'), 5000);
		foreach($users AS $user) {
			$this->column_data['list'][$user->id] = $user->getUserFullName() . ' ('.$user->username.')';
		}
		return $this->column_data;
	}
}