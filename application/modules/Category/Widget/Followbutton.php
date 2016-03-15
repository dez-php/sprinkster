<?php

namespace Category\Widget;

class Followbutton extends \Base\Widget\AbstractMenuPermissionWidget {
	
	public $category_id = null;
	public $following = null;
	public $title = null;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result() {
		$self = \User\User::getUserData();
		$this->render('index', [
				'category_id' => $this->category_id, 
				'following' => $this->following, 
				'me' => $self,
				'title' => $this->title
		]);
	}
	
}