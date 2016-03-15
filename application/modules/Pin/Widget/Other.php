<?php

namespace Pin\Widget;

class Other extends \Base\Widget\PermissionWidget {

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
    		$userTable = new \User\User();
    		$user_info = $userTable->get($this->pin->user_id);
    		if($user_info) {
        		$this->render('index', ['user' => $user_info]);
    		}
    	}
    }

}
