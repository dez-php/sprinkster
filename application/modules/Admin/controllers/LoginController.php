<?php

namespace Admin;

class LoginController extends \Core\Base\Action {
	
	private $errors = array();
	
	public function init() {
		$this->_ = new \Translate\Locale('Backend\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}
	
	public function indexAction() {	
		//if loget redirect
		$self_data = \User\User::getUserData();
		if($self_data->id && $self_data->is_admin) {
			$this->redirect( $this->url(array(),'admin') );
		}
		
		$request = $this->getRequest();
		
		$userTable = new \User\User();
		
		$this->x_form_cmd = $userTable->getXFormCmd();
		
		//check login and redirect if true
		if( $this->validateLogin() && ($user_id = $userTable->validateLoginAdmin($request->getPost('email'), $request->getPost('password'))) !== false ) {
			// Added strpos check to pass McAfee PCI compliance test
// 			\Core\Http\Thread::run(array(new \Update\Update(),'autoupdate'));
			$redirect = $request->getPost('redirect');
			if($redirect && strpos($redirect, $this->url(array(),'admin')) !== false) {
				$this->redirect( str_replace('&amp;', '&', $redirect) );
			} else {
				$this->redirect( $this->url(array(),'admin') );
			}
		}
		
		// set error's
		if(!$this->errors && ($error = $userTable->getErrors()) !== null ) {
			if($error == \User\User::USER_NOT_FOUND) {
				$this->errors['email'] = $this->_('User not found');
			} else if($error == \User\User::WRONG_PASSWORD) {
				$this->errors['password'] = $this->_('Incorrect password entered');
			} else if($error == \User\User::USER_NOT_ACTIVE) {
				$this->errors['warning'] = $this->_('The email address for this account has not yet been verified. Please check your email for the activation link');
			}
		}
		
		// Added strpos check to pass McAfee PCI compliance test
		if($request->getPost('redirect') && strpos($request->getPost('redirect'), $request->getBaseUrl()) !== false) {
			$this->redirect = $request->getPost('redirect');
		} elseif (\Core\Session\Base::get('redirect')) {
			$this->redirect = \Core\Session\Base::get('redirect');
			if($request->isPost()) {
				\Core\Session\Base::clear('redirect');
			}
		} else {
			$this->redirect = '';
		}
		
		$this->render('index', array('errors' => $this->errors));
		
	}

	private function validateLogin() {
		$request = $this->getRequest();
		if($request->isPost()) {
			if( $request->getPost('X-form-cmd') == $this->x_form_cmd ) {
				$validator = new \Core\Form\Validator(array(
					'translate' => $this->_		
				));
				$validator->addEmail('email');
				$validator->addPassword('password', array(
					'error_text_min' => $this->_('Password must contain more than %d characters')
				));
				if($validator->validate()) {
					return true;
				} else {
					$this->errors = $validator->getErrors();
				}
			} else {
				$this->errors['x-form-cmd'] = $this->_('Incorrect form data');
			}
		}
		return false;
	}
	
	
	
}