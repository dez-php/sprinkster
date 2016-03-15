<?php

namespace User;

abstract class UserOrderAbstract {
	
	/**
	 * @var \Core\Db\Select
	 */
	protected $sql;

	/**
	 * @var \Core\Db\Table\Row
	 */
	protected $extend;

	/**
	 * @var \User\User
	 */
	protected $userTable;
	
	public function __construct(\Core\Db\Select $sql, \Core\Db\Table\Row $extend, \User\User $userTable) {
		$this->sql = $sql;
		$this->extend = $extend;
		$this->userTable = $userTable;
	}
	
	abstract public function getExtendetSql();
	
}