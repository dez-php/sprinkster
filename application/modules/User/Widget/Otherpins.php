<?php

namespace User\Widget;

class Otherpins extends \Base\Widget\PermissionWidget {

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
		
		$user_id = $request->getQuery('user_id');
		$pin_id = (int)$request->getQuery('pin_id');
		
		$userTable = new \User\User();
		$user_info = $userTable->fetchRow(array('id = ?' => $user_id));
		
		if($user_info && $user_info['pins'] > ($page-1)*$limit) {
			$data['user'] = $user_info;
			$pinsTable = new \Pin\Pin();
			$data['pins'] = $pinsTable->getAll($pinsTable->makeWhere(array('user_id' => $user_id, 'status' => 1, 'id' => '!='.$pin_id)), 'id DESC', $limit,$offset);
			$this->responseJsonCallback($this->render('page', $data,true));
		} else {
			$this->responseJsonCallback(false);
		}
	}
	
	
	public function result() {
		$request = $this->getRequest();
		
		if($request->getQuery('user_id') && $request->getQuery('page')) {
			return $this->getPage();
		}
		
		$data = array(
			'pins' => false,
			'init' => false,
			'pin_id' => 0,
			'user_id' => 0
		);
		
		$this->limit2 = min(9,(int)$this->limit?(int)$this->limit:9);
		
		if($this->pin && $this->pin->user_id) {
			if(1 >= $this->pin->pins)
				return;
			$data['user_id'] = (int)$this->pin->user_id;
			$data['pin_id'] = $this->pin->id;
		} else if( 0 < ( $user_request_id = (int)$request->getQuery('user_id') ) ) {
			$data['user_id'] = $user_request_id;
			$data['pin_id'] = (int)$request->getQuery('pin_id');
		}
		
// 		if($request->getRequest('getuserinfo') == 'true') {
 			$result = $this->userInfo();
 			if($result) {
 				$data = array_merge($data, $result);
 			}
// 		} else {
// 			$data['init'] = true;
// 		}

//		$result = $this->userInfo();
//		if($result) {
//			$data = array_merge($data, $result);
//		}
		
		$this->render('otherpins', $data);
		
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
		
		if($user_id && $pin_id) {
			$userTable = new \User\User();
			$user_info = $userTable->get($user_id);
			if($user_info && $user_info->pins > 1) {
				$data = [];
				// filter pins by category_id
				if((int)$this->limit) {
					$limit = $this->limit = min((int)$this->limit,$user_info->pins);
				} else {
					$limit = $this->limit = min(9,$user_info->pins);
				}
				
				if(0 >= $limit) 
					return;
				
				$data['user'] = $user_info;
				
				$pinsTable = new \Pin\Pin();
				$filter = $pinsTable->makeWhere([
					'user_id' => $user_id,
					'id' => '!='.$this->pin->id
				]);
				$data['total'] = $pinsTable->getCount($filter);
				$data['pins'] = $pinsTable->getAll($filter,'id DESC', $limit);
				
				return $data;
			}
		}
		return false;
	}
	
	
}