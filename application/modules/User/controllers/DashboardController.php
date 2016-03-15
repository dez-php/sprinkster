<?php

namespace User;

use Core\Base\Action;
use Core\Session\Base;

class DashboardController extends \Base\PermissionController {
	
	public function init() {
		if(!\User\User::getUserData()->id)
			$this->redirect($this->url([ 'controller' => 'login', 'query' => '?next=' . urlencode($this->url([ 'controller' => 'dashboard' ], 'user_c')) ], 'user_c', FALSE, FALSE));

		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
//		$request = $this->getRequest();
//
//		$user = new \User\User();
//		$user = $user->fetchRow($user->makeWhere([ 'id' => $request->getRequest('user_id') ]));

//		if(!$user)
//			$this->forward('error404');
		$userData = \User\User::getUserData();
		$this->render('index', ['userData' => $userData]);
	}
	
	public function closetAction()
	{
		$request = $this->getRequest();

		$user = new \User\User();
		$user = $user->fetchRow($user->makeWhere([ 'id' => $request->getRequest('user_id') ]));
		
		if(!$user)
			$this->forward('error404');
		Action::
		$this->render('closet', [ 'user' => $user ]);
	}

	public function mypurchasesAction()
	{
		$userData = \User\User::getUserData();
		$this->render('purchases', ['userData' => $userData]);
	}

	public function mysalesAction()
	{
		$userData = \User\User::getUserData();
		$this->render('sales', ['userData' => $userData]);
	}
	
}