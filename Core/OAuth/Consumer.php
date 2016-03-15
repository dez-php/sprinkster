<?php

namespace Core\OAuth;

class Consumer {
	public $key;
	public $secret;
	public function __construct($key, $secret, $callback_url = NULL) {
		$this->key = $key;
		$this->secret = $secret;
		$this->callback_url = $callback_url;
	}
	public function __toString() {
		return "Core\OAuth\Consumer[key=$this->key,secret=$this->secret]";
	}
}

?>