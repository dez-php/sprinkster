<?php

namespace Facebook\Helper;

class Me extends \Facebook\Library\Facebook {

	public $scope = 'email,publish_actions,user_likes'; //user_friends

	const FBSS_COOKIE_NAME = 'fbssesst';

	// We can set this to a high number because the main session
	// expiration will trump this.
	const FBSS_COOKIE_EXPIRE = 31556926; // 1 year

	/**
	 * @param array $config
	 */
	public function __construct(array $config = []) {
		if(!isset($config['app_id']) || !$config['app_id'])
			$config['app_id'] = \Base\Config::get('facebook_key');
		if(!isset($config['app_secret']) || !$config['app_secret'])
			$config['app_secret'] = \Base\Config::get('facebook_pass');
		if(!isset($config['version']) || !$config['version'])
			$config['default_graph_version'] = 'v2.4';
		try {
			parent::__construct($config);
		} catch(\Exception $e) {
			\Base\Config::set('facebook_status', 0);
		}
	}

	private $_user;
    private $at;
    public function setAccessToken($at) {
        $this->at = $at;
    }

	public function getUser() {
		if($this->_user)
			return $this->_user;
		try {
			$helper = $this->getRedirectLoginHelper();
			if(!$helper)
				throw new \Exception('Missing FB helper');
			$accessToken = $helper->getAccessToken();
			if(!$accessToken || $this->at)
				throw new \Exception('Missing FB accessToken');
			$at = $accessToken ? $accessToken->getValue() : $this->at;
			$user = $this->get('/me?fields=id,name,email', $at)->getDecodedBody();
			if(!$user)
				return null;
			$user['accessToken'] = $at;
			$this->_user = $user;
			$this->setLongAccessToken();
			return $this->_user;
		} catch(\Exception $e) {
			try {
				$userParams = $this->get('/me?fields=id,name,email', $this->getLongAccessToken());
				$user = $userParams->getDecodedBody();
				if(!$user)
					return null;
				$user['accessToken'] = $userParams->getRequest()->getAccessToken();
				return $this->_user = $user;
			} catch(\Exception $e) { }
		}
	}

	public function getPermisions($permision) {
		try {
			$user = $this->getUser();
			$helper = $this->get("/me/permissions", $user['accessToken']);
			if(!$helper)
				return false;
			$perm = $helper->getDecodedBody();
			if($perm && isset($perm['data'])) {
				foreach($perm['data'] AS $perm) {
					if($perm['permission'] == $permision)
						return $perm['status'] == 'granted';
				}
			}
			return false;
		} catch(\Exception $e) {}
	}
	
	/**
	 * @param string|null $next
	 * @param string $controller
	 * @return Ambigous <string, multitype:string >
	 */
	public function getLoginLink($next = null, $controller='login', $display = null) {

		$redirect_uri = \Core\Base\Action::getInstance()->url(array('controller' => $controller, 'query' => ($next?'?next=' . $next:'')), 'facebook', false, false);

		return parent::getRedirectLoginHelper()->getLoginUrl($redirect_uri, explode(',',$this->scope));
	}

	public function setLongAccessToken() {
		if(!$this->_user)
			return;
		setcookie(self::FBSS_COOKIE_NAME . '_long', $this->_user['accessToken'], time() + self::FBSS_COOKIE_EXPIRE, '/', '.'.\Core\Http\Request::getInstance()->getDomain());
		\Core\Session\Base::set(self::FBSS_COOKIE_NAME . '_long', $this->_user['accessToken']);
	}
	
	public function getLongAccessToken() {
		if(isset($_COOKIE[self::FBSS_COOKIE_NAME . '_long']))
			return $_COOKIE[self::FBSS_COOKIE_NAME . '_long'];
		return \Core\Session\Base::get(self::FBSS_COOKIE_NAME . '_long');
	}
	
	public function getFriends() {
		$user = $this->getUser();
		if($user) {
			$cache = $this->getInitCache();
			$cache_id = 'friends_' . $user['id'];
			if( ( $friends = $cache->get($cache_id) ) === false ) {
				$userFbData = $this->get('me/friends', $user['accessToken'])->getDecodedBody();

				$friends = array();
				if(isset($userFbData['data'])) {
					$friends = $userFbData['data'];
					$at = $user['accessToken'];
					if(isset($userFbData['paging']['next'])) {
						while (true) {
							$data = json_decode(file_get_contents($userFbData['paging']['next'] . '&access_token=' . $at), true);
							if(!$data || isset($data['data']) || !$data['data']) {
								break;
							}
							$friends = array_merge($friends, $data['data']);
						}
					}
					//$cache->set($cache_id, $friends);
				}
			}
			
			return $friends;
		}
		return false;
	}
	
	/**
	 * @return \Core\Cache\Frontend\String
	 */
	private function getInitCache() {
		$frontendOptionsCore = array(
				'lifetime' => 300,
		);
		 
		$frontendOptionsPage = array(
				'lifetime' => 300
		);
		
		if(!file_exists(BASE_PATH . '/cache/fb/')) {
			mkdir(BASE_PATH . '/cache/fb/', 0777, true);
		}
		
		$backendOptions  = array('cache_dir' => BASE_PATH . '/cache/fb/');
		$cache = \Core\Cache\Base::factory('String', 'File', $frontendOptionsCore, $backendOptions);
		$cache->clean(\Core\Cache\Base::CLEANING_MODE_OLD);
		return $cache;
	}
	
	
}