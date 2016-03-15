<?php

namespace Cart;

use Core\Base\Action;
use Core\Session\Base;

class DashboardController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function mysubscriptionsAction() {
		$userData = \User\User::getUserData();
		$this->render('mysubscriptions', ['userData' => $userData]);
	}
	
}