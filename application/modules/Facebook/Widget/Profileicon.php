<?php

namespace Facebook\Widget;

class Profileicon extends \Base\Widget\PermissionWidget {
	
	public function init()
	{
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
		$this->facebook = new \Facebook\Helper\Me();
	}

	public function result()
	{
		if(!\Base\Config::get('facebook_status') || NULL === ($user_id = $this->getRequest()->getRequest('user_id')))
			return;

		$user = (new \Facebook\OauthFacebook())->fetchRow([ 'user_id = ?' => $user_id ]);

		if(!$user)
			return;

		$this->render('index', [ 'user' => $user ]);
	}

}