<?php

namespace User\Widget;

class Head  extends \Base\Widget\PermissionWidget {

	protected $user_id;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function setId($filter) {
		$this->user_id = $filter;
		return $this;
	}
	
	
	public function result() {
		$data = array();
		$userTable = new \User\User();
		$data['user'] = $userTable->get($this->user_id);
		
		if(!$data['user']) {
			$this->forward('error404');
		}
		
		if($data['user'] && $data['user']->country_iso_code_3) {
			$countryTable = new \Country\Country();
			$data['country'] = $countryTable->fetchRow(array('iso_code_3 = ?' => $data['user']->country_iso_code_3));
		} else {
			$data['country'] = null;
		}
		
		$this->render('head', $data);
	}
	
	
}