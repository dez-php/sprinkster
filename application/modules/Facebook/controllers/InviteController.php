<?php

namespace Facebook;

class InviteController extends \Base\PermissionController {
	
	/**
	 * @var \Facebook\Helper\Me
	 */
	private $facebook;
	
	public function init() {
		$this->_ = new \Translate\Locale('Front\\'.__NAMESPACE__, self::getModule('Language')->getLanguageId());
		$this->facebook = new \Facebook\Helper\Me();
		if(!\Base\Config::get('facebook_status')) {
			$this->redirect( $this->url(array(),'welcome_home') );
		}
	}
	
	public function indexAction() {
		$this->forward('error404');
		$self_data = \User\User::getUserData();
		$request = $this->getRequest();
		if(!$self_data->id) {
			$this->redirect( $this->url(array('controller' => 'login'),'user_c') );
		}
		
		if($request->isPost()) {
			$return = array();
			$validator = new \Core\Form\Validator(array(
					'translate' => $this->_
			));
			$validator->addNumber('id', array(
					'min' => 1,
					'error_text_min' => $this->_('Facebook ID is invalid'),
					'error_text' => $this->_('Facebook ID is invalid')
			));
			$validator->addText('key', array(
					'min' => 32,
					'max' => 32,
					'error_text_min' => $this->_('Key is invalid'),
					'error_text_max' => $this->_('Key is invalid')
			));
			if($validator->validate()) {
				$inviteTable = new \Facebook\OauthFacebookInvite();
				if( $inviteTable->countByCode($request->getPost('key')) ) {
					$return['error']['isInvated'] = $this->_('This user was already invited by you');
				} else {
					$new = $inviteTable->fetchNew();
					$new->user_id = $self_data->id;
					$new->facebook_id = $request->getPost('id');
					$new->code = $request->getPost('key');
					try {
						if($new->save()) {
							$return['success'] = true;
						} else {
							$return['error']['save'] = $this->_('There was a problem with the record. Please try again!');
						}
					} catch (\Core\Exception $e) {
						$return['error']['Exception'] = $e->getMessage();
					}
				}
			} else {
				$return['error'] = $validator->getErrors();
			}
			
			$this->responseJsonCallback($return);
			exit;
		}
		
		$this->is_loged = $this->facebook->getUser();
		
		if($this->is_loged) {
			$friends = $this->facebook->getFriends();
			
			if($friends === false) {
				$this->facebook->scope = 'read_friendlists';
				$this->facebook->getLoginLink($this->url(array('controller'=>'invite'),'facebook_c'));
			}
		}
		
		$this->render('index');
	}
	
}