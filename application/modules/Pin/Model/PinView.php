<?php

namespace Pin;

class PinView extends \Base\Model\Reference {
	
	public static function updateCounter($pin_id) {
		$request = \Core\Http\Request::getInstance();
		$viewTable = new \Pin\PinView();
		$user_data = \User\User::getUserData();
		$now = \Core\Date::getInstance(null, \Core\Date::SQL_FULL, true)->toString();
		/* delete oldest */
		$viewTable->delete(array('date_added <= ?' => \Core\Date::getInstance(null, \Core\Date::SQL_FULL, true)->setInterval('-3 months')->toString()));
		/* end delete */
		$filter = array(
			'pin_id' => $pin_id,
			'user_ip' => $request->getClientIp(),
			'where' => 'DATE(date_added) = DATE(\''.$now.'\')'
		);
			
		if($user_data->id) {
			$filter['user_id'] = $user_data->id;
		}
		$row = $viewTable->fetchRow($viewTable->makeWhere($filter));
		if( !is_null($row) ) {
			$row->views = $row->views+1;
			$row->save();
		} else {
			$row = $viewTable->fetchNew();
			$row->pin_id = $pin_id;
			$row->user_id = $user_data->id ? $user_data->id : null;
			$row->user_ip = $request->getClientIp();
			$row->date_added = $now;
			$row->views = 1;
			$row->save();
		}
	}
	
}