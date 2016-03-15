<?php

namespace Install;

use Core\Cache\Exception;

class AdminController extends \Core\Base\Action {

    public function init() {
        if(!\User\User::getUserData()->is_admin) { $this->redirect($this->url([],'admin_login')); }
        $this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
    }

    public function indexAction() {

        \Core\Base\Init::requestCache();
        $modules = \Core\Arrays::array_change_value_case(array_keys(\Install\Modules::getModules()));

        $db = \Core\Db\Init::getDefaultAdapter();
        if($modules) {
            $db->query('INSERT IGNORE INTO `module` (`module`) VALUES (\'' . implode("'),('",$modules) . '\')');
        }
        $basemodules = $installmodule = array();

        if($modules) {
            foreach($modules AS $k=>$module) {
                if($module) {
                    try { $moduleConfig = \Install\Modules::getModulesConfig($module); } catch(\Core\Exception $e) { $moduleConfig = null; }
                    if($moduleConfig && $moduleConfig->get('hasInstall')) {
                        $installmodule[] = $module;
                    } else {
                        $basemodules[] = $module;
                    }
                }
            }
        }
        if($basemodules) {
            $db->query("UPDATE module SET install = 1 WHERE install = 0 AND module IN (" . $db->quote($basemodules) . ")");
        }

        $data['modules'] = $sort_order = array();

        /* all modules */
        $base = base64_decode(\Core\Base\Init::BASE_DATA);
        $allModules = $this->modulesChecker();
        if($allModules) {
            foreach($allModules AS $value) {
                $data['modules'][$value['model']] = [
                    'id' => $value['id'],
                    'module' => $value['model'],
                    'install' => 0,
                    'title' => $value['title'],
                    'description' => $value['description'],
                    'version' => $value['version'],
                    'config' => null,
                    'allow' => 0,
                    'buy' => $base . 'clients/buy?m=' . $value['model'] . '&s=' . \Core\Registry::forceGet('system_type')
                ];
                $sort_order[$value['model']] = $value['title'];
            }
        }
        /* all modules */

        if($installmodule) {
            $modulesData = $db->fetchAll("SELECT * FROM module WHERE  module IN (" . $db->quote($installmodule) . ") ORDER BY module ASC");
            $appModules = \Install\Modules::getModules();
            foreach($modulesData AS $m) {
                if(in_array(strtolower($m['module']), \Core\Arrays::array_change_value_case(array_keys($appModules)) ) ) {
                    $moduleConfig = \Install\Modules::getModulesConfig($m['module']);
                    if($moduleConfig->get('hasInstall')) {

                        $translate = new \Translate\Locale('Backend\\'.ucfirst(strtolower($m['module'])), self::getModule('Language')->getLanguageId());

                        $data['modules'][$m['module']] = array(
                            'id' => $m['id'],
                            'module' => $m['module'],
                            'install' => $m['install'],
                            'title' => $translate->_($moduleConfig->get('title')),
                            'description' => $translate->_($moduleConfig->get('description')),
                            'version' => $moduleConfig->get('version'),
                            'config' => $moduleConfig,
                            'allow' => $this->allow($m['module']),
                            'buy' => $base . 'clients/buy?m=' . $m['module'] . '&s=' . \Core\Registry::forceGet('system_type')
                        );
                        $sort_order[$m['module']] = $translate->_($moduleConfig->get('title'));
                    }
                }
            }
        }
        array_multisort($sort_order, SORT_ASC, $data['modules']);

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
        $moduleTable = new \Install\Modules();

        $demo_user_id = \Base\Config::get('demo_user_id');
        if($demo_user_id && $demo_user_id == \User\User::getUserData()->id) {
            \Core\Session\Base::set('msg-error', $this->_('You don\'t have permissions for this action!'));
            $this->redirect( $this->url(array('module'=>'install','controller'=>'admin'),'admin_module') );
        }

        $module = $moduleTable->fetchRow($moduleTable->makeWhere(array(
            'id' => $this->getRequest()->getRequest('id'),
            'install' => 0
        )));
        if($module && $this->allow($module->module)) {
            $classname = $this->getFrontController()->formatModuleName('\\' . $module->module . '\install\module');
            $file = str_replace('\\',DIRECTORY_SEPARATOR,$this->getFrontController()->classToFilename($classname));
            $moduleTable->getAdapter()->beginTransaction();
            try {
                $require = \Install\Modules::getModulesConfig($module->module,false);
                $require = $require ? $require->get('require') : false;
                $require_array = array();
                if($require) {
                    foreach($require->toArray() AS $require_module => $require_name) {
                        if($require_module == 'sys_version') {
                            /*if(!version_compare(\Core\Registry::get('system_version'), $require_name, '>=')) {
                                $require_array[$require_module] = sprintf($this->_('System version >= "%s"'), $require_name);
                            }*/
                        } else if( $this->allow($require_module) ) {
                            if(!\Install\Modules::isInstalled($require_module)) {
                                $require_array[$require_module] = $require_name;
                            }
                        } else {
                            if(!\Install\Modules::isInstalled($require_module)) {
                                $require_array[$require_module] = $require_name;
                            }
                        }
                    }
                }
                if($require_array) {
                    throw new Exception(sprintf($this->_('This module require following modules: %s'),implode(',', $require_array)));
                }

                if(file_exists(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'modules' . $file)) {
                    include_once APPLICATION_PATH . DIRECTORY_SEPARATOR . 'modules' . $file;
                    $obj = new $classname();
                    if($obj instanceof \Base\Install\Module) {
                        $obj->install();
                        $obj->_insertPages();
                        $obj->_insertReference();
                        $obj->addPermissions();
                    }
                }

                //new install help
                $otherinstall = \Install\Modules::getModulesInstall($module->module);
                foreach($otherinstall AS $otis) {
                    $extobj = new $otis();
                    if(method_exists($extobj, 'extendInstall' . ucfirst(strtolower($module->module))) && is_callable([$extobj, 'extendInstall' . ucfirst(strtolower($module->module))]))
                        call_user_func([$extobj, 'extendInstall' . ucfirst(strtolower($module->module))]);
                }
                $otherinstall = \Install\Modules::getModulesAfterInstall($module->module);
                foreach($otherinstall AS $otis) {
                    $classname = $otis[0];
                    $extobj = new $classname();
                    if(method_exists($extobj, 'extendInstall' . ucfirst(strtolower($module->module))) && is_callable([$extobj, 'extendInstall' . ucfirst(strtolower($opts[1]))]))
                        call_user_func([$extobj, 'extendInstall' . ucfirst(strtolower($opts[1]))]);
                }
                //end new install help

                \Core\Utils\FileHelper::removeDirectory(BASE_PATH . '/cache/MetadataCache');
                @mkdir(BASE_PATH . '/cache/MetadataCache', 0777, true);
                $module->install = 1;
                $module->save();
                $moduleTable->getAdapter()->commit();
                \Core\Session\Base::set('msg-success', $this->_('Module successfully installed!'));
            } catch (\Core\Exception $e) {
                \Core\Session\Base::set('msg-error', $e->getMessage());
                $moduleTable->getAdapter()->rollBack();
            }
        }
        $this->redirect( $this->url(array('module'=>'install','controller'=>'admin'),'admin_module') );
    }

    public function uninstallAction() {
        $moduleTable = new \Install\Modules();

        $demo_user_id = \Base\Config::get('demo_user_id');
        if($demo_user_id && $demo_user_id == \User\User::getUserData()->id) {
            \Core\Session\Base::set('msg-error', $this->_('You don\'t have permissions for this action!'));
            $this->redirect( $this->url(array('module'=>'install','controller'=>'admin'),'admin_module') );
        }

        $module = $moduleTable->fetchRow($moduleTable->makeWhere(array(
            'id' => $this->getRequest()->getRequest('id'),
            'install' => 1
        )));
        if($module && $this->allow($module->module)) {
            $classname = $this->getFrontController()->formatModuleName('\\' . $module->module . '\install\module');
            $file = str_replace('\\',DIRECTORY_SEPARATOR,$this->getFrontController()->classToFilename($classname));
            $moduleTable->getAdapter()->beginTransaction();
            try {
                $require_array = array();
                foreach(array_keys($this->getApplication()->getModules()) AS $m) {
                    if(\Install\Modules::isInstalled($m)) {
                        $moduleConfig = \Install\Modules::getModulesConfig($m);
                        if($moduleConfig && ($config = $moduleConfig->get('require')) !== null) {
                            $require = $config->toArray();

                            if(array_key_exists(strtolower($module->module), $require)) {
                                $require_array[$moduleConfig->get('title')] = $moduleConfig->get('title');
                            }
                        }
                    }
                }
                if($require_array) {
                    throw new Exception(sprintf($this->_('The following modules "%s" are required this module!'),implode(',', $require_array)));
                }

                if(file_exists(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'modules' . $file)) {
                    include_once APPLICATION_PATH . DIRECTORY_SEPARATOR . 'modules' . $file;
                    $obj = new $classname();
                    if($obj instanceof \Base\Install\Module) {
                        if(method_exists($obj,'uninstall')) {
                            $obj->uninstall();
                        }
                    }
                }

                //new install help
                $otherinstall = \Install\Modules::getModulesUninstall($module->module);
                foreach($otherinstall AS $otis) {
                    $extobj = new $otis();
                    if(method_exists($extobj, 'extendUninstall' . ucfirst(strtolower($module->module))) && is_callable([$extobj, 'extendUninstall' . ucfirst(strtolower($module->module))]))
                        call_user_func([$extobj, 'extendUninstall' . ucfirst(strtolower($module->module))]);
                }
                $otherinstall = \Install\Modules::getModulesAfterUninstall($module->module);
                foreach($otherinstall AS $otis) {
                    $classname = $otis[0];
                    $extobj = new $classname();
                    if(method_exists($extobj, 'extendUninstall' . ucfirst(strtolower($module->module))) && is_callable([$extobj, 'extendUninstall' . ucfirst(strtolower($opts[1]))]))
                        call_user_func([$extobj, 'extendUninstall' . ucfirst(strtolower($opts[1]))]);
                }
                //end new install help

                \Core\Utils\FileHelper::removeDirectory(BASE_PATH . '/cache/MetadataCache');
                @mkdir(BASE_PATH . '/cache/MetadataCache', 0777, true);
                $module->install = 0;
                $module->save();

                $moduleTable->getAdapter()->commit();
                \Core\Session\Base::set('msg-success', $this->_('Module successfully uninstalled!'));
            } catch (\Core\Exception $e) {
                \Core\Session\Base::set('msg-error', $e->getMessage());
                $moduleTable->getAdapter()->rollBack();
            }
        }
        $this->redirect( $this->url(array('module'=>'install','controller'=>'admin'),'admin_module') );
    }

    public function deleteAction() {
        $moduleTable = new \Install\Modules();

        $demo_user_id = \Base\Config::get('demo_user_id');
        if($demo_user_id && $demo_user_id == \User\User::getUserData()->id) {
            \Core\Session\Base::set('msg-error', $this->_('You don\'t have permissions for this action!'));
            $this->redirect( $this->url(array('module'=>'install','controller'=>'admin'),'admin_module') );
        }

        $module = $moduleTable->fetchRow($moduleTable->makeWhere(array(
            'id' => $this->getRequest()->getRequest('id'),
            'install' => 1
        )));

        if($module && $this->allow($module->module)) {
            $classname = $this->getFrontController()->formatModuleName('\\' . $module->module . '\install\module');
            $file = str_replace('\\',DIRECTORY_SEPARATOR,$this->getFrontController()->classToFilename($classname));
            $moduleTable->getAdapter()->beginTransaction();

            try {
                $require_array = array();
                foreach(array_keys($this->getApplication()->getModules()) AS $m) {
                    if(\Install\Modules::isInstalled($m)) {
                        $moduleConfig = \Install\Modules::getModulesConfig($m);
                        if($moduleConfig && ($config = $moduleConfig->get('require')) !== null) {
                            $require = $config->toArray();
                            if(array_key_exists(strtolower($module->module), $require)) {
                                $require_array[$moduleConfig->get('title')] = $moduleConfig->get('title');
                            }
                        }
                    }
                }
                if($require_array) {
                    throw new Exception(sprintf($this->_('The following modules "%s" are required this module!'),implode(',', $require_array)));
                }

                //new install help
                $otherinstall = \Install\Modules::getModulesUninstall($module->module);
                foreach($otherinstall AS $otis) {
                    $extobj = new $otis();
                    if(method_exists($extobj, 'extendUninstall' . ucfirst(strtolower($module->module))) && is_callable([$extobj, 'extendUninstall' . ucfirst(strtolower($module->module))]))
                        call_user_func([$extobj, 'extendUninstall' . ucfirst(strtolower($module->module))]);
                }
                $otherinstall = \Install\Modules::getModulesAfterUninstall($module->module);
                foreach($otherinstall AS $otis) {
                    $classname = $otis[0];
                    $extobj = new $classname();
                    if(method_exists($extobj, 'extendUninstall' . ucfirst(strtolower($module->module))) && is_callable([$extobj, 'extendUninstall' . ucfirst(strtolower($opts[1]))]))
                        call_user_func([$extobj, 'extendUninstall' . ucfirst(strtolower($opts[1]))]);
                }
                $otherinstall = \Install\Modules::getModulesDelete($module->module);
                foreach($otherinstall AS $otis) {
                    $extobj = new $otis();
                    if(method_exists($extobj, 'extendDelete' . ucfirst(strtolower($module->module))) && is_callable([$extobj, 'extendDelete' . ucfirst(strtolower($module->module))]))
                        call_user_func([$extobj, 'extendDelete' . ucfirst(strtolower($module->module))]);
                }
                $otherinstall = \Install\Modules::getModulesAfterDelete($module->module);
                foreach($otherinstall AS $otis) {
                    $classname = $otis[0];
                    $extobj = new $classname();
                    if(method_exists($extobj, 'extendDelete' . ucfirst(strtolower($module->module))) && is_callable([$extobj, 'extendDelete' . ucfirst(strtolower($opts[1]))]))
                        call_user_func([$extobj, 'extendDelete' . ucfirst(strtolower($opts[1]))]);
                }
                //end new install help

                if(file_exists(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'modules' . $file)) {
                    include_once APPLICATION_PATH . DIRECTORY_SEPARATOR . 'modules' . $file;
                    $obj = new $classname();
                    if($obj instanceof \Base\Install\Module) {
                        if(method_exists($obj,'uninstall')) {
                            $obj->uninstall();
                        }
                        if(method_exists($obj,'delete')) {
                            $obj->delete();
                        }
                        $obj->_deletePages();
                        $obj->_deleteReference();
                        $obj->removePermissions();

                        $obj->dropTables();
                    }
                }

                \Core\Utils\FileHelper::removeDirectory(BASE_PATH . '/cache/MetadataCache');
                @mkdir(BASE_PATH . '/cache/MetadataCache', 0777, true);
                $module->install = 0;
                $module->save();

                //delete translate
                $translateTable = new \Core\Db\Table('translate');
                $check = $this->getFrontController()->formatModuleName('\\\\' . $module->module . '\\\\');
                $translateTable->delete(array('namespace LIKE \'%' . $check . '%\' OR namespace LIKE \'%' . rtrim($check,'\\') . '\''));
                //end delete translate

                $moduleTable->getAdapter()->commit();
                \Core\Session\Base::set('msg-success', $this->_('Module successfully uninstalled!'));
            } catch (\Core\Exception $e) {
                \Core\Session\Base::set('msg-error', $e->getMessage());
                $moduleTable->getAdapter()->rollBack();
            }
        }

        $this->redirect( $this->url(array('module'=>'install','controller'=>'admin'),'admin_module') );
    }

    public function modulesChecker() {
        $action = \Core\Base\Action::getInstance();

        $cache = $this->getUrlCache();
        $key = md5('modules_check_' . $action->getRequest()->getDomain());
        if( ( $getedVersions = $cache->get($key) ) === false ) {
            $curl = new \Core\Http\Curl();
            $curl->setParams(array('d' => $action->getRequest()->getDomain(), 't'=>\Core\Registry::get('system_type'),'v'=>\Core\Registry::get('system_version')));
            $curl->useCurl(function_exists('curl_init'));
            $curl->setCookiepath(BASE_PATH . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'cookie.txt');
            $curl->setMaxredirect(5);
            $curl->execute(base64_decode(\Core\Base\Init::BASE_DATA) . 'clients/modules');
            if($curl->getError()) {
                $cache->set($key, json_encode(array()));
            } else {
                $result = $curl->getResult();
                $cache->set($key, $result);
            }
        }
        $modulesSet = array();
        if( ( $getedVersions = $cache->get($key) ) !== false ) {
            $get = @json_decode($getedVersions, true);
            if($get) {
                $modulesSet = $get;
            }
        }
        return $modulesSet;
    }

    /**
     * @param string $url
     * @return \Core\Cache\Frontend\String
     */
    private function getUrlCache() {
        $frontendOptionsCore = array(
            'lifetime' => 86400/4,
        );

        $frontendOptionsPage = array(
            'lifetime' => 86400/4
        );

        if(!file_exists(BASE_PATH . '/cache/modules/')) {
            mkdir(BASE_PATH . '/cache/modules/', 0777, true);
        }

        $backendOptions  = array('cache_dir' => BASE_PATH . '/cache/modules/');
        $cache = \Core\Cache\Base::factory('String', 'File', $frontendOptionsCore, $backendOptions);
        $cache->clean(\Core\Cache\Base::CLEANING_MODE_OLD);
        return $cache;
    }

}