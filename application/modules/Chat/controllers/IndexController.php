<?php

namespace Chat;

class IndexController extends \Base\PermissionController {

	public function indexAction()
	{
		$user = \User\User::getUserData();

		if(!$user || !$user->id)
			return $this->redirect($this->url([ 'controller' => 'login' ], 'user_c'));

		$session = Session::register();
		$this->render('index', [ 'session' => $session ]);
	}

}