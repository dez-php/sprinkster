<?php

namespace Cart;

class AdminStatusController extends \Core\Base\Action {
	
	public function init() {
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {		
		$this->render('index');
	}
	
	public function editAction() {		
		$this->render('edit');
	}
	
	public function createAction() {		
		$this->render('edit');
	}
	
}