<?php

namespace Twitter\Widget;

class Oauth extends \Base\Widget\PermissionWidget {
	
	protected $class;
	protected $method = 'login';
	protected $redirect;
	/**
	 * @var \Twitter\Helper\Me
	 */
	protected $twitter;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
		$this->twitter = new \Twitter\Helper\Me();
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
		
		if(\Base\Config::get('twitter_status') && is_callable(array($this,$module))) {
			$this->$module();
		}
	}
	
	private function connect() {

		$userInfo = \User\User::getUserData();
		if($userInfo->id) {
			$userTable = new \Twitter\OauthTwitter();
			$user_info = $userTable->fetchRow(array('user_id = ?' => $userInfo->id));
			
			$data['user'] = array(
				'isConnected' => false,
				'isTimeline' => false		
			); 
			if($user_info) {
				$data['user'] = array(
						'isConnected' => true,
						'isTimeline' => \Base\Config::get('twitter_post_timeline') && $user_info->timeline
				);
			}
			
			$this->render('connect', $data);
		}
	}
	
	private function login() {
		$this->loginUrl = $this->twitter->getLoginLink($this->redirect, 'login');
		
		$this->render('login');
	}
	
	private function register() {
		$this->loginUrl = $this->twitter->getLoginLink($this->redirect, 'register');
		
		$this->render('register');
	}
	
}