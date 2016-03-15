<?php

namespace Cart\Widget;

class Mysubscriptions extends \Base\Widget\AbstractMenuPermissionWidget {
	
	public $user = null;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result() {
		$user = \User\User::getUserData();
		$this->render('index', ['user' => $user]);
	}
	
}