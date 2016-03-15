<?php

namespace User\Widget;

class Login  extends \Base\Widget\PermissionWidget {
	
	public $userTableFieldsDisable = ['password','password_new','password_key','email','activate_url','is_admin'];
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result() {}
	
	public function checkLogedAction() {
		$self = \User\User::getUserData();
		$this->responseJsonCallback($self->id ? true : false);
	}
	
	public function getDataAction() {
		$self = \User\User::getUserData();
		$json = ['loged' => $self->id ? true : false];
		if($self->id) {
			foreach($self->toArray() AS $k=>$v) {
				if(!in_array($k, $this->userTableFieldsDisable))
					$json[$k] = $v;
			}
			$json['fullname'] = $self->getUserFullName();
			$json['avatars'] = \User\Helper\Avatar::getImages($self);
			$json['covers'] = \User\Helper\Cover::getImages($self);
		}
		$this->responseJsonCallback($json);
	}
	
}