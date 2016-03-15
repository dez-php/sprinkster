<?php

namespace User\Widget;

use \Core\Interfaces\IPersistentWidget;
use \Core\Interfaces\ICacheableWidget;

class Profiledropdown extends \Core\Base\Widget implements IPersistentWidget, ICacheableWidget {

	protected $pin = null;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function setPin($pin) {
		$this->pin = $pin;
		return $this;
	}
	
	public function result() {
		$user = isset($this->options['user']) ? $this->options['user'] : NULL;

		if(!$user)
			return;

		$this->render('index', [ 'user' => $user ]);
	}
	
	
}