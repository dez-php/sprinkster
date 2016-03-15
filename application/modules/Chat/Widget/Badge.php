<?php

namespace Chat\Widget;

class Badge extends \Base\Widget\PermissionWidget {

	protected $me = NULL;

	public function init()
	{
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
		$this->me = \User\User::getUserData();
	}

	public function result()
	{
		$user_id = isset($this->options['user_id']) ? (int) $this->options['user_id'] : 0;

		if(0 >= $user_id || !\Core\Http\Url::ping(\Base\Config::get('chat_server_url') . '/socket.io/socket.io.js'))
			return $this->render('offline');
		
		$this->render('badge', [ 'user_id' => $user_id ]);
	}


}