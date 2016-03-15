<?php

namespace Chat\Widget;

class Chat extends \Base\Widget\PermissionWidget {
	
	protected $me = NULL;

	public function init()
	{
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
		$this->me = \User\User::getUserData();
	}

	public function result()
	{
		if(!$this->me->id || !\Core\Http\Url::ping(\Base\Config::get('chat_server_url') . '/socket.io/socket.io.js'))
			return;

		if(self::getModule('Chat')->isMobile())
			return;
		
		$this->render('chat', [ 'me' => $this->me ]);
	}

}