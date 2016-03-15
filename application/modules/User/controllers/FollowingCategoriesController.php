<?php

namespace User;

class FollowingCategoriesController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {	
		
		$request = $this->getRequest();
		$userTable = new \User\User();
		$user = $userTable->fetchRow($userTable->makeWhere(array('id' => $request->getRequest('user_id'))));
		if(!$user) {
			$this->forward('error404');
		}

		$this->render('index', ['user' => $user]);
		
	}
	
	
	
}