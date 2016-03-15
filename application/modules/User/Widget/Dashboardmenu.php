<?php
namespace User\Widget;

class Dashboardmenu extends \Base\Widget\AbstractMenuPermissionWidget {
	
	public $user = null;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result() {
			$this->render('index', array());
	}
	
	public function totalRows() {
//		if(!$this->user || !isset($this->user->id) || !$this->user->id)
//			return 0;
//
//		$widget = new \User\Widget\Grid();
//		$widget->setFilter(['callback' => ['id' => '\User\UserFollow::userFollowingWithoutWishlists(' . $this->user->id . ')']]);
//		return $widget->countUsers();
	}
	
}