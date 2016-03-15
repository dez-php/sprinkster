<?php

namespace Wishlist;

class ShareRemoveController extends \Base\PermissionController {
	
	private $errors = array();
	
	public function init() {
		$this->noLayout(true);
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function indexAction() {
		$wishlist_id = $this->getRequest()->getParam('wishlist_id');
		
		if($this->getRequest()->isPost()) {
			if(!$wishlist_id) {
				$return['error'] = $this->_('Missing parameters!');
			} else {
				$wishlistTable = new \Wishlist\Wishlist();
				$data['wishlist'] = $wishlistTable->get($wishlist_id);
				if(!$data['wishlist']) {
					$return['error'] = $this->_('Collection not found!');
				} else {
					if($data['wishlist']->self_shared_wishlist) {
						$sharedWishlistTable = new \Wishlist\WishlistShare();
						$wishlist = $sharedWishlistTable->fetchRow(array(
							'wishlist_id = ?' => $wishlist_id,
							'share_id' => \User\User::getUserData()->id
						));
						$sharedWishlistTable->getAdapter()->beginTransaction();
						try {
							//remove activity
							\Activity\Activity::remove($wishlist->user_id, 'INVITEWISHLISTALLOW',null,$wishlist->wishlist_id);
							(new \Pin\PinRepin())->delete(['user_id = ?' => \User\User::getUserData()->id, 'wishlist_id = ?' => $wishlist->wishlist_id]);
							$wishlist->delete();
							$return['ok'] = true;
							$sharedWishlistTable->getAdapter()->commit();
						} catch (\Core\Exception $e) {
							$sharedWishlistTable->getAdapter()->rollBack();
							$return['error'] = $e->getMessage();
						}
					} else {
						$return['error'] = $this->_('This Collection is not shared with you');
					}
				}
			}
			$this->responseJsonCallback($return);
		} else {
			if(!$wishlist_id) {
				$this->forward('error404');
			}
			$wishlistTable = new \Wishlist\Wishlist();
			$data['wishlist'] = $wishlistTable->get($wishlist_id);
			if(!$data['wishlist']) {
				$this->forward('error404');
			}
			$this->render('index', $data);
		}
	}
	
}