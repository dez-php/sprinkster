<?php

namespace User\Widget;

class Followbutton extends \Base\Widget\AbstractMenuPermissionWidget {

    public $user_id = null;
    public $following = null;
    public $username = null;
    public $template = 'index';

	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

    public function result() {
        $self = \User\User::getUserData();
        if($self->id == $this->user_id)
            return;
        $this->render($this->template, [
            'user_id' => $this->user_id,
            'following' => $this->following,
            'me' => $self,
            'username' => $this->username
        ]);
    }

}