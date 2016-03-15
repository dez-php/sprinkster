<?php

namespace Wishlist;

class FollowController extends \Base\PermissionController {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		
		$request = $this->getRequest();

    	//login popup
    	$self = \User\User::getUserData();
    	if (!$self->id)
    		$this->forward(
    				'popup', [
    					'url' => $this->url($request->getParams()),
    					'action' => 'follow-wishlist'
    				],
    				'login',
    				'user');
    	//end login popup
		
		$request = $this->getRequest();
		if(!$request->isXmlHttpRequest()) {
			$this->forward('error404');
		}
		
		$data = array();
		if($self->id) {
			
			$wishlistTable = new \Wishlist\Wishlist();
			$wishlist_info = $wishlistTable->get($request->getRequest('wishlist_id'));
			if($wishlist_info && $wishlist_info->user_id != $self->id) {
				$followHelper = new \Wishlist\Helper\Follow($wishlist_info->id);
				if(!$wishlist_info->following_wishlist) {
					$result = $followHelper->followWishlist();
					if($result) {
						//send notification
						$data['isFollow'] = true;
						$data['isFollowUser'] = $followHelper->is_follow_user;
					} else {
						$data['error'] = $this->_('There was a problem with the record. Please try again!');
					}
				} else {
					$result = $followHelper->unfollowWishlist();
					if($result) {
						$data['isFollow'] = false;
						$data['isFollowUser'] = $followHelper->is_follow_user;
					} else {
						$data['error'] = $this->_('There was a problem with the record. Please try again!');
					}
				}
				$data['infouser'] = \User\User::getInfo($wishlist_info->user_id);
				$data['infowishlist'] = $wishlistTable->getInfo($wishlist_info->id);
				
			} else {
				$data['error'] = $this->_('There was a problem with the record. Please try again!');
			}
		} else {
			$data['popup'] = TRUE;
			$data['location'] = $this->url(array('controller' => 'login'),'user_c');
		}
		
		$this->responseJsonCallback( $data );
		
	}
	
	
}