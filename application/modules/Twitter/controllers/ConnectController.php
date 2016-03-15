<?php

namespace Twitter;

class ConnectController extends \Base\PermissionController {
	
	/**
	 * @var \Twitter\Helper\Me
	 */
	private $twitter;
	
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
		$userInfo = \User\User::getUserData();
		$this->close_box = false;
		if($userInfo->id) {
			
			$user = $this->twitter->get('account/verify_credentials');
			
			if(isset($user->id)) {

				$userFbDataTable = new \Twitter\OauthTwitter();
				$userFbDataDb = $userFbDataTable->fetchRow(array('twitter_id = ?' => $user->id));
				$userFbDataDb2 = $userFbDataTable->fetchRow(array('user_id = ?' => $userInfo->id));
				if($userFbDataDb) {
					if($userFbDataDb->user_id == $userInfo->id) {
						$enable_action = true;
					} else {
						$enable_action = $this->_('There is another profile that is associated with your Twitter account');
					}
				} else {
					$enable_action = true;
				}
				
				if($enable_action === true) {
					if($userFbDataDb2) { 
						if($userFbDataDb2->delete()) {
							$this->close_box = true;
						} else {
							$this->close_box = true;
						}
					} else {
						$new = $userFbDataTable->fetchNew();
						$new->user_id = $userInfo->id;
						$new->twitter_id = $user->id;
						$userTable = new \User\User();
						$XFormCmd = $userTable->getXFormCmd();
						$new->oauth_token = \Core\Session\Base::get($XFormCmd . '_tw_access_token[oauth_token]');
						$new->oauth_token_secret = \Core\Session\Base::get($XFormCmd . '_tw_access_token[oauth_token_secret]');
						$new->date_added = \Core\Date::getInstance(null,\Core\Date::SQL_FULL, true)->toString();
						$new->date_modified = $new->date_added;
						if(isset($new->username)) {
							$new->username = $user->screen_name;
						}
						if($new->save()) {
							$this->close_box = true;
						} else {
							$this->close_box = true;
						}
					}
				} else {
					\Core\Session\Base::set('connect_error', $enable_action);
					$this->close_box = true;
				}
				
			} else {
				$redirect = $this->twitter->getLoginLink($this->url(array(),'settings'), 'connect');
				$this->redirect($redirect);
			}
		} else {
			$this->close_box = true;
		}
		$this->render('index');
	}
	
}