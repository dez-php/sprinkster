<?php

namespace Core\Db\Table;

class Definition {
	
	/**
	 *
	 * @var array
	 */
	protected $_tableConfigs = array ();
	
	/**
	 * __construct()
	 *
	 * @param array|\Core\Config\Main $options        	
	 */
	public function __construct($options = null) {
		if ($options instanceof \Core\Config\Main) {
			$this->setConfig ( $options );
		} elseif (is_array ( $options )) {
			$this->setOptions ( $options );
		}
	}
	
	/**
	 * setConfig()
	 *
	 * @param \Core\Config\Main $config        	
	 * @return \Core\Db\Table\Definition
	 */
	public function setConfig(\Core\Config\Main $config) {
		$this->setOptions ( $config->toArray () );
		return $this;
	}
	
	/**
	 * setOptions()
	 *
	 * @param array $options        	
	 * @return \Core\Db\Table\Definition
	 */
	public function setOptions(Array $options) {
		foreach ( $options as $optionName => $optionValue ) {
			$this->setTableConfig ( $optionName, $optionValue );
		}
		return $this;
	}
	
	/**
	 *
	 * @param string $tableName        	
	 * @param array $tableConfig        	
	 * @return \Core\Db\Table\Definition
	 */
	public function setTableConfig($tableName, array $tableConfig) {
		// @todo logic here
		$tableConfig [\Core\Db\Table::DEFINITION_CONFIG_NAME] = $tableName;
		$tableConfig [\Core\Db\Table::DEFINITION] = $this;
		
		if (! isset ( $tableConfig [\Core\Db\Table::NAME] )) {
			$tableConfig [\Core\Db\Table::NAME] = $tableName;
		}
		
		$this->_tableConfigs [$tableName] = $tableConfig;
		return $this;
	}
	
	/**
	 * getTableConfig()
	 *
	 * @param string $tableName        	
	 * @return array
	 */
	public function getTableConfig($tableName) {
		return $this->_tableConfigs [$tableName];
	}
	
	/**
	 * removeTableConfig()
	 *
	 * @param string $tableName        	
	 */
	public function removeTableConfig($tableName) {
		unset ( $this->_tableConfigs [$tableName] );
	}
	
	/**
	 * hasTableConfig()
	 *
	 * @param string $tableName        	
	 * @return bool
	 */
	public function hasTableConfig($tableName) {
		return (isset ( $this->_tableConfigs [$tableName] ));
	}
}
