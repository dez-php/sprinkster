<?php

namespace User\Widget;

class Followingcategories extends \Base\Widget\AbstractMenuPermissionWidget {
	
	public $user = null;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result() {
		$request = $this->getRequest();
		if($this->user && isset($this->user->id) && $this->user->id) {
			$widget = new \Category\Widget\Grid();
			$widget->setFilter(['callback' => ['id' => '\User\UserFollow::userFollowingCategory(' . $this->user->id . ')']]);
			$this->render('index', array('user_id' => $this->user->id, 'total' => $widget->countCategories()));
		}
	}
	
	public function totalRows() {
		if(!$this->user || !isset($this->user->id) || !$this->user->id)
			return 0;
			
		$widget = new \Category\Widget\Grid();
		$widget->setFilter(['callback' => ['id' => '\User\UserFollow::userFollowingCategory(' . $this->user->id . ')']]);
		return $widget->countCategories();
	}
	
}