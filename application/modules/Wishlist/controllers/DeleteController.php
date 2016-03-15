<?php

namespace Wishlist;

class DeleteController extends \Base\PermissionController {
	
	private $errors = array();
	
	public function init() {
		set_time_limit(0);
		$request = $this->getRequest();
		if($request->isXmlHttpRequest()) {
			$this->noLayout(true);
		}
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		$request = $this->getRequest();
		$data = array(
				'location' => false
		);
		$userInfo = \User\User::getUserData();
		if(!$userInfo->id) {
			$data['location'] = $this->url(array('controller' => 'login'),'user_c');
		}
		
		$wishlistTable = new \Wishlist\Wishlist();
		$this->x_form_cmd = $wishlistTable->getXFormCmd();
		
		$wishlist = $wishlistTable->fetchRow(array('id = ?' => $request->getRequest('wishlist_id')));
		if(!$wishlist) {
			$this->forward('error404');
		} else if($userInfo->is_admin ? false : $wishlist->user_id != $userInfo->id ) {
			$this->forward('error404');
		}
		
		$userTable = new \User\User();
		$data['isXmlHttpRequest'] = $request->isXmlHttpRequest();
		
		$user = $userTable->fetchRow(array('id = ?' => $wishlist->user_id));
		
		if($request->isPost()) {
			//$wishlist->status = 3;
			$wishlistTable->getAdapter()->beginTransaction();
			try {
				$demo_user_id = \Base\Config::get('demo_user_id');
				if($demo_user_id && $demo_user_id == \User\User::getUserData()->id) {
					 $this->errors['delete'] = $this->_('You don\'t have permissions for this action!');
				} else {
					$deleted = $wishlistTable->extendDelete($wishlist->id);
					if($deleted) {
						$wishlistTable->getAdapter()->commit();
						$url = $this->url(array('user_id' => $user->id,'query'=>$user->username),'user');
						if($data['isXmlHttpRequest']) {
							$this->responseJsonCallback(array('location' => $url));
							exit;
						} else {
							$this->redirect($url);
						}
					} else {
						$wishlistTable->getAdapter()->rollBack();
						$this->errors['deleteRecord'] = $this->_('There was a problem with the record. Please try again!');
					}
				}
			} catch (\Core\Exception $e) {
				$wishlistTable->getAdapter()->rollBack();
				$this->errors['Exception'] = $e->getMessage();
			}
		}
		if($data['isXmlHttpRequest'] && $this->errors) {
			$this->responseJsonCallback(array('errors' => $this->errors, 'location' => $data['location']));
			exit;
		}
		$this->forward('error404');
		
		$data['wishlist'] = $wishlist;
		
		//render script
		$this->render('index', $data);
	}
	
	
	
}

?>