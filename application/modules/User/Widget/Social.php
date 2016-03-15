<?php

namespace User\Widget;

class Social  extends \Core\Base\Widget {

	protected $method = 'login';
	protected $redirect = null;
	protected $simple = false;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function setMethod($method) {
		$this->method = $method;
		return $this;
	}
	
	public function setSimple($simple) {
		$this->simple = $simple;
		return $this;
	}
	
	public function setRedirect($redirect) {
		$this->redirect = $redirect;
		return $this;
	}
	
	public function result() {
		if($this->simple) {
			$this->render('simple');
		} else {
			$this->render('index');
		}
	}
	
	
}