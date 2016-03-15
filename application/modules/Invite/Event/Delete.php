<?php

namespace Invite\Event;

class Delete {
	
	public function __construct() {
		set_time_limit(0);
		ignore_user_abort(true);
	}
	
	public function byEmail($user_id) {
		$userTable = new \User\User();
		$user = $userTable->fetchRow(array('id = ?' => $user_id));
		if($user) {
			$inviteTable = new \Invite\Invite();
			$inviteTable->delete(array('email = ?' => $user->email));
		}
	}

}