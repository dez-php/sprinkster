<?php
namespace Paymentgateway;

use \Core\Base\Action;

class IndexController extends Action {

	private $me = NULL;

	public function init()
	{
		$this->me = \User\User::getUserData();
		
		if (!$this->me->id)
			return $this->redirect($this->url(array('controller' => 'login'), 'user_c'));

		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function indexAction() {
		$this->render('index', [ 'me' => $this->me ]);
	}

}