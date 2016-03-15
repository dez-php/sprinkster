<?php

namespace Twitter;

class TimelineController extends \Core\Base\Action {
	
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
				
				if($userFbDataDb) {
					if($userFbDataDb->timeline) {
						$userFbDataDb->timeline = 0;
					} else {
						$userFbDataDb->timeline = 1;
					}
					$userFbDataDb->save();
				}
				$this->close_box = true;
				
			} else {
				$redirect = $this->twitter->getLoginLink($this->url(array(),'settings'), 'timeline');
				$this->redirect($redirect);
			}
		} else {
			$this->close_box = true;
		}
		$this->render('index');
	}
	
}