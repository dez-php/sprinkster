<?php

namespace Page\Widget;

class Pinitbutton extends \Base\Widget\PermissionWidget {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function result() {
		$request = $this->getRequest();
		
		$this->render('index');
	}
	
	
	
}