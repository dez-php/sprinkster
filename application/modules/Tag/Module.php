<?php

namespace Tag;

class Module extends \Core\Base\Module
{

    public function registerEvent( \Core\Base\Event $e, $application ) {
    	if(!$this->getRequest()->isXmlHttpRequest())
    		return;
    	$self = $this;
    	$e->register('onBeforeDispatch', function() use($self) {
    		$request = $self->getRequest();
    		if($request->getParam('___layout___') == 'admin')
    			return;

    		(new \Tag\TagSearchProvider)->register();
    	});
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
    
    public function isAccessible() {
    	return \Install\Modules::isInstalled(__NAMESPACE__);
    }
}
