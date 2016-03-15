<?php

namespace Core;

class Event extends \Core\Component {
	public $sender;
	public $handled=false;
	public $params;
	public function __construct($sender=null,$params=null)
	{
		$this->sender=$sender;
		$this->params=$params;
	}
}