<?php

namespace Facebook\Widget;

class Grid extends \Base\Widget\PermissionWidget {

	protected $call;

	/**
	 * @var \Facebook\Helper\Me
	 */
	private $facebook;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
		$this->facebook = new \Facebook\Helper\Me();
		if(!\Base\Config::get('facebook_status')) {
			$this->redirect( $this->url(array(),'welcome_home') );
		}
	}
	
	public function setCall($call) {
		$this->call = $call;
		return $this;
	}
	
	public function result() {
		
		$method = $this->call;
		if(is_callable(array($this,$method))) {
			$this->$method();
		}
		
	}
	
	//////////////////////////
	private function forInvate() {
		$request = $this->getRequest();
		
		$self_data = \User\User::getUserData();
		
		if($self_data->id) {
			$friends = $this->facebook->getFriends();
			
			$userTable = new \User\User();
			$fboauthInviteTable = new \Facebook\OauthFacebookInvite();
			$fboauthTable = new \Facebook\OauthFacebook();
			//friends filter
			$filterArray = array();
			if($friends) {
				foreach($friends AS $friend) {
					$filterArray[$friend['id']] = $friend['name'];
				}
			}
			
			if($filterArray) {
				$invated = $fboauthInviteTable->getAdapter()->fetchPairs($fboauthInviteTable->select()->from($fboauthInviteTable,array('facebook_id','facebook_id'))->where($fboauthInviteTable->makeWhere(array('facebook_id' => array_keys($filterArray),'user_id' => $self_data->id))));
				if($invated) {
					foreach($filterArray AS $k=>$f) {
						if(in_array($k, $invated)) {
							unset($filterArray[$k]);
						}
					}
				}
				unset($invated);
				$registered = $fboauthTable->getAdapter()->fetchPairs($fboauthTable->select()->from($fboauthTable,array('facebook_id','facebook_id'))->where($fboauthTable->makeWhere(array('facebook_id' => array_keys($filterArray)))));
				if($registered) {
					foreach($filterArray AS $k=>$f) {
						if(in_array($k, $registered)) {
							unset($filterArray[$k]);
						}
					}
				}
				unset($registered);
			}

			$data = array();
			$data['users'] = $filterArray;
			
			$this->access_token = $this->facebook->getAccessToken();
			
			$data['query'] = 'options='.urlencode(serialize($this->options));
			
			$data['from_widget'] = $request->getRequest('widget');
			
			if( $request->isXmlHttpRequest() && $request->getRequest('callback') ) {
				$this->responseJsonCallback( $this->render('forInvate', $data, true) );
			} else {
				$this->render('forInvate', $data);
			}
		}
	}
	
	private function fromUser() {
		$request = $this->getRequest();
		
		$self_data = \User\User::getUserData();
		if($self_data->id) { 
			$friends = $this->facebook->getFriends();
			
			$userTable = new \User\User();
			$fboauthTable = new \Facebook\OauthFacebook();
			//friends filter
			$filterArray = array();
			if($friends) {
				foreach($friends AS $friend) {
					$filterArray[] = $friend['id'];
				}
			}
			
			if($filterArray) {
				$where = $userTable->makeWhere(array('id' => $fboauthTable->select()->from($fboauthTable,'user_id')->useIndex('facebook_id')->where($fboauthTable->makeWhere(array('facebook_id' => $filterArray)))));
				$limit = 500;
			} else {
				$where = 'user.id=0';
				$limit = 0;
			}
			
			$data = array();
			$data['users'] = $userTable->getAll($where,null,$limit);
			
			$data['query'] = 'options='.urlencode(serialize($this->options));
			
			$data['from_widget'] = $request->getRequest('widget');
			
			if( $request->isXmlHttpRequest() && $request->getRequest('callback') ) {
				$this->responseJsonCallback( $this->render('fromUser', $data, true) );
			} else {
				$this->render('fromUser', $data);
			}
		}
	}
	
}