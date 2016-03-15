<?php

namespace Twitter;

class LoginController extends \Base\PermissionController {
	
	/**
	 * @var \Twitter\Helper\Me
	 */
	private $twitter;

    private $errors = [];
	
	public function init() {
		if(!\Base\Config::get('twitter_status')) {
			$this->redirect( $this->url(array(),'welcome_home') );
		}
		$userTable = new \User\User();
		$XFormCmd = $userTable->getXFormCmd();
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
		if(\Core\Session\Base::get($XFormCmd . '_tw_access_token')) {
			$this->twitter = new \Twitter\Helper\Me(\Core\Session\Base::get($XFormCmd . '_tw_access_token[oauth_token]'), \Core\Session\Base::get($XFormCmd . '_tw_access_token[oauth_token_secret]'));
		} else {
			$this->twitter = new \Twitter\Helper\Me(\Core\Session\Base::get('twitter_oauth[oauth_token]'), \Core\Session\Base::get('twitter_oauth[oauth_token_secret]'));
		}
		
		$request = $this->getRequest();
		if($request->getQuery('oauth_verifier')) {
			$next = '';
			if($request->issetQuery('next')) {
				$next = '&next=' . urlencode(html_entity_decode($request->getQuery('next')));
			}
			$access_token = $this->twitter->getAccessToken($request->getQuery('oauth_verifier'));
			\Core\Session\Base::set($XFormCmd . '_tw_access_token', $access_token);
			$this->redirect( $this->url(array('controller' => 'login', 'query' => ($next?'?next=' . $next:'')), 'twitter', false, false) );
		}
		
	}

	public function indexAction() {
		
		$request = $this->getRequest();
		
		$user = $this->twitter->get('account/verify_credentials');

		if(isset($user->errors)) {
			$this->render('error', array('errors' => $user->errors));
		} elseif(isset($user->id)) {
			$OauthTwitterTable = new \Twitter\OauthTwitter();
			$userOauth = $OauthTwitterTable->fetchRow(array('twitter_id = ?' => $user->id));
			if($userOauth) {
				$userTable = new \User\User();
				if( $userTable->loginById($userOauth->user_id) ) {
					$XFormCmd = $userTable->getXFormCmd();
					$userOauth->oauth_token = \Core\Session\Base::get($XFormCmd . '_tw_access_token[oauth_token]');
					$userOauth->oauth_token_secret = \Core\Session\Base::get($XFormCmd . '_tw_access_token[oauth_token_secret]');
					$userOauth->save();
					\Core\Session\Base::clear($XFormCmd . '_tw_access_token');
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
			$this->forward('index', array(), 'not-allowed');
		}
		
		
	}
	
}