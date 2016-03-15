<?php

namespace Pin;

class SearchController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {	
		
		$this->render('index');
		
	}
	
	
	
}

?>