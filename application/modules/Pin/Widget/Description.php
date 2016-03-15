<?php

namespace Pin\Widget;

class Description extends \Base\Widget\PermissionWidget {

	protected $pin;
	
    public function init() {
        $this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
    }
	
	/**
	 * @param \Core\Db\Table\Row\AbstractRow $pin
	 * @return \Pin\Widget\Comment
	 */
	public function setPin($pin) {
		$this->pin = $pin;
		return $this;
	}
	
	/**
	 * @return \Core\Db\Table\Row\AbstractRow
	 */
	public function getPin() {
		return $this->pin;
	}

    public function result() {
    	if($this->pin && isset($this->pin->id)) {
        	$this->render('index', ['pin' => $this->pin]);
    	}
    }

}
