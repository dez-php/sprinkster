<?php

namespace Maintenance;

class IndexController extends \Core\Base\Action {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		$this->render('index');
	}
	
}