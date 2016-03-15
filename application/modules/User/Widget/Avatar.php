<?php

namespace User\Widget;

class Avatar extends \Base\Widget\PermissionWidget {

	protected $method = 'avatar';
	protected $redirect = null;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function setMethod($method) {
		$this->method = $method;
		return $this;
	}
	
	public function setRedirect($redirect) {
		$this->redirect = $redirect;
		return $this;
	}
	
	public function result() {
		$this->render('index');
	}
	
	
}