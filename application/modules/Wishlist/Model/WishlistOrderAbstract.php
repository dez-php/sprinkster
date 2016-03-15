<?php

namespace Wishlist;

abstract class WishlistOrderAbstract {
	
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
	
	public function __construct(\Core\Db\Select $sql, \Core\Db\Table\Row $extend, \Wishlist\Wishlist $wishlistTable) {
		$this->sql = $sql;
		$this->extend = $extend;
		$this->wishlistTable = $wishlistTable;
	}
	
	abstract public function getExtendetSql();
	
}