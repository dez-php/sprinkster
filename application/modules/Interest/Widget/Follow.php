<?php

namespace Interest\Widget;

class Follow extends \Base\Widget\AbstractMenuPermissionWidget {
	
	public $user = null;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function result() {
		$request = $this->getRequest();
		if(($user_id = $this->getRequest()->getParam('user_id')) !== null) {
			$interestTable = new \Interest\InterestFollow();
			
			$this->render('index', array('user_id' => $user_id, 'total' => $interestTable->countByUserId($user_id)));
		}
	}
	
	public function totalRows() { 
		if(!$this->user || !isset($this->user->id) || !$this->user->id)
			return 0;
		
		$interestTable = new \Interest\InterestFollow();
		return $interestTable->countByUserId($this->user->id);
	}
	
}