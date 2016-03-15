<?php

namespace User;

class ForgottenController extends \Core\Base\Action {

	/**
	 * @var null|array
	 */
	public $errors;

	public function init() {
		$this->_ = new \Translate\Locale('Front\\' . __NAMESPACE__, self::getModule('Language')->getLanguageId());

		if ($this->getRequest()->isXmlHttpRequest())
			$this->noLayout(true);
	}

	public function indexAction() {
		//if loget redirect
		if (\User\User::getUserData()->id)
			$this->redirect($this->url([], 'welcome_home'));

		$request = $this->getRequest();

		if (\Core\Session\Base::get('success')) {
			$this->success = $this->_('A new password has been sent to your email with instructions');
			\Core\Session\Base::clear('success');
		}

		$userTable = new \User\User();

		$this->x_form_cmd = $userTable->getXFormCmd();

		//check login and redirect if true
		if ($this->validate() && ($user = $userTable->validateForgottenPassword($request->getPost('email'))) !== false) {
			$key = md5(serialize($user->toArray()) . time() . mt_rand(0, time()));
			$new_password = $userTable->generatePassword(8);
			$user->password_key = $key;
			$user->password_new = md5($new_password);
			try {
				if ($user->save()) {
					////////////// notification
					$NotificationTable = new \Notification\Notification();
					$Notification = $NotificationTable->setLanguageId($user->language_id)->setReplace(array(
								'user_id' => $user->id,
								'user_firstname' => $user->firstname,
								'user_lastname' => $user->lastname,
								'user_fullname' => $user->getUserFullname(),
								'forgot_password_url' => $this->url(array('controller' => 'reset-password', 'user_id' => $user->id, 'query' => '?key=' . $key . '&user_id=' . $user->id), 'user_c', false, false),
								'new_password' => $new_password,
							))->get('send_forgot_password_request');

					if ($Notification) {
						$email = new \Helper\Email();
						$email->addFrom(\Base\Config::get('no_reply'));
						$email->addTo($user->email, $user->getUserFullname());
						$email->addTitle($Notification->title);
						$email->addHtml($Notification->description);
						if ($email->send()) {
							\Core\Session\Base::set('success', true);
							$this->redirect($this->url(array('controller' => 'forgotten'), 'user_c'));
						} else {
							$this->errors['send'] = $this->_('There was a problem with the mail server. Please try again!');
						}
					} else {
						$this->errors['Notification'] = $this->_('There was a problem with the record. Please try again!');
					}
					/////////////////////////
				} else {
					$this->errors['save'] = $this->_('There was a problem with the record. Please try again!');
				}
			} catch (\Core\Exception $e) {
				$this->errors['Exception'] = $e->getMessage();
			}
		}

		// set error's
		if (!$this->errors && ($error = $userTable->getErrors()) !== null) {
			if ($error == \User\User::USER_NOT_FOUND) {
				$this->errors['email'] = $this->_('User not found');
			} else if ($error == \User\User::USER_NOT_ACTIVE) {
				$this->errors['warning'] = $this->_('The email address for this account has not yet been verified. Please check your email for the activation link');
			}
		}

		if ($request->isXmlHttpRequest() && $request->getQuery('callback')) {
			$this->responseJsonCallback(array(
				'content' => $this->render('index', null, null, true),
				'title' => $this->_('Forgotten')
			));
		} else {
			$this->render('index');
		}
	}

	private function validate() {
		$request = $this->getRequest();
		if ($request->isPost()) {
			if ($request->getPost('X-form-cmd') == $this->x_form_cmd) {
				$validator = new \Core\Form\Validator(array(
					'translate' => $this->_
				));
				$validator->addEmail('email');
				if ($validator->validate()) {
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