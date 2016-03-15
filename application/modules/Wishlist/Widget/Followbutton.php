<?php

namespace Wishlist\Widget;

class Followbutton extends \Base\Widget\AbstractMenuPermissionWidget {
	
	public $user_id = null;
	public $wishlist_id = null;
	public $following = null;
	public $title = null;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result() {
		$self = \User\User::getUserData();
		if($self->id == $this->user_id)
			return;
		$this->render('index', [
				'user_id' => $this->user_id, 
				'wishlist_id' => $this->wishlist_id, 
				'following' => $this->following, 
				'me' => $self,
				'title' => $this->title
		]);
	}
	
}