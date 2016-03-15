<?php

namespace User\Widget;

class Followingwishlists extends \Base\Widget\AbstractMenuPermissionWidget {
	
	public $user = null;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result() {
		$request = $this->getRequest();
		if($this->user && isset($this->user->id) && $this->user->id) {
			$widget = new \Wishlist\Widget\Grid();
			$widget->setFilter(['callback' => ['id' => '\User\UserFollow::userFollowingWishlists(' . $this->user->id . ')']]);
			$this->render('index', array('user_id' => $this->user->id, 'total' => $widget->countWishlists()));
		}
	}
	
	public function totalRows() {
		if(!$this->user || !isset($this->user->id) || !$this->user->id)
			return 0;
			
		$widget = new \Wishlist\Widget\Grid();
		$widget->setFilter(['callback' => ['id' => '\User\UserFollow::userFollowingWishlists(' . $this->user->id . ')']]);
		return $widget->countWishlists();
	}
	
}