<?php

namespace User;

use \Base\Traits\Errorly;

class DeleteController extends \Core\Base\Action {
	
	use Errorly;

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
			'location' => false,
			'user' => null
		);
		$userInfo = \User\User::getUserData();
		$data['isXmlHttpRequest'] = $request->isXmlHttpRequest();
		if(!$userInfo->id) {
			$data['location'] = $this->url(array('controller' => 'login'),'user_c');
		} else {
			
			$userTable = new \User\User();
			
			$user_id = $request->getRequest('user_id');
			
			$user_info = $userTable->fetchRow(array('id = ?' => $user_id));
			if(!$user_info) {
				$this->errors['deleteRecord'] = $this->_('There was a problem with the record. Please try again!');
			} else {
				
				if($userInfo->id != $user_info->id && !$userInfo->is_admin) {
					$this->errors['deleteRecord'] = $this->_('You do not have permission for this action!');
				} else {
			
					$this->x_form_cmd = $userTable->getXFormCmd();
					
					if($request->isPost()) {
						$user_info->status = 2;
						$userTable->getAdapter()->beginTransaction();
						try {
							if($user_info->save()) {
								//send notifikation
								$NotificationTable = new \Notification\Notification();
								$NotificationTable->send('delete_account', [
										'user_id' => $user_info->id,
										'user_firstname' => $user_info->firstname,
										'user_lastname' => $user_info->lastname,
										'user_username' => $user_info->username,
										'user_fullname' => $user_info->getUserFullname(),
											
										'language_id' => $user_info->language_id,
										'email' => $user_info->email,
										'fullname' => $user_info->getUserFullname(),
										'notify' => 1
								]);
								
								//end notifikation
								$userTable->getAdapter()->commit();
								$url = $this->url(array('controller' => 'login','action'=>'logout'),'user_c_a');
								if($data['isXmlHttpRequest']) {
									$this->responseJsonCallback(array('location' => $url));
									exit;
								} else {
									$this->redirect($url);
								}
							} else {
								$userTable->getAdapter()->rollBack();
								$this->errors['deleteRecord'] = $this->_('There was a problem with the record. Please try again!');
							}
						} catch (\Core\Exception $e) {
							$userTable->getAdapter()->rollBack();
							$this->errors['Exception'] = $e->getMessage();
						}
					}
					if($data['isXmlHttpRequest'] && $this->errors) {
						$this->responseJsonCallback(array('errors' => $this->errors, 'location' => $data['location']));
						exit;
					}
					
					$data['user'] = $user_info;
				}
			}
		}
		
		//render script
		$this->render('index', $data);
		
		
	}
	
	
	
}