<?php

namespace User\Widget;

class Pinprofile  extends \Base\Widget\PermissionWidget {

	protected $pin;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function setPin($pin) {
		$this->pin = $pin;
		return $this;
	}
	
	
	public function result() {
		$request = $this->getRequest();
		$data = array(
			'user' => false,
			'init' => false
		);
		
// 		$data['user_id'] = 0;
// 		if($this->pin && $this->pin->user_id) {
// 			$data['user_id'] = (int)$this->pin->user_id;
// 		} else if( 0 < ( $user_request_id = (int)$request->getQuery('user_id') ) ) {
// 			$data['user_id'] = $user_request_id;
// 		}
// 		if($request->getRequest('getuserinfo') == 'true') {
// 			if($data['user_id']) {
// 				$data['user'] = (new \User\User())->get($data['user_id']);;
// 			}
// 		} else {
// 			$data['init'] = true;
// 		}
		if($this->pin) {
			$data['user'] = (new \User\User())->get($this->pin->user_id);
		}
		
		$this->render('pinprofile', $data);
	}
	
	
}