<?php
namespace Core\Db\Table\Row;

class DynamicRow extends AbstractRow implements \ArrayAccess, \IteratorAggregate {

	protected $_extra = [];

	public function __get($columnName)
	{
		$columnName = $this->_transformColumn($columnName);

		if (! array_key_exists ( $columnName, $this->_data ))
		{
			if(! array_key_exists ( $columnName, $this->_extra ))
			{
				require_once 'Db/Table/Row/Exception.php';
				throw new \Core\Db\Table\Row\Exception ( "Specified column \"$columnName\" is not in the row" );
			}

			return $this->_extra [$columnName];
		}

		return $this->_data [$columnName];
	}
	
	public function __set($columnName, $value)
	{
		$columnName = $this->_transformColumn($columnName);

		if (! array_key_exists ( $columnName, $this->_data ))
		{
			$this->_extra[$columnName] = $value;
			return;
		}

		$this->_data [$columnName] = $value;
		$this->_modifiedFields [$columnName] = true;
	}

	public static function createFromAbstractRow(AbstractRow $row)
	{
		$result = new self;

		$result->_data = $row->_data;
		$result->_cleanData = $row->_cleanData;
		$result->_modifiedFields = $row->_modifiedFields;
		$result->_table = $row->_table;
		$result->_connected = $row->_connected;
		$result->_readOnly = $row->_readOnly;
		$result->_tableClass = $row->_tableClass;
		$result->_primary = $row->_primary;
		$result->_limit = $row->_limit;
		$result->_where = $row->_where;
		$result->_order = $row->_order;

		return $result;
	}

}