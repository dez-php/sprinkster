<?php

namespace Admin;

class Module extends \Core\Base\Module
{
	
	public function registerEvent( \Core\Base\Event $e, $application ) {
		$e->register('onBootstrap', [$this , 'onBootstrap']);
		$e->register('onBeforeDispatch', [$this , 'onBeforeDispatch']);
	}
	
	public function onBootstrap() {
		$request = $this->getRequest();
		if($request->getSegment(1) == 'admin') {
			$request->setParams('___layout___', 'admin');
		}
	}
	
	public function onBeforeDispatch() {
		$request = $this->getRequest();
		if($request->getParam('___layout___') == 'admin') {
			$template_map = \Core\Base\Action::getInstance()->getInvokeArg('template_map');
			$template_map['layout'] = 'Admin/views/admin.phtml';
			\Core\Base\Action::getInstance()->setInvokeArg('template_map', $template_map);
			$user_data = \User\User::getUserData();
			if(!$user_data->id || !$user_data->is_admin) {
				$action = \Core\Base\Action::getInstance();
				if($action->getApplication()->getRouter()->getCurrentRouteName() != 'admin_login') {
					\Core\Session\Base::set('redirect', \Core\Http\Request::getInstance()->getFullUrl());
					$action->redirect($action->url(array(),'admin_login'));
				}
			}
		}
	}
	
    public function versionChecker() {
    	$action = \Core\Base\Action::getInstance();
    	
    	$cache = $this->getUrlCache();
    	$key = md5('versions_check_' . $action->getRequest()->getDomain());
    	if( ( $getedVersions = $cache->get($key) ) === false ) {
    		$curl = new \Core\Http\Curl();
    		$curl->setParams(array('d' => $action->getRequest()->getDomain(), 't'=>\Core\Registry::get('system_type'),'v'=>\Core\Registry::get('system_version')));
    		$curl->useCurl(function_exists('curl_init'));
    		$curl->setCookiepath(BASE_PATH . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'cookie.txt');
    		$curl->setMaxredirect(5);
    		$curl->execute(base64_decode(\Core\Base\Init::BASE_DATA) . 'clients/p3/versions');
    		if($curl->getError()) {
    			$cache->set($key, serialize(array()));
    		} else {
    			$result = $curl->getResult();
    			$cache->set($key, $result);
    		}
    	} 
    	$sys = false;$sysv = \Core\Registry::get('system_version'); $modulesSet = array(); $themes = array();
    	if( ( $getedVersions = $cache->get($key) ) !== false ) {
    		$get = @unserialize($getedVersions);
    		if($get) {
    			$modules = self::getAllModulesConfig();
    			$versionCheck = array();
    			foreach($modules AS $mod => $config) {
    				if($config->version) {
    					$versionCheck[strtolower($mod)] = array('v' => $config->version, 't' => $config->title);
    				} else {
    					$versionCheck[strtolower($mod)] = array('v' => \Core\Registry::get('system_version'), 't' => $config->title);
    				}
    			}
                $themes_has = $this->getThemes();
    			foreach($get AS $g) {
    				if(isset($g['module'])) {
                        if ($g['module'] == 'system' && $g['version'] != 'core') {
                            $sys = version_compare($g['version'], \Core\Registry::get('system_version'), '>');
                            $sysv = $g['version'];
                        } else if ($g['version'] != 'core' && $g['module'] && isset($versionCheck[$g['module']])) {
                            if (version_compare($g['version'], $versionCheck[$g['module']]['v'], '>')) {
                                $modulesSet[$g['module']] = array(
                                    'currently' => $versionCheck[$g['module']]['v'],
                                    'title' => $versionCheck[$g['module']]['t'],
                                    'new' => $g['version']
                                );
                            }
                        }
                    } else if(isset($g['theme']) && isset($themes_has[$g['theme']])) {
                        if (version_compare($g['version'], $themes_has[$g['theme']]['v'], '>')) {
                            $themes[$g['theme']] = array(
                                'currently' => $themes_has[$g['theme']]['v'],
                                'title' => $themes_has[$g['theme']]['t'],
                                'new' => $g['version']
                            );
                        }
                    }
    			}
    		}
    	}
    	return array('system' => $sys,'version' => $sysv, 'modules' => $modulesSet, 'themes' => $themes);
    }

    private function getThemes() {
        $theme_dir = $this->getFrontController()->getThemeDirectory();
        $return = array();
        $type = \Core\Registry::get('system_type');
        if(file_exists($theme_dir) && is_dir($theme_dir)) {
            $themes = glob($theme_dir . '/*');
            if($themes) {
                foreach($themes AS $theme) {
                    if(is_file($theme . DIRECTORY_SEPARATOR . 'config.php')) {
                        $name = basename($theme);
                        $config = include_once $theme . DIRECTORY_SEPARATOR . 'config.php';
                        if(isset($config['title'])) {
                            $return[$type . '_' . $name] = array(
                                't' => $config['title'],
                                'v' => isset($config['version']) ? $config['version'] : '1.0.0'
                            );
                        }
                    }
                }
            }
        }
        return $return;
    }

	/**
	 * @param string $url
	 * @return \Core\Cache\Frontend\String
	 */
	private function getUrlCache() {
		$frontendOptionsCore = array(
			'lifetime' => 86400,
		);
		 
		$frontendOptionsPage = array(
			'lifetime' => 86400
		);
		
		if(!file_exists(BASE_PATH . '/cache/version/')) {
			mkdir(BASE_PATH . '/cache/version/', 0777, true);
		}
		
		$backendOptions  = array('cache_dir' => BASE_PATH . '/cache/version/');
		$cache = \Core\Cache\Base::factory('String', 'File', $frontendOptionsCore, $backendOptions);
		$cache->clean(\Core\Cache\Base::CLEANING_MODE_OLD);
		return $cache;
	}
    
}
