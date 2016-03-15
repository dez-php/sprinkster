<?php

namespace Conversation\Widget;

class Send extends \Base\Widget\PermissionWidget {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result() {
		$data = array();
		
		if(!$this->allow('Conversation'))
			return;
		
		$self = \User\User::getUserData();
		
		if(!$self->id || !($user_id = $this->getRequest()->getRequest('user_id')))
			return;
		
		$user = (new \User\User)->fetchRow([ 'id = ?' => $user_id ]);
		
		if(!$user || $user->id == $self->id)
			return;

		$this->render('index', [ 'user' => $user ]);
	}
	
}