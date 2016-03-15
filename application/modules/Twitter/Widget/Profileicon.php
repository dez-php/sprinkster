<?php

namespace Twitter\Widget;

class Profileicon extends \Base\Widget\PermissionWidget {
	
	public function init()
	{
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result()
	{
		if(!\Base\Config::get('twitter_status') || NULL === ($user_id = $this->getRequest()->getRequest('user_id')))
			return;

		$user = (new \Twitter\OauthTwitter())->fetchRow([ 'user_id = ?' => $user_id ]);
		
		if(!$user)
			return;

		$this->render('index', [ 'user' => $user ]);
	}
	
	
	
}