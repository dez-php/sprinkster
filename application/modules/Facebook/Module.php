<?php

namespace Facebook;

class Module extends \Core\Base\Module
{

	public function registerEvent( \Core\Base\Event $e, $application ) {
		if(!\Base\Config::get('facebook_status') || $this->getRequest()->isXmlHttpRequest())
			return;
		$e->register('onBeforeDispatch.pin', [$this , 'onBeforeDispatch']);
	}
	
	public function onBeforeDispatch() {
		$request = $this->getRequest();
		if($request->isXmlHttpRequest() || $request->getController() != 'index')
			return;
			
		if( ( $pin_id = $request->getParam('pin_id') ) !== null ) {
			$pinTable = new \Pin\Pin();
			$data['pin'] = $pinTable->get($pin_id);
			if($data['pin']) {
				$action = \Core\Base\Action::getInstance();
				$action->getLayout()->placeholder('meta_tags', $action->render('meta_tags', $data, ['module' => 'facebook', 'controller'=>'connect'], true));
			}
		}
	}
	
}
