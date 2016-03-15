<?php

namespace Invite;

class IndexController extends \Base\PermissionController {
	
	private $errors = array();
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {
		//if not loget redirect
		$self_data = \User\User::getUserData();
		if(!$self_data->id) {
			$this->redirect( $this->url(array('controller' => 'login'),'user_c') );
		}
		
		if(\Core\Session\Base::get('success')) {
			$this->success = $this->_('The invitation was sent successfully!');
			\Core\Session\Base::clear('success');
		}
		
		$request = $this->getRequest();
		
		$userTable = new \User\User();
		
		$this->x_form_cmd = $userTable->getXFormCmd();
		
		$this->limit = (int)\Base\Config::get('invite_limit_email');
		
		if($request->isPost() && $this->validate()) {
			$inviteTable = new \Invite\Invite();
			$NotificationTable = new \Notification\Notification();
			for($i=0; $i<$this->limit; $i++) {
				if($request->getPost('email' . $i)) {
					$new = $inviteTable->fetchNew();
					$new->code = md5($this->x_form_cmd . microtime(true).$request->getPost('email' . $i));
					$new->email = $request->getPost('email' . $i);
					$new->user_id = $self_data->id;
					$new->send = 1;
					if($new->save()) {
						$Notification = $NotificationTable->setReplace(array(
								'user_id' => $self_data->id,
								'user_firstname' => $self_data->firstname,
								'user_lastname' => $self_data->lastname,
								'user_username' => $self_data->username,
								'user_fullname' => $self_data->getUserFullname(),
								'user_message' => $this->escape($request->getPost('message')),
								'invate_url' => $this->url(array('controller' => 'register', 'user_id' => $self_data->id,'query' => $self_data->username . '?invited_code=' . $new->code),'user_c', false, false)
						))->get('send_invite');
						if($Notification) {
							$email = new \Helper\Email();
							$email->addFrom(\Base\Config::get('no_reply'));
							$email->addTo($request->getPost('email' . $i));
							$email->addTitle($Notification->title);
							$email->addHtml($Notification->description);
							$email->send();
						}
					}
				}
			}
			if($request->isXmlHttpRequest()) {
				$this->responseJsonCallback(array('ok' => true));
				exit;
			} else {
				\Core\Session\Base::set('success',true);
				$this->redirect($this->url(array(),'invite'));
			}
		}
		
		if($this->errors && $request->isXmlHttpRequest()) {
			$this->responseJsonCallback(array('errors' => $this->errors));
			exit;
		}
		
		$this->render('index');
	}

	private function validate() {
		$request = $this->getRequest();
		if($request->isPost()) {
			if( $request->getPost('X-form-cmd') == $this->x_form_cmd ) {
				$validator = new \Core\Form\Validator(array(
					'translate' => $this->_		
				));
				for($i=0; $i<$this->limit; $i++) {
					if($request->getPost('email' . $i)) {
						$validator->addEmail('email' . $i);
					}
				}
				if($validator->validate()) {
					return true;
				} else {
					$this->errors = $validator->getErrors();
				}
			} else {
				$this->errors['x-form-cmd'] = $this->_('Incorrect form data');
			}
			return $this->errors ? false : true;
		}
		return false;
	}
	
}