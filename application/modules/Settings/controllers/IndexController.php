<?php

namespace Settings;

use \Base\Traits\FormInputPopulator;
use \Base\Traits\Errorly;

class IndexController extends \Core\Base\Action {
	
	use FormInputPopulator;
	use Errorly;

	/**
	 * @var null|array
	 */
	public $errors;
	
	public function init() {
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
			return $this->render('nopermision', ['user' => $self_info]);
		
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
		
		if($request->isPost()) {
			foreach($userInfo AS $key => $value) {
				if($request->issetPost($key)) {
					$val = $request->getPost($key);
					if($val == 'null') {
						$val = null;
					} else {
						$val = $this->escape($val);
					}
					$userInfo->{$key} = $val;
				}
			}
			$checked_array = array(
				'search_engines',
				'notification_comment_pin',
				'notification_mentioned',
				'notification_follow_user',
				'notification_like_pin',
				'notification_repin_pin',
				'notification_group_wishlist',
				//'notification_news'
			);
			foreach($checked_array AS $checked_method) {
				$userInfo->{$checked_method} = (int)$request->issetPost($checked_method);
			}
		}
		
		if($this->validateEdit($userInfo)) {
			try {
				if($userInfo->save()) {
					//extend form
					$forms = \Base\FormExtend::getExtension('userForm.settings');
					foreach($forms AS $form) {
						if($form->save) {
							$saveName = $form->save;
							new $saveName(array('user_id'=>$userInfo->id, 'parent'=>$this,'type'=>'settings'));
						}
					}
					//end extend form
					\Core\Session\Base::set('success',true);
					$this->redirect($this->url(array('user_id' => $user_id),'settings'));
				} else {
					$this->errors['Exception'] = $this->_('Nothing has changed');
				}
			} catch (\Core\Exception $e) {
				$this->errors['Exception'] = $e->getMessage();
			}
		}
		
		$data['user'] = $userInfo;
		$data['languages'] = \Core\Base\Action::getModule('Language')->getLanguages();
		$countryTable = new \Country\Country();
		$data['countries'] = $countryTable->fetchAll(array('status = 1'), 'name ASC');
		
		$this->render('index', $data);
	}

	private function validateEdit($userInfo) {
		$request = $this->getRequest();
		//$userInfo = \User\User::getUserData();
		if($request->isPost()) {
			if( $request->getPost('X-form-cmd') == $this->x_form_cmd ) {
				$validator = new \Core\Form\Validator(array(
					'translate' => $this->_		
				));
				$validator->addEmail('email');
				$validator->addUsername('username');

				$validator->addCountryIso('country_iso_code_3');

				if($request->getPost('website'))
					$validator->addUrl('website', array('error_text' => $this->_('Please enter a valid Website')));
				
				$forms = \Base\FormExtend::getExtension('userForm.settings');
				foreach($forms AS $form) {
					if($form->validator) {
						$validatorName = $form->validator;
						new $validatorName(array('validator'=>$validator, 'parent'=>$this,'type'=>'settings'));
					}
				}
				
				if($validator->validate()) {
					$userTable = new \User\User();
					// check username if exist
					if($userTable->countBy($userTable->makeWhere(array('id' => '!='.$userInfo->id, 'username' => $request->getPost('username'))))) {
						$this->errors['username'] = $this->_('This username is already used');	
					} else if(in_array(strtolower($request->getPost('username')), (new \User\User())->getReserved())) {
						$this->errors['username'] = $this->_('This username is already used');
					}
					// check username if exist
					if($userTable->countBy($userTable->makeWhere(array('id' => '!='.$userInfo->id, 'email' => $request->getPost('email'))))) {
						$this->errors['email'] = $this->_('This e-mail address is already used');	
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