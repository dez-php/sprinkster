<?php

namespace Conversation\Widget;

use \Core\Interfaces\ICacheableWidget;

class Pinsend extends \Base\Widget\PermissionWidget implements ICacheableWidget {
	
	protected $pin;
	protected $template = 'index';
	
	public function init()
	{
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result()
	{
		$this->pin = (new \Pin\Pin())->get((int)$this->getRequest()->getParam('pin_id'));
		if(!$this->allow('Conversation') || !$this->pin || !$this->pin->id)
			return;

		$user = \User\User::getUserData();

		// Disable conversation widget for own pins
		if(!$user->id || ($user->id && $user->id === $this->pin->user_id))
			return;

		$this->render($this->template, [ 'pin' => $this->pin, 'me' => $user ]);
	}
	
}