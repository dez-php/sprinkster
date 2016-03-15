<?php

namespace User\Widget\Pinaction;

use \Core\Interfaces\ICacheableWidget;

class Profile extends \Core\Base\Widget implements ICacheableWidget {

	protected $pin = null;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function setPin($pin) {
		$this->pin = $pin;
		return $this;
	}
	
	public function result() { 
		$this->render('index');
	}
	
	
}