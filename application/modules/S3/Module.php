<?php

namespace S3;

class Module extends \Core\Base\Module
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
    
    public function isAccessible() {
    	return \Install\Modules::isInstalled(__NAMESPACE__);
    }
    
    public function getAutoloaderConfig()
    {
    	return array(
			'namespaces' => array(
    			'Aws' => __DIR__ . DIRECTORY_SEPARATOR . 'Library'. DIRECTORY_SEPARATOR . 'Aws\\' ,
			),
    	);
    }
    
}