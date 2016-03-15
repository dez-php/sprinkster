<?php

namespace Conversation;

class QuestionUserController extends \Base\PermissionController {
	
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
		
		$user_id = $request->getRequest('user_id');
		
		$userTable = new \User\User();
		$user = $userTable->get($user_id);
		
		if(!$user) {
			$this->forward('error404');
		}
		
		$data['user'] = $user;
		
		$this->render('index', $data);
	}
	
}