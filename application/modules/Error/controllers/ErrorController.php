<?php

namespace Error;

class ErrorController extends \Core\Base\Action {
	
	public function init() {
		if($this->getRequest()->getParam('___layout___') == 'admin')
			$this->forward('error404', [], 'error', 'admin');
		if($this->getRequest()->isXmlHttpRequest())
			$this->noLayout(true);
		try {
			$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
		} catch (\Exception $e) {}
	}
	
	public function indexAction($data = null) {
		//render script
		$this->render('index', $data);
	}
	
	public function notfoundAction() {
		//render script
		$this->render('404');
		
	}
	
	public function phpAction($data = null) {
		//render script
		$this->render('php', $data);
		
	}
	
}

?>