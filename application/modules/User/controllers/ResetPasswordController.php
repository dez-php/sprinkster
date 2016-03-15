<?php

namespace User;

class ResetPasswordController extends \Core\Base\Action {
	
	/**
	 * @var null|array
	 */
	public $errors;
	public $success;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
	}

	public function indexAction() {
		$request = $this->getRequest();
		$userTable = new \User\User();
		$user = $userTable->fetchRow($userTable->makeWhere(array('id' => $request->getRequest('user_id'))));
		if(!$user || $user->password_key != $request->getQuery('key')) {
			$this->forward('error404');
		}
		if($user->password_new) {
			$user->password = $user->password_new;
			$user->password_new = null;
			$user->password_key = null;
		}
		try {
			if($user->save()) {
				if($userTable->loginById($user->id)) {
					$this->success = $this->_('You have successfully reset your password. Now you can access your account with your new password!');
				}
				// set error's
				if(!$this->errors && ($error = $userTable->getErrors()) !== null ) {
					if($error == \User\User::USER_NOT_FOUND) {
						$this->errors['email'] = $this->_('User not found');
					} else if($error == \User\User::USER_NOT_ACTIVE) {
						$this->errors['warning'] = $this->_('User not activated');
					}
				}
			} else {
				$this->errors['save'] = $this->_('There was a problem with the record. Please try again!');
			}
		} catch (\Core\Exception $e) {
			$this->errors['Exception'] = $e->getMessage();
		}
		
		$this->render('index');
	}
	
}