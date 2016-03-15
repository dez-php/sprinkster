<?php

namespace Pin\Helper;

class PinRelated {

	protected $pin;
	
	public function __construct($pin) {
		$this->pin = $pin;
	}
	
	public function getSql() {
		return null;
	}
	
}