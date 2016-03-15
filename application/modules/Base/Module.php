<?php

namespace Base;

class Module extends \Core\Base\Module
{
	
	public function getConfig()
	{
		return include __DIR__ . '/config/module.config.php';
	}
	
	public function registerEvent( \Core\Base\Event $e, $application ) {
		$e->register('onBootstrap', [$this , 'onBootstrap']);
        $e->register('onBeforeDispatch', [$this, 'onBeforeDispatch']);
	}

    public function onBeforeDispatch() {
//		\Base\Config::set('config_theme', 'zillow');
//        $this->getFrontController()->setTheme('zillow');
        if( ($theme = \Base\Config::get('config_theme')) !== null && $theme != 'default' ) {
            $front = $this->getFrontController();
            if(is_file($front->getThemeDirectory() . DS . $theme . DS . 'init.php')){
                include_once $front->getThemeDirectory() . DS . $theme . DS . 'init.php';
            }
        }
    }
	
	public function onBootstrap() {
		$this->abortListener();
		\Core\Http\Thread::listen();
			
		if(\Core\Http\Request::getInstance()->issetQuery('get_version')) {
			exit( 'Pinterest Clone Script v'.\Core\Registry::get('system_version') );
		}
			
		$request = $this->getRequest();
		if(\Base\Config::get('date_timezone')) {
			ini_set('date.timezone', \Base\Config::get('date_timezone'));
		}
		if( ($theme = \Base\Config::get('config_theme')) !== null ) {
			$this->getFrontController()->setTheme($theme);
		}
		if($request->isXmlHttpRequest())
			return;
		if($request->getServer('HTTP_HOST') && $request->getBaseUrl() != \Base\Config::get('base_url')) {
			\Base\Config::updateKey('base_url', $request->getBaseUrl());
		} else if(\Base\Config::get('base_url')) {
			$request->setBaseUrl(\Base\Config::get('base_url'));
		}
	
	}
	
	private function abortListener() {
		ignore_user_abort(false);
		register_shutdown_function(function() {
			if(connection_aborted()) {
				exit;
			}
		});
	}
	
}
