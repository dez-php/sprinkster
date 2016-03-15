<?php

namespace User\Widget;

class Following extends \Base\Widget\AbstractMenuPermissionWidget {
	
	public $user = null;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result() {
		$request = $this->getRequest();
		if($this->user && isset($this->user->id) && $this->user->id) {
			$widget = new \User\Widget\Grid();
			$widget->setFilter(['callback' => ['id' => '\User\UserFollow::userFollowingWithoutWishlists(' . $this->user->id . ')']]);
			$this->render('index', array('user_id' => $this->user->id, 'total' => $widget->countUsers()));
		}
	}
	
	public function totalRows() {
		if(!$this->user || !isset($this->user->id) || !$this->user->id)
			return 0;
			
		$widget = new \User\Widget\Grid();
		$widget->setFilter(['callback' => ['id' => '\User\UserFollow::userFollowingWithoutWishlists(' . $this->user->id . ')']]);
		return $widget->countUsers();
	}
	
}