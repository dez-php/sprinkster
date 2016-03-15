<?php

namespace Cron;

class LikeController extends \Core\Base\Action {

	public function init() {
		$this->noLayout(true);
		set_time_limit(0);
		ignore_user_abort(true);
	}
	
	public function indexAction() {
		$pinTable = new \Pin\Pin();
		$pins = $pinTable->fetchAll(null,'RAND()', mt_rand(5,15));
		
		$userTable = new \User\User();
		$users = $userTable->fetchAll(null,'RAND()',mt_rand(5,15));
		
		$pinLikeTable = new \Pin\PinLike();
		
		foreach($users AS $user) {
			foreach($pins AS $pin) {
				$row = $pinLikeTable->fetchRow($pinLikeTable->makeWhere(array('user_id'=>$user->id,'pin_id'=>$pin->id)));
				if(!$row) {
					$row = $pinLikeTable->fetchNew();
					$row->user_id = $user->id;
					$row->pin_id = $pin->id;
					$row->date_added = \Core\Date::getInstance(null,\Core\Date::SQL_FULL,true)->toString();
					$row->save();
				}
			}
		}
	}
}