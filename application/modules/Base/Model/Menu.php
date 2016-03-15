<?php
namespace Base;

use \Core\Base\MemcachedManager;

class Menu extends \Base\Model\Reference {
	
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->setRowClass('\Base\MenuRow');
	}
	
	public static function getMenu($id, $parent_id = false) {
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
		
		foreach($data AS $d) {
			$d['active'] = $active == $d['id'];
			if(!self::isAccessible($d['module']) && !trim($d['has_required'])) 
				continue;
			if($action->allow($d['module']))
				$result[] = self::toClosure($d);
		}

		return $result;
	}
	
	private static function getMenuWhere($where = null, $order = 'sort_order ASC') {
		$action = \Core\Base\Action::getInstance();
		
		$db = \Core\Db\Init::getDefaultAdapter();
		$sql = $db->select()
					->from('menu', ['*', 'has_required' => '(SELECT GROUP_CONCAT(`module`) FROM `menu_required` WHERE menu_id = menu.id LIMIT 1)'])
					->where('status = 1')
					->order($order);
				if($where)
					$sql->where($where);
		
		$resret = array();
		$data = $db->fetchAll($sql);
		
		foreach($data AS $d) {
			if(!self::isAccessible($d['module']) && !trim($d['has_required'])) 
				continue;
			if($action->allow($d['module']))
				$resret[] = self::toClosure($d);
		}

		return $resret;
	}
	
	public static function getByGuid($guid) {
		return (new self())->fetchRow(['guid = ?' => (string)$guid]);			
	}
	
	public static function isGuid($menu, $guid, &$menuReturn=null) {
		if(is_array($menu)) {
			foreach($menu AS $m) {
				if(isset($m->guid) && $m->guid == $guid) {
					$menuReturn = $m;
					return true;
				}
			}
		}
		return false;
	}
	
	public static function getMenuCallback($id, $parent_id = false, $callback) {
		$menu = self::getMenu($id, $parent_id);
		if($callback instanceof \Closure) {
			$menu = call_user_func($callback, $menu);
		}
		return $menu;
	}
	
	public static function getMenuRoutes($id, $parent_id = false) {
		$menu = self::getMenu($id, $parent_id);
		$return = array();
		foreach($menu AS $m) {
			$return[$m->widget] = $m->widget;
		}
		return $return;
	}
	
	public static function getMenuConfig(array $where) {
		$self = new self();
		$row = $self->fetchRow($self->makeWhere($where));
		if($row && $row->config) {
			return unserialize($row->config);
		}
		return array();
	}
	
	protected static function toClosure($d) {
		$s = microtime(true);
		$self = new self();
		$data = [
			'table'     => $self,
			'data'      => $d,
			'readOnly'  => true,
			'stored'    => true
		];
		
		$rowClass = $self->getRowClass();
		if (!class_exists($rowClass)) {
			require_once 'Loader.php';
			\Core\Loader\Loader::loadClass($rowClass);
		}

		return new $rowClass($data);
		
// 		$menu = new \Core\Base\Closure();
// 		foreach($d AS $k=>$v) {
// 			if($k == 'disabled') {
// 				$src = $k . '_closure';
// 				$menu->$src = $menu->$k;

// 				$sys_check = '';
// 				if(trim($d['has_required']) && count($modules = explode(',', $d['has_required'])) > 0) {
// 					$maped = array_map(function($module) {
// 						return '\Core\Base\Action::getInstance()->isModuleAccessible("' . $module . '")';
// 					}, $modules);
// 					$sys_check = 'if(' . implode(' || ', $maped) . ') { return false; } else { return true; }';
// 				}
// 				if(trim($v)) {
// 					try {
// 						call_user_func(create_function('$menu', '$menu->' . $k . ' = function($data = null, $config = null) { ' . $sys_check . ' ' . $v . ' };'), $menu);
// 					} catch (\Core\Exception $e) {
// 						call_user_func(create_function('$menu', '$menu->' . $k . ' = function($data = null, $config = null) { ' . $sys_check . ' return false; };'), $menu);
// 					}
// 				} else {
// 					call_user_func(create_function('$menu', '$menu->' . $k . ' = function($data = null, $config = null) { ' . $sys_check . ' return false; };'), $menu);
// 				}
// 			} else {
// 				$menu->{$k} = $v;
// 			}
// 		}
// 		return $menu;
	}
	
	public static function isAccessible($module) {
		if(!$module) return true;
		static $action = null, $front = null, $app = null, $cache = [];
		if($action === null) $action = \Core\Base\Action::getInstance();
		if($front === null) $front = $action->getFrontController();
		if($app === null) $app = $action->getApplication();
		$module = $front->formatModuleName($module);
		if(!isset($cache[$module])) $cache[$module] = (( $object = $app->getModules($module) ) instanceof \Core\Base\Module) && $object->isAccessible();
			return $cache[$module];
	}
	
	public function getActive() {
		if( !\Core\Registry::isRegistered('selected_id') ) {
			$max = 0;
			$id = 0; 
			$request = \Core\Http\Request::getInstance();
			$uri = explode('?',$request->getFullUri())[0];
			$uri = trim($uri, '/');
			if(trim($uri,'/') == '') {
				\Core\Registry::set('selected_id', 0);
				return 0;
			}
			if(function_exists('similar_text')) {
				$user = \User\User::getUserData();
// 				$all_menus = $data ? $data : \Base\Menu::getMenu($group);
				$all_menus = self::getMenuWhere('group_id IN("FeatureMenu","UserMenu","TopMenu")', 'id DESC');
// 				$all_menus = (new self())->fetchAll(['group_id IN("FeatureMenu","UserMenu","TopMenu")'], 'id DESC');
				foreach($all_menus AS $m) {
					if(is_array($m)) { $m = (object)$m; }
					if(!$m->widget)
						continue;
					
// 					try {
// 						if($m->disabled())
// 							continue;
// 					} catch (\Exception $e) {}
					
					try {
						$route = @unserialize($m->route);
						if(!$route) { $route = []; }
						$route['user_id'] = $user->id;
						$url = trim(str_replace($request->getBaseUrl(), '/', (new \Core\Base\Core())->url($route, $m->widget, false, false)), '/');
					} catch (\Exception $e) {
						$url = '-11111';
					}
					
					if(trim($url,'/') == trim($uri, '/')) {
						$max = 100;
						$id = $m->id;
						break;
					} else {
						similar_text($url, $uri, $p);
	// 					var_dump([$m->id, $url, $uri, $p]);
						if($p > $max) { 
							$max = $p;
							$id = $m->id;
						}
					}
					
				}
			} 
			\Core\Registry::set('selected_id', $max > 70 ? $id : 0);
		} 
		return \Core\Registry::get('selected_id');
	}
	
}

?>