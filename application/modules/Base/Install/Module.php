<?php

namespace Base\Install;

class Module extends \Core\Base\Core {

	protected $tables = [];
	protected $system_reference = [];

	public function __construct() {
		
	}
	
	public static function getInfo() {
		return array();
	}
	
	/**
	 * @param string $table
	 * @param string|array $key
	 * @param string|null $value
	 * @return Ambigous <\Core\Db\Table\Select, number>
	 */
	public function existRecord($table, $key, $value = null) {
		$table = new \Core\Db\Table($table);
		if($value === null) {
			if(is_array($key)) {
				return $table->countBy($key);
			} else {
				throw new \Core\Exception('Value should be array(1, 2, 3)');
			}
		}
		return $table->countBy(array($key => $value));
	}
	
	/**
	 * @param string $table
	 * @param array $data
	 * @return Ambigous <\Core\Db\Table\mixed, mixed, multitype:>
	 */
	public function execute($table, array $data)
	{
		try
		{ 
			if($table == 'menu')
			{
				if(!isset($data['sort_order']))
					$data['sort_order'] = 0;

				if(!isset($data['guid']) || !$data['guid'])
					$data['guid'] = $this->guid( (int)$data['parent_id'] . $data['title'] . $data['group_id'] );
			}

			$table = new \Core\Db\Table($table);
			return $table->insert($data);
		}
		catch (\Core\Exception $e)
		{ 
			throw new \Core\Exception($e->getMessage());
			\Core\Log::write($e->getMessage());
			return FALSE; 
		}
	}


	/**
	 * @param string $table
	 * @param array $data
	 * @return Ambigous <\Core\Db\Table\mixed, mixed, multitype:>
	 */
	public function update($table, array $data, $where) {
		try
		{ 
			if($table == 'menu')
			{
				$parent_id = isset($data['parent_id']) ? $data['parent_id'] : NULL;
				$title = isset($data['title']) ? $data['title'] : NULL;
				$group_id = isset($data['group_id']) ? $data['group_id'] : NULL;

				if(!isset($data['guid']) || !$data['guid'])
					$data['guid'] = $this->guid( (int) $parent_id . $title . $group_id );
			}
		
			$table = new \Core\Db\Table($table);

			return $table->update($data, $where);
		}
		catch (\Core\Exception $e)
		{
			throw new \Core\Exception($e->getMessage());
			\Core\Log::write($e->getMessage());
		}

		return FALSE;
	}
	
	public function query($query) {
		try {
			$db = \Core\Db\Init::getDefaultAdapter();
			return $db->query($query);
		} catch (\Core\Db\Exception $e) {
			throw new \Core\Exception($e->getMessage());
			\Core\Log::write($e->getMessage());
			return false; 
		}
	}
	
	public function getRecordId($table, $key) {
		$table = new \Core\Db\Table($table);
		$res = $table->fetchRow($table->makeWhere($key));
		if($res) {
			return $res->id;
		}
		return null;
	}
	
	public function get($table, $key) {
		$table = new \Core\Db\Table($table);
		return $table->fetchRow($table->makeWhere($key));;
	}
	
	public function getAll($table, $key) {
		$table = new \Core\Db\Table($table);
		return $table->fetchAll($table->makeWhere($key));;
	}
	
	public function deleteRecord($table, $key) {
		$table = new \Core\Db\Table($table);
		return $table->delete($table->makeWhere($key));
	}


	/**
	 * @param string $table
	 * @param array $data
	 * @return Ambigous <\Core\Db\Table\mixed, mixed, multitype:>
	 */
	public function installIfNotExist($table, array $data) {
		$table = new \Core\Db\Table($table);
		$row = $table->fetchRow($table->makeWhere($data));
		
 		// \Core\Log::log(var_export(array($table, $data),true));return;
		if(!$row) {
			if($table == 'menu') {
				if(!isset($data['guid']) || !$data['guid']) { $data['guid'] = $this->guid( (int)$data['parent_id'] . $data['title'] . $data['group_id'] ); }
			}
			return $table->insert($data);
 		//	return $this->execute($table, $data);
		}
	}
	
	public function install() {}
	
	public function alter($sql) {
		$parse = new \Core\Db\Schema\Parser();
		$queries = $parse->delta( $sql, false, false);
		if(isset($queries['dbh_global']) && is_array($queries['dbh_global'])) {
			foreach($queries['dbh_global'] AS $table => $subs) {
				foreach($subs AS $query) {
					if(trim($query['query'])) {
						$this->query($query['query']);
					}
				}
			}
		}
	}
	
	public function getMax($row, $table, $where = null) {
		$table = new \Core\Db\Table($table);
		$sql = $table->select()->from($table, 'MAX(' . $row . ') AS max')->limit(1);
		if($where) { $sql->where($table->makeWhere($where)); }
		return (int)$table->fetchRow($sql)->max;
	}

	public function getModuleDirectory() {
		return $this->getFrontController()->getModuleDirectoryWithDefault($this->getNamespace());
	}
	
	public function insertBeforeMenu($guid, $data) {
		$before = is_array($guid) ? $guid : [$guid];
		if(!isset($data['guid']) || !$data['guid']) { $data['guid'] = $this->guid( (int)$data['parent_id'] . $data['title'] . $data['group_id'] ); }
		foreach($before AS $r) {
			if( ( $row = $this->get('menu', array('guid' => $r)) ) !== null ) {
				$data['sort_order'] = $row->sort_order;
				$all = $this->getAll('menu', ['group_id' => $row->group_id,'parent_id' => $row->parent_id,'sort_order' => '>=' . $row->sort_order]);
				foreach($all AS $s => $a) {
					$a->sort_order = ($data['sort_order'] + 2) + $s;
					$a->save();
				}
				$row->sort_order = $row->sort_order + 1;
				$row->save();
				if(!isset($data['group_id']) || !$data['group_id']) { $data['group_id'] = $row->group_id; }
				return $this->execute('menu', $data);
				break;
			}
		}
		if(!isset($data['sort_order'])) {$data['sort_order'] = 0; }
		return $this->execute('menu', $data);
	}
	
	public function insertAfterMenu($after, $data) {
		$after = is_array($after) ? $after : array($after);
		foreach($after AS $r) {
			if( ( $row = $this->get('menu', array('guid' => $r)) ) !== null ) {
				$data['sort_order'] = $row->sort_order + 1;
				if(!isset($data['guid']) || !$data['guid']) { $data['guid'] = $this->guid( (int)$row->parent_id . $data['title'] . $data['group_id'] ); }
				$all = $this->getAll('menu', ['group_id' => $row->group_id,'parent_id' => $row->parent_id,'sort_order' => '>' . $row->sort_order]);
				foreach($all AS $s => $a) {
					$a->sort_order = ($data['sort_order'] + 1) + $s;
					$a->save();
				}
				if(!isset($data['group_id']) || !$data['group_id']) { $data['group_id'] = $row->group_id; }
				return $this->execute('menu', $data);
				break;
			}
		}
		if(!isset($data['sort_order'])) {$data['sort_order'] = 0; }
		return $this->execute('menu', $data);
	}
	
	public function appendMenu($data) {
		$max = $this->getMax('sort_order', 'menu', ['group_id' => $data['group_id'],'parent_id' => $data['parent_id']]);
		$data['sort_order'] = ((int)$max + 1);
		return $this->execute('menu', $data);
	}
	
	public function prependMenu($data) {
		$data['sort_order'] = 0;
		$all = $this->getAll('menu', ['group_id' => $data['group_id'],'parent_id' => $data['parent_id']]);
		foreach($all AS $s => $a) {
			$a->sort_order = ($data['sort_order'] + 1) + $s;
			$a->save();
		}
		return $this->execute('menu', $data);
	}

	public function addPermissions()
	{

		$permission_config_path = $this->getModuleDirectory() . '/config/permission.config.php';

		if(!file_exists($permission_config_path) || !is_file($permission_config_path))
			return;

		$permission_config = require($permission_config_path);

		if(!$permission_config || !is_array($permission_config) || !isset($permission_config['permissions']))
			return;

		$permissions = $permission_config['permissions'];

		foreach($permissions as $code => $metadata)
		{
			if(!$code || !isset($metadata['name']))
				continue;

			$name = $metadata['name'];
			$description = isset($metadata['description']) ? $metadata['description'] : NULL;

			\Permission\Permission::register($code, $name, $description ?: NULL);
		}
	}

	public function removePermissions()
	{

		$permission_config_path = $this->getModuleDirectory() . '/config/permission.config.php';

		if(!file_exists($permission_config_path) || !is_file($permission_config_path))
			return;

		$permission_config = require($permission_config_path);

		if(!$permission_config || !is_array($permission_config) || !isset($permission_config['permissions']))
			return;

		$permissions = $permission_config['permissions'];

		foreach($permissions as $code => $metadata)
		{
			if(!$code || !isset($metadata['name']))
				continue;

			\Permission\Permission::unregister($code);
		}
	}
	
	public function _insertPages() {
		if(!isset($this->page) || !is_array($this->page) || !isset($this->page[0]['key']))
			return;
		$languages = \Core\Base\Action::getModule('Language')->getLanguages();
		foreach($this->page AS $r => $p) {
			if(!isset($this->page_description[$r]))
				continue;
			if(!$this->existRecord('page', 'key', $p['key'])) {
				if(!isset($p['date_added']))
					$p['date_added'] = date('Y-m-d H:i:s');
				if(!isset($p['date_modified']))
					$p['date_modified'] = date('Y-m-d H:i:s');
				$page_id = $this->execute('page', $p);
			} else {
				$page_id = $this->getRecordId('page', ['key' => $p['key']]);
			}
			if(!$page_id)
				continue;
			foreach($languages AS $l) {
				if(!$this->existRecord('page_description', ['page_id' => $page_id,'language_id' => $l->id])) {
					$this->page_description[$r]['page_id'] = $page_id;
					$this->page_description[$r]['language_id'] = $l->id;
					$this->execute('page_description', $this->page_description[$r]);
				}
			}
		}
	}
	
	public function _deletePages() {
		if(!isset($this->page) || !is_array($this->page) || !isset($this->page[0]['key']))
			return;
		foreach($this->page AS $r => $p) {
			$this->deleteRecord('page', array('key'=>$p['key']));
		}
	}
	
	public function _insertReference() {
		if(!is_array($this->system_reference))
			return;
		foreach($this->system_reference AS $c) {
			$c['guid'] = $this->guid( serialize($c) );
			if(!$this->existRecord('system_reference', 'guid', $c['guid'])) {
				$this->execute('system_reference', $c);
			}
		}
	}
	
	public function _deleteReference() {
		$this->deleteRecord('system_reference', ['module' => strtolower($this->getNamespace())]);
	}
	
	///////////////////////////////////////
	
	public function insertBefore($table, array $rowSearch, array $data, $where = []) {
		if(is_array($rowSearch)) {
			foreach($rowSearch AS $search) {
				if(!is_array($search)) { $search = $rowSearch; }
				list($key, $value) = each($search); 
					if(!$value)
						continue;
				if( ( $row = $this->get($table, [$key => $value]) ) !== null ) {
					$data['sort_order'] = $row->sort_order;
					$where['sort_order'] = ' > ' . $row->sort_order;
					$all = $this->getAll($table, $where);
					foreach($all AS $s => $a) {
						$a->sort_order = ($data['sort_order'] + 2) + $s;
						$a->save();
					}
					$row->sort_order = $row->sort_order + 1;
					$row->save();
					return $this->execute($table, $data);
				}
			}
		}
		return $this->prependTo($table, $data, $where);
	}
	
	public function insertAfter($table, array $rowSearch, array $data, $where = []) {
		if(is_array($rowSearch)) {
			foreach($rowSearch AS $search) {
				if(!is_array($search)) { $search = $rowSearch; }
				list($key, $value) = each($search); 
					if(!$value)
						continue;
				if( ( $row = $this->get($table, [$key => $value]) ) !== null ) {
					$data['sort_order'] = $row->sort_order + 1;
					$where['sort_order'] = ' > ' . $row->sort_order;
					$all = $this->getAll($table, $where);
					foreach($all AS $s => $a) {
						$a->sort_order = ($data['sort_order'] + 1) + $s;
						$a->save();
					}
// 					$row->sort_order = $row->sort_order + 1;
// 					$row->save();
					return $this->execute($table, $data);
				}
			}
		}
		return $this->appendTo($table, $data, $where);
	}
	
	public function appendTo($table, $data, $where = []) {
		$max = $this->getMax('sort_order', $table, $where);
		$data['sort_order'] = (int)$max ? ((int)$max + 1) : 0;
		return $this->execute($table, $data);
	}
	
	public function prependTo($table, $data, $where = []) {
		$data['sort_order'] = 0;
		$all = $this->getAll($table, $where);
		foreach($all AS $s => $a) {
			$a->sort_order = ($data['sort_order'] + 1) + $s;
			$a->save();
		}
		return $this->execute($table, $data);
	}

	public function dropTables($tables = [])
	{
		if(!$tables || empty($tables))
			$tables = $this->tables;

		if(!$tables || empty($tables))
			return;

		if(!is_array($tables))
			$tables = [ $tables ];

		$db = \Core\Db\Init::getDefaultAdapter();

		foreach($tables as $table)
		{
			$table = str_replace([ '\'', '"', '`' ], '', $table);
			$this->query("DROP TABLE IF EXISTS {$db->quoteTableAs($table)}");
		}
	}
	
	public function removeMenuIfNoChilds($guid) {
		$parent_id = $this->getRecordId('menu', ['guid' => $guid]);
			if($parent_id && !$this->getRecordId('menu', ['parent_id' => $parent_id]))
				$this->deleteRecord('menu', ['id' => $parent_id]);
	}
	
}