<?php

namespace Update;

class AdminController extends \Core\Base\Action {
	
	public function init() {
		if(!\User\User::getUserData()->is_admin) { $this->redirect($this->url([],'admin_login')); }
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		$data = array('modules' => array());
		
		$update = new \Update\Update();
		$data_dir = glob(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.php');
		if($data_dir) {
			$sort_order = array();
			foreach($data_dir AS $file) {
				$basename = basename($file);
				$name = pathinfo($file,PATHINFO_FILENAME);
				$module = basename(dirname($file));
				if($this->allow($module) && !in_array(strtolower($module), array('autoupdate')) && \Install\Modules::isInstalled($module)) {
					include_once $file;
					$className = $this->getFrontController()->formatModuleName('\Update\Sql\\'.$module.'\\'.$name);
					$reflection = new \Core\Reflection\Classes($className);
					if($reflection->isSubclassOf('\Base\Install\Module')) {
						try {
							$moduleTitle = self::getModuleConfig($module)->get('title');
							if(!$moduleTitle) {
								$moduleTitle = $module;
							}
						} catch (\Core\Exception $e) {
							$moduleTitle = $module;
						}
						$dataInfo = call_user_func(array($className,'getInfo'));
						if(!isset($dataInfo['title'])) { $dataInfo['title'] = $name; }
						if(!isset($dataInfo['version'])) { $dataInfo['version'] = 1; }
						if(!isset($dataInfo['description'])) { $dataInfo['description'] = ''; }
						$dataInfo['id'] = $module.'-'.$name;
						$dataInfo['install'] = $update->isInstalled($dataInfo['id'], $dataInfo['version']) ? 1 : 0;
						$data['modules'][$dataInfo['install']][$moduleTitle][$name] = $dataInfo;
						$sort_order[$dataInfo['install']][$moduleTitle][$name] =  $moduleTitle . '_' . $dataInfo['title'];
					}
				}
			}
			foreach($data['modules'] AS $k=>$v) {
				array_multisort($sort_order[$k], SORT_ASC, $data['modules'][$k]);
				foreach($data['modules'][$k] AS $k2=>$v2) {
					array_multisort($sort_order[$k][$k2], SORT_ASC, $data['modules'][$k][$k2]);
				}
			}
			//array_multisort($sort_order, SORT_ASC, $data['modules']);
		}
		
		if(\Core\Session\Base::get('msg-success')) {
			$data['success'] = \Core\Session\Base::get('msg-success');
			\Core\Session\Base::clear('msg-success');
		} else if(\Core\Session\Base::get('msg-error')) {
			$data['error'] = \Core\Session\Base::get('msg-error');
			\Core\Session\Base::clear('msg-error');
		}
		
		$this->render('index', $data);
	}
	
	public function installAction() {
		$update = new \Update\Update();
		$clear_id = basename($this->getRequest()->getRequest('id'));
		$id = str_replace('-', '\\', $clear_id);
		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . str_replace('-', DIRECTORY_SEPARATOR, $clear_id) . '.php';
		if($id && file_exists($file)) {
			include_once $file;
			$className = $this->getFrontController()->formatModuleName('\Update\Sql\\'.$id);
			$reflection = new \Core\Reflection\Classes($className);
			if($reflection->isSubclassOf('\Base\Install\Module')) {
				$update->getAdapter()->beginTransaction();
				try {
					\Core\Utils\FileHelper::removeDirectory(BASE_PATH . '/cache/MetadataCache');
					$object = new $className();
					$object->install();
					
					$dataInfo = $object->getInfo();
					if(!isset($dataInfo['version'])) { $dataInfo['version'] = 1; }
					$dataInfo['id'] = $clear_id;
					$update = new \Update\Update();
					$update->install($dataInfo);
					$update->getAdapter()->commit();
					\Core\Session\Base::set('msg-success', $this->_('SQL Update successfully installed!'));
				} catch (\Core\Exception $e) {
					\Core\Session\Base::set('msg-error', $e->getMessage());
					$update->getAdapter()->rollBack();
				}
			} else {
				\Core\Session\Base::set('msg-error', sprintf($this->_('Update "%s" is not an instance to "\Base\Install\Module"!'), $id));
			}
		} else {
			\Core\Session\Base::set('msg-error', sprintf($this->_('Update "%s" not found!'), $id));
		}
		$this->redirect( $this->url(array('module'=>'update','controller'=>'admin'),'admin_module') );
	}
	
}