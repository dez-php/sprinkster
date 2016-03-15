<?php

namespace Interest;

class Module extends \Core\Base\Module
{
	
	public function registerEvent( \Core\Base\Event $e, $application ) {
		$request = $this->getRequest();
		if($request->getParam('___layout___') == 'admin' || $request->isXmlHttpRequest())
			return;
		$e->register('onBeforeDispatch.category.interest', [$this , 'onBeforeDispatch']);
	}

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
    
    public function isAccessible() {
    	return \Install\Modules::isInstalled(__NAMESPACE__);
    }
    
    public function onBeforeDispatch() {
    	$dir = $this->getComponent('Alias')->get(__NAMESPACE__) . '/asset/';
    	$document = $this->getComponent('document');
    	$asset = $this->getComponent('AssetManager');
    	$asset->publish($dir);
    	$document->addScriptFile($asset->getPublishedUrl($dir) . '/js/interest.main.js');
    	$document->addCssFile($asset->getPublishedUrl($dir) . '/css/interest.main.css');
    }
}
