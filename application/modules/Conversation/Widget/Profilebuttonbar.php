<?php

namespace Conversation\Widget;

class Profilebuttonbar extends \Base\Widget\PermissionWidget {

	public $user;
	public $store;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result() {
		$data = array();

		if(!$this->allow('Conversation'))
			return;

		$self = \User\User::getUserData();
		
		if(!$self->id || !$this->user || !isset($this->user->id) || !($user_id = $this->user->id) || $user_id == $self->id)
			return;

		$short = isset($this->options['short']) ? !!$this->options['short'] : FALSE;

		$this->render('index', [ 'user' => $this->user, 'store' => $this->store, 'pinprofile' => $this->pinprofile, 'short' => $short ]);
	}
	
}