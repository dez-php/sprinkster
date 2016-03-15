<?php

namespace Settings;

use \Base\Traits\Errorly;

class PasswordController extends \Core\Base\Action {
	
	use Errorly;

	/**
	 * @var null|array
	 */
	public $errors;
	
	public function init() {
		if($this->getRequest()->isXmlHttpRequest())
			$this->noLayout(true);
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function indexAction() {
		$self_info = \User\User::getUserData();
		$request = $this->getRequest();
		
		$user_id = $request->getRequest('user_id');
		
		if(!$self_info->id) {
			$this->redirect( $this->url(array('controller'=>'login'),'user_c') );
		}

        $demo_user_id = \Base\Config::get('demo_user_id');
        if($demo_user_id && $demo_user_id == \User\User::getUserData()->id)
            return $this->render('nopermision', ['user' => $self_info], ['controller' => 'index']);
		
		$data['self_edit'] = true;
		if(!$self_info->is_admin || !$user_id) {
			$user_id = $self_info->id;
		} elseif($user_id != $self_info->id) {
			$data['self_edit'] = false;
		}
		
		$userTable = new \User\User();
		$userInfo = $userTable->fetchRow(array('id = ?' => $user_id));
		
		if(!$userInfo) {
			$this->redirect( $this->url(array('controller'=>'login'),'user_c') );
		}
		
		if(\Core\Session\Base::get('success')) {
			$this->success = $this->_('Your account has been successfully updated');
			\Core\Session\Base::clear('success');
		}
		
		$this->x_form_cmd = $userTable->getXFormCmd();
		
		if($this->validateEdit()) {
			try {
				$userInfo->password = md5($request->getPost('new_password'));
				if($userInfo->save()) {
					\Core\Session\Base::set('success',true);
					$this->redirect($this->url(array('controller' => 'password'),'settings_c'));
				} else {
					$this->errors['Exception'] = $this->_('Nothing has changed');
				}
			} catch (\Core\Exception $e) {
				$this->errors['Exception'] = $e->getMessage();
			}
		}
		
		$data['user'] = $userInfo;
		$data['isXmlHttpRequest'] = $request->isXmlHttpRequest();
		
		$this->render('index', $data);
	}

	private function validateEdit() {
		$request = $this->getRequest();
		$userInfo = \User\User::getUserData();
		if($request->isPost()) {
			if( $request->getPost('X-form-cmd') == $this->x_form_cmd ) {
				$validator = new \Core\Form\Validator(array(
					'translate' => $this->_		
				));
				$validator->addPassword('old_password', array(
						'min' => 3,
						'error_text_min' => $this->_('Old password must contain more than %d characters')
				));
				$validator->addPassword('new_password', array(
						'min' => 3,
						'error_text_min' => $this->_('New password must contain more than %d characters')
				));
				$validator->addPassword('confirm_password', array(
						'min' => 3,
						'error_text_min' => $this->_('Confirm password must contain more than %d characters')
				));
				if($validator->validate()) {
					if( $userInfo->password != md5($request->getPost('old_password')) ) {
						$this->errors['old_password'] = $this->_('The old password you entered was incorrect.');
					}
					if( md5($request->getPost('new_password')) != md5($request->getPost('confirm_password')) ) {
						$this->errors['confirm_password'] = $this->_('Password confirmation does not match password!');
					}
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