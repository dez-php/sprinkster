<?php

namespace Pin\Helper;

abstract class Format {
	
	protected $pin;
	protected $withKey;
	
	public function __construct($pin, $withKey = true) {
		$this->pin = $pin;
		$this->withKey = $withKey;
	}
	
	abstract public function result();
	
	protected function formatParameters($parts) {
		if($parts && is_array($parts)) {
			$temp = array();
			foreach($parts AS $part) {
				$d = explode('=>', $part);
				$temp[array_shift($d)] = implode('=>',$d);
			}
			return $temp;
		}
		return null;
	}
	
}