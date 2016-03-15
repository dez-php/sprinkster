<?php

namespace Maintenance;

class Module extends \Core\Base\Module
{
	public function registerEvent( \Core\Base\Event $e, $application ) {
		if(!\Base\Config::get('site_maintenance'))
			return;
		$self = $this;
		$e->register('onBeforeDispatch', [$this, 'onBeforeDispatch']);
	}

	public function onBeforeDispatch() {
		$request = $this->getRequest();
		if($request->getParam('___layout___') == 'admin' || $request->getModule() == 'maintenance')
			return;
		\Core\Base\Action::getInstance()->forward('index',null,'index','maintenance');
	}
	
}