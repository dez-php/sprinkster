<?php

namespace Facebook;

class LoginController extends \Base\PermissionController {

	/**
	 * @var null|array
	 */
	public $errors;
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

	public function indexAction() {
		$request = $this->getRequest();

		$user = $this->facebook->getUser();

		if($user) {
			$OauthFacebookTable = new \Facebook\OauthFacebook();
			$userOauth = $OauthFacebookTable->fetchRow(array('facebook_id = ?' => $user['id']));
			if($userOauth) {
				$userTable = new \User\User();
				if( $userTable->loginById($userOauth->user_id) ) {
					$userOauth->access_token = $user['accessToken'];
					$userOauth->save();
					$this->facebook->setLongAccessToken();
					// Added strpos check to pass McAfee PCI compliance test
					$redirect = $request->getRequest('next');
					if($redirect && strpos($redirect, $request->getBaseUrl()) !== false) {
						$this->redirect( str_replace('&amp;', '&', $redirect) );
					} else {
						$this->redirect( $this->url(array(),'welcome_home') );
					}
				}
		
				// set error's
				if(!$this->errors && ($error = $userTable->getErrors()) !== null ) {
					if($error == \User\User::USER_NOT_FOUND) {
						$this->forward('index', array(), 'register');
					} else if($error == \User\User::USER_NOT_ACTIVE) {
						$this->errors['warning'] = $this->_('User not activated');
					}
				}
		
			} else {
				$this->forward('index', array(), 'register');
			}
			
			$this->render('index', null, ['module'=>'user','controller'=>'login'] );
		} else {
			// not allow
			$this->forward('index', array(), 'not-allowed');
		}
	}
	
}