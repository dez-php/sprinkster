<?php

namespace Core\Db;

class Table extends \Core\Db\Table\AbstractTable {
	
	/**
	 * __construct() - For concrete implementation of Core\Db\Table
	 *
	 * @param string|array $config
	 *        	string can reference a \Core\Registry key for a db adapter
	 *        	OR it can reference the name of a table
	 * @param array|\Core\Db\Table\Definition $definition        	
	 *
	 * @return \Core\Db\Table\AbstractTable
	 */
	public function __construct($config = array(), $definition = null) {
		if ($definition !== null && is_array ( $definition )) {
			$definition = new \Core\Db\Table\Definition ( $definition );
		}
		
		if (is_string ( $config )) {
			if (\Core\Registry::isRegistered ( $config )) {
				trigger_error ( __CLASS__ . '::' . __METHOD__ . '(\'registryName\') is not valid usage of Core\Db\Table, ' . 'try extending Core\Db\Table\AbstractTable in your extending classes.', E_USER_NOTICE );
				$config = array (
						self::ADAPTER => $config 
				);
			} else {
				// process this as table with or without a definition
				if ($definition instanceof \Core\Db\Table\Definition && $definition->hasTableConfig ( $config )) {
					// this will have DEFINITION_CONFIG_NAME & DEFINITION
					$config = $definition->getTableConfig ( $config );
				} else {
					$config = array (
							self::NAME => $config 
					);
				}
			}
		}
		
		parent::__construct ( $config );
	}
}
