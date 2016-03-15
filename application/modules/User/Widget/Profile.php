<?php

namespace User\Widget;

class Profile extends \Base\Widget\UserTabWidget {

	protected $tab_id = 'profile';
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function tab()
	{
		$this->render('tab');
	}

	public function content()
	{
		$data = [];

		if(!$this->user->id)
			return;

		$data['user'] = $this->user;
		$data['menu'] = \Base\PermissionMenu::getMenu('UserMenu');

		$this->render('tabcontent', $data);
	}

}