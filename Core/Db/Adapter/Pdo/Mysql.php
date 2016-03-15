<?php

namespace Core\Db\Adapter\Pdo;

class Mysql extends \Core\Db\Adapter\Pdo\AbstractPdo {
	
	/**
	 * PDO type.
	 *
	 * @var string
	 */
	protected $_pdoType = 'mysql';
	
	/**
	 * Keys are UPPERCASE SQL datatypes or the constants
	 * \Core\Db\Init::INT_TYPE, \Core\Db\Init::BIGINT_TYPE, or
	 * \Core\Db\Init::FLOAT_TYPE.
	 *
	 * Values are:
	 * 0 = 32-bit integer
	 * 1 = 64-bit integer
	 * 2 = float or decimal
	 *
	 * @var array Associative array of datatypes to values 0, 1, or 2.
	 */
	protected $_numericDataTypes = array (
			\Core\Db\Init::INT_TYPE => \Core\Db\Init::INT_TYPE,
			\Core\Db\Init::BIGINT_TYPE => \Core\Db\Init::BIGINT_TYPE,
			\Core\Db\Init::FLOAT_TYPE => \Core\Db\Init::FLOAT_TYPE,
			'INT' => \Core\Db\Init::INT_TYPE,
			'INTEGER' => \Core\Db\Init::INT_TYPE,
			'MEDIUMINT' => \Core\Db\Init::INT_TYPE,
			'SMALLINT' => \Core\Db\Init::INT_TYPE,
			'TINYINT' => \Core\Db\Init::INT_TYPE,
			'BIGINT' => \Core\Db\Init::BIGINT_TYPE,
			'SERIAL' => \Core\Db\Init::BIGINT_TYPE,
			'DEC' => \Core\Db\Init::FLOAT_TYPE,
			'DECIMAL' => \Core\Db\Init::FLOAT_TYPE,
			'DOUBLE' => \Core\Db\Init::FLOAT_TYPE,
			'DOUBLE PRECISION' => \Core\Db\Init::FLOAT_TYPE,
			'FIXED' => \Core\Db\Init::FLOAT_TYPE,
			'FLOAT' => \Core\Db\Init::FLOAT_TYPE 
	);
	
	/**
	 * Creates a PDO object and connects to the database.
	 *
	 * @return void
	 * @throws \Core\Db\Adapter\Exception
	 */
	protected function _connect() {
		if ($this->_connection) {
			return;
		}
		
		if (! empty ( $this->_config ['charset'] )) {
			$initCommand = "SET NAMES '" . $this->_config ['charset'] . "'";
			$this->_config ['driver_options'] [1002] = $initCommand; // 1002 =
			                                                       // PDO::MYSQL_ATTR_INIT_COMMAND
		}
		
		parent::_connect ();
	}
	
	/**
	 *
	 * @return string
	 */
	public function getQuoteIdentifierSymbol() {
		return "`";
	}
	
	/**
	 * Returns a list of the tables in the database.
	 *
	 * @return array
	 */
	public function listTables() {
		return $this->fetchCol ( 'SHOW TABLES' );
	}
	
	/**
	 * Returns the column descriptions for a table.
	 *
	 * The return value is an associative array keyed by the column name,
	 * as returned by the RDBMS.
	 *
	 * The value of each array element is an associative array
	 * with the following keys:
	 *
	 * SCHEMA_NAME => string; name of database or schema
	 * TABLE_NAME => string;
	 * COLUMN_NAME => string; column name
	 * COLUMN_POSITION => number; ordinal position of column in table
	 * DATA_TYPE => string; SQL datatype name of column
	 * DEFAULT => string; default expression of column, null if none
	 * NULLABLE => boolean; true if column can have nulls
	 * LENGTH => number; length of CHAR/VARCHAR
	 * SCALE => number; scale of NUMERIC/DECIMAL
	 * PRECISION => number; precision of NUMERIC/DECIMAL
	 * UNSIGNED => boolean; unsigned property of an integer type
	 * PRIMARY => boolean; true if column is part of the primary key
	 * PRIMARY_POSITION => integer; position of column in primary key
	 * IDENTITY => integer; true if column is auto-generated with unique values
	 *
	 * @param string $tableName        	
	 * @param string $schemaName
	 *        	OPTIONAL
	 * @return array
	 */
	public function describeTable($tableName, $schemaName = null) {
		// @todo use INFORMATION_SCHEMA someday when MySQL's
		// implementation has reasonably good performance and
		// the version with this improvement is in wide use.
		if ($schemaName) {
			$sql = 'DESCRIBE ' . $this->quoteIdentifier ( "$schemaName.$tableName", true );
		} else {
			$sql = 'DESCRIBE ' . $this->quoteIdentifier ( $tableName, true );
		}
		$stmt = $this->query ( $sql );
		
		// Use FETCH_NUM so we are not dependent on the CASE attribute of the
		// PDO connection
		$result = $stmt->fetchAll ( \Core\Db\Init::FETCH_NUM );
		
		$field = 0;
		$type = 1;
		$null = 2;
		$key = 3;
		$default = 4;
		$extra = 5;
		
		$desc = array ();
		$i = 1;
		$p = 1;
		foreach ( $result as $row ) {
			list ( $length, $scale, $precision, $unsigned, $primary, $primaryPosition, $identity ) = array (
					null,
					null,
					null,
					null,
					false,
					null,
					false 
			);
			if (preg_match ( '/unsigned/', $row [$type] )) {
				$unsigned = true;
			}
			if (preg_match ( '/^((?:var)?char)\((\d+)\)/', $row [$type], $matches )) {
				$row [$type] = $matches [1];
				$length = $matches [2];
			} else if (preg_match ( '/^decimal\((\d+),(\d+)\)/', $row [$type], $matches )) {
				$row [$type] = 'decimal';
				$precision = $matches [1];
				$scale = $matches [2];
			} else if (preg_match ( '/^float\((\d+),(\d+)\)/', $row [$type], $matches )) {
				$row [$type] = 'float';
				$precision = $matches [1];
				$scale = $matches [2];
			} else if (preg_match ( '/^((?:big|medium|small|tiny)?int)\((\d+)\)/', $row [$type], $matches )) {
				$row [$type] = $matches [1];
				// The optional argument of a MySQL int type is not precision
				// or length; it is only a hint for display width.
			}
			if (strtoupper ( $row [$key] ) == 'PRI') {
				$primary = true;
				$primaryPosition = $p;
				if ($row [$extra] == 'auto_increment') {
					$identity = true;
				} else {
					$identity = false;
				}
				++ $p;
			}
			$desc [$this->foldCase ( $row [$field] )] = array (
					'SCHEMA_NAME' => null, // @todo
					'TABLE_NAME' => $this->foldCase ( $tableName ),
					'COLUMN_NAME' => $this->foldCase ( $row [$field] ),
					'COLUMN_POSITION' => $i,
					'DATA_TYPE' => $row [$type],
					'DEFAULT' => $row [$default],
					'NULLABLE' => ( bool ) ($row [$null] == 'YES'),
					'LENGTH' => $length,
					'SCALE' => $scale,
					'PRECISION' => $precision,
					'UNSIGNED' => $unsigned,
					'PRIMARY' => $primary,
					'PRIMARY_POSITION' => $primaryPosition,
					'IDENTITY' => $identity 
			);
			++ $i;
		}
		return $desc;
	}
	
	/**
	 * Adds an adapter-specific LIMIT clause to the SELECT statement.
	 *
	 * @param string $sql        	
	 * @param integer $count        	
	 * @param integer $offset
	 *        	OPTIONAL
	 * @throws \Core\Db\Adapter\Exception
	 * @return string
	 */
	public function limit($sql, $count, $offset = 0) {
		$count = intval ( $count );
		if ($count <= 0) {
			/**
			 * @see \Core\Db\Adapter\Exception
			 */
			require_once 'Db/Adapter/Exception.php';
			throw new \Core\Db\Adapter\Exception ( "LIMIT argument count=$count is not valid" );
		}
		
		$offset = intval ( $offset );
		if ($offset < 0) {
			/**
			 * @see \Core\Db\Adapter\Exception
			 */
			require_once 'Db/Adapter/Exception.php';
			throw new \Core\Db\Adapter\Exception ( "LIMIT argument offset=$offset is not valid" );
		}
		
		$sql .= " LIMIT $count";
		if ($offset > 0) {
			$sql .= " OFFSET $offset";
		}
		
		return $sql;
	}
}
