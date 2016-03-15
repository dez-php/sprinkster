<?php

namespace Facebook\Widget;

class Oauth extends \Base\Widget\PermissionWidget {
	
	protected $class;
	protected $method = 'login';
	protected $redirect;
	/**
	 * @var \Facebook\Helper\Me
	 */
	protected $facebook;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
		$this->facebook = new \Facebook\Helper\Me();
	}
	
	public function setClass($class) {
		$this->class = $class;
		return $this;
	}
	
	public function setMethod($method) {
		$this->method = $method;
		return $this;
	}
	
	public function setRedirect($redirect) {
		$this->redirect = $redirect;
		return $this;
	}

	public function result() {
		$module = $this->method;
		
		if(\Base\Config::get('facebook_status') && is_callable(array($this,$module))) {
			$this->$module();
		}
	}
	
	private function connect() {
		//$this->loginUrl = $this->facebook->getLoginLink($this->redirect);
		$userInfo = \User\User::getUserData();
		if($userInfo->id) {
			$userTable = new \Facebook\OauthFacebook();
			$user_info = $userTable->fetchRow(array('user_id = ?' => $userInfo->id));
			
			$data['user'] = array(
				'isConnected' => false,
				'isTimeline' => false		
			); 
			if($user_info) {
				$data['user'] = array(
						'isConnected' => true,
						'isTimeline' => \Base\Config::get('facebook_post_timeline')&&$user_info->timeline
				);
			}
			
			$this->render('connect', $data);
		}
	}
	
	private function login() {
		$this->loginUrl = $this->facebook->getLoginLink($this->redirect);
		
		$this->render('login');
	}
	
	private function register() {
		$this->loginUrl = $this->facebook->getLoginLink($this->redirect, 'register');
		
		$this->render('register');
	}
	
}