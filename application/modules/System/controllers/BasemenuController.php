<?php

namespace System;

class BasemenuController extends \Core\Base\Action {
	
	public $menu_groups = [
		'FeatureMenu' => 'Base menu',
		'AdminMenu' => 'Admin Menu',
		'AdminMenuSystem' => 'Admin Menu System',
		'UserMenu' => 'User Menu',
		'TopMenu' => 'Top Menu',
		'AddMenu' => 'Add Menu',
		'AfterHeader' => 'After Header'
	];
	
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