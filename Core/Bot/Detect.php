<?php

namespace Core\Bot;

class Detect {
	
	private $list;
	private $bot;
	
	public function __construct() {
		$this->list = (new \Core\Bot\Detect\BotList())->getList();
	}
	
	public function isBot() {
		$user_agent = \Core\Http\Request::getInstance()->getServer('HTTP_USER_AGENT');
		if($user_agent) {
			foreach($this->list AS $robot) {
				if ($robot && strpos(strtolower($user_agent), strtolower($robot)) !== false) {
					$this->bot = $robot;
					return true;
				}
			}
		}
		return false;
	}
	
	public function getBot() {
		if($this->isBot())
			return $this->bot;
		return false;
	}
	
}