<?php

namespace Wishlist;

class ShareController extends \Base\PermissionController {
	
	private $errors = array();
	
	public function init() {
		$this->noLayout(true);
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
		} else {
			$method = $request->getPost('method');
			$wishlist_id = $request->getPost('bid');
			$sharedTable = new \Wishlist\WishlistShare();
			$shared = $sharedTable->fetchRow($sharedTable->makeWhere(array('share_id'=>$userInfo->id,'wishlist_id'=>$wishlist_id)));
			if($shared) {
				if($method == 'accept') {
					try {
						$shared->accept = 1;
						$shared->save();
						$data['ok'] = true;
						//add activity
						\Activity\Activity::set($shared->user_id, 'INVITEWISHLISTALLOW',null,$shared->wishlist_id);
					} catch (\Core\Exception $e) {
						$data['error'] = $e->getMessage();
					}
				} else if($method == 'decline') {
					try {
						//add activity
						\Activity\Activity::remove($shared->share_id, 'INVITEWISHLIST',null,$shared->wishlist_id);
						$shared->delete();
						$data['ok'] = true;
					} catch (\Core\Exception $e) {
						$data['error'] = $e->getMessage();
					}
				} else {
					$data['error'] = $this->_('Unknown action');
				}
			} else {
				$data['error'] = $this->_('No record found');
			}
		}
		$this->responseJsonCallback($data);
	}
	
}