<?php

namespace User;

class CollectionController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		$request = $this->getRequest();

		$user = new \User\User();
		$user = $user->fetchRow($user->makeWhere([ 'id' => $request->getRequest('user_id') ]));
		
		if(!$user)
			$this->forward('error404');

		$this->render('index', [ 'user' => $user ]);
	}
	
}