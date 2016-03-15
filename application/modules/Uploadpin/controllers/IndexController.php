<?php

namespace Uploadpin;

class IndexController extends \Base\PermissionController {
	
	public function init() {
		$request = $this->getRequest();
		if($request->isXmlHttpRequest()) {
			$this->noLayout(true);
		}
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {	
		
		$this->action = $this->url(array('controller' => 'upload'),'uploadpin_c');
		
		$this->render('index');
		
	}
	
	
	
}

?>