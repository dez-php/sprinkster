<?php

namespace System;

class DefaultFollowController extends \Core\Base\Action {
	
	public function init() {
		if(!\User\User::getUserData()->is_admin) { $this->redirect($this->url([],'admin_login')); }
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		$this->render('index');
	}
	
	public function createAction() {
		$this->render('edit');
	}
	
	public function editAction() {
		$this->render('edit');
	}
	
	public function autocompleteAction() {
		$request = $this->getRequest();
		$userTable = new \User\User();
		$word = $request->getRequest('query');
		$users = $userTable->fetchAll($userTable->makeWhere(array(
			'where' => 'username LIKE ' . $userTable->getAdapter()->quote($word.'%') . ' OR firstname LIKE ' . $userTable->getAdapter()->quote($word.'%') . ' OR lastname LIKE ' . $userTable->getAdapter()->quote($word.'%'),
			'status' => 1	
		)), null, 100);
		$results = array();
		foreach($users AS $user) {
			$results[] = array(
				'id' => $user->id,
				'fullname' => $user->getUserFullname()		
			);
		}
		$this->responseJsonCallback($results);
	}
	
}