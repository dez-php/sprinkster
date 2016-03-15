<?php

namespace Pin;

class CreateController extends \Base\PermissionController {
	
	public function init() {
		$this->noLayout(true);
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function indexAction() {
		
		$this->render('index');
	}
	
}