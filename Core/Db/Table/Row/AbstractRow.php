<?php

namespace Core\Db\Table\Row;

abstract class AbstractRow implements \ArrayAccess, \IteratorAggregate {
	
	/**
	 * The data for each column in the row (column_name => value).
	 * The keys must match the physical names of columns in the
	 * table for which this row is defined.
	 *
	 * @var array
	 */
	protected $_data = array ();
	
	/**
	 * This is set to a copy of $_data when the data is fetched from
	 * a database, specified as a new tuple in the constructor, or
	 * when dirty data is posted to the database with save().
	 *
	 * @var array
	 */
	protected $_cleanData = array ();
	
	/**
	 * Tracks columns where data has been updated.
	 * Allows more specific insert and
	 * update operations.
	 *
	 * @var array
	 */
	protected $_modifiedFields = array ();
	
	/**
	 * \Core\Db\Table\AbstractTable parent class or instance.
	 *
	 * @var \Core\Db\Table\AbstractTable
	 */
	protected $_table = null;
	
	/**
	 * Connected is true if we have a reference to a live
	 * \Core\Db\Table\AbstractTable object.
	 * This is false after the Rowset has been deserialized.
	 *
	 * @var boolean
	 */
	protected $_connected = true;
	
	/**
	 * A row is marked read only if it contains columns that are not physically
	 * represented within
	 * the database schema (e.g.
	 * evaluated columns/\Core\Db\Expr columns). This can also be passed
	 * as a run-time config options as a means of protecting row data.
	 *
	 * @var boolean
	 */
	protected $_readOnly = false;
	
	/**
	 * Name of the class of the \Core\Db\Table\AbstractTable object.
	 *
	 * @var string
	 */
	protected $_tableClass = null;
	
	/**
	 * Primary row key(s).
	 *
	 * @var array
	 */
	protected $_primary;
	protected $_limit;
	protected $_where;
	protected $_order;
	
	/**
	 * Constructor.
	 *
	 * Supported params for $config are:-
	 * - table = class name or object of type \Core\Db\Table\AbstractTable
	 * - data = values of columns in this row.
	 *
	 * @param array $config
	 *        	OPTIONAL Array of user-specified config options.
	 * @return void
	 * @throws \Core\Db\Table\Row\Exception
	 */
	public function __construct(array $config = array()) {
		if (isset ( $config ['table'] ) && $config ['table'] instanceof \Core\Db\Table\AbstractTable) {
			$this->_table = $config ['table'];
			$this->_tableClass = get_class ( $this->_table );
		} elseif ($this->_tableClass !== null) {
			$this->_table = $this->_getTableFromString ( $this->_tableClass );
		}
		
		if (isset ( $config ['data'] )) {
			if (! is_array ( $config ['data'] )) {
				require_once 'Db/Table/Row/Exception.php';
				throw new \Core\Db\Table\Row\Exception ( 'Data must be an array' );
			}
			$this->_data = $config ['data'];
		}
		if (isset ( $config ['stored'] ) && $config ['stored'] === true) {
			$this->_cleanData = $this->_data;
		}
		
		if (isset ( $config ['readOnly'] ) && $config ['readOnly'] === true) {
			$this->setReadOnly ( true );
		}
		
		// Retrieve primary keys from table schema
		if (($table = $this->_getTable ())) {
			$info = $table->info ();
			$this->_primary = ( array ) $info ['primary'];
		}
		
		$this->init ();
	}
	
	/**
	 * Transform a column name from the user-specified form
	 * to the physical form used in the database.
	 * You can override this method in a custom Row class
	 * to implement column name mappings, for example inflection.
	 *
	 * @param string $columnName
	 *        	Column name given.
	 * @return string The column name after transformation applied (none by
	 *         default).
	 * @throws \Core\Db\Table\Row\Exception if the $columnName is not a string.
	 */
	protected function _transformColumn($columnName) {
		if (! is_string ( $columnName )) {
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( 'Specified column is not a string' );
		}
		// Perform no transformation by default
		return $columnName;
	}
	
	/**
	 * Retrieve row field value
	 *
	 * @param string $columnName
	 *        	The user-specified column name.
	 * @return string The corresponding column value.
	 * @throws \Core\Db\Table\Row\Exception if the $columnName is not a column
	 *         in the row.
	 */
	public function __get($columnName) {
		$columnName = $this->_transformColumn ( $columnName );
		if (! array_key_exists ( $columnName, $this->_data )) {
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( "Specified column \"$columnName\" is not in the row" );
		}
		return $this->_data [$columnName];
	}
	
	/**
	 * Set row field value
	 *
	 * @param string $columnName
	 *        	The column key.
	 * @param mixed $value
	 *        	The value for the property.
	 * @return void
	 * @throws \Core\Db\Table\Row\Exception
	 */
	public function __set($columnName, $value) {
		$columnName = $this->_transformColumn ( $columnName );
		if (! array_key_exists ( $columnName, $this->_data )) {
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( "Specified column \"$columnName\" is not in the row" );
		}
		$this->_data [$columnName] = $value;
		$this->_modifiedFields [$columnName] = true;
	}
	
	/**
	 * Unset row field value
	 *
	 * @param string $columnName
	 *        	The column key.
	 * @return \Core\Db\Table\Row\AbstractRow
	 * @throws \Core\Db\Table\Row\Exception
	 */
	public function __unset($columnName) {
		$columnName = $this->_transformColumn ( $columnName );
		if (! array_key_exists ( $columnName, $this->_data )) {
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( "Specified column \"$columnName\" is not in the row" );
		}
		if ($this->isConnected () && in_array ( $columnName, $this->_table->info ( 'primary' ) )) {
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( "Specified column \"$columnName\" is a primary key and should not be unset" );
		}
		unset ( $this->_data [$columnName] );
		return $this;
	}
	
	/**
	 * Test existence of row field
	 *
	 * @param string $columnName
	 *        	The column key.
	 * @return boolean
	 */
	public function __isset($columnName) {
		$columnName = $this->_transformColumn ( $columnName );
		return array_key_exists ( $columnName, $this->_data );
	}
	
	/**
	 * Store table, primary key and data in serialized object
	 *
	 * @return array
	 */
	public function __sleep() {
		return array (
				'_tableClass',
				'_primary',
				'_data',
				'_cleanData',
				'_readOnly',
				'_modifiedFields' 
		);
	}
	
	/**
	 * Setup to do on wakeup.
	 * A de-serialized Row should not be assumed to have access to a live
	 * database connection, so set _connected = false.
	 *
	 * @return void
	 */
	public function __wakeup() {
		$this->_connected = false;
	}
	
	/**
	 * Proxy to __isset
	 * Required by the ArrayAccess implementation
	 *
	 * @param string $offset        	
	 * @return boolean
	 */
	public function offsetExists($offset) {
		return $this->__isset ( $offset );
	}
	
	/**
	 * Proxy to __get
	 * Required by the ArrayAccess implementation
	 *
	 * @param string $offset        	
	 * @return string
	 */
	public function offsetGet($offset) {
		return $this->__get ( $offset );
	}
	
	/**
	 * Proxy to __set
	 * Required by the ArrayAccess implementation
	 *
	 * @param string $offset        	
	 * @param mixed $value        	
	 */
	public function offsetSet($offset, $value) {
		$this->__set ( $offset, $value );
	}
	
	/**
	 * Proxy to __unset
	 * Required by the ArrayAccess implementation
	 *
	 * @param string $offset        	
	 */
	public function offsetUnset($offset) {
		return $this->__unset ( $offset );
	}
	
	/**
	 * Initialize object
	 *
	 * Called from {@link __construct()} as final step of object instantiation.
	 *
	 * @return void
	 */
	public function init() {
	}
	
	/**
	 * Returns the table object, or null if this is disconnected row
	 *
	 * @return \Core\Db\Table\AbstractTable null
	 */
	public function getTable() {
		return $this->_table;
	}
	
	/**
	 * Set the table object, to re-establish a live connection
	 * to the database for a Row that has been de-serialized.
	 *
	 * @param \Core\Db\Table\AbstractTable $table        	
	 * @return boolean
	 * @throws \Core\Db\Table\Row\Exception
	 */
	public function setTable(\Core\Db\Table\AbstractTable $table = null) {
		if ($table == null) {
			$this->_table = null;
			$this->_connected = false;
			return false;
		}
		
		$tableClass = get_class ( $table );
		if (! $table instanceof $this->_tableClass) {
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( "The specified Table is of class $tableClass, expecting class to be instance of $this->_tableClass" );
		}
		
		$this->_table = $table;
		$this->_tableClass = $tableClass;
		
		$info = $this->_table->info ();
		
		if ($info ['cols'] != array_keys ( $this->_data )) {
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( 'The specified Table does not have the same columns as the Row' );
		}
		
		if (! array_intersect ( ( array ) $this->_primary, $info ['primary'] ) == ( array ) $this->_primary) {
			
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( "The specified Table '$tableClass' does not have the same primary key as the Row" );
		}
		
		$this->_connected = true;
		return true;
	}
	
	/**
	 * Query the class name of the Table object for which this
	 * Row was created.
	 *
	 * @return string
	 */
	public function getTableClass() {
		return $this->_tableClass;
	}
	
	/**
	 * Test the connected status of the row.
	 *
	 * @return boolean
	 */
	public function isConnected() {
		return $this->_connected;
	}
	
	/**
	 * Test the read-only status of the row.
	 *
	 * @return boolean
	 */
	public function isReadOnly() {
		return $this->_readOnly;
	}
	
	/**
	 * Set the read-only status of the row.
	 *
	 * @param boolean $flag        	
	 * @return boolean
	 */
	public function setReadOnly($flag) {
		$this->_readOnly = ( bool ) $flag;
	}
	
	/**
	 * Returns an instance of the parent table's \Core\Db\Table\Select object.
	 *
	 * @return \Core\Db\Table\Select
	 */
	public function select() {
		return $this->getTable ()->select ();
	}
	
	/**
	 * Saves the properties to the database.
	 *
	 * This performs an intelligent insert/update, and reloads the
	 * properties with fresh data from the table on success.
	 *
	 * @return mixed The primary key value(s), as an associative array if the
	 *         key is compound, or a scalar if the key is single-column.
	 */
	public function save() {
		/**
		 * If the _cleanData array is empty,
		 * this is an INSERT of a new row.
		 * Otherwise it is an UPDATE.
		 */
		if (empty ( $this->_cleanData )) {
			return $this->_doInsert ();
		} else {
			return $this->_doUpdate ();
		}
	}
	
	/**
	 *
	 * @return mixed The primary key value(s), as an associative array if the
	 *         key is compound, or a scalar if the key is single-column.
	 */
	protected function _doInsert() {
		/**
		 * A read-only row cannot be saved.
		 */
		if ($this->_readOnly === true) {
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( 'This row has been marked read-only' );
		}
		
		/**
		 * Run pre-INSERT logic
		 */
		$this->_insert ();
		
		/**
		 * Execute the INSERT (this may throw an exception)
		 */
		$data = array_intersect_key ( $this->_data, $this->_modifiedFields );
		$primaryKey = $this->_getTable ()->insert ( $data );
		
		/**
		 * Normalize the result to an array indexed by primary key column(s).
		 * The table insert() method may return a scalar.
		 */
		if (is_array ( $primaryKey )) {
			$newPrimaryKey = $primaryKey;
		} else {
			// ZF-6167 Use tempPrimaryKey temporary to avoid that zend encoding
			// fails.
			$tempPrimaryKey = ( array ) $this->_primary;
			$newPrimaryKey = array (
					current ( $tempPrimaryKey ) => $primaryKey 
			);
		}
		
		/**
		 * Save the new primary key value in _data.
		 * The primary key may have
		 * been generated by a sequence or auto-increment mechanism, and this
		 * merge should be done before the _postInsert() method is run, so the
		 * new values are available for logging, etc.
		 */
		$this->_data = array_merge ( $this->_data, $newPrimaryKey );
		
		/**
		 * Run post-INSERT logic
		 */
		$this->_postInsert ();
		
		/**
		 * Update the _cleanData to reflect that the data has been inserted.
		 */
		$this->_refresh ();
		
		return $primaryKey;
	}
	
	/**
	 *
	 * @return mixed The primary key value(s), as an associative array if the
	 *         key is compound, or a scalar if the key is single-column.
	 */
	protected function _doUpdate() {
		/**
		 * A read-only row cannot be saved.
		 */
		if ($this->_readOnly === true) {
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( 'This row has been marked read-only' );
		}
		
		/**
		 * Get expressions for a WHERE clause
		 * based on the primary key value(s).
		 */
		$where = $this->_getWhereQuery ( false );
		
		/**
		 * Run pre-UPDATE logic
		 */
		$this->_update ();
		
		/**
		 * Compare the data to the modified fields array to discover
		 * which columns have been changed.
		 */
		$diffData = array_intersect_key ( $this->_data, $this->_modifiedFields );
		
		/**
		 * Were any of the changed columns part of the primary key?
		 */
		$pkDiffData = array_intersect_key ( $diffData, array_flip ( ( array ) $this->_primary ) );
		
		/**
		 * Execute cascading updates against dependent tables.
		 * Do this only if primary key value(s) were changed.
		 */
		if (count ( $pkDiffData ) > 0) {
			$depTables = $this->_getTable ()->getDependentTables ();
			if (! empty ( $depTables )) {
				$pkNew = $this->_getPrimaryKey ( true );
				$pkOld = $this->_getPrimaryKey ( false );
				foreach ( $depTables as $tableClass ) {
					$t = $this->_getTableFromString ( $tableClass );
					$t->_cascadeUpdate ( $this->getTableClass (), $pkOld, $pkNew );
				}
			}
		}
		
		/**
		 * Execute the UPDATE (this may throw an exception)
		 * Do this only if data values were changed.
		 * Use the $diffData variable, so the UPDATE statement
		 * includes SET terms only for data values that changed.
		 */
		if (count ( $diffData ) > 0) {
			$this->_getTable ()->update ( $diffData, $where );
		}
		
		/**
		 * Run post-UPDATE logic.
		 * Do this before the _refresh()
		 * so the _postUpdate() function can tell the difference
		 * between changed data and clean (pre-changed) data.
		 */
		$this->_postUpdate ();
		
		/**
		 * Refresh the data just in case triggers in the RDBMS changed
		 * any columns.
		 * Also this resets the _cleanData.
		 */
		$this->_refresh ();
		
		/**
		 * Return the primary key value(s) as an array
		 * if the key is compound or a scalar if the key
		 * is a scalar.
		 */
		$primaryKey = $this->_getPrimaryKey ( true );
		if (count ( $primaryKey ) == 1) {
			return current ( $primaryKey );
		}
		
		return $primaryKey;
	}
	
	/**
	 * Deletes existing rows.
	 *
	 * @return int The number of rows deleted.
	 */
	public function delete() {
		/**
		 * A read-only row cannot be deleted.
		 */
		if ($this->_readOnly === true) {
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( 'This row has been marked read-only' );
		}
		
		$where = $this->_getWhereQuery ();
		
		/**
		 * Execute pre-DELETE logic
		 */
		$this->_delete ();
		
		/**
		 * Execute cascading deletes against dependent tables
		 */
		$depTables = $this->_getTable ()->getDependentTables ();
		if (! empty ( $depTables )) {
			$pk = $this->_getPrimaryKey ();
			foreach ( $depTables as $tableClass ) {
				$t = $this->_getTableFromString ( $tableClass );
				$t->_cascadeDelete ( $this->getTableClass (), $pk );
			}
		}
		
		/**
		 * Execute the DELETE (this may throw an exception)
		 */
		$result = $this->_getTable ()->delete ( $where );
		
		/**
		 * Execute post-DELETE logic
		 */
		$this->_postDelete ();
		
		/**
		 * Reset all fields to null to indicate that the row is not there
		 */
		$this->_data = array_combine ( array_keys ( $this->_data ), array_fill ( 0, count ( $this->_data ), null ) );
		
		return $result;
	}
	public function getIterator() {
		return new \ArrayIterator ( ( array ) $this->_data );
	}
	
	/**
	 * Returns the column/value data as an array.
	 *
	 * @return array
	 */
	public function toArray() {
		return ( array ) $this->_data;
	}
	
	/**
	 * Sets all data in the row from an array.
	 *
	 * @param array $data        	
	 * @return \Core\Db\Table\Row\AbstractRow Provides a fluent interface
	 */
	public function setFromArray(array $data) {
		$data = array_intersect_key ( $data, $this->_data );
		
		foreach ( $data as $columnName => $value ) {
			$this->__set ( $columnName, $value );
		}
		
		return $this;
	}
	
	/**
	 * Refreshes properties from the database.
	 *
	 * @return void
	 */
	public function refresh() {
		return $this->_refresh ();
	}
	
	/**
	 * Retrieves an instance of the parent table.
	 *
	 * @return \Core\Db\Table\AbstractTable
	 */
	protected function _getTable() {
		if (! $this->_connected) {
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( 'Cannot save a Row unless it is connected' );
		}
		return $this->_table;
	}
	
	/**
	 * Retrieves an associative array of primary keys.
	 *
	 * @param bool $useDirty        	
	 * @return array
	 */
	protected function _getPrimaryKey($useDirty = true) {
		if (! is_array ( $this->_primary )) {
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( "The primary key must be set as an array" );
		}
		
		$primary = array_flip ( $this->_primary );
		if ($useDirty) {
			$array = array_intersect_key ( $this->_data, $primary );
		} else {
			$array = array_intersect_key ( $this->_cleanData, $primary );
		}
		if (count ( $primary ) != count ( $array )) {
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( "The specified Table '$this->_tableClass' does not have the same primary key as the Row" );
		}
		return $array;
	}
	
	/**
	 * Constructs where statement for retrieving row(s).
	 *
	 * @param bool $useDirty        	
	 * @return array
	 */
	protected function _getWhereQuery($useDirty = true) {
		$where = array ();
		$db = $this->_getTable ()->getAdapter ();
		$primaryKey = $this->_getPrimaryKey ( $useDirty );
		$info = $this->_getTable ()->info ();
		$metadata = $info [\Core\Db\Table\AbstractTable::METADATA];
		
		// retrieve recently updated row using primary keys
		$where = array ();
		foreach ( $primaryKey as $column => $value ) {
			$tableName = $db->quoteIdentifier ( $info [\Core\Db\Table\AbstractTable::NAME], true );
			$type = $metadata [$column] ['DATA_TYPE'];
			$columnName = $db->quoteIdentifier ( $column, true );
			$where [] = $db->quoteInto ( "{$tableName}.{$columnName} = ?", $value, $type );
		}
		return $where;
	}
	
	/**
	 * Refreshes properties from the database.
	 *
	 * @return void
	 */
	protected function _refresh() {
		$where = $this->_getWhereQuery ();
		$row = $this->_getTable ()->fetchRow ( $where );
		
		if (null === $row) {
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( 'Cannot refresh row as parent is missing' );
		}
		
		$this->_data = $row->toArray ();
		$this->_cleanData = $this->_data;
		$this->_modifiedFields = array ();
	}
	
	/**
	 * Allows pre-insert logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _insert() {
	}
	
	/**
	 * Allows post-insert logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _postInsert() {
	}
	
	/**
	 * Allows pre-update logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _update() {
	}
	
	/**
	 * Allows post-update logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _postUpdate() {
	}
	
	/**
	 * Allows pre-delete logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _delete() {
	}
	
	/**
	 * Allows post-delete logic to be applied to row.
	 * Subclasses may override this method.
	 *
	 * @return void
	 */
	protected function _postDelete() {
	}
	
	/**
	 * Prepares a table reference for lookup.
	 *
	 * Ensures all reference keys are set and properly formatted.
	 *
	 * @param \Core\Db\Table\AbstractTable $dependentTable        	
	 * @param \Core\Db\Table\AbstractTable $parentTable        	
	 * @param string $ruleKey        	
	 * @return array
	 */
	protected function _prepareReference(\Core\Db\Table\AbstractTable $dependentTable,\Core\Db\Table\AbstractTable $parentTable, $ruleKey) {
		$parentTableName = (get_class ( $parentTable ) === '\Core\Db\Table') ? $parentTable->getDefinitionConfigName () : get_class ( $parentTable );
		$map = $dependentTable->getReference ( $parentTableName, $ruleKey );
		
		if (! isset ( $map [\Core\Db\Table\AbstractTable::REF_COLUMNS] )) {
			$parentInfo = $parentTable->info ();
			$map [\Core\Db\Table\AbstractTable::REF_COLUMNS] = array_values ( ( array ) $parentInfo ['primary'] );
		}
		
		$map [\Core\Db\Table\AbstractTable::COLUMNS] = ( array ) $map [\Core\Db\Table\AbstractTable::COLUMNS];
		$map [\Core\Db\Table\AbstractTable::REF_COLUMNS] = ( array ) $map [\Core\Db\Table\AbstractTable::REF_COLUMNS];
		
		return $map;
	}
	
	/**
	 * Query a dependent table to retrieve rows matching the current row.
	 *
	 * @param string|\Core\Db\Table\AbstractTable $dependentTable        	
	 * @param
	 *        	string OPTIONAL $ruleKey
	 * @param
	 *        	\Core\Db\Table\Select OPTIONAL $select
	 * @return \Core\Db\Table\Rowset\AbstractRowset Query result from
	 *         $dependentTable
	 * @throws \Core\Db\Table\Row\Exception If $dependentTable is not a table or
	 *         is not loadable.
	 */
	public function findDependentRowset($dependentTable, $ruleKey = null,\Core\Db\Table\Select $select = null) {
		$db = $this->_getTable ()->getAdapter ();
		
		if (is_string ( $dependentTable )) {
			$dependentTable = $this->_getTableFromString ( $dependentTable );
		}
		
		if (! $dependentTable instanceof \Core\Db\Table\AbstractTable) {
			$type = gettype ( $dependentTable );
			if ($type == 'object') {
				$type = get_class ( $dependentTable );
			}
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( "Dependent table must be a \Core\Db\Table\AbstractTable, but it is $type" );
		}
		
		// even if we are interacting between a table defined in a class and a
		// table via extension, ensure to persist the definition
		if (($tableDefinition = $this->_table->getDefinition ()) !== null && ($dependentTable->getDefinition () == null)) {
			$dependentTable->setOptions ( array (
					\Core\Db\Table\AbstractTable::DEFINITION => $tableDefinition 
			) );
		}
		
		if ($select === null) {
			$select = $dependentTable->select ();
		} else {
			$select->setTable ( $dependentTable );
		}
		
		$map = $this->_prepareReference ( $dependentTable, $this->_getTable (), $ruleKey );
		
		for($i = 0; $i < count ( $map [\Core\Db\Table\AbstractTable::COLUMNS] ); ++ $i) {
			$parentColumnName = $db->foldCase ( $map [\Core\Db\Table\AbstractTable::REF_COLUMNS] [$i] );
			$value = $this->_data [$parentColumnName];
			// Use adapter from dependent table to ensure correct query
			// construction
			$dependentDb = $dependentTable->getAdapter ();
			$dependentColumnName = $dependentDb->foldCase ( $map [\Core\Db\Table\AbstractTable::COLUMNS] [$i] );
			$dependentColumn = $dependentDb->quoteIdentifier ( $dependentColumnName, true );
			$dependentInfo = $dependentTable->info ();
			$type = $dependentInfo [\Core\Db\Table\AbstractTable::METADATA] [$dependentColumnName] ['DATA_TYPE'];
			if (is_null ( $value )) {
				$select->where ( "$dependentColumn IS NULL" );
			} else {
				$select->where ( "$dependentColumn = ?", $value, $type );
			}
		}
		
		$where_if = $this->_getWhereFromReferenceMap ( $this->_getTable ()->info ( 'referenceMap' ), get_class ( $dependentTable ) );
		if ($where_if) {
			foreach ( $where_if as $whereRef ) {
				$where = $whereRef;
				try {
					$whereLambda = @create_function ( '', 'return ' . $whereRef . ';' );
					if (is_string ( $whereLambda )) {
						$where = $whereLambda ();
					}
				} catch ( \Core\Db\Exception $e ) {
				}
				if ($where) {
					$select->where ( $where );
				}
			}
		}
		
		return $dependentTable->fetchAll ( $select );
	}
	public function getMapData($dependentTable, $ruleKey = null,\Core\Db\Table\Select $select = null) {
		if (is_string ( $dependentTable ) && isset ( $this->{strtolower ( $dependentTable )} )) {
			return $this->{strtolower ( $dependentTable )};
		}
		return null;
		// return $this->findDependentRow($dependentTable, $ruleKey, $select);
	}
	
	/**
	 * Query a dependent table to retrieve rows matching the current row.
	 *
	 * @param string|\Core\Db\Table\AbstractTable $dependentTable        	
	 * @param
	 *        	string OPTIONAL $ruleKey
	 * @param
	 *        	\Core\Db\Table\Select OPTIONAL $select
	 * @return \Core\Db\Table\Row\AbstractRow Query result from $dependentTable
	 * @throws \Core\Db\Table\Row\Exception If $dependentTable is not a table or
	 *         is not loadable.
	 */
	public function findDependentRow($dependentTable, $ruleKey = null,\Core\Db\Table\Select $select = null) {
		$db = $this->_getTable ()->getAdapter ();
		
		if (is_string ( $dependentTable )) {
			$dependentTable = explode ( '\\', $dependentTable );
			$dependentTable = implode ( '\\', array_map ( 'ucfirst', $dependentTable ) );
			$dependentTable = $this->_getTableFromString ( $dependentTable );
		}
		
		if (! $dependentTable instanceof \Core\Db\Table\AbstractTable) {
			$type = gettype ( $dependentTable );
			if ($type == 'object') {
				$type = get_class ( $dependentTable );
			}
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( "Dependent table must be a \Core\Db\Table\AbstractTable, but it is $type" );
		}
		
		// even if we are interacting between a table defined in a class and a
		// table via extension, ensure to persist the definition
		if (($tableDefinition = $this->_table->getDefinition ()) !== null && ($dependentTable->getDefinition () == null)) {
			$dependentTable->setOptions ( array (
					\Core\Db\Table\AbstractTable::DEFINITION => $tableDefinition 
			) );
		}
		
		if ($select === null) {
			$select = $dependentTable->select ();
		} else {
			$select->setTable ( $dependentTable );
		}
		
		$map = $this->_prepareReference ( $dependentTable, $this->_getTable (), $ruleKey );
		// var_dump(get_class($dependentTable),$map,$this->_getTable()->info('referenceMap'));
		for($i = 0; $i < count ( $map [\Core\Db\Table\AbstractTable::COLUMNS] ); ++ $i) {
			$parentColumnName = $db->foldCase ( $map [\Core\Db\Table\AbstractTable::REF_COLUMNS] [$i] );
			$value = $this->_data [$parentColumnName];
			// Use adapter from dependent table to ensure correct query
			// construction
			$dependentDb = $dependentTable->getAdapter ();
			$dependentColumnName = $dependentDb->foldCase ( $map [\Core\Db\Table\AbstractTable::COLUMNS] [$i] );
			$dependentColumn = $dependentDb->quoteIdentifier ( $dependentColumnName, true );
			$dependentInfo = $dependentTable->info ();
			$type = $dependentInfo [\Core\Db\Table\AbstractTable::METADATA] [$dependentColumnName] ['DATA_TYPE'];
			if (is_null ( $value )) {
				$select->where ( "$dependentColumn IS NULL" );
			} else {
				$select->where ( "$dependentColumn = ?", $value, $type );
			}
		}
		$select->limit ( 1 );
		
		$where_if = $this->_getWhereFromReferenceMap ( $this->_getTable ()->info ( 'referenceMap' ), get_class ( $dependentTable ) );
		if ($where_if) {
			foreach ( $where_if as $whereRef ) {
				$where = $whereRef;
				try {
					$whereLambda = @create_function ( '', 'return ' . $whereRef . ';' );
					if (is_string ( $whereLambda )) {
						$where = $whereLambda ();
					}
				} catch ( \Core\Db\Exception $e ) {
				}
				if ($where) {
					$select->where ( $where );
				}
			}
		}
		return $dependentTable->fetchRow ( $select );
	}
	private function _getWhereFromReferenceMap($referenceMap, $search) {
		$where = array ();
		if (is_array ( $referenceMap )) {
			foreach ( $referenceMap as $name => $map ) {
				if (isset ( $map ['refTableClass'] ) && strtolower ( $map ['refTableClass'] ) == strtolower ( $search ) && isset ( $map ['where'] )) {
					$where [] = $map ['where'];
				} else if (isset ( $map ['referenceMap'] ['refTableClass'] ) && strtolower ( $map ['referenceMap'] ['refTableClass'] ) == strtolower ( $search ) && isset ( $map ['referenceMap'] ['where'] )) {
					$where [] = $map ['referenceMap'] ['where'];
				}
			}
		}
		return $where;
	}
	
	/**
	 * Query a parent table to retrieve the single row matching the current row.
	 *
	 * @param string|\Core\Db\Table\AbstractTable $parentTable        	
	 * @param
	 *        	string OPTIONAL $ruleKey
	 * @param
	 *        	\Core\Db\Table\Select OPTIONAL $select
	 * @return \Core\Db\Table\Row\AbstractRow Query result from $parentTable
	 * @throws \Core\Db\Table\Row\Exception If $parentTable is not a table or is
	 *         not loadable.
	 */
	public function findParentRow($parentTable, $ruleKey = null,\Core\Db\Table\Select $select = null) {
		$db = $this->_getTable ()->getAdapter ();
		
		if (is_string ( $parentTable )) {
			$parentTable = $this->_getTableFromString ( $parentTable );
		}
		
		if (! $parentTable instanceof \Core\Db\Table\AbstractTable) {
			$type = gettype ( $parentTable );
			if ($type == 'object') {
				$type = get_class ( $parentTable );
			}
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( "Parent table must be a \Core\Db\Table\AbstractTable, but it is $type" );
		}
		
		// even if we are interacting between a table defined in a class and a
		// table via extension, ensure to persist the definition
		if (($tableDefinition = $this->_table->getDefinition ()) !== null && ($parentTable->getDefinition () == null)) {
			$parentTable->setOptions ( array (
					\Core\Db\Table\AbstractTable::DEFINITION => $tableDefinition 
			) );
		}
		
		if ($select === null) {
			$select = $parentTable->select ();
		} else {
			$select->setTable ( $parentTable );
		}
		
		$map = $this->_prepareReference ( $this->_getTable (), $parentTable, $ruleKey );
		
		// iterate the map, creating the proper wheres
		for($i = 0; $i < count ( $map [\Core\Db\Table\AbstractTable::COLUMNS] ); ++ $i) {
			$dependentColumnName = $db->foldCase ( $map [\Core\Db\Table\AbstractTable::COLUMNS] [$i] );
			$value = $this->_data [$dependentColumnName];
			// Use adapter from parent table to ensure correct query
			// construction
			$parentDb = $parentTable->getAdapter ();
			$parentColumnName = $parentDb->foldCase ( $map [\Core\Db\Table\AbstractTable::REF_COLUMNS] [$i] );
			$parentColumn = $parentDb->quoteIdentifier ( $parentColumnName, true );
			$parentInfo = $parentTable->info ();
			
			// determine where part
			$type = $parentInfo [\Core\Db\Table\AbstractTable::METADATA] [$parentColumnName] ['DATA_TYPE'];
			$nullable = $parentInfo [\Core\Db\Table\AbstractTable::METADATA] [$parentColumnName] ['NULLABLE'];
			if ($value === null && $nullable == true) {
				$select->where ( "$parentColumn IS NULL" );
			} elseif ($value === null && $nullable == false) {
				return null;
			} else {
				$select->where ( "$parentColumn = ?", $value, $type );
			}
		}
		
		return $parentTable->fetchRow ( $select );
	}
	
	/**
	 *
	 * @param string|\Core\Db\Table\AbstractTable $matchTable        	
	 * @param string|\Core\Db\Table\AbstractTable $intersectionTable        	
	 * @param
	 *        	string OPTIONAL $callerRefRule
	 * @param
	 *        	string OPTIONAL $matchRefRule
	 * @param
	 *        	\Core\Db\Table\Select OPTIONAL $select
	 * @return \Core\Db\Table\Rowset\Abstract Query result from $matchTable
	 * @throws \Core\Db\Table\Row\Exception If $matchTable or $intersectionTable
	 *         is not a table class or is not loadable.
	 */
	public function findManyToManyRowset($matchTable, $intersectionTable, $callerRefRule = null, $matchRefRule = null,\Core\Db\Table\Select $select = null) {
		$db = $this->_getTable ()->getAdapter ();
		
		if (is_string ( $intersectionTable )) {
			$intersectionTable = $this->_getTableFromString ( $intersectionTable );
		}
		
		if (! $intersectionTable instanceof \Core\Db\Table\AbstractTable) {
			$type = gettype ( $intersectionTable );
			if ($type == 'object') {
				$type = get_class ( $intersectionTable );
			}
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( "Intersection table must be a \Core\Db\Table\AbstractTable, but it is $type" );
		}
		
		// even if we are interacting between a table defined in a class and a
		// table via extension, ensure to persist the definition
		if (($tableDefinition = $this->_table->getDefinition ()) !== null && ($intersectionTable->getDefinition () == null)) {
			$intersectionTable->setOptions ( array (
					\Core\Db\Table\AbstractTable::DEFINITION => $tableDefinition 
			) );
		}
		
		if (is_string ( $matchTable )) {
			$matchTable = $this->_getTableFromString ( $matchTable );
		}
		
		if (! $matchTable instanceof \Core\Db\Table\AbstractTable) {
			$type = gettype ( $matchTable );
			if ($type == 'object') {
				$type = get_class ( $matchTable );
			}
			require_once 'Db/Table/Row/Exception.php';
			throw new \Core\Db\Table\Row\Exception ( "Match table must be a \Core\Db\Table\AbstractTable, but it is $type" );
		}
		
		// even if we are interacting between a table defined in a class and a
		// table via extension, ensure to persist the definition
		if (($tableDefinition = $this->_table->getDefinition ()) !== null && ($matchTable->getDefinition () == null)) {
			$matchTable->setOptions ( array (
					\Core\Db\Table\AbstractTable::DEFINITION => $tableDefinition 
			) );
		}
		
		if ($select === null) {
			$select = $matchTable->select ();
		} else {
			$select->setTable ( $matchTable );
		}
		
		// Use adapter from intersection table to ensure correct query
		// construction
		$interInfo = $intersectionTable->info ();
		$interDb = $intersectionTable->getAdapter ();
		$interName = $interInfo ['name'];
		$interSchema = isset ( $interInfo ['schema'] ) ? $interInfo ['schema'] : null;
		$matchInfo = $matchTable->info ();
		$matchName = $matchInfo ['name'];
		$matchSchema = isset ( $matchInfo ['schema'] ) ? $matchInfo ['schema'] : null;
		
		$matchMap = $this->_prepareReference ( $intersectionTable, $matchTable, $matchRefRule );
		
		for($i = 0; $i < count ( $matchMap [\Core\Db\Table\AbstractTable::COLUMNS] ); ++ $i) {
			$interCol = $interDb->quoteIdentifier ( 'i' . '.' . $matchMap [\Core\Db\Table\AbstractTable::COLUMNS] [$i], true );
			$matchCol = $interDb->quoteIdentifier ( 'm' . '.' . $matchMap [\Core\Db\Table\AbstractTable::REF_COLUMNS] [$i], true );
			$joinCond [] = "$interCol = $matchCol";
		}
		$joinCond = implode ( ' AND ', $joinCond );
		
		$select->from ( array (
				'i' => $interName 
		), array (), $interSchema )->joinInner ( array (
				'm' => $matchName 
		), $joinCond, \Core\Db\Select::SQL_WILDCARD, $matchSchema )->setIntegrityCheck ( false );
		
		$callerMap = $this->_prepareReference ( $intersectionTable, $this->_getTable (), $callerRefRule );
		
		for($i = 0; $i < count ( $callerMap [\Core\Db\Table\AbstractTable::COLUMNS] ); ++ $i) {
			$callerColumnName = $db->foldCase ( $callerMap [\Core\Db\Table\AbstractTable::REF_COLUMNS] [$i] );
			$value = $this->_data [$callerColumnName];
			$interColumnName = $interDb->foldCase ( $callerMap [\Core\Db\Table\AbstractTable::COLUMNS] [$i] );
			$interCol = $interDb->quoteIdentifier ( "i.$interColumnName", true );
			$interInfo = $intersectionTable->info ();
			$type = $interInfo [\Core\Db\Table\AbstractTable::METADATA] [$interColumnName] ['DATA_TYPE'];
			$select->where ( $interDb->quoteInto ( "$interCol = ?", $value, $type ) );
		}
		
		$stmt = $select->query ();
		
		$config = array (
				'table' => $matchTable,
				'data' => $stmt->fetchAll ( \Core\Db\Init::FETCH_ASSOC ),
				'rowClass' => $matchTable->getRowClass (),
				'readOnly' => false,
				'stored' => true 
		);
		
		$rowsetClass = $matchTable->getRowsetClass ();
		if (! class_exists ( $rowsetClass )) {
			try {
				require_once 'Loader/Loader.php';
				\Core\Loader\Loader::loadClass ( $rowsetClass );
			} catch ( \Core\Exception $e ) {
				require_once 'Db/Table/Row/Exception.php';
				throw new \Core\Db\Table\Row\Exception ( $e->getMessage (), $e->getCode (), $e );
			}
		}
		$rowset = new $rowsetClass ( $config );
		return $rowset;
	}
	
	/**
	 * Turn magic function calls into non-magic function calls
	 * to the above methods.
	 *
	 * @param string $method        	
	 * @param array $args
	 *        	OPTIONAL \Core\Db\Table\Select query modifier
	 * @return \Core\Db\Table\Row\AbstractRow|\Core\Db\Table\Rowset\AbstractRowset|integer
	 * @throws \Core\Db\Table\Row\Exception If an invalid method is called.
	 */
	public function __call($method, array $args) {
		$matches = array ();
		
		if (count ( $args ) && $args [0] instanceof \Core\Db\Table\Select) {
			$select = $args [0];
		} else {
			$select = null;
		}
		
		/**
		 * Recognize methods for Has-Many cases:
		 * findParent<Class>()
		 * findParent<Class>By<Rule>()
		 * Use the non-greedy pattern repeat modifier e.g.
		 * \w+?
		 */
		if (preg_match ( '/^findParent(\w+?)(?:By(\w+))?$/', $method, $matches )) {
			$class = $matches [1];
			$ruleKey1 = isset ( $matches [2] ) ? $matches [2] : null;
			return $this->findParentRow ( $class, $ruleKey1, $select );
		}
		
		/**
		 * Recognize methods for Many-to-Many cases:
		 * find<Class1>Via<Class2>()
		 * find<Class1>Via<Class2>By<Rule>()
		 * find<Class1>Via<Class2>By<Rule1>And<Rule2>()
		 * Use the non-greedy pattern repeat modifier e.g. \w+?
		 */
		if (preg_match ( '/^find(\w+?)Via(\w+?)(?:By(\w+?)(?:And(\w+))?)?$/', $method, $matches )) {
			$class = $matches [1];
			$viaClass = $matches [2];
			$ruleKey1 = isset ( $matches [3] ) ? $matches [3] : null;
			$ruleKey2 = isset ( $matches [4] ) ? $matches [4] : null;
			
			$table_info = $this->getTable ()->info ();
			if (isset ( $table_info ['referenceMap'] [$class] )) {
				$class = $table_info ['referenceMap'] [$class] ['refTableClass'];
			}
			if (isset ( $table_info ['name'] ) && strtolower ( $table_info ['name'] ) == strtolower ( $viaClass )) {
				$viaClass = get_class ( $this->getTable () );
			}
			
			return $this->findManyToManyRowset ( $class, $viaClass, $ruleKey1, $ruleKey2, $select );
		}
		
		/**
		 * Recognize methods for Belongs-To cases:
		 * find<Class>()
		 * find<Class>By<Rule>()
		 * Use the non-greedy pattern repeat modifier e.g.
		 * \w+?
		 */
		if (preg_match ( '/^findOne(\w+?)(?:By(\w+))?$/', $method, $matches )) {
			$class = $matches [1];
			$ruleKey1 = isset ( $matches [2] ) ? $matches [2] : null;
			$table_info = $this->getTable ()->info ();
			if (isset ( $table_info ['referenceMap'] [$class] )) {
				$class = $table_info ['referenceMap'] [$class] ['refTableClass'];
			}
			return $this->findDependentRow ( $class, $ruleKey1, $select );
		}
		
		/**
		 * Recognize methods for Belongs-To cases:
		 * find<Class>()
		 * find<Class>By<Rule>()
		 * Use the non-greedy pattern repeat modifier e.g.
		 * \w+?
		 */
		if (preg_match ( '/^find(\w+?)(?:By(\w+))?$/', $method, $matches )) {
			$class = $matches [1];
			$ruleKey1 = isset ( $matches [2] ) ? $matches [2] : null;
			$table_info = $this->getTable ()->info ();
			if (isset ( $table_info ['referenceMap'] [$class] )) {
				$class = $table_info ['referenceMap'] [$class] ['refTableClass'];
			}
			return $this->findDependentRowset ( $class, $ruleKey1, $select );
		}
		
		if (strtolower ( $method ) == 'limit') {
			if (count ( $args ) && ( int ) $args [0]) {
				$this->_limit = ( int ) $args [0];
			}
			return $this;
		}
		
		if (strtolower ( $method ) == 'where') {
			if (count ( $args ) && $args [0]) {
				$this->_where = $args [0];
			}
			return $this;
		}

		if (strtolower ( $method ) == 'order') {
			if (count ( $args ) && $args [0]) {
				$this->_order = $args [0];
			}
			return $this;
		}
		
		if(isset($this->{$method}) && $this->{$method} instanceof \Closure) {
			return call_user_func($this->{$method});
		}
		
		/*
		 * $boardTable = new \Board\Board(); $board =
		 * $boardTable->fetchRow(array('pins > 2'));
		 * var_dump($board->Limit(2)->Order('id
		 * ASC')->Pin()->Board()->User()->Pins());
		 */
		
		// if(NULL == $this->getTable())
		// {
		// 	var_dump($this);
		// 	die(var_dump(debug_backtrace())[0]);
		// }

		$referenceMap = $this->getTable()->info ( 'referenceMap' );
		foreach ( $referenceMap as $name => $map ) {
			$map ['columns'] = is_array($map ['columns'])&&isset($map ['columns'][0]) ? $map ['columns'][0] : $map ['columns'];
			$map ['refColumns'] = is_array($map ['refColumns'])&&isset($map ['refColumns'][0]) ? $map ['refColumns'][0] : $map ['refColumns'];
				
			$multy = str_replace ( strtolower ( $name ), '', strtolower ( $method ) ) === 's';
			$counter = str_replace ( strtolower ( $name ) . 's', '', strtolower ( $method ) ) === 'count';
			if (strtolower ( $name ) == strtolower ( $method ) || strtolower ( $name ) . 's' == strtolower ( $method ) || strtolower ( 'count'.$name ) . 's' == strtolower ( $method )) {
				$value = $this->_data [$map ['columns']];
				
				$obj = new $map ['refTableClass'] ();
				if ($select) {
					$select->where ( $map ['refColumns'] . (is_null ( $value ) ? ' IS NULL' : ' = ?'), $this->{$map ['columns']} );
				} else {
					$select = $obj->select ()->where ( $map ['refColumns'] . (is_null ( $value ) ? ' IS NULL' : ' = ?'), $this->{$map ['columns']} );
				}
				
				if (isset ( $map ['where'] )) {
					try { 
						$whereLambda = @create_function ( '', 'return ' . $map ['where'] . ';' );
						if (is_string ( $whereLambda )) {
							$map ['where'] = $whereLambda();
						}
					} catch ( \Core\Db\Exception $e ) {
					}
					if ($map ['where']) {
						$select->where ( $map ['where'] );
					} 
				}
				
				if ($this->_where) {
					$select->where ( $this->_where );
					$this->_where = null;
				}

				if ($this->_order) {
					$select->order ( $this->_order );
					$this->_order = null;
				}
				
				if($counter) {
					$select->from($obj,'COUNT(1) AS total')->limit(1);
					$sql_key = md5($select->assemble());
					if(\Core\Registry::isRegistered($sql_key))
						return \Core\Registry::get($sql_key);
					$return = $obj->fetchRow ( $select )->total;
					\Core\Registry::set($sql_key, $return);
					return $return;
				}
				if ($multy) {
					if ($this->_limit) {
						$select->limit ( $this->_limit );
						$this->_limit = null;
					}
					$sql_key = md5($select->assemble());
					if(\Core\Registry::isRegistered($sql_key))
						return \Core\Registry::get($sql_key);
					$return = $obj->fetchAll ( $select );
					\Core\Registry::set($sql_key, $return);
					return $return;
				} else {
					$sql_key = md5($select->assemble());
					if(\Core\Registry::isRegistered($sql_key))
						return \Core\Registry::get($sql_key);
					$return = $obj->fetchRow ( $select );
					\Core\Registry::set($sql_key, $return);
					return $return;
				}
			}
		}
		
		require_once 'Db/Table/Row/Exception.php';
		throw new \Core\Db\Table\Row\Exception ( "Unrecognized method '$method()'" );
	}
	
	/**
	 * _getTableFromString
	 *
	 * @param string $tableName        	
	 * @return \Core\Db\Table\AbstractTable
	 */
	protected function _getTableFromString($tableName) {
		if (is_string ( $tableName )) {
			$tableName = explode ( '\\', $tableName );
			$tableName = implode ( '\\', array_map ( 'ucfirst', $tableName ) );
			// $tableName =
		// \Core\Base\Front::getInstance()->formatModuleName($tableName);
		}
		
		if ($this->_table instanceof \Core\Db\Table\AbstractTable) {
			$tableDefinition = $this->_table->getDefinition ();
			
			if ($tableDefinition !== null && $tableDefinition->hasTableConfig ( $tableName )) {
				return new \Core\Db\Table ( $tableName, $tableDefinition );
			}
		}
		
		// assume the tableName is the class name
		if (! class_exists ( $tableName )) {
			try {
				require_once 'Loader/Loader.php';
				\Core\Loader\Loader::loadClass ( $tableName );
			} catch ( \Core\Exception $e ) {
				require_once 'Db/Table/Row/Exception.php';
				throw new \Core\Db\Table\Row\Exception ( $e->getMessage (), $e->getCode (), $e );
			}
		}
		
		$options = array ();
		
		if (($table = $this->_getTable ())) {
			$options ['db'] = $table->getAdapter ();
		}
		
		if (isset ( $tableDefinition ) && $tableDefinition !== null) {
			$options [\Core\Db\Table\AbstractTable::DEFINITION] = $tableDefinition;
		}
		
		return new $tableName ( $options );
	}
}
