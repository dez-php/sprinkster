<?php

namespace Core\Db\Table\Rowset;

abstract class AbstractRowset implements \SeekableIterator, \Countable, \ArrayAccess {
	/**
	 * The original data for each row.
	 *
	 * @var array
	 */
	protected $_data = array ();
	
	/**
	 * \Core\Db\Table\AbstractTable object.
	 *
	 * @var \Core\Db\Table\AbstractTable
	 */
	protected $_table;
	
	/**
	 * Connected is true if we have a reference to a live
	 * \Core\Db\Table\AbstractTable object.
	 * This is false after the Rowset has been deserialized.
	 *
	 * @var boolean
	 */
	protected $_connected = true;
	
	/**
	 * \Core\Db\Table\AbstractTable class name.
	 *
	 * @var string
	 */
	protected $_tableClass;
	
	/**
	 * \Core\Db\Table\Row\AbstractRow class name.
	 *
	 * @var string
	 */
	protected $_rowClass = '\Core\Db\Table\Row';
	
	/**
	 * Iterator pointer.
	 *
	 * @var integer
	 */
	protected $_pointer = 0;
	
	/**
	 * How many data rows there are.
	 *
	 * @var integer
	 */
	protected $_count;
	
	/**
	 * Collection of instantiated \Core\Db\Table\Row objects.
	 *
	 * @var array
	 */
	protected $_rows = array ();
	
	/**
	 *
	 * @var boolean
	 */
	protected $_stored = false;
	
	/**
	 *
	 * @var boolean
	 */
	protected $_readOnly = false;
	
	/**
	 *
	 * @var string
	 */
	protected $_error;
	protected $_limit;
	protected $_order;
	
	/**
	 * Constructor.
	 *
	 * @param array $config        	
	 */
	public function __construct(array $config) {
		if (isset ( $config ['table'] )) {
			$this->_table = $config ['table'];
			$this->_tableClass = get_class ( $this->_table );
		}
		if (isset ( $config ['rowClass'] )) {
			$this->_rowClass = $config ['rowClass'];
		}
		if (! class_exists ( $this->_rowClass )) {
			require_once 'Loader/Loader.php';
			\Core\Loader\Loader::loadClass ( $this->_rowClass );
		}
		if (isset ( $config ['data'] )) {
			$this->_data = $config ['data'];
		}
		if (isset ( $config ['readOnly'] )) {
			$this->_readOnly = $config ['readOnly'];
		}
		if (isset ( $config ['stored'] )) {
			$this->_stored = $config ['stored'];
		}
		
		// set the count of rows
		$this->_count = count ( $this->_data );
		
		$this->init ();
	}
	
	/**
	 * Store data, class names, and state in serialized object
	 *
	 * @return array
	 */
	public function __sleep() {
		return array (
				'_data',
				'_tableClass',
				'_rowClass',
				'_pointer',
				'_count',
				'_rows',
				'_stored',
				'_readOnly' 
		);
	}
	
	/**
	 * Setup to do on wakeup.
	 * A de-serialized Rowset should not be assumed to have access to a live
	 * database connection, so set _connected = false.
	 *
	 * @return void
	 */
	public function __wakeup() {
		$this->_connected = false;
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
		$rows = array();
		foreach($this AS $row) {
			$results = call_user_func_array(array($row, $method), $args);
			if($results instanceof \Core\Db\Table\Rowset && $results->count()) {
				foreach($results AS $result) {
					$rows[] = $result->toArray();
				}
			} elseif($results) {
				$rows[] = $results->toArray();
			}
		}

		$data = array (
				'table' => $this->getTable(),
				'data' => $rows,
				'readOnly' => $this->_readOnly,
				'rowClass' => $this->getTable()->getRowClass(),
				'stored' => true 
		);
		
		$rowsetClass = $this->getTable()->getRowsetClass ();
		if (! class_exists ( $rowsetClass )) {
			require_once 'Loader.php';
			\Core\Loader\Loader::loadClass ( $rowsetClass );
		}
		return new $rowsetClass ( $data );
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
	 * Return the connected state of the rowset.
	 *
	 * @return boolean
	 */
	public function isConnected() {
		return $this->_connected;
	}
	
	/**
	 * Returns the table object, or null if this is disconnected rowset
	 *
	 * @return \Core\Db\Table\AbstractTable
	 */
	public function getTable() {
		return $this->_table;
	}
	
	/**
	 * Set the table object, to re-establish a live connection
	 * to the database for a Rowset that has been de-serialized.
	 *
	 * @param \Core\Db\Table\AbstractTable $table        	
	 * @return boolean
	 * @throws \Core\Db\Table\Row_Exception
	 */
	public function setTable(\Core\Db\Table\AbstractTable $table) {
		$this->_table = $table;
		$this->_connected = false;
		// @todo This works only if we have iterated through
		// the result set once to instantiate the rows.
		foreach ( $this as $row ) {
			$connected = $row->setTable ( $table );
			if ($connected == true) {
				$this->_connected = true;
			}
		}
		return $this->_connected;
	}
	
	/**
	 * Query the class name of the Table object for which this
	 * Rowset was created.
	 *
	 * @return string
	 */
	public function getTableClass() {
		return $this->_tableClass;
	}
	
	/**
	 * Rewind the Iterator to the first element.
	 * Similar to the reset() function for arrays in PHP.
	 * Required by interface Iterator.
	 *
	 * @return \Core\Db\Table\Rowset\Abstract Fluent interface.
	 */
	public function rewind() {
		$this->_pointer = 0;
		return $this;
	}
	
	/**
	 * Return the current element.
	 * Similar to the current() function for arrays in PHP
	 * Required by interface Iterator.
	 *
	 * @return \Core\Db\Table\Row\AbstractRow current element from the
	 *         collection
	 */
	public function current() {
		if ($this->valid () === false) {
			return null;
		}
		
		// do we already have a row object for this position?
		if (empty ( $this->_rows [$this->_pointer] )) {
			$this->_rows [$this->_pointer] = new $this->_rowClass ( array (
					'table' => $this->_table,
					'data' => $this->_data [$this->_pointer],
					'stored' => $this->_stored,
					'readOnly' => $this->_readOnly 
			) );
		}
		
		// return the row object
		return $this->_rows [$this->_pointer];
	}
	
	/**
	 * Return the identifying key of the current element.
	 * Similar to the key() function for arrays in PHP.
	 * Required by interface Iterator.
	 *
	 * @return int
	 */
	public function key() {
		return $this->_pointer;
	}
	
	/**
	 * Move forward to next element.
	 * Similar to the next() function for arrays in PHP.
	 * Required by interface Iterator.
	 *
	 * @return void
	 */
	public function next() {
		++ $this->_pointer;
	}
	
	/**
	 * Check if there is a current element after calls to rewind() or next().
	 * Used to check if we've iterated to the end of the collection.
	 * Required by interface Iterator.
	 *
	 * @return bool False if there's nothing more to iterate over
	 */
	public function valid() {
		return $this->_pointer >= 0 && $this->_pointer < $this->_count;
	}
	
	/**
	 * Returns the number of elements in the collection.
	 *
	 * Implements Countable::count()
	 *
	 * @return int
	 */
	public function count() {
		return $this->_count;
	}
	
	/**
	 * Take the Iterator to position $position
	 * Required by interface SeekableIterator.
	 *
	 * @param int $position
	 *        	the position to seek to
	 * @return \Core\Db\Table\Rowset\Abstract
	 * @throws \Core\Db\Table\Rowset_Exception
	 */
	public function seek($position) {
		$position = ( int ) $position;
		if ($position < 0 || $position >= $this->_count) {
			require_once 'Db/Table/Rowset/Exception.php';
			throw new \Core\Db\Table\Rowset\Exception ( "Illegal index $position" );
		}
		$this->_pointer = $position;
		return $this;
	}
	
	/**
	 * Check if an offset exists
	 * Required by the ArrayAccess implementation
	 *
	 * @param string $offset        	
	 * @return boolean
	 */
	public function offsetExists($offset) {
		return isset ( $this->_data [( int ) $offset] );
	}
	
	/**
	 * Get the row for the given offset
	 * Required by the ArrayAccess implementation
	 *
	 * @param string $offset        	
	 * @return \Core\Db\Table\Row\AbstractRowtract
	 */
	public function offsetGet($offset) {
		$offset = ( int ) $offset;
		if ($offset < 0 || $offset >= $this->_count) {
			require_once 'Db/Table/Rowset/Exception.php';
			throw new \Core\Db\Table\Rowset\Exception ( "Illegal index $offset" );
		}
		$this->_pointer = $offset;
		
		return $this->current();
	}
	
	/**
	 * Does nothing
	 * Required by the ArrayAccess implementation
	 *
	 * @param string $offset        	
	 * @param mixed $value        	
	 */
	public function offsetSet($offset, $value) {

	}
	
	/**
	 * Does nothing
	 * Required by the ArrayAccess implementation
	 *
	 * @param string $offset        	
	 */
	public function offsetUnset($offset)
	{
		unset($this->_data[$offset]);
		$this->_data = array_values($this->_data);
		$this->_count = count($this->_data);
		$this->current();
	}
	
	/**
	 * Returns a \Core\Db\Table\Row from a known position into the Iterator
	 *
	 * @param int $position
	 *        	the position of the row expected
	 * @param bool $seek
	 *        	wether or not seek the iterator to that position after
	 * @return \Core\Db\Table\Row
	 * @throws \Core\Db\Table\Rowset_Exception
	 */
	public function getRow($position, $seek = false) {
		$key = $this->key ();
		try {
			$this->seek ( $position );
			$row = $this->current ();
		} catch ( \Core\Db\Table\Rowset\Exception $e ) {
			require_once 'Db/Table/Rowset/Exception.php';
			throw new \Core\Db\Table\Rowset\Exception ( 'No row could be found at position ' . ( int ) $position, 0, $e );
		}
		if ($seek == false) {
			$this->seek ( $key );
		}
		return $row;
	}
	
	/**
	 * Returns all data as an array.
	 *
	 * Updates the $_data property with current row object values.
	 *
	 * @return array
	 */
	public function toArray() {
		// @todo This works only if we have iterated through
		// the result set once to instantiate the rows.
		foreach ( $this->_rows as $i => $row ) {
			$this->_data [$i] = $row->toArray ();
		}
		return $this->_data;
	}
	
	/**
	 *
	 * @return \Core\Db\Table\Rowset\AbstractRowset The row results per the
	 *         \Core\Db\Adapter fetch mode.
	 */
	public function findMapData($dependentTable, $ruleKey = null,\Core\Db\Table\Select $select = null, $joinTable = null) {
		$db = $this->getTable ()->getAdapter ();
		
		if (is_string ( $dependentTable )) {
			$dependentTable = $this->_getTableFromString ( $dependentTable );
		}
		
		if (! $dependentTable instanceof \Core\Db\Table\AbstractTable) {
			$type = gettype ( $dependentTable );
			if ($type == 'object') {
				$type = get_class ( $dependentTable );
			}
			require_once 'Db/Table/Rowset/Exception.php';
			throw new \Core\Db\Table\Rowset\Exception ( "Dependent table must be a \Core\Db\Table\AbstractTable, but it is $type" );
		}
		
		// even if we are interacting between a table defined in a class and a
		// table via extension, ensure to persist the definition
		if (($tableDefinition = $this->getTable ()->getDefinition ()) !== null && ($dependentTable->getDefinition () == null)) {
			$dependentTable->setOptions ( array (
					\Core\Db\Table\AbstractTable::DEFINITION => $tableDefinition 
			) );
		}
		
		if ($select === null) {
			$select = $dependentTable->select ();
		} else {
			$select->setTable ( $dependentTable );
		}
		
		$map = $this->_prepareReference ( $dependentTable, $this->getTable (), $ruleKey );
		
		if ($this->_data) {
			$single = array ();
			$where_or = $where_or2 = array ();
			$class_name = get_class ( $dependentTable );
			$indexes = array ();
			$index_formated = $dependentTable->getIndexes ( true );
			if ($select instanceof \Core\Db\Select) {
				$where_part = $select->getPart ( \Core\Db\Select::WHERE );
				if ($where_part && preg_match_all ( '~(' . implode ( '|', $select->getTable ()->getColumns () ) . ')~i', implode ( ' ', $where_part ), $match )) {
					foreach ( $match [1] as $m ) {
						if (isset ( $index_formated [$m] )) {
							$indexes [$index_formated [$m]] = $index_formated [$m];
						}
					}
				}
			}
			foreach ( $this->_data as $key => $value ) {
				for($i = 0; $i < count ( $map [\Core\Db\Table\AbstractTable::COLUMNS] ); ++ $i) {
					$parentColumnName = $db->foldCase ( $map [\Core\Db\Table\AbstractTable::REF_COLUMNS] [$i] );
					$value = $value [$parentColumnName];
					if (! isset ( $single [$class_name . '_' . $parentColumnName . '_' . $value] )) {
						// Use adapter from dependent table to ensure correct
						// query construction
						$dependentDb = $dependentTable->getAdapter ();
						$dependentColumnName = $dependentDb->foldCase ( $map [\Core\Db\Table\AbstractTable::COLUMNS] [$i] );
						$dependentColumn = $dependentDb->quoteIdentifier ( $dependentColumnName, true );
						$dependentInfo = $dependentTable->info ();
						$type = $dependentInfo [\Core\Db\Table\AbstractTable::METADATA] [$dependentColumnName] ['DATA_TYPE'];
						// $select->orWhere("$dependentColumn = ?", $value,
						// $type);
						$where_or [$class_name . '_' . $parentColumnName . '_' . $value] = $dependentColumn . " = '" . $value . "'";
						if (isset ( $where_or2 [$dependentColumn] )) {
							if (is_array ( $where_or2 [$dependentColumn] )) {
								$where_or2 [$dependentColumn] [] = $value;
							} else {
								$where_or2 [$dependentColumn] = array (
										$where_or2 [$dependentColumn],
										$value 
								);
							}
						} else {
							$where_or2 [$dependentColumn] = $value;
						}
						$single [$class_name . '_' . $parentColumnName . '_' . $value] = $value;
						if (isset ( $index_formated [$dependentColumnName] )) {
							$indexes [$index_formated [$dependentColumnName]] = $index_formated [$dependentColumnName];
						}
					}
					$this->_data [$key] [strtolower ( $class_name )] = $parentColumnName . '@@' . $dependentColumnName . '@@' . $value;
				}
			}
			if ($where_or) {
				$adapter = $this->getTable ()->getAdapter ();
				foreach ( $where_or2 as $row => $where ) {
					if (is_array ( $where )) {
						$select->where ( $row . ' IN (' . $adapter->quote ( $where ) . ')' );
					} else {
						$select->where ( $row . ' = ' . $adapter->quote ( $where ) );
					}
				}
				// $select->where(implode(' OR ', $where_or));
				if ($indexes) {
					$select->useIndex ( $indexes );
				}
			}
			
			$where_if = $this->_getWhereFromReferenceMap ( $this->getTable ()->info ( 'referenceMap' ), get_class ( $dependentTable ) );
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
			
			$result = $dependentTable->fetchAll ( $select );
			if ($joinTable) {
				$result->findMapData ( $joinTable );
			}
			foreach ( $this->_data as $key => $value ) {
				list ( $parentColumnName, $dependentColumnName, $id ) = explode ( '@@', $value [strtolower ( $class_name )] );
				$this->_data [$key] [strtolower ( $class_name )] = array ();
				if ($result->count ()) {
					foreach ( $result as $row ) {
						if ($row [$dependentColumnName] == $value [$parentColumnName] && $value [$parentColumnName] == $id) {
							if ($map [\Core\Db\Table\AbstractTable::SINGLE_ROW]) {
								$this->_data [$key] [strtolower ( $class_name )] = $row;
								break;
							} else {
								$this->_data [$key] [strtolower ( $class_name )] [] = $row;
							}
						}
					}
				} else {
					if ($map [\Core\Db\Table\AbstractTable::SINGLE_ROW]) {
						$this->_data [$key] [strtolower ( $class_name )] = $dependentTable->fetchNew ();
					} else {
						$this->_data [$key] [strtolower ( $class_name )] [] = $dependentTable->fetchNew ();
					}
				}
			}
		}
		
		return $this;
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
	 * _getTableFromString
	 *
	 * @param string $tableName        	
	 * @return \Core\Db\Table\AbstractTable
	 */
	protected function _getTableFromString($tableName) {
		if ($this->getTable () instanceof \Core\Db\Table\AbstractTable) {
			$tableDefinition = $this->getTable ()->getDefinition ();
			
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
				require_once 'Db/Table/Rowset/Exception.php';
				throw new \Core\Db\Table\Rowset\Exception ( $e->getMessage (), $e->getCode (), $e );
			}
		}
		
		$options = array ();
		
		if (($table = $this->getTable ())) {
			$options ['db'] = $table->getAdapter ();
		}
		
		if (isset ( $tableDefinition ) && $tableDefinition !== null) {
			$options [\Core\Db\Table\AbstractTable::DEFINITION] = $tableDefinition;
		}
		
		return new $tableName ( $options );
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
		$map [\Core\Db\Table\AbstractTable::SINGLE_ROW] = ( bool ) isset ( $map [\Core\Db\Table\AbstractTable::SINGLE_ROW] ) ? $map [\Core\Db\Table\AbstractTable::SINGLE_ROW] : false;
		
		return $map;
	}
	
	/**
	 * Deletes existing rows.
	 *
	 * @return boolean
	 */
	public function delete() {
		$this->getTable ()->getAdapter ()->beginTransaction ();
		try {
			foreach ( $this as $row ) {
				$row->delete ();
			}
			$this->getTable ()->getAdapter ()->commit ();
			return true;
		} catch ( \Core\Db\Exception $e ) {
			$this->getTable ()->getAdapter ()->rollBack ();
			$this->_error = $e->getMessage ();
			return false;
		}
	}
	public function getError() {
		return $this->_error;
	}
    public function setError($error) {
        $this->_error = $error;
        return $this;
    }
}
