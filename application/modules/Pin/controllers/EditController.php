<?php

namespace Pin;

class EditController extends \Base\PermissionController {
	
	public function indexAction()
	{
		$request = $this->getRequest();
		$isXmlHttpRequest = $request->isXmlHttpRequest();
		$front = $this->getFrontController();
		
		$data = [];
		
		$pin_id = $request->getRequest('pin_id');
		
		$userInfo = \User\User::getUserData();
		
		if(!$userInfo->id)
			return $isXmlHttpRequest ? $this->responseJsonCallback([ 'status' => FALSE, 'errors' => [ $this->_('You have to be logged in in order to update your content.') ] ]) : $this->forward('error404');

		$pinTable = new \Pin\Pin();
		
		$pin = $pinTable->get($pin_id); 
		if(!$pin) {
			$this->forward('error404');
		} else if($userInfo->is_admin ? false : $pin->user_id != $userInfo->id ) {
			$this->forward('error404');
		}

		$type = $pin->getType();
		if(!$type)
			$this->forward('error404');
		$this->forward('index', ['pin' => $pin], 'edit', $type);
		
	}

}