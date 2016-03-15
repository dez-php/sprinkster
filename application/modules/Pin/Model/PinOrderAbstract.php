<?php

namespace Pin;

abstract class PinOrderAbstract {
	
	/**
	 * @var \Core\Db\Select
	 */
	protected $sql;

	/**
	 * @var \Core\Db\Table\Row
	 */
	protected $extend;

	/**
	 * @var \Pin\Pin
	 */
	protected $pinTable;
	
	public function __construct(\Core\Db\Select $sql, \Core\Db\Table\Row $extend, \Pin\Pin $pinTable) {
		$this->sql = $sql;
		$this->extend = $extend;
		$this->pinTable = $pinTable;
	}
	
	abstract public function getExtendetSql();
	
}