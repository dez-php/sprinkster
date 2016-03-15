<?php

namespace Core\Db;

/*
class Items extends \Core\Db\ActiveRecord {
	protected $_name = 'Item';

	protected $_referenceMap    = array(
			'Board' => array(
					'columns'           => 'board_id',
					'refTableClass'     => 'Board\Board',
					'refColumns'        => 'id'
			),
			'Source' => array(
					'columns'           => 'source_id',
					'refTableClass'     => 'Source\Source',
					'refColumns'        => 'id'
			),
			'User' => array(
					'columns'           => 'user_id',
					'refTableClass'     => 'User\User',
					'refColumns'        => 'id'
			),
			'Like' => array(
					'columns'           => 'pin_id',
					'refTableClass'     => 'Pin\PinLike',
					'refColumns'        => 'id',
					'where'				=> '"user_id = " . \User\User::getUserData()->id'
			),
	);
} 
*/

class ActiveRecord extends Table {
	
	private $_select;
	private $_map;
	
	/**
	 * __construct() - For concrete implementation of Core\Db\Table
	 *
	 * @param string|array $config
	 *        	string can reference a \Core\Registry key for a db adapter
	 *        	OR it can reference the name of a table
	 * @param array|\Core\Db\Table\Definition $definition
	 *
	 * @return \Core\Db\ActiveRecord
	 */
	public function __construct($config = array(), $definition = null) {
		parent::__construct($config, $definition);
		$this->setModel();
	}
	
	/**
	 * @return \Core\Db\ActiveRecord
	 */
	private function setModel() {
		$this->_select = new Select($this->getAdapter());
		$_counter = count($this->_select->getPart(Select::SQL_FROM));
		$_info = $this->info();
		$cols = array();
		foreach($_info['cols'] AS $c) {
			$cols['t'.$_counter . '.' . $c] = $c;
		}
		$this->_select->from(array('t'.$_counter=>$this->_name), $cols, $this->_schema);
		////////////////////
		\Core\Registry\Component::create(array(
				'name' => 'db_reference_' . $this->_name,
				'class' => '\Core\Registry'
		));
		////////////////////
		$this->_map[] = $this;
		return $this;
	}
	
	/**
	 * @return \Core\Db\ActiveRecord
	 */
	public static function getModel() {
		$className = get_called_class();
		return new $className();
	}
	
	/**
	 * @param string $_reference
	 * @throws \Core\Db\Table\Row\Exception
	 * @throws \Core\Db\Exception
	 * @return \Core\Db\ActiveRecord
	 */
	public function with($_reference) {
		
		$info = $this->info();
		$referenceMap = $this->_getReferenceMapNormalized();
		
		if(isset($referenceMap[$_reference])) { 
			$dependentTable = $referenceMap[$_reference]['refTableClass'];
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
			if (($tableDefinition = $this->getDefinition ()) !== null && ($dependentTable->getDefinition () == null)) {
				$dependentTable->setOptions ( array (
						\Core\Db\Table\AbstractTable::DEFINITION => $tableDefinition
				) );
			}
			
			$infoModel = $dependentTable->info ();
			
			$_counter = count($this->_select->getPart(Select::SQL_FROM));

			$cols = array();
			foreach($infoModel['cols'] AS $c) {
				$cols['t'.$_counter . '.' . $c] = $c;
			}
			$a = $this->getAdapter();
			$this->_select->joinLeft(array('t'.$_counter=>$infoModel['name']),$a->quoteColumnAs('t0.'.$referenceMap[$_reference]['columns'][0],null).'='.$a->quoteColumnAs('t'.$_counter.'.'.$referenceMap[$_reference]['refColumns'][0],null),$cols, $infoModel['schema']);

			////////////////////
			\Core\Registry\Component::getComponent('db_reference_' . $this->_name)->set('t'.$_counter, array(
				'name' => $_reference,
				'dependent' => $dependentTable,
				'info' => $infoModel,
				'table' => $infoModel['name']
			));
			////////////////////
			
			$where_if = $this->_getWhereFromReferenceMap ( $referenceMap, get_class ( $dependentTable ) );
			if ($where_if) {
				foreach ( $where_if as $whereRef ) {
					$where = $whereRef;
					try {
						$whereLambda = @create_function ( '', 'return ' . $whereRef . ';' );
						if (is_string ( $whereLambda )) {
							$where = $whereLambda ();
						}
					} catch ( \Core\Db\Exception $e ) { }
					if ($where) {
						$this->_select->where ( $where );
					}
				}
			}
			
			$this->_map[] = $dependentTable;
			
			return $this;
		} else {
			throw new \Core\Db\Exception('there is no reference for ' . $_reference);
		}
		
		return $this;
	}
	
	/**
	 * @param string $_reference
	 * @throws \Core\Db\Table\Row\Exception
	 * @throws \Core\Db\Exception
	 * @return \Core\Db\ActiveRecord
	 */
	public function withPrev($_reference) {
		
		$last = end($this->_map);
		
		if(!$last || !is_object($last))
			throw new \Core\Db\Exception('Last query error!');
		
		$info = $last->info();
		$referenceMap = $last->_getReferenceMapNormalized();
		
		if(isset($referenceMap[$_reference])) { 
			$dependentTable = $referenceMap[$_reference]['refTableClass'];
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
			if (($tableDefinition = $this->getDefinition ()) !== null && ($dependentTable->getDefinition () == null)) {
				$dependentTable->setOptions ( array (
						\Core\Db\Table\AbstractTable::DEFINITION => $tableDefinition
				) );
			}
			
			$infoModel = $dependentTable->info ();
			
			$_counter = count($this->_select->getPart(Select::SQL_FROM));

			$cols = array();
			foreach($infoModel['cols'] AS $c) {
				$cols['t'.$_counter . '.' . $c] = $c;
			}
			$a = $this->getAdapter();
			$this->_select->joinLeft(array('t'.$_counter=>$infoModel['name']),$a->quoteColumnAs('t'.($_counter-1).'.'.$referenceMap[$_reference]['columns'][0],null).'='.$a->quoteColumnAs('t'.$_counter.'.'.$referenceMap[$_reference]['refColumns'][0],null),$cols, $infoModel['schema']);

			////////////////////
			\Core\Registry\Component::getComponent('db_reference_' . $this->_name)->set('t'.$_counter, array(
				'name' => $_reference,
				'dependent' => $dependentTable,
				'info' => $infoModel,
				'table' => $infoModel['name']
			));
			////////////////////
			
			$where_if = $this->_getWhereFromReferenceMap ( $referenceMap, get_class ( $dependentTable ) );
			if ($where_if) {
				
				foreach ( $where_if as $whereRef ) {
					$where = $whereRef;
					try {
						$whereLambda = @create_function ( '', 'return ' . $whereRef . ';' );
						if (is_string ( $whereLambda )) {
							$where = $whereLambda ();
						}
					} catch ( \Core\Db\Exception $e ) { }
					if ($where) {
						$this->_select->where ( $where );
					}
				}
			}
			return $this;
		} else {
			throw new \Core\Db\Exception('there is no reference for ' . $_reference);
		}
		
		return $this;
	}
	
	/**
	 * @param string $in
	 * @return mixed
	 */
	protected function translateWhere($in) {
		if(is_string($in)) {
			$_registry = \Core\Registry\Component::getComponent('db_reference_' . $this->_name);
			$temporary = array('$1t0$2.' => '~([\s|`])?'.$this->_name.'([\s|`])?.~');
			foreach($_registry->getRegExp('^t([\d]{1,})$') AS $key => $dependent) {
				$temporary['$1'.$key.'$2.'] = '~([\s|`])?'.$dependent['table'].'([\s|`])?.~';
			}
			return preg_replace($temporary,array_keys($temporary), $in);
		}
		return $in;
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
	 * Generate WHERE clause from user-supplied string or array
	 *
	 * @param string|array $where
	 *        	OPTIONAL An SQL WHERE clause.
	 * @return \Core\Db\Select
	 */
	protected function _where($where) {
		$where = ( array ) $where;
		
		foreach ( $where as $key => $val ) {
			// is $key an int?
			if (is_int ( $key )) {
				// $val is the full condition
				$this->_select->where ( $this->translateWhere($val) );
			} else {
				// $key is the condition with placeholder,
				// and $val is quoted into the condition
				$this->_select->where ( $this->translateWhere($key), $val );
			}
		}
		
		return $this->_select;
	}
	
	/**
	 * Adds a WHERE condition to the query by AND.
	 *
	 * If a value is passed as the second param, it will be quoted
	 * and replaced into the condition wherever a question-mark
	 * appears. Array values are quoted and comma-separated.
	 *
	 * <code>
	 * // simplest but non-secure
	 * $select->where("id = $id");
	 *
	 * // secure (ID is quoted but matched anyway)
	 * $select->where('id = ?', $id);
	 *
	 * // alternatively, with named binding
	 * $select->where('id = :id');
	 * </code>
	 *
	 * Note that it is more correct to use named bindings in your
	 * queries for values other than strings. When you use named
	 * bindings, don't forget to pass the values when actually
	 * making a query:
	 *
	 * <code>
	 * $db->fetchAll($select, array('id' => 5));
	 * </code>
	 *
	 * @param string $cond
	 *        	The WHERE condition.
	 * @param mixed $value
	 *        	OPTIONAL The value to quote into the condition.
	 * @param constant $type
	 *        	OPTIONAL The type of the given value
	 * @return \Core\Db\ActiveRecord This \Core\Db\ActiveRecord object.
	 */
	public function where($cond, $value = null, $type = null) {
		$this->_select->where($cond, $value, $type);
		
		return $this;
	}
	
	/**
	 * Sets a limit count and offset to the query.
	 *
	 * @param int $count
	 *        	OPTIONAL The number of rows to return.
	 * @param int $offset
	 *        	OPTIONAL Start returning after this many rows.
	 * @return \Core\Db\ActiveRecord This \Core\Db\ActiveRecord object.
	 */
	public function limit($count = null, $offset = null) {
		$this->_select->limit($count, $offset);
		return $this;
	}
	
	/**
	 * Sets the limit and count by page number.
	 *
	 * @param int $page
	 *        	Limit results to this page number.
	 * @param int $rowCount
	 *        	Use this many rows per page.
	 * @return \Core\Db\ActiveRecord This \Core\Db\ActiveRecord object.
	 */
	public function limitPage($page, $rowCount) {
		$page = ($page > 0) ? $page : 1;
		$rowCount = ($rowCount > 0) ? $rowCount : 1;
		return $this->limit(( int ) $rowCount, ( int ) $rowCount * ($page - 1));
	}

	/**
	 * Adds a row order to the query.
	 *
	 * @param mixed $spec
	 *        	The column(s) and direction to order by.
	 * @return \Core\Db\ActiveRecord This \Core\Db\ActiveRecord object.
	 */
	public function order($spec) {
		$this->_select->order($spec);
		
		return $this;
	}
	
	/**
	 * Fetches all rows.
	 *
	 * Honors the \Core\Db\Adapter fetch mode.
	 *
	 * @param string|array
	 * @param string|array $order
	 *        	OPTIONAL An SQL ORDER clause.
	 * @param int $count
	 *        	OPTIONAL An SQL LIMIT count.
	 * @param int $offset
	 *        	OPTIONAL An SQL LIMIT offset.
	 * @return \Core\Db\Table\Rowset\AbstractRowset The row results per the
	 *         \Core\Db\Adapter fetch mode.
	 */
	public function fetchAll($where = null, $order = null, $count = null, $offset = null) {
		
		if ($where !== null) { 
			$this->_where ( $where );
		}
			
		if ($order !== null) {
			$this->order ( $order );
		} else if ($this->_order) {
			$this->order ( $this->_order );
		}
			
		if ($count !== null || $offset !== null) {
			$this->limit ( $count, $offset );
		} else if (( int ) $this->_limit > 0) {
			$this->limit ( ( int ) $this->_limit );
		}

		$rows = $this->getAdapter()->fetchAll( $this->_select );
		
		if($rows) {
			$_registry = \Core\Registry\Component::getComponent('db_reference_' . $this->_name);
			foreach($rows AS &$row) {
				$new_row = $fnc_row = array();
				foreach($row AS $name => $data) {
					list($_reference, $key) = explode('.', $name, 2);
					if( ($_referenceName = $_registry->forceGet($_reference)) !== null ) {
						
						if(!isset($fnc_row[$_referenceName['name']])) {
							$rowClass = $_referenceName['dependent']->getRowClass ();
							if (! class_exists ( $rowClass )) {
								require_once 'Loader.php';
								\Core\Loader\Loader::loadClass ( $rowClass );
							}
							$fnc_row[$_referenceName['name']] = new $rowClass ( array (
									'table' => $_referenceName['dependent'],
									'data' => array_map(function($r) { return null;},array_flip($_referenceName['info']['cols'])),
									'readOnly' => false,
									'stored' => true
							) );
						}
						$fnc_row[$_referenceName['name']]->{$key} = $data;
						$new_row[$_referenceName['name']] = function() use($fnc_row, $_referenceName) {
							return $fnc_row[$_referenceName['name']];
						};
					} else {
						$new_row[ $key ] = $data;
					}
				}
				$row = $new_row;
			}
		}
		
		$data = array (
				'table' => $this,
				'data' => $rows,
				'readOnly' => false,
				'rowClass' => $this->getRowClass (),
				'stored' => true 
		);
		
		$rowsetClass = $this->getRowsetClass ();
		if (! class_exists ( $rowsetClass )) {
			require_once 'Loader.php';
			\Core\Loader\Loader::loadClass ( $rowsetClass );
		}
		return new $rowsetClass ( $data );
		
	}
	
	/**
	 * Fetches one row in an object of type \Core\Db\Table\Row\Abstract,
	 * or returns null if no row matches the specified criteria.
	 *
	 * @param string|array
	 * @param string|array $order
	 *        	OPTIONAL An SQL ORDER clause.
	 * @return \Core\Db\Table\Row\AbstractRow null row results per the
	 *         \Core\Db\Adapter fetch mode, or null if no row found.
	 */
	public function fetchRow($where = null, $order = null) {
		$this->limit(1);
		$rows = $this->fetchAll($where, $order, 1);

		if (count ( $rows ) == 0) {
			return null;
		}
		
		return $rows [0];
	}
	
	/**
	  * Return sql select string.
	 *
	 * @return string
	 */
	public function __toString() {
		return (string)$this->_select;
	}
	
}