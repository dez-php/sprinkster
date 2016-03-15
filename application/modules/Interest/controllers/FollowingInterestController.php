<?php

namespace Interest;

class FollowingInterestController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {	
		$request = $this->getRequest();
		//render view
		$userTable = new \User\User();
		$user = $userTable->get($request->getRequest('user_id'));
		if(!$user) {
			$this->forward('error404');
		}
		$this->render('index', ['user' => $user]);
		
	}
	
	
	
}