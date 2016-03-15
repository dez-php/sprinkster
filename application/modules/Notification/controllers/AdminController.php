<?php

namespace Notification;

class AdminController extends \Core\Base\Action {
	
	public function init() {
		if(!\User\User::getUserData()->is_admin) { $this->redirect($this->url([],'admin_login')); }
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {		
		$this->render('index');
	}
	
	public function editAction() {		
		$this->render('edit');
	}
	
}