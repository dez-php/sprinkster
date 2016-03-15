<?php

namespace Pin\Widget;

class Groupbuttons extends \Base\Widget\PermissionWidget {

	public $pin;
	
    public function init() {
        $this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
    }

    public function result() {
    	if(!$this->pin)
    		return;
        $this->render('index', ['pin' => $this->pin]);
    }

}