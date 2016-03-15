<?php
namespace Base;

use \Core\Base\Debug;

use \Core\Base\MemcachedManager;
use \Core\Interfaces\IPersistentWidget;
use \Core\Interfaces\ICacheableWidget;

class PermissionMenu extends Menu implements IPersistentWidget {
	
	public static function getMenu($id, $parent_id = false) {
		Debug::start();

		$action = \Core\Base\Action::getInstance();
		$db = \Core\Db\Init::getDefaultAdapter();
		$cache_key = MemcachedManager::key(__CLASS__, __METHOD__, $id, $parent_id);
		$result = [];

		$data = MemcachedManager::get($cache_key, function() use ($db, $id, $parent_id) {
			$sql = $db->select()
				->from('menu', ['*', 'has_required' => '(SELECT GROUP_CONCAT(`module`) FROM `menu_required` WHERE menu_id = menu.id LIMIT 1)'])
				->where('status = 1')
				->where('group_id = ?', $id)
				->order('sort_order ASC');

			if($parent_id !== false) {
				if($parent_id === null) {
					$sql->where('parent_id IS NULL');
				} else {
					$sql->where('parent_id = ?', $parent_id);
				}
			}

			return $db->fetchAll($sql);
		});
			
		$active = (new self())->getActive();

		foreach($data AS $d)
		{
			$d['active'] = $active == $d['id'];

			if(!self::isAccessible($d['module']) && !trim($d['has_required'])) 
				continue;

			if(!$action->allow($d['module']))
				continue;

			$permission = $d['widget'];

			if(!$d['is_widget'])
			{
				try 
				{
					$route = \Core\Base\Action::getInstance()->getRouter()->getRoute($permission)->getDefaults();
					
					//$permission = array_map(function($route) {
					$permission = [];

					$permission[] = isset($route['module']) ? $route['module'] : 'home';
					$permission[] = isset($route['controller']) ? $route['controller'] : 'index';
					$permission[] = isset($route['action']) ? $route['action'] : 'index';

					$permission = strtolower(implode('.',$permission));

						//return strtolower(implode('.',$permission));
					//}, [ $route ])[0];
				}
				catch (\Core\Exception $e)
				{
					$permission = $d['module'];
				}
			}

			// User has the required permission
			if(!\Permission\Permission::capable($permission))
				continue;

			$result[] = self::toClosure($d);

		}

		return $result;
	}
	
	public static function getMenuCallback($id, $parent_id = false, $callback) {
		$menu = self::getMenu($id, $parent_id);
		if($callback instanceof \Closure) {
			$menu = call_user_func($callback, $menu);
		}
		return $menu;
	}
	
}

?>