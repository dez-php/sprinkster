<?php

namespace Core\Helper;

abstract class Init {
	public function __construct() {
		$this->init ();
	}
	abstract public function init();
}