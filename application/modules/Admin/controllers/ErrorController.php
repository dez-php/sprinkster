<?php

namespace Admin;

class ErrorController extends \Core\Base\Action {
	
	public function init() {
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		$this->render('nopermision');
	}
	
	public function nopermisionAction() {
		$this->render('nopermision');
	}
	
	public function error404Action() {
		$this->noLayout(true);
		
		$layout = $this->getLayout();
		$layout->setController ( $this )->content = $this->render('error404', null, null, true);
		$content = $layout->response ();
		
		exit($this->getResponse ()->appendBody ( $content ));
		
	}
	
}