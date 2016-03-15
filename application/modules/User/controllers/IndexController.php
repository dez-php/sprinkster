<?php

namespace User;

class IndexController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		$user = (new User)->fetchRow([ 'id = ?' => (int) $this->getRequest()->getRequest('user_id') ]);
		
		if(!$user)
			$this->forward('error404');

		$this->render('index', ['user' => $user]);
	}
	
}