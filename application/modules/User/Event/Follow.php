<?php

namespace User\Event;

class Follow {
	
	public function __construct() {
		set_time_limit(0);
		ignore_user_abort(true);
	}
	
	public function followDefault($user_id) {
		$powerTable = new \User\UserFollowDefault();
		
		$users = $powerTable->fetchAll();
		foreach($users AS $user) {
			$follow = new \User\Helper\Follow($user->user_id,$user_id);
			if(!$follow->is_follow) {
				$follow->system = 1;
				$follow->followUser();
			}
		}
	}
	
}