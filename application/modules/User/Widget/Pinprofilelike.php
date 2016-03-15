<?php

namespace User\Widget;

class Pinprofilelike  extends \Core\Base\Widget {

	protected $pin;
	protected $limit;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function setLimit($limit) {
		$this->limit = $limit;
		return $this;
	}
	
	public function setPin($pin) {
		$this->pin = $pin;
		return $this;
	}
	
	public function getPage() {
		$request = $this->getRequest();
		
		$limit = (int)$request->getQuery('limit');
		if($limit < 1) { 
			$limit = 1; 
		}
		
		$page = (int)$request->getQuery('page');
		if($page < 1) { 
			$page = 1; 			
		} 
		
		$offset = ($page*$limit) - $limit;
		$pin_id = $request->getQuery('pin_id');
		
		$pinTable = new \Pin\Pin();
		$pin_info = $pinTable->fetchRow(array('id = ?' => $pin_id));
		if($pin_info) {
			$data['pin'] = $pin_info;
			$userTable = new \User\User();
			$pinLikeTable = new \Pin\PinLike();
			$data['users'] = $userTable->fetchAll($userTable->makeWhere(array(
					'id' => array($pinLikeTable->select()->from($pinLikeTable,'user_id')->where('pin_id = ?', $pin_id))
			)), 'id DESC', $limit,$offset);
			
			$this->responseJsonCallback($this->render('page', $data,true));
		} else {
			$this->responseJsonCallback(false);
		}
	}
	
	
	public function result() {
		$request = $this->getRequest();
		
		if($request->getQuery('pin_id') && $request->getQuery('page')) {
			return $this->getPage();
		}
		
		if(!$this->pin)
			return;
		
		$data = array(
			'users' => false,
			'init' => false,
			'pin_id' => 0,
			'user_id' => 0
		);
		
		if($this->pin && $this->pin->user_id) {
			if(0 >= $this->pin->likes)
				return;
			$data['user_id'] = (int)$this->pin->user_id;
			$data['pin_id'] = $this->pin->id;
		} else if( 0 < ( $user_request_id = (int)$request->getQuery('user_id') ) ) {
			$data['user_id'] = $user_request_id;
			$data['pin_id'] = (int)$request->getQuery('pin_id');
		}
		
//  		if($request->getRequest('getuserinfo') == 'true') {
 			$result = $this->userInfo();
 			if($result) {
 				$data = array_merge($data, $result);
 			}
//  		} else {
//  			$data['init'] = true;
//  		}

//		$result = $this->userInfo();
//		if($result) {
//			$data = array_merge($data, $result);
//		}

		$this->render('pinprofilelike', $data);
	}
	
	private function userInfo() {
		$request = $this->getRequest();
		
		$user_id = 0;
		if($this->pin && $this->pin->user_id) {
			$user_id = (int)$this->pin->user_id;
		} else if( 0 < ( $user_request_id = (int)$request->getQuery('user_id') ) ) {
			$user_id = $user_request_id;
		}
		
		$pin_id = 0;
		if( 0 < ( $pin_request_id = (int)$request->getQuery('pin_id') ) ) {
			$pin_id = $pin_request_id;
		}
		
		if(!$this->pin) {
			$pinTable = new \Pin\Pin();
			$pin_info = $pinTable->get($pin_id);
		} else {
			$pin_info = $this->pin;
		}
	
		if($user_id && $pin_info) {
			$userTable = new \User\User();
			$user_info = $userTable->get($user_id);
			if($user_info && $pin_info->likes > 0) {
				$data = [];
				// filter pins by category_id
				if((int)$this->limit) {
					$limit = $this->limit = min((int)$this->limit,$pin_info->likes);
				} else {
					$limit = $this->limit = min(9,$pin_info->likes);
				}
				
				if($limit < 1) { return false; }
				
				$data['user'] = $user_info;
				
				$pinsTable = new \Pin\Pin();
				$data['pin_id'] = $pin_id;
				
				
				$data['limit'] = $limit;
				
				$userTable = new \User\User();
				$pinLikeTable = new \Pin\PinLike();
				
				$data['total'] = $pin_info->likes;
				
				$data['users'] = $userTable->fetchAll($userTable->makeWhere(array(
						'id' => array($pinLikeTable->select()->from($pinLikeTable,'user_id')->where('pin_id = ?', $pin_id))
				)), 'id DESC', $limit);
				
				return $data;
			}
		}
		return false;
	}
	
	
}