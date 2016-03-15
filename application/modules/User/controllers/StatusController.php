<?php

namespace User;

class StatusController extends \Core\Base\Action {
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		//redirect to home if is status allowed
// 		if((int)\Base\Config::get('register_user_status')) {
// 			$this->redirect( $this->url(array(),'welcome_home') );
// 		}
		
		$userTable = new \User\User();
		$user = $userTable->fetchRow($userTable->makeWhere(array('id' => $this->getRequest()->getRequest('user_id'))));
		if(!$user) {
			$this->forward('error404');
		}
		
		if(!$user->status_send) {
			$activate_url = md5($user->id.$user->email.$user->date_added);
			$NotificationTable = new \Notification\Notification();
			$Notification = $NotificationTable->setLanguageId($user->language_id)->setReplace(array(
				'user_id' => $user->id,
				'user_firstname' => $user->firstname,
				'user_lastname' => $user->lastname,
				'user_fullname' => $user->getUserFullname(),
				'activate_url' => ($this->url(array('controller' => 'activate','user_id'=>$user->id,'query'=>$user->username),'user_c') . '?key=' . $activate_url)
			))->get('activate_profile');
		
			$data['byEmail'] = false;
			if($Notification) {
				$email = new \Helper\Email();
				$email->addFrom(\Base\Config::get('no_reply'));
				$email->addTo($user->email, $user->getUserFullname());
				$email->addTitle($Notification->title);
				$email->addHtml($Notification->description);
				if( $email->send() ) {
					$user->status_send = 1;
					$user->activate_url = $activate_url;
					$user->save();
					$data['byEmail'] = true;
				}
			}
		}
		
		$data['user'] = $user;
		
		$this->render('index', $data);
	}
	
}