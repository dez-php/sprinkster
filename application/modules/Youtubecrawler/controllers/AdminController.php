<?php

namespace Youtubecrawler;

class AdminController extends \Core\Base\Action {
	
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
	
	public function linksAction() {
		$this->render('links');
	}

	public function keyAction() {
		$this->render('key');
	}
	
	public function autocompleteAction() {
		$this->noLayout(true);
		$return = array();
		$search = $this->getRequest()->getRequest('query');
		if(!$search) {
			$search = $this->getRequest()->getRequest('value');
		}
		
		if( mb_strlen($search,'utf-8') >=2) {
			$userTable = new \User\User();
			$search = $userTable->getAdapter()->quote($search . '%');
			$users = $userTable->fetchAll(
					$userTable->makeWhere(array(
							'where' => '(username LIKE '.$search.' OR firstname LIKE '.$search.' OR lastname LIKE '.$search.')'
					)),
					null,
					300
			);
		
			foreach($users AS $user) {
				$return[] = array(
						'id' => $user->id,
						'name' => $user->getUserFullname(),
						'avatar' => \User\Helper\Avatar::getImages($user),
						'firstname' => $user->firstname,
						'lastname' => $user->lastname,
						'username' => $user->username
				);
			}
		}
		$this->responseJsonCallback($return);
	}
	
}