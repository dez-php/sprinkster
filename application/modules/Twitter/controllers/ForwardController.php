<?php

namespace Twitter;

use Core\Session\Base;

class ForwardController extends \Base\PermissionController {
	
	/**
	 * @var \Twitter\Helper\Me
	 */
	private $twitter;
	
	public function init() {
		if(!\Base\Config::get('twitter_status')) {
			$this->redirect( $this->url(array(),'welcome_home') );
		}
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
		if(\Core\Session\Base::get('twitter_oauth')) {
			$this->twitter = new \Twitter\Helper\Me(\Core\Session\Base::get('twitter_oauth[oauth_token]'),\Core\Session\Base::get('twitter_oauth[oauth_token_secret]'));
			$access_token = $this->twitter->getAccessToken($this->getRequest()->getQuery('oauth_verifier'));
			$next = '';
			if(\Core\Session\Base::get('twitter_next')) {
				$next = '&next=' . urlencode(\Core\Session\Base::get('twitter_next'));
			}
			$twitter_next = 'login';
			if(\Core\Session\Base::get('twitter_redirect_tw')) {
				$twitter_next = \Core\Session\Base::get('twitter_redirect_tw');
			}
			\Core\Session\Base::clear('twitter_oauth');
			\Core\Session\Base::clear('twitter_next');
			\Core\Session\Base::clear('twitter_redirect_tw');
			$userTable = new \User\User();
			$XFormCmd = $userTable->getXFormCmd();
			\Core\Session\Base::set($XFormCmd . '_tw_access_token', $access_token);
			if($this->twitter->http_code == 200) {
				$this->redirect( $this->url(array('controller' => $twitter_next, 'query' => ($next?'?next='.$next:'')), 'twitter', false, false) );
			} else {
				$this->forward('index', array(), 'not-allowed');
			}
		} else {
			$this->twitter = new \Twitter\Helper\Me();
		}
	}

	public function indexAction() {
		
		$request = $this->getRequest();
		
		$controller = $request->getRequest('redirect_tw','login');
		$next = $request->getQuery('next');
		
		\Core\Session\Base::set('twitter_redirect_tw', $controller);
		\Core\Session\Base::set('twitter_next', $next);
		
		$request_token = $this->twitter->getRequestToken( $this->url(array('controller' => 'forward'), 'twitter') );
		\Core\Session\Base::set('twitter_oauth', $request_token);
		if($this->twitter->http_code == 200) {
			$request_token_url = $this->twitter->getAuthorizeURL($request_token['oauth_token']);
			$this->redirect( $request_token_url );
		} else {
		
			$this->forward('index', array(), 'not-allowed');
		
		}
		
	}

	
	
}