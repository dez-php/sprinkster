<?php

namespace Category;

abstract class CategoryOrderAbstract {
	
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
	
	public function __construct(\Core\Db\Select $sql, \Core\Db\Table\Row $extend, \Category\Category $pinTable) {
		$this->sql = $sql;
		$this->extend = $extend;
		$this->categoryTable = $pinTable;
	}
	
	abstract public function getExtendetSql();
	
}