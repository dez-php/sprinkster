<?php

namespace User;

class FunController extends \Core\Base\Action {
	
	public function init()
	{
		$this->forward('index', [], 'index');
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction()
	{
		$user = (new User)->fetchRow([ 'id = ?' => (int) $this->getRequest()->getRequest('user_id') ]);
		
		if(!$user)
			$this->forward('error404');

		$this->render('index', ['user' => $user]);
	}
		
}