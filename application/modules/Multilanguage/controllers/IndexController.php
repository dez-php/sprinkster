<?php

namespace Multilanguage;

class IndexController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		//render script
		$request = $this->getRequest();
		$template = 'index';
		if($request->isXmlHttpRequest()) {
			$this->noLayout(true);
			$template = 'ajax';
		}
		
		// get languages for menu
		$data['languages'] = self::getModule('Language')->getLanguages();
		
		$this->render($this->checkSystemView($template), $data);
		
	}
	
}