<?php

namespace Core\Db\Table;

class Select extends \Core\Db\Select {
	/**
	 * Table schema for parent \Core\Db\Table.
	 *
	 * @var array
	 */
	protected $_info;
	
	/**
	 * Table integrity override.
	 *
	 * @var array
	 */
	protected $_integrityCheck = true;
	
	/**
	 * Table instance that created this select object
	 *
	 * @var \Core\Db\Table\AbstractTable
	 */
	protected $_table;
	
	/**
	 * Class constructor
	 *
	 * @param \Core\Db\Table\AbstractTable $adapter        	
	 */
	public function __construct(\Core\Db\Table\AbstractTable $table) {
		parent::__construct ( $table->getAdapter () );
		
		$this->setTable ( $table );
	}
	
	/**
	 * Return the table that created this select object
	 *
	 * @return \Core\Db\Table\AbstractTable
	 */
	public function getTable() {
		return $this->_table;
	}
	
	/**
	 * Sets the primary table name and retrieves the table schema.
	 *
	 * @param \Core\Db\Table\AbstractTable $adapter        	
	 * @return \Core\Db\Select This \Core\Db\Select object.
	 */
	public function setTable(\Core\Db\Table\AbstractTable $table) {
		$this->_adapter = $table->getAdapter ();
		$this->_info = $table->info ();
		$this->_table = $table;
		
		return $this;
	}
	
	/**
	 * Sets the integrity check flag.
	 *
	 * Setting this flag to false skips the checks for table joins, allowing
	 * 'hybrid' table rows to be created.
	 *
	 * @param \Core\Db\Table\AbstractTable $adapter        	
	 * @return \Core\Db\Select This \Core\Db\Select object.
	 */
	public function setIntegrityCheck($flag = true) {
		$this->_integrityCheck = $flag;
		return $this;
	}
	
	/**
	 * Tests query to determine if expressions or aliases columns exist.
	 *
	 * @return boolean
	 */
	public function isReadOnly() {
		$readOnly = false;
		$fields = $this->getPart ( \Core\Db\Table\Select::COLUMNS );
		$cols = $this->_info [\Core\Db\Table\AbstractTable::COLS];
		
		if (! count ( $fields )) {
			return $readOnly;
		}
		
		foreach ( $fields as $columnEntry ) {
			$column = $columnEntry [1];
			$alias = $columnEntry [2];
			
			if ($alias !== null) {
				$column = $alias;
			}
			
			switch (true) {
				case ($column == self::SQL_WILDCARD) :
					break;
				
				case ($column instanceof \Core\Db\Expr) :
				case (! in_array ( $column, $cols )) :
					$readOnly = true;
					break 2;
			}
		}
		
		return $readOnly;
	}
	
	/**
	 * Adds a FROM table and optional columns to the query.
	 *
	 * The table name can be expressed
	 *
	 * @param array|string|\Core\Db\Expr|\Core\Db\Table\AbstractTable $name
	 *        	The table name or an
	 *        	associative array relating
	 *        	table name to correlation
	 *        	name.
	 * @param array|string|\Core\Db\Expr $cols
	 *        	The columns to select from this table.
	 * @param string $schema
	 *        	The schema name to specify, if any.
	 * @return \Core\Db\Table\Select This \Core\Db\Table\Select object.
	 */
	public function from($name, $cols = self::SQL_WILDCARD, $schema = null) {
		if ($name instanceof \Core\Db\Table\AbstractTable) {
			$info = $name->info ();
			$name = $info [\Core\Db\Table\AbstractTable::NAME];
			if (isset ( $info [\Core\Db\Table\AbstractTable::SCHEMA] )) {
				$schema = $info [\Core\Db\Table\AbstractTable::SCHEMA];
			}
		}
		
		return $this->joinInner ( $name, null, $cols, $schema );
	}
	
	/**
	 * Performs a validation on the select query before passing back to the
	 * parent class.
	 * Ensures that only columns from the primary \Core\Db\Table are returned in
	 * the result.
	 *
	 * @return string null object as a SELECT string (or null if a string cannot
	 *         be produced)
	 */
	public function assemble() {
		$fields = $this->getPart ( \Core\Db\Table\Select::COLUMNS );
		$primary = $this->_info [\Core\Db\Table\AbstractTable::NAME];
		$schema = $this->_info [\Core\Db\Table\AbstractTable::SCHEMA];
		
		if (count ( $this->_parts [self::UNION] ) == 0) {
			
			// If no fields are specified we assume all fields from primary
			// table
			if (! count ( $fields )) {
				$this->from ( $primary, self::SQL_WILDCARD, $schema );
				$fields = $this->getPart ( \Core\Db\Table\Select::COLUMNS );
			}
			
			$from = $this->getPart ( \Core\Db\Table\Select::FROM );
			
			if ($this->_integrityCheck !== false) {
				foreach ( $fields as $columnEntry ) {
					list ( $table, $column ) = $columnEntry;
					
					// Check each column to ensure it only references the
					// primary table
					if ($column) {
						if (! isset ( $from [$table] ) || $from [$table] ['tableName'] != $primary) {
							require_once 'Db/Table/Select/Exception.php';
							throw new \Core\Db\Table\Select\Exception ( 'Select query cannot join with another table' );
						}
					}
				}
			}
		}
		
		return parent::assemble ();
	}
}