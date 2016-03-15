<?php

namespace Core\Db\Statement\Pdo;

class Ibm extends \Core\Db\Statement\Pdo {
	/**
	 * Returns an array containing all of the result set rows.
	 *
	 * Behaves like parent, but if limit()
	 * is used, the final result removes the extra column
	 * 'zend_db_rownum'
	 *
	 * @param int $style
	 *        	OPTIONAL Fetch mode.
	 * @param int $col
	 *        	OPTIONAL Column number, if fetch mode is by column.
	 * @return array Collection of rows, each in a format by the fetch mode.
	 * @throws \Core\Db\Statement\Exception
	 */
	public function fetchAll($style = null, $col = null) {
		$data = parent::fetchAll ( $style, $col );
		$results = array ();
		$remove = $this->_adapter->foldCase ( 'CORE_DB_ROWNUM' );
		
		foreach ( $data as $row ) {
			if (is_array ( $row ) && array_key_exists ( $remove, $row )) {
				unset ( $row [$remove] );
			}
			$results [] = $row;
		}
		return $results;
	}
	
	/**
	 * Binds a parameter to the specified variable name.
	 *
	 * @param mixed $parameter
	 *        	Name the parameter, either integer or string.
	 * @param mixed $variable
	 *        	Reference to PHP variable containing the value.
	 * @param mixed $type
	 *        	OPTIONAL Datatype of SQL parameter.
	 * @param mixed $length
	 *        	OPTIONAL Length of SQL parameter.
	 * @param mixed $options
	 *        	OPTIONAL Other options.
	 * @return bool
	 * @throws \Core\Db\Statement\Exception
	 */
	public function _bindParam($parameter, &$variable, $type = null, $length = null, $options = null) {
		try {
			if (($type === null) && ($length === null) && ($options === null)) {
				return $this->_stmt->bindParam ( $parameter, $variable );
			} else {
				return $this->_stmt->bindParam ( $parameter, $variable, $type, $length, $options );
			}
		} catch ( PDOException $e ) {
			require_once 'Db/Statement/Exception.php';
			throw new \Core\Db\Statement\Exception ( $e->getMessage (), $e->getCode (), $e );
		}
	}
}