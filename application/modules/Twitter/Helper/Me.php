<?php

namespace Twitter\Helper;

include_once dirname(__DIR__) . '/Library/OAuth2.php';

class Me extends \OAuth2 {
	
	public $key, $secret;

	public function __construct($oauth_token = NULL, $oauth_token_secret = NULL, $key = null, $secret = null) {
		$this->key = $key ? $key : \Base\Config::get('twitter_key');
		$this->secret = $secret ? $secret : \Base\Config::get('twitter_pass');
		parent::__construct(
			$this->key,
			$this->secret,
			$oauth_token,
			$oauth_token_secret
		);
		if(\Core\Http\Request::getInstance()->getQuery('code')) {
			//$this->setLongAccessToken();
		}
	}
	
	/**
	 * @param string|null $next
	 * @param string $controller
	 * @return Ambigous <string, multitype:string >
	 */
	public function getLoginLink($next = null, $controller='login', $display = null) {
		return \Core\Base\Action::getInstance()->url(array('controller' => 'forward', 'query' => '?redirect_tw=' . $controller . ($next?'&next=' . $next:'')), 'twitter', false, false);
	}
	
}