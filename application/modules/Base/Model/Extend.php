<?php

namespace Base;

class Extend extends \Base\Model\Reference {
	
	/**
	 * @param string $module
	 * @param string $type
	 * @param string $order
	 * @return \Core\Db\Table\Rowset\AbstractRowset
	 */
	public function getExtension($module, $type, $order = 'sort_order ASC') {
		return $this->fetchAll(array(
			'module = ?' => $module,
			'type = ?' => $type,
			'status = 1'
		), $order);
	}
	
}