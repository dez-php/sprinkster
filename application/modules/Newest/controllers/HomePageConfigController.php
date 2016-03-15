<?php

namespace Newest;

use Local\Library\Live; 
class HomePageConfigController extends \Core\Base\Action {
	
	public function init() {
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		$this->render('index');
	}
	
	
}