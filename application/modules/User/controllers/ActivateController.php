<?php

namespace User;

class ActivateController extends \Core\Base\Action {

	public function indexAction() {
		set_time_limit(0);
		$request = $this->getRequest();
		
		$userTable = new \User\User();
		$user = $userTable->fetchRow($userTable->makeWhere(array('activate_url' => $this->getRequest()->getRequest('key') ? $this->getRequest()->getRequest('key') : '-1')));
		if(!$user) {
			$this->forward('error404');
		}
		
		$user->status = 1;
		$user->activate_url = null;
		$user->save();
		$userTable->loginById($user->id);

		$tmpFol = new \User\UserFollowTemp();
		$tmpFol2 = new \User\UserFollow();
		$all = $tmpFol->fetchAll($tmpFol->makeWhere(array('user_id' => $user->id)));
		foreach($all AS $r) {
			$new = $tmpFol2->fetchNew();
			foreach ($r AS $k=>$v) {
				if($k != 'id') {
					$new->{$k} = $v;
				}
			}
			$new->save();
			$r->delete();
		}
		$this->redirect( $request->getBaseUrl() );
	}
}