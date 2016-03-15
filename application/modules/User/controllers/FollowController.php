<?php

namespace User;

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
    					'action' => 'follow-user'
    				],
    				'login',
    				'user');
    	//end login popup
    	
		if(!$request->isXmlHttpRequest()) {
			$this->forward('error404');
		}
		
		$data = array();
		if($self->id) {
			$userTable = new \User\User();
			$user_info = $userTable->get($request->getRequest('user_id'));
			
			if($user_info && $self->id != $user_info->id) {
				$followHelper = new \User\Helper\Follow($request->getRequest('user_id'));
				if(!$user_info->following_user) {
					$result = $followHelper->followUser();
					if($result) {
						if($user_info->notification_follow_user) {
							//send notifikation
							$NotificationTable = new \Notification\Notification();
							$NotificationTable->send('follow_user', [
								'user_id' => $user_info->id,
								'user_firstname' => $user_info->firstname,
								'user_lastname' => $user_info->lastname,
								'user_username' => $user_info->username,
								'user_fullname' => $user_info->getUserFullname(),
								'author_url' => $this->url(array('user_id'=>$self->id,'query'=>$self->username),'user'),
								'author_fullname' => $self->getUserFullname(),
									
								'language_id' => $user_info->language_id,
								'email' => $user_info->email,
								'fullname' => $user_info->getUserFullname(),
								'notify' => $user_info->notification_follow_user
							]);
							
						}
						$data['isFollow'] = true;
						//add activity
						\Activity\Activity::set($user_info->id, 'FOLLOW');
					} else {
						$data['error'] = $this->_('There was a problem with the record. Please try again!');
					}
				} else {
					$result = $followHelper->unfollowUser();
					//add activity
					\Activity\Activity::remove($user_info->id, 'FOLLOW');
					if($result) {
						$data['isFollow'] = false;
					} else {
						$data['error'] = $this->_('There was a problem with the record. Please try again!');
					}
				}
				$user_info = $userTable->get($request->getRequest('user_id'));
				$data['info'] = $userTable->getInfo($request->getRequest('user_id'));
			} else {
				$data['error'] = $this->_('There was a problem with the record. Please try again!');
			}
			
		} else {
			$data['popup'] = TRUE;
			$data['location'] = $this->url([ 'controller' => 'login' ], 'user_c');
		}
		
		$this->responseJsonCallback( $data );
		
	}
	
	
}