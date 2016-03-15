<?php
namespace Base\Model;

use \Core\Base\MemcachedManager;

class Reference extends \Core\Db\Table\AbstractTable {

	private static $cache = [];
	
	public function setReferenceMap() {
		$className = get_called_class();
		if(!isset(self::$cache[$className]))
			self::$cache[$className] = $this->getReferences(get_called_class());
		return self::$cache[$className];
	}
	
	private function getReferences($class, $parent_id = null) { 
		$db = \Core\Db\Init::getDefaultAdapter();
		$cache_key = MemcachedManager::key(__CLASS__, __METHOD__, 'system_reference_' . $class . '_' . $parent_id);

		$references = MemcachedManager::load($cache_key);

		if(!$references)
		{
			if($parent_id)
				$references = $db->fetchAll('SELECT *, 0 AS childs FROM system_reference sr WHERE parent_id = ' . $db->quote($parent_id) . ' LIMIT 1');
			else
				$references = $db->fetchAll('SELECT *, (SELECT COUNT(1) FROM system_reference WHERE parent_id = sr.id LIMIT 1) AS childs FROM system_reference sr WHERE object LIKE ' . $db->quote(str_replace('\\', '\\\\', $class)) . ' AND parent_id IS NULL');

			MemcachedManager::save($cache_key, $references);
		}

		$result = [];

		foreach($references AS $ref)
		{
			$result[$ref['name']] = [
				'columns'           => $ref['columns'],
				'refTableClass'     => $ref['refTableClass'],
				'refColumns'        => $ref['refColumns'],
				'singleRow'			=> (bool)$ref['singleRow']
			];

			if($ref['where']) { $result[$ref['name']]['where'] = $ref['where']; }
			if($ref['childs']) { $result[$ref['name']]['referenceMap'] = array_shift($this->getReferences(null, $ref['id'])); }
		} 
		return $result;
	}
	
}