<?php

namespace Pin\Widget\Action;

use \Core\Interfaces\ICacheableWidget;

class Like extends \Core\Base\Widget implements ICacheableWidget {
	
	protected $pin;
	protected $template;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result() {
		if(!$this->pin || !$this->pin->id)
			return;

		$user = \User\User::getUserData();

		// Disable like widget for own pins
		if($user->id && $user->id === $this->pin->user_id)
			return;

		$this->render($this->template, [ 'pin'=>$this->pin, 'me' => $user ]);
	}
	
}