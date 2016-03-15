<?php

namespace Invite;

class Invite extends \Base\Model\Reference {
	
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->setRowClass('\Invite\InviteRow');
	}
	
}