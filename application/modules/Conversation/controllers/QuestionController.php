<?php

namespace Conversation;

class QuestionController extends \Base\PermissionController {
	
	public function init() {
		$this->noLayout(true);
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function indexAction() {
    	
    	$request = $this->getRequest();

    	//login popup
    	$userInfo = \User\User::getUserData();
    	if (!$userInfo->id)
    		$this->forward(
    				'popup', [
    					'url' => $this->url($request->getParams()),
    					'action' => 'popup'
    				],
    				'login',
    				'user');
    	//end login popup
		
		$pin_id = $request->getRequest('pin_id');
		
		$pinTable = new \Pin\Pin();
		$pin = $pinTable->get($pin_id);
		
		if(!$pin) {
			$this->forward('error404');
		}
		
		$data['pin'] = $pin;
		
		$this->render('index', $data);
	}
	
}